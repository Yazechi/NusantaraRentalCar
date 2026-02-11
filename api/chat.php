<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/chat_errors.log');

// Start session if not already started (for guest tracking)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($data['message'] ?? '');

    if (empty($userMessage)) {
        echo json_encode(['response' => 'Please enter a message.']);
        exit;
    }

    // Gemini API Configuration
    $gemini_api_key = get_site_setting('gemini_api_key') ?? '';
    $use_ai = !empty($gemini_api_key);

    // Get all available cars from database for context
    $cars_context = getCarsContext();

    // Try Gemini AI first, fallback to keyword matching if it fails
    $reply = '';
    $recommended_cars = [];
    
    if ($use_ai) {
        $reply = getGeminiResponse($userMessage, $cars_context, $gemini_api_key);
        // Extract car IDs mentioned in the response
        $recommended_cars = extractMentionedCars($reply, $conn);
    }

    // Fallback to enhanced keyword matching if AI fails or not configured
    if (empty($reply)) {
        $reply = getKeywordResponse($userMessage, $conn);
        $recommended_cars = extractMentionedCars($reply, $conn);
    }

    // Save chat history
    $session_id = session_id() ?: 'guest_' . uniqid();
    $user_id = $_SESSION['user_id'] ?? null;

    $stmt = $conn->prepare("INSERT INTO chat_history (user_id, session_id, message, response) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $session_id, $userMessage, $reply);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'response' => $reply,
        'cars' => $recommended_cars
    ]);
    
} catch (Exception $e) {
    error_log("Chat error: " . $e->getMessage());
    echo json_encode(['response' => 'Sorry, I encountered an error. Please try again.']);
}

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Get all available cars as context for AI
 */
function getCarsContext() {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            c.name, 
            cb.name AS brand_name,
            c.seats,
            c.transmission,
            c.fuel_type,
            c.price_per_day,
            c.year,
            c.description
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        WHERE c.is_available = 1
        ORDER BY c.price_per_day ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $cars = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($cars)) {
        return "No cars currently available.";
    }
    
    $context = "Available cars in our rental fleet:\n\n";
    foreach ($cars as $car) {
        $context .= "- {$car['brand_name']} {$car['name']} ({$car['year']})\n";
        $context .= "  Seats: {$car['seats']}, Transmission: {$car['transmission']}, Fuel: {$car['fuel_type']}\n";
        $context .= "  Price: Rp " . number_format($car['price_per_day'], 0, ',', '.') . " per day\n";
        if (!empty($car['description'])) {
            $context .= "  Description: {$car['description']}\n";
        }
        $context .= "\n";
    }
    
    return $context;
}

/**
 * Get response from Google Gemini AI
 */
function getGeminiResponse($userMessage, $carsContext, $apiKey) {
    try {
        // Gemini API endpoint
        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
        
        // System prompt
        $systemPrompt = "You are a helpful car rental assistant for Nusantara Rental Car. " .
                       "Answer customer questions about available cars based on the information provided. " .
                       "Be friendly, concise, and helpful. If asked about cars not in the list, politely say they're not available. " .
                       "Always mention prices in Indonesian Rupiah (Rp). Keep responses under 100 words. " .
                       "Format your response in plain text suitable for chat - use line breaks for readability but NO markdown formatting (no asterisks, no bold). " .
                       "List items on separate lines with simple numbering (1., 2., 3.) or dashes (-). " .
                       "Make it easy to read in a simple text chat box.\n\n" .
                       $carsContext;
        
        // Prepare request
        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt],
                        ['text' => "Customer: " . $userMessage]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2000,
                'topP' => 0.9,
                'topK' => 40
            ]
        ];
        
        // Check if cURL is available
        if (!function_exists('curl_init')) {
            error_log("cURL is not enabled - cannot use Gemini AI");
            return ''; // Fallback to keywords
        }
        
        // Make API request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log errors
        if ($curlError) {
            error_log("Gemini API cURL error: $curlError (after 30s timeout)");
            return ''; // Fallback to keywords
        }
        
        error_log("Gemini API HTTP status: $httpCode");
        
        // Handle rate limiting or errors
        if ($httpCode === 429) {
            error_log("Gemini API rate limit exceeded");
            return ''; // Fallback to keywords
        }
        
        if ($httpCode !== 200) {
            error_log("Gemini API error: HTTP $httpCode - Response: " . substr($response, 0, 500));
            return ''; // Fallback to keywords
        }
        
        $data = json_decode($response, true);
        
        // Log full response for debugging
        error_log("Gemini API response: " . print_r($data, true));
        
        // Try different response structures
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($data['candidates'][0]['content']['parts'][0]['text']);
        } elseif (isset($data['candidates'][0]['output'])) {
            return trim($data['candidates'][0]['output']);
        } elseif (isset($data['text'])) {
            return trim($data['text']);
        }
        
        // If we got here, unexpected response format
        error_log("Gemini API unexpected response format: " . json_encode($data));
        return ''; // Fallback to keywords
        
        error_log("Gemini API unexpected response format: " . print_r($data, true));
        return ''; // Fallback to keywords
        
    } catch (Exception $e) {
        error_log("Gemini AI exception: " . $e->getMessage());
        return ''; // Fallback to keywords
    }
}

/**
 * Enhanced keyword-based response (fallback)
 */
