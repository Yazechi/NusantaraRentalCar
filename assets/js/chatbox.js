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

// Global function for redirection (persists across reloads)
window.goToCarDetail = function(id, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    window.location.href = `car-detail.php?id=${id}`;
};

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
        carsContainer.style.cssText = 'display: flex; gap: 12px; margin-top: 12px; flex-wrap: wrap; justify-content: flex-start;';
        
        cars.forEach(car => {
            const isAvailable = car.stock > 0;
            const statusColor = isAvailable ? '#22c55e' : '#ef4444';
            const statusText = isAvailable ? 'Available' : 'Out of Stock';
            
            const carCard = document.createElement('div');
            // Use onclick attribute string for persistence in innerHTML
            carCard.setAttribute('onclick', `goToCarDetail(${car.id}, event)`);
            carCard.style.cssText = `border: 1.5px solid #eee; border-radius: 12px; padding: 10px; background: white; width: 165px; cursor: pointer; transition: all 0.2s; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.05);`;
            
            // Hover effect
            carCard.onmouseover = () => {
                carCard.style.transform = 'translateY(-4px)';
                carCard.style.boxShadow = '0 8px 20px rgba(0,0,0,0.1)';
                carCard.style.borderColor = '#c9a84c';
            };
            carCard.onmouseout = () => {
                carCard.style.transform = 'translateY(0)';
                carCard.style.boxShadow = '0 4px 12px rgba(0,0,0,0.05)';
                carCard.style.borderColor = '#eee';
            };
            
            carCard.innerHTML = `
                <div style="position: absolute; top: 8px; right: 8px; background: ${statusColor}; color: white; font-size: 9px; padding: 2px 6px; border-radius: 4px; font-weight: 800; text-transform: uppercase;">${statusText}</div>
                <img src="${car.image}" alt="${car.brand} ${car.name}" 
                     style="width: 100%; height: 95px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;"
                     onerror="this.src='assets/images/no-car.png'; this.onerror=null;">
                <div style="font-size: 13px; font-weight: 700; color: #1a1a2e; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${car.brand} ${car.name}</div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                    <span style="font-size: 11px; color: #64748b;">${car.year}</span>
                    <span style="font-size: 11px; color: #c9a84c; font-weight: 600;">${car.stock} Units</span>
                </div>
                <div style="font-size: 13px; color: #22c55e; font-weight: 800; margin-top: 6px;">Rp ${car.price}<small style="font-size: 9px; color: #94a3b8; font-weight: normal;">/day</small></div>
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
    typingDiv.className = 'chat-message bot typing-indicator';
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

        // Remove typing indicator
        const indicators = document.querySelectorAll('.typing-indicator');
        indicators.forEach(el => el.remove());

        appendMessage(data.response, false, data.cars || null);
    } catch (error) {
        const indicators = document.querySelectorAll('.typing-indicator');
        indicators.forEach(el => el.remove());
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
