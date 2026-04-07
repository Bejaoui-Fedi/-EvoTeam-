function initChatbot() {
    console.log('Chatbot JS: Initializing...');
    const bubble = document.getElementById('chatbot-bubble');
    const chatWindow = document.getElementById('chat-window');
    const closeBtn = document.getElementById('close-chat');
    const sendBtn = document.getElementById('send-message');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');
    
    // Voice elements
    const sttBtn = document.getElementById('start-stt');
    const toggleVoiceBtn = document.getElementById('toggle-voice');

    if (!bubble || !chatWindow) {
        console.error('Chatbot JS: Required elements not found!', { bubble, chatWindow });
        return;
    }

    let isRecording = false;
    let voiceEnabled = true;

    // Speech Recognition Setup
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;

    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.lang = 'fr-FR';
        recognition.continuous = false;
        recognition.interimResults = false;

        recognition.onstart = () => {
            console.log('STT: Started');
            isRecording = true;
            sttBtn.classList.add('recording');
        };

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            console.log('STT: Result:', transcript);
            chatInput.value = transcript;
            sendMessage();
        };

        recognition.onerror = (event) => {
            console.error('STT: Error:', event.error);
            isRecording = false;
            sttBtn.classList.remove('recording');
        };

        recognition.onend = () => {
            console.log('STT: Ended');
            isRecording = false;
            sttBtn.classList.remove('recording');
        };
    } else {
        sttBtn.style.display = 'none';
        console.warn('STT: Web Speech API not supported');
    }

    // Speech Synthesis
    function speak(text) {
        if (!voiceEnabled) return;
        
        // Nettoyer le texte du markdown pour une meilleure lecture
        const cleanText = text.replace(/[*_#]/g, '');
        
        const utterance = new SpeechSynthesisUtterance(cleanText);
        utterance.lang = 'fr-FR';
        
        // Essayer de trouver une voix française
        const voices = window.speechSynthesis.getVoices();
        const frenchVoice = voices.find(v => v.lang.startsWith('fr'));
        if (frenchVoice) utterance.voice = frenchVoice;

        window.speechSynthesis.speak(utterance);
    }

    // Toggle Voice
    toggleVoiceBtn.addEventListener('click', () => {
        voiceEnabled = !voiceEnabled;
        if (voiceEnabled) {
            toggleVoiceBtn.className = 'fa-solid fa-volume-high me-3 status-icon';
            toggleVoiceBtn.title = 'Désactiver le son';
        } else {
            toggleVoiceBtn.className = 'fa-solid fa-volume-xmark me-3 status-icon off';
            toggleVoiceBtn.title = 'Activer le son';
            window.speechSynthesis.cancel();
        }
    });

    // Start STT
    sttBtn.addEventListener('click', () => {
        if (!recognition) return;
        if (isRecording) {
            recognition.stop();
        } else {
            recognition.start();
        }
    });

    // Toggle Chat Window
    bubble.addEventListener('click', () => {
        console.log('Chatbot JS: Bubble clicked');
        const isVisible = chatWindow.style.display === 'flex' || getComputedStyle(chatWindow).display === 'flex';
        chatWindow.style.display = isVisible ? 'none' : 'flex';
        
        if (!isVisible) {
            console.log('Chatbot JS: Opening chat window');
            chatInput.focus();
        }
    });

    closeBtn.addEventListener('click', () => {
        console.log('Chatbot JS: Close button clicked');
        chatWindow.style.display = 'none';
        window.speechSynthesis.cancel();
    });

    // Send Message
    function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        console.log('Chatbot JS: Sending message:', message);

        // Append User Message
        appendMessage('user', message);
        chatInput.value = '';

        // Show Loading
        const loadingId = appendLoading();

        // AJAX to Symfony API
        fetch('/api/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: message }),
        })
        .then(async response => {
            console.log('Chatbot JS: API Response received', response.status);
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Chatbot JS: Failed to parse JSON:', text);
                throw new Error('Invalid JSON response');
            }
        })
        .then(data => {
            removeLoading(loadingId);
            if (data.response) {
                appendMessage('bot', data.response);
                speak(data.response);
            } else if (data.error) {
                appendMessage('bot', "Erreur Chatbot : " + data.error);
            }
        })
        .catch(error => {
            removeLoading(loadingId);
            appendMessage('bot', "Désolé, une erreur est survenue.");
            console.error('Chatbot JS: Fetch error:', error);
        });
    }


    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    function appendMessage(role, text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${role}`;
        msgDiv.innerText = text;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function appendLoading() {
        const id = 'loading-' + Date.now();
        const loadingDiv = document.createElement('div');
        loadingDiv.id = id;
        loadingDiv.className = 'typing-indicator';
        loadingDiv.innerHTML = '<span></span><span></span><span></span>';
        chatMessages.appendChild(loadingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return id;
    }

    function removeLoading(id) {
        const loadingDiv = document.getElementById(id);
        if (loadingDiv) loadingDiv.remove();
    }
}

// Robust initialization
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatbot);
} else {
    initChatbot();
}