function getKeywordResponse($userMessage, $conn) {
    $userMessage = strtolower($userMessage);
    $reply = "I'm here to help! You can ask me about:\n" .
             "- Family cars (7+ seats)\n" .
             "- Budget/cheap cars\n" .
             "- Luxury/premium cars\n" .
             "- Automatic or manual transmission\n" .
             "- Specific car brands\n" .
             "- Available cars";
    
    if (strpos($userMessage, 'keluarga') !== false || strpos($userMessage, 'family') !== false) {
    $stmt = $conn->prepare("SELECT c.name, cb.name AS brand_name FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.seats >= 7 AND c.is_available = 1 LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $stmt->close();
    
    if ($car) {
        $reply = "For families, I recommend the " . $car['brand_name'] . " " . $car['name'] . " with spacious seating (7+ seats).";
    } else {
        $reply = "Sorry, no family cars are currently available.";
    }
} elseif (strpos($userMessage, 'murah') !== false || strpos($userMessage, 'cheap') !== false || strpos($userMessage, 'budget') !== false) {
    $stmt = $conn->prepare("SELECT c.name, c.price_per_day, cb.name AS brand_name FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.is_available = 1 ORDER BY c.price_per_day ASC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $stmt->close();
    
    if ($car) {
        $reply = "Our most affordable car is the " . $car['brand_name'] . " " . $car['name'] . " at only Rp " . number_format($car['price_per_day'], 0, ',', '.') . " per day.";
    } else {
        $reply = "Sorry, no cars are currently available.";
    }
} elseif (strpos($userMessage, 'mewah') !== false || strpos($userMessage, 'luxury') !== false || strpos($userMessage, 'premium') !== false) {
    $stmt = $conn->prepare("SELECT c.name, c.price_per_day, cb.name AS brand_name FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.is_available = 1 ORDER BY c.price_per_day DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $stmt->close();
    
    if ($car) {
        $reply = "For a luxury experience, try the " . $car['brand_name'] . " " . $car['name'] . " at Rp " . number_format($car['price_per_day'], 0, ',', '.') . " per day.";
    }
} elseif (strpos($userMessage, 'automatic') !== false || strpos($userMessage, 'matic') !== false) {
    $stmt = $conn->prepare("SELECT c.name, cb.name AS brand_name FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.transmission = 'automatic' AND c.is_available = 1 LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $stmt->close();
    
    if ($car) {
        $reply = "We have the " . $car['brand_name'] . " " . $car['name'] . " with automatic transmission.";
    }
} elseif (strpos($userMessage, 'manual') !== false) {
    $stmt = $conn->prepare("SELECT c.name, cb.name AS brand_name FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.transmission = 'manual' AND c.is_available = 1 LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $stmt->close();
    
    if ($car) {
        $reply = "We have the " . $car['brand_name'] . " " . $car['name'] . " with manual transmission.";
    }
} elseif (strpos($userMessage, 'halo') !== false || strpos($userMessage, 'hai') !== false || strpos($userMessage, 'hi') !== false || strpos($userMessage, 'hello') !== false) {
        $reply = "Hello! I'm your car rental assistant. Ask me about our available cars!";
    } elseif (strpos($userMessage, 'berapa') !== false || strpos($userMessage, 'price') !== false || strpos($userMessage, 'harga') !== false) {
        $stmt = $conn->prepare("
            SELECT c.name, c.price_per_day, cb.name AS brand_name 
            FROM cars c 
            JOIN car_brands cb ON c.brand_id = cb.id 
            WHERE c.is_available = 1 
            ORDER BY c.price_per_day ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $cars = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        if ($cars) {
            $reply = "Here are our car prices:\n";
            $count = 0;
            foreach ($cars as $car) {
                $reply .= "- {$car['brand_name']} {$car['name']}: Rp " . number_format($car['price_per_day'], 0, ',', '.') . "/day\n";
                $count++;
                if ($count >= 5) break; // Show max 5
            }
        }
    } elseif (strpos($userMessage, 'ada apa') !== false || strpos($userMessage, 'available') !== false || strpos($userMessage, 'mobil') !== false) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count,
            GROUP_CONCAT(CONCAT(cb.name, ' ', c.name) SEPARATOR ', ') as car_list
            FROM cars c 
            JOIN car_brands cb ON c.brand_id = cb.id 
            WHERE c.is_available = 1
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        if ($data && $data['count'] > 0) {
            $reply = "We have {$data['count']} available cars. Type 'price' to see prices or ask about specific features!";
        } else {
            $reply = "No cars are currently available. Please check back later.";
        }
    }
    
    return $reply;
}

/**
 * Extract car information mentioned in the AI response
 */
function extractMentionedCars($response, $conn) {
    $cars = [];
    
    try {
        // Get all available cars to match against
        $stmt = $conn->prepare("
            SELECT c.id, c.name, cb.name AS brand_name, c.image_main, c.price_per_day, c.year
            FROM cars c 
            JOIN car_brands cb ON c.brand_id = cb.id 
            WHERE c.is_available = 1
        ");
        
        if (!$stmt) {
            error_log("extractMentionedCars SQL error: " . $conn->error);
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $allCars = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Check which cars are mentioned in the response
        foreach ($allCars as $car) {
            $fullName = $car['brand_name'] . ' ' . $car['name'];
            
            // Check if car is mentioned in response (case-insensitive)
            if (stripos($response, $fullName) !== false || 
                stripos($response, $car['name']) !== false) {
                $cars[] = [
                    'id' => $car['id'],
                    'name' => $car['name'],
                    'brand' => $car['brand_name'],
                    'image' => 'uploads/cars/' . $car['image_main'],
                    'price' => number_format($car['price_per_day'], 0, ',', '.'),
                    'year' => $car['year']
                ];
                
                // Limit to 3 cars max in display
                if (count($cars) >= 3) break;
            }
        }
    } catch (Exception $e) {
        error_log("extractMentionedCars error: " . $e->getMessage());
    }
    
    return $cars;
}