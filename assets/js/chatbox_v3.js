// Load chat history from Database on page load
document.addEventListener('DOMContentLoaded', function() {
    loadChatHistoryFromDB();
});

function toggleChat() {
    const chatWindow = document.getElementById('chat-window');
    chatWindow.style.display = (chatWindow.style.display === 'none' || chatWindow.style.display === '') ? 'flex' : 'none';
    if (chatWindow.style.display === 'flex') {
        document.getElementById('chat-input').focus();
    }
}

async function loadChatHistoryFromDB() {
    const chatContent = document.getElementById('chat-content');
    if (!chatContent) return;

    try {
        const response = await fetch('api/chat_history.php');
        const data = await response.json();

        if (data.status === 'success' && data.history.length > 0) {
            // Clear default welcome message if we have history
            chatContent.innerHTML = '';
            
            data.history.forEach(item => {
                // Append User Message
                if (item.message) {
                    appendMessage(item.message, true, null, false);
                }
                // Append Bot Response
                if (item.response) {
                    appendMessage(item.response, false, null, false);
                }
            });
        } else {
            // Ensure welcome message is there if empty
            if (chatContent.children.length === 0) {
                chatContent.innerHTML = `
                    <div class="chat-message bot">
                        Hello! I am the MeTrev Assistant. How can I help you today?
                        <ul>
                            <li>Find a car for rent</li>
                            <li>Check prices and availability</li>
                            <li>Ask about our rental terms</li>
                        </ul>
                    </div>
                `;
            }
        }
        chatContent.scrollTop = chatContent.scrollHeight;
    } catch (error) {
        console.error("Failed to load chat history:", error);
    }
}

// Global variable to hold the image data
let currentChatImageBase64 = null;

function previewChatImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        if (!file.type.startsWith('image/')) {
            console.error("Selected file is not an image.");
            return;
        }

        const reader = new FileReader();
        
        reader.onload = function(e) {
            const dataUrl = e.target.result;
            
            document.getElementById('chat-preview-img').src = dataUrl;
            document.getElementById('chat-image-preview').style.display = 'inline-block';
            
            // FIX: We keep the full dataUrl here (with the prefix) so PHP knows if it's a PNG or JPEG
            currentChatImageBase64 = dataUrl;

            const chatContent = document.getElementById('chat-content');
            if (chatContent) chatContent.scrollTop = chatContent.scrollHeight;
        };

        reader.readAsDataURL(file);
    }
}

function clearChatImage() {
    document.getElementById('chat-image-input').value = '';
    document.getElementById('chat-preview-img').src = '';
    document.getElementById('chat-image-preview').style.display = 'none';
    currentChatImageBase64 = null;
}

window.goToCarDetail = function(id, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    window.location.href = `car-detail.php?id=${id}`;
};

