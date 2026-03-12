<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/chat_errors.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($data['message'] ?? '');
    $imageBase64 = $data['image'] ?? null;

    if (empty($userMessage) && empty($imageBase64)) {
        echo json_encode(['response' => 'Please enter a message or upload an image.']);
        exit;
    }

    $gemini_api_key = get_site_setting('gemini_api_key') ?? '';
    $use_ai = !empty($gemini_api_key);
    $cars_context = getCarsContext();

    $reply = '';
    $recommended_cars = [];
    
    if ($use_ai) {
        $reply = getGeminiResponse($userMessage, $imageBase64, $cars_context, $gemini_api_key);
        if (!empty($reply)) {
            $recommended_cars = extractMentionedCars($reply, $userMessage, $conn);
        }
    }

    // Fallback to keyword matching if AI fails
    if (empty($reply)) {
        if ($imageBase64) {
             $reply = "I'm having trouble analyzing that image right now. Could you describe the car you're looking for?";
        } else {
             $reply = getKeywordResponse($userMessage, $conn);
             $recommended_cars = extractMentionedCars($reply, $userMessage, $conn);
        }
    }

    // Save chat history strictly tied to the current browser session, NOT the permanent user ID.
    // This ensures if a user logs out, the chat resets completely for their next session.
    $session_id = session_id() ?: 'guest_' . uniqid();
    $dbMessage = $imageBase64 ? "[Image Uploaded] " . $userMessage : $userMessage;

    $stmt = $conn->prepare("INSERT INTO chat_history (session_id, message, response) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $session_id, $dbMessage, $reply);
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

function getCarsContext() {
    global $conn;
    $stmt = $conn->prepare("SELECT c.id, c.name, cb.name AS brand_name, ct.name AS type_name, c.seats, c.transmission, c.fuel_type, (SELECT GROUP_CONCAT(DISTINCT color SEPARATOR ', ') FROM car_stock WHERE car_id = c.id AND status = 'available' AND id NOT IN (SELECT car_stock_id FROM orders WHERE status IN ('pending', 'approved') AND rental_end_date >= CURDATE())) AS available_colors, c.price_per_day, c.year, c.description, GROUP_CONCAT(DISTINCT rg.name SEPARATOR ', ') AS rental_goals, (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available' AND cs.id NOT IN (SELECT car_stock_id FROM orders WHERE status IN ('pending', 'approved') AND rental_end_date >= CURDATE())) as available_stock FROM cars c JOIN car_brands cb ON c.brand_id = cb.id LEFT JOIN car_types ct ON c.type_id = ct.id LEFT JOIN car_rental_goals crg ON c.id = crg.car_id LEFT JOIN rental_goals rg ON crg.rental_goal_id = rg.id WHERE c.is_available = 1 GROUP BY c.id ORDER BY c.price_per_day ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $cars = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($cars)) return "No cars currently available.";
    
    $context = "Fleet Information:\n";
    foreach ($cars as $car) {
        $avail = (int)$car['available_stock'];
        $stock = $avail > 0 ? "AVAILABLE ({$avail} units)" : "UNAVAILABLE (Out of stock)";
        $colors = !empty($car['available_colors']) ? $car['available_colors'] : "Not specified";
        $context .= "- {$car['brand_name']} {$car['name']} ({$car['year']}): {$stock}. Colors available: {$colors}. Price: Rp " . number_format($car['price_per_day'], 0, ',', '.') . "/day. Type: {$car['type_name']}, Seats: {$car['seats']}, Gear: {$car['transmission']}.\n";
    }
    return $context;
}

function getGeminiResponse($userMessage, $imageBase64, $carsContext, $apiKey) {
    try {
        // Use v1beta for system_instruction support
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
        
        $systemPrompt = "You are a helpful car rental assistant for MeTrev Rental Mobil. Respond in the same language as the customer. " .
                        "Answer based ONLY on this Fleet Context: \n" . $carsContext . "\n\n" .
                        "RULES:\n" .
                        "1. If a car is UNAVAILABLE, explicitly say so and suggest AVAILABLE alternatives.\n" .
                        "2. If an image is provided, identify the car and check if it's in our fleet.\n" .
                        "3. NO markdown (no bold, no asterisks). Use plain text and line breaks.\n" .
                        "4. Keep responses under 200 words.";

        $userParts = [];

        // Correctly handle the Base64 image data
        if ($imageBase64) {
            // Strip headers like "data:image/png;base64," if they exist
            if (preg_match('/^data:image\/(\w+);base64,/', $imageBase64, $type)) {
                $imageBase64 = substr($imageBase64, strpos($imageBase64, ',') + 1);
                $mimeType = 'image/' . $type[1];
            } else {
                $mimeType = 'image/jpeg'; // Default
            }

            $userParts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $imageBase64
                ]
            ];
            $userMessage = empty($userMessage) ? "What car is this and do you have it in your fleet?" : $userMessage;
        }

        $userParts[] = ['text' => $userMessage];

        $requestData = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => [['role' => 'user', 'parts' => $userParts]],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 800
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Gemini API Error: " . $response);
            return '';
        }

        $resData = json_decode($response, true);
        return $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';

    } catch (Exception $e) {
        error_log("Gemini AI exception: " . $e->getMessage());
        return '';
    }
}

/**
 * Enhanced keyword-based response (fallback)
 */
function getKeywordResponse($userMessage, $conn) {
    $userMessage = strtolower($userMessage);
    $reply = "I'm here to help! Ask me about SUVs, Sedans, EVs, or specific needs like 'family' or 'budget' cars.";
    
    // Simple logic to keep the chat alive if AI fails
    if (strpos($userMessage, 'harga') !== false || strpos($userMessage, 'price') !== false) {
        $reply = "Our rentals start from Rp 300.000 per day. Which type of car are you interested in?";
    }
    return $reply;
}

/**
 * Extract car information mentioned in the AI response
 */
function extractMentionedCars($response, $userMessage, $conn) {
    $cars = [];
    try {
        $stmt = $conn->prepare("SELECT c.id, c.name, cb.name AS brand_name, c.image_main, c.price_per_day, c.year, (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available' AND cs.id NOT IN (SELECT car_stock_id FROM orders WHERE status IN ('pending', 'approved') AND rental_end_date >= CURDATE())) as available_stock FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.is_available = 1");
        $stmt->execute();
        $allCars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($allCars as $car) {
            $fullName = $car['brand_name'] . ' ' . $car['name'];
            if (stripos($response, $fullName) !== false || stripos($response, $car['name']) !== false) {
                $cars[] = [
                    'id' => $car['id'],
                    'name' => $car['name'],
                    'brand' => $car['brand_name'],
                    'image' => 'uploads/cars/' . $car['image_main'],
                    'price' => number_format($car['price_per_day'], 0, ',', '.'),
                    'year' => $car['year'],
                    'stock' => (int)$car['available_stock']
                ];
                if (count($cars) >= 4) break;
            }
        }
    } catch (Exception $e) { error_log($e->getMessage()); }
    return $cars;
}