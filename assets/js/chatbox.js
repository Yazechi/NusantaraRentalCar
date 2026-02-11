// Load chat history from sessionStorage on page load
document.addEventListener('DOMContentLoaded', function() {
    loadChatHistory();
});

function toggleChat() {
    const chatWindow = document.getElementById('chat-window');
    chatWindow.style.display = (chatWindow.style.display === 'none' || chatWindow.style.display === '') ? 'flex' : 'none';
    
    // Focus input when opening
    if (chatWindow.style.display === 'flex') {
        document.getElementById('chat-input').focus();
    }
}

function saveChatHistory() {
    const chatContent = document.getElementById('chat-content');
    if (chatContent) {
        sessionStorage.setItem('chatHistory', chatContent.innerHTML);
    }
}

function loadChatHistory() {
    const chatContent = document.getElementById('chat-content');
    const savedHistory = sessionStorage.getItem('chatHistory');
    
    if (chatContent) {
        if (savedHistory) {
            // Load saved history
            chatContent.innerHTML = savedHistory;
        } else {
            // First time - save the initial welcome message
            saveChatHistory();
        }
        chatContent.scrollTop = chatContent.scrollHeight;
    }
}

function appendMessage(content, isUser = false, cars = null) {
    const chatContent = document.getElementById('chat-content');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${isUser ? 'user' : 'bot'}`;
    
    // Add text content
    const textDiv = document.createElement('div');
    textDiv.textContent = content;
    textDiv.style.whiteSpace = 'pre-line';
    messageDiv.appendChild(textDiv);
    
    // Add car cards if provided
    if (cars && cars.length > 0) {
        const carsContainer = document.createElement('div');
        carsContainer.style.cssText = 'display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;';
        
        cars.forEach(car => {
            const carCard = document.createElement('div');
            carCard.style.cssText = 'border: 1px solid #ddd; border-radius: 8px; padding: 8px; background: white; width: 150px; cursor: pointer;';
            carCard.onclick = () => window.location.href = `car-detail.php?id=${car.id}`;
            
            carCard.innerHTML = `
                <img src="${car.image}" alt="${car.brand} ${car.name}" 
                     style="width: 100%; height: 80px; object-fit: cover; border-radius: 4px; margin-bottom: 5px;"
                     onerror="this.src='assets/images/no-car.png'; this.onerror=null;">
                <div style="font-size: 12px; font-weight: bold; color: #333;">${car.brand} ${car.name}</div>
                <div style="font-size: 11px; color: #666;">${car.year}</div>
                <div style="font-size: 12px; color: #4CAF50; font-weight: bold; margin-top: 3px;">Rp ${car.price}/day</div>
            `;
            
            carsContainer.appendChild(carCard);
        });
        
        messageDiv.appendChild(carsContainer);
    }
    
    chatContent.appendChild(messageDiv);
    chatContent.scrollTop = chatContent.scrollHeight;
    
    // Save to sessionStorage
    saveChatHistory();
}

async function handleSend() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();

    if (!message) return;

    // Display user message
    appendMessage(message, true);
    input.value = '';

    // Show typing indicator
    const chatContent = document.getElementById('chat-content');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chat-message bot';
    typingDiv.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Thinking...';
    chatContent.appendChild(typingDiv);
    chatContent.scrollTop = chatContent.scrollHeight;

    try {
        const response = await fetch('api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });
        const data = await response.json();

        // Remove typing indicator and show response with car images
        if (typingDiv && typingDiv.parentNode) {
            typingDiv.remove();
        }
        appendMessage(data.response, false, data.cars || null);
    } catch (error) {
        if (typingDiv && typingDiv.parentNode) {
            typingDiv.remove();
        }
        appendMessage('Sorry, something went wrong. Please try again.', false);
        console.error("Failed to send message:", error);
    }
}

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