// NOTICE: imageBase64 parameter completely removed to prevent rendering
// Added shouldScroll param to prevent jumping while loading history
function appendMessage(content, isUser = false, cars = null, shouldScroll = true) {
    const chatContent = document.getElementById('chat-content');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${isUser ? 'user' : 'bot'}`;

    if (content) {
        const textDiv = document.createElement('div');
        textDiv.textContent = content;
        textDiv.style.whiteSpace = 'pre-line';
        messageDiv.appendChild(textDiv);
    }
    
    if (cars && cars.length > 0) {
        const carsContainer = document.createElement('div');
        carsContainer.style.cssText = 'display: flex; gap: 12px; margin-top: 12px; flex-wrap: wrap; justify-content: flex-start;';
        
        cars.forEach(car => {
            // FIX: Ensure stock is parsed as an integer
            const stockCount = parseInt(car.stock) || 0;
            const isAvailable = stockCount > 0;
            const statusColor = isAvailable ? '#22c55e' : '#ef4444';
            const statusText = isAvailable ? 'Available' : 'Out of Stock';
            
            const carCard = document.createElement('div');
            carCard.setAttribute('onclick', `goToCarDetail(${car.id}, event)`);
            carCard.style.cssText = `border: 1.5px solid #eee; border-radius: 12px; padding: 10px; background: white; width: 165px; cursor: pointer; transition: all 0.2s; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.05);`;
            
            carCard.innerHTML = `
                <div style="position: absolute; top: 8px; right: 8px; background: ${statusColor}; color: white; font-size: 9px; padding: 2px 6px; border-radius: 4px; font-weight: 800; text-transform: uppercase;">${statusText}</div>
                <img src="${car.image}" alt="${car.brand} ${car.name}" 
                     style="width: 100%; height: 95px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;"
                     onerror="this.src='assets/images/no-car.png'; this.onerror=null;">
                <div style="font-size: 13px; font-weight: 700; color: #1a1a2e; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${car.brand} ${car.name}</div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                    <span style="font-size: 11px; color: #64748b;">${car.year}</span>
                    <span style="font-size: 11px; color: #c9a84c; font-weight: 600;">${stockCount} Units</span>
                </div>
                <div style="font-size: 13px; color: #22c55e; font-weight: 800; margin-top: 6px;">Rp ${car.price}<small style="font-size: 9px; color: #94a3b8; font-weight: normal;">/day</small></div>
            `;
            carsContainer.appendChild(carCard);
        });
        messageDiv.appendChild(carsContainer);
    }
    
    chatContent.appendChild(messageDiv);
    if (shouldScroll) {
        chatContent.scrollTop = chatContent.scrollHeight;
    }
}

async function handleSend() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    // Prevent sending if both text and image are empty
    if (!message && !currentChatImageBase64) return;

    // Capture the payload
    const msgToSend = message;
    const imgToSend = currentChatImageBase64;

    // UI Feedback: Show user message in the chatbox
    let displayMsg = msgToSend;
    if (!displayMsg && imgToSend) {
        displayMsg = "[Image uploaded for analysis]";
    } else if (displayMsg && imgToSend) {
        displayMsg = "[Image uploaded] " + displayMsg;
    }
    
    // ONLY passing text, NEVER the image
    appendMessage(displayMsg, true);
    
    // VERY IMPORTANT: Clear inputs immediately and nullify image variable 
    // so it doesn't get sent on the NEXT chat message
    input.value = '';
    const tempImage = currentChatImageBase64;
    
    // Nuke the preview UI aggressively
    const previewImg = document.getElementById('chat-preview-img');
    const previewContainer = document.getElementById('chat-image-preview');
    const fileInput = document.getElementById('chat-image-input');
    
    if (previewImg) previewImg.src = '';
    if (fileInput) fileInput.value = '';
    if (previewContainer) previewContainer.style.setProperty('display', 'none', 'important');
    
    currentChatImageBase64 = null;

    // Show loading indicator
    const chatContent = document.getElementById('chat-content');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chat-message bot typing-indicator';
    typingDiv.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Thinking...';
    chatContent.appendChild(typingDiv);
    chatContent.scrollTop = chatContent.scrollHeight;

    try {
        const response = await fetch('api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                message: msgToSend,
                image: tempImage // Use the temp variable, not currentChatImageBase64 which is now null
            })
        });
        const data = await response.json();

        // Remove indicator and append AI response
        const indicators = document.querySelectorAll('.typing-indicator');
        indicators.forEach(el => el.remove());

        appendMessage(data.response, false, data.cars || null);
    } catch (error) {
        document.querySelectorAll('.typing-indicator').forEach(el => el.remove());
        appendMessage('Sorry, something went wrong. Please try again.', false);
        console.error("Failed to send message:", error);
    }
}

// Enter key support
document.getElementById('chat-input')?.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        handleSend();
    }
});

// Close chat when clicking outside
document.addEventListener('click', function(e) {
    const chatWidget = document.getElementById('chat-widget');
    const chatWindow = document.getElementById('chat-window');
    if (chatWidget && chatWindow && chatWindow.style.display === 'flex') {
        if (!chatWidget.contains(e.target)) {
            chatWindow.style.display = 'none';
        }
    }
});