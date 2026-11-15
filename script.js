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
                <button type="button" data-question="Tourist attractions in Al-Ahsa">
                    Tourist attractions
                </button>

                <button type="button" data-question="Restaurants in Al-Ahsa">
                    Restaurants
                </button>

                <button type="button" data-question="Cafes in Al-Ahsa">
                    Cafes
                </button>

                <button type="button" data-question="Hotels in Al-Ahsa">
                    Hotels
                </button>

                <button type="button" data-question="Markets in Al-Ahsa">
                    Markets
                </button>

                <button type="button" data-question="Events and seasons in Al-Ahsa">
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

        /* Close button */
        document.getElementById("aiChatClose")
            .addEventListener("click", closeAI);

        /* Send question */
        document.getElementById("aiChatForm")
            .addEventListener("submit", function (event) {
                event.preventDefault();

                const input = document.getElementById("aiChatInput");
                const question = input.value.trim();

                if (!question) return;

                addAIMessage(question, "user");
                input.value = "";

                sendAIQuestion(question);
            });

        /* Suggested questions */
        chatWindow.querySelectorAll("[data-question]")
            .forEach(function (button) {
                button.addEventListener("click", function () {
                    const question = button.dataset.question;

                    addAIMessage(question, "user");

                    sendAIQuestion(question);
                });
            });
    }

    chatWindow.classList.toggle("open");

    if (chatWindow.classList.contains("open")) {
        const input = document.getElementById("aiChatInput");

        if (input) input.focus();
    }
}


/* =====================================
   CLOSE AI ASSISTANT
===================================== */

function closeAI() {
    const chatWindow = document.getElementById("aiChatWindow");

    if (chatWindow) {
        chatWindow.classList.remove("open");
    }
}


/* =====================================
   ADD MESSAGE TO CHAT
===================================== */

function addAIMessage(message, sender) {
    const messages = document.getElementById("aiChatMessages");

    if (!messages) return;

    const messageElement = document.createElement("div");

    messageElement.className = "ai-message " + sender;
    messageElement.textContent = message;

    messages.appendChild(messageElement);

    messages.scrollTop = messages.scrollHeight;
}


/* =====================================
   SHOW LOADING MESSAGE
===================================== */

function showAILoading() {
    const messages = document.getElementById("aiChatMessages");

    if (!messages) return null;

    const loadingElement = document.createElement("div");

    loadingElement.className = "ai-message bot ai-loading";
    loadingElement.textContent = "Searching Al-Ahsa information...";

    messages.appendChild(loadingElement);

    messages.scrollTop = messages.scrollHeight;

    return loadingElement;
}


/* =====================================
   SEND QUESTION TO PHP BACKEND
===================================== */

async function sendAIQuestion(question) {
    const loadingElement = showAILoading();

    try {
        const response = await fetch("ai-assistant.php", {
            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            credentials: "same-origin",

            body: JSON.stringify({
                question: question
            })
        });

        if (!response.ok) {
            throw new Error("Server connection failed.");
        }

        const data = await response.json();

        if (loadingElement) {
            loadingElement.remove();
        }

        if (!data.success) {
            addAIMessage(
                data.reply || "Sorry, I couldn't find an answer. Please try again.",
                "bot"
            );

            return;
        }

        /* Display assistant reply */
        if (data.reply) {
            addAIMessage(data.reply, "bot");
        }

        /* Display database results */
        if (Array.isArray(data.results) && data.results.length > 0) {
            displayAIResults(data.results);
        }

    } catch (error) {
        console.error("AI Assistant Error:", error);

        if (loadingElement) {
            loadingElement.remove();
        }

        addAIMessage(
            "Sorry, I couldn't connect to the information service. Please try again later.",
            "bot"
        );
    }
}


/* =====================================
   DISPLAY RESULTS FROM DATABASE
===================================== */

function displayAIResults(results) {
    const messages = document.getElementById("aiChatMessages");

    if (!messages) return;

    results.forEach(function (item) {
        const resultCard = document.createElement("div");

        resultCard.className = "ai-message bot ai-result";

        /* Place name */
        const name = document.createElement("strong");
        name.textContent = item.name || "Al-Ahsa place";

        resultCard.appendChild(name);

        /* Place image */
        if (item.image) {
            const image = document.createElement("img");

            image.src = item.image;
            image.alt = item.name || "Place image";
            image.loading = "lazy";

            image.style.maxWidth = "100%";
            image.style.borderRadius = "8px";
            image.style.marginTop = "8px";

            image.onerror = function () {
                image.remove();
            };

            resultCard.appendChild(image);
        }

        /* Place description */
        if (item.description) {
            const description = document.createElement("p");

            description.textContent = item.description;

            resultCard.appendChild(description);
        }

        /* Place location */
        if (item.location) {
            const location = document.createElement("p");

            location.textContent = "Location: " + item.location;

            resultCard.appendChild(location);
        }

        /* Place details link */
        if (item.url) {
            const link = document.createElement("a");

            link.textContent = "View Details";
            link.href = item.url;

            const isSafeRelativePath =
                item.url.startsWith("/") ||
                item.url.startsWith("./") ||
                item.url.startsWith("../");

            let isSafeExternalUrl = false;

            try {
                const parsedUrl = new URL(item.url, window.location.href);

                isSafeExternalUrl =
                    parsedUrl.protocol === "http:" ||
                    parsedUrl.protocol === "https:";
            } catch (error) {
                isSafeExternalUrl = false;
            }

            if (isSafeRelativePath || isSafeExternalUrl) {
                link.target = "_blank";
                link.rel = "noopener noreferrer";

                resultCard.appendChild(link);
            }
        }

        messages.appendChild(resultCard);
    });

    messages.scrollTop = messages.scrollHeight;
}