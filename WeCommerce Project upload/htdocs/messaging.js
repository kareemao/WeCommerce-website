document.addEventListener('DOMContentLoaded', function() {
    // Toggle chat area when a user is selected (for mobile)
    const userLinks = document.querySelectorAll('.users-list a');
    const chatArea = document.querySelector('.chat-area');
    const backIcon = document.querySelector('.back-icon');
    
    if (userLinks && chatArea && backIcon) {
        userLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (window.innerWidth <= 768) {
                    document.querySelector('.users').style.display = 'none';
                    chatArea.style.display = 'flex';
                }
            });
        });
        
        backIcon.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.innerWidth <= 768) {
                document.querySelector('.users').style.display = 'block';
                chatArea.style.display = 'none';
            }
        });
    }
    
    // Search functionality
    const searchInput = document.querySelector('.search input');
    const searchBtn = document.querySelector('.search button');
    
    if (searchInput && searchBtn) {
        searchBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const users = document.querySelectorAll('.users-list a');
            
            users.forEach(user => {
                const userName = user.querySelector('.details span').textContent.toLowerCase();
                if (userName.includes(searchTerm)) {
                    user.style.display = 'flex';
                } else {
                    user.style.display = 'none';
                }
            });
        });
    }
    
    // Send message functionality
    const messageForm = document.querySelector('.typing-area');
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const messageInput = this.querySelector('input');
            const message = messageInput.value.trim();
            
            if (message) {
                // In a real app, you would send this to your server
                const chatBox = document.querySelector('.chat-box');
                const newMessage = document.createElement('div');
                newMessage.className = 'chat outgoing';
                newMessage.innerHTML = `
                    <div class="details">
                        <p>${message}</p>
                    </div>
                `;
                chatBox.appendChild(newMessage);
                messageInput.value = '';
                
                // Scroll to bottom
                chatBox.scrollTop = chatBox.scrollHeight;
                
                // Simulate reply (in real app, this would come from server)
                setTimeout(() => {
                    const reply = document.createElement('div');
                    reply.className = 'chat incoming';
                    reply.innerHTML = `
                        <img src="images/user2.png" alt="User">
                        <div class="details">
                            <p>Thanks for your message!</p>
                        </div>
                    `;
                    chatBox.appendChild(reply);
                    chatBox.scrollTop = chatBox.scrollHeight;
                }, 1000);
            }
        });
    }
});