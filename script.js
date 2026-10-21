/* =====================================
   SIDEBAR MENU
===================================== */

function toggleMenu() {
    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.classList.toggle("open");
    }
}

/* Close sidebar when clicking outside */
document.addEventListener("click", function (event) {
    const sidebar = document.getElementById("sidebar");
    const menuButton = document.querySelector(".menu-button");

    if (!sidebar || !menuButton) return;

    if (
        !sidebar.contains(event.target) &&
        !menuButton.contains(event.target)
    ) {
        sidebar.classList.remove("open");
    }
});

/* =====================================
   AI ASSISTANT CHAT WINDOW
===================================== */

function openAI() {
    let chatWindow = document.getElementById("aiChatWindow");

    /* Create the chat window if it doesn't exist */
    if (!chatWindow) {
        chatWindow = document.createElement("section");
        chatWindow.id = "aiChatWindow";
        chatWindow.className = "ai-chat-window";
        chatWindow.setAttribute("aria-label", "AI Assistant");

        chatWindow.innerHTML = `
            <div class="ai-chat-header">
                <div class="ai-chat-heading">
                    <h3>Al-Ahsa AI Assistant</h3>
                    <p>Your guide to Al-Ahsa</p>
                </div>
                <button type="button"
                    class="ai-chat-close"
                    id="aiChatClose"
                    aria-label="Close assistant">&times;</button>
            </div>

            <div class="ai-chat-messages" id="aiChatMessages">
                <div class="ai-message bot">
                    Hello! Welcome to Al-Ahsa Vision Hub.
                    How can I help you explore Al-Ahsa?
                </div>
            </div>

            <div class="ai-chat-suggestions">
                <button type="button" data-question="Tourist attractions">
                    Tourist attractions
                </button>
                <button type="button" data-question="Restaurants in Al-Ahsa">
                    Restaurants
                </button>
                <button type="button" data-question="Cafes in Al-Ahsa">
                    Cafes
                </button>
                <button type="button" data-question="Events and seasons">
                    Events
                </button>
            </div>

            <form class="ai-chat-form" id="aiChatForm">
                <input
                    type="text"
                    id="aiChatInput"
                    placeholder="Ask about Al-Ahsa..."
                    autocomplete="off"
                    aria-label="Type your question"
                    required
                >
                <button type="submit" aria-label="Send message">
                    &#10148;
                </button>
            </form>
        `;

        document.body.appendChild(chatWindow);

        document.getElementById("aiChatClose").addEventListener("click", closeAI);

        document.getElementById("aiChatForm").addEventListener("submit", function (event) {
            event.preventDefault();

            const input = document.getElementById("aiChatInput");
            const question = input.value.trim();

            if (!question) return;

            addAIMessage(question, "user");
            input.value = "";

            showDemoReply(question);
        });

        chatWindow.querySelectorAll("[data-question]").forEach(function (button) {
            button.addEventListener("click", function () {
                const question = button.dataset.question;

                addAIMessage(question, "user");
                showDemoReply(question);
            });
        });
    }

    chatWindow.classList.toggle("open");

    if (chatWindow.classList.contains("open")) {
        const input = document.getElementById("aiChatInput");
        if (input) input.focus();
    }
}

/* Close assistant */
function closeAI() {
    const chatWindow = document.getElementById("aiChatWindow");

    if (chatWindow) {
        chatWindow.classList.remove("open");
    }
}

/* Add a message to the conversation */
function addAIMessage(message, sender) {
    const messages = document.getElementById("aiChatMessages");

    if (!messages) return;

    const messageElement = document.createElement("div");
    messageElement.className = "ai-message " + sender;
    messageElement.textContent = message;

    messages.appendChild(messageElement);
    messages.scrollTop = messages.scrollHeight;
}

/* Demo replies until an AI service is connected */
function showDemoReply(question) {
    const text = question.toLowerCase();
    let reply = "";

    if (
        text.includes("restaurant") ||
        text.includes("مطعم") ||
        text.includes("food")
    ) {
        reply = "You can explore the Restaurants section on our website to discover dining options in Al-Ahsa.";
    } else if (
        text.includes("cafe") ||
        text.includes("coffee") ||
        text.includes("مقهى") ||
        text.includes("كافيه")
    ) {
        reply = "Visit the Cafes section on our website to explore cafes in Al-Ahsa.";
    } else if (
        text.includes("event") ||
        text.includes("season") ||
        text.includes("فعالية") ||
        text.includes("موسم")
    ) {
        reply = "Explore the events and seasons information on our website for activities in Al-Ahsa.";
    } else if (
        text.includes("attraction") ||
        text.includes("place") ||
        text.includes("tourist") ||
        text.includes("سياحي") ||
        text.includes("أماكن") ||
        text.includes("اماكن")
    ) {
        reply = "You can start with famous attractions such as Al-Qarah Mountain, Ibrahim Palace, and Al-Qaisariya Souq.";
    } else {
        reply = "Thanks for your question! This assistant is currently a demo. Connecting an AI service will allow it to answer a wider range of questions.";
    }

    addAIMessage(reply, "bot");
}