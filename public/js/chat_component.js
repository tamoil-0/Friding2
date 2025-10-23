const currentUserId = window.CURRENT_USER_ID;
const CHAT_API_BASE = '/outings';

const participantContainer = document.getElementById("participants-list");
const resumeContainer = document.getElementById("resume-outgoing");
const chatContainer = document.getElementById("chat-messages");
const btnSend = document.getElementById("btn-send");
const replyBox = document.getElementById("reply-box");
const reportBox = document.getElementById("report-box");
const inputChat = document.getElementById("input-chat");

let replyTo = null;

function getParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

async function fetchAndRenderInitialData(eventId) {
    if (!eventId) {
        console.error("Error: ID de evento no encontrado en la URL.");
        participantContainer.innerHTML = "<p>Error 404: ID de evento no encontrado.</p>";
        resumeContainer.innerHTML = "<p>Error 404: ID de evento no encontrado.</p>";
        chatContainer.innerHTML = "<p>Error 404: ID de evento no encontrado.</p>";
        return null;
    }

    try {
        const response = await fetch(`/outings/${eventId}`);
        if (!response.ok) throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || "Error desconocido al cargar la salida.");
        }
        console.log("Datos de la API /outings/:int:", data.outing); // Depuración
        return data.outing;
    } catch (error) {
        console.error("Error fetching event data:", error);
        participantContainer.innerHTML = "<p>Error al cargar el evento: " + error.message + "</p>";
        resumeContainer.innerHTML = "<p>Error al cargar el evento: " + error.message + "</p>";
        chatContainer.innerHTML = "<p>Error al cargar el chat: " + error.message + "</p>";
        return null;
    }
}

function renderParticipants(event) {
    if (!event || !event.created_by || !event.participants) {
        console.error("Datos del evento inválidos:", event);
        participantContainer.innerHTML = "<p>Error: Datos del evento incompletos.</p>";
        return;
    }

    const creator = event.created_by;
    const participants = event.participants || [];

    if (!creator || !creator.id || !creator.full_name) {
        console.error("Creador inválido:", creator);
        participantContainer.innerHTML = "<p>Error: No se pudo cargar el creador del evento.</p>";
        return;
    }

    let participantsHtml = `
        <button id="toggle-participants" class="btn-participants w-40">
            Integrantes (${participants.length})
        </button>
        <div id="participant-content" class="participant-content">
            <h3>Creado Por</h3>
            <a href="/profile/view/${creator.id}" class="d-flex align-items-center my-10 td-none tc-black">
                <div>
                    <img src="${creator.photo_url || '/img/default-profile.png'}" class="circle circle-outline w-55px" alt="${creator.full_name}">
                </div>
                <p class="mx-0-7r texto-s">${creator.full_name}</p>
            </a>
            <h3>Participantes</h3>
            <div class="participant-list">
    `;

    participants.forEach(p => {
        if (p && p.id && p.full_name) {
            participantsHtml += `
                <a href="/profile/view/${p.id}" class="d-flex row align-items-center my-10 td-none tc-black">
                    <div>
                        <img src="${p.photo_url || '/img/default-profile.png'}" class="circle circle-outline w-55px" alt="${p.full_name}">
                    </div>
                    <p class="mx-0-7r texto-s">${p.full_name}</p>
                </a>`;
        } else {
            console.warn("Participante inválido:", p);
        }
    });

    participantsHtml += `
            </div>
        </div>
        <div class="m-0-5">
            <a href="/myoutgoing" class="td-none tc-white text-s tc-bold borderless btn-tertiary br-10 p-0-5-1">
                <i class="fa-solid fa-right-from-bracket"></i> Volver
            </a>
        </div>
    `;

    participantContainer.innerHTML = participantsHtml;

    const toggleBtn = document.getElementById("toggle-participants");
    const participantsContent = document.getElementById("participant-content");
    if (toggleBtn && participantsContent) {
        toggleBtn.addEventListener("click", () => {
            participantsContent.classList.toggle("open");
        });
    } else {
        console.error("No se encontraron elementos toggle-participants o participant-content");
    }
}

function renderResume(event) {
    if (!event || !event.title || !event.description) {
        console.error("Datos del evento inválidos:", event);
        resumeContainer.innerHTML = "<p>Error: Datos del evento incompletos.</p>";
        return;
    }

    const title = event.title || "Sin título";
    const resume = event.description || "Sin descripción";

    let resumeHtml = `
        <h3 class="tc-center texto">
            Platica: ${title}
        </h3>
        <p class="tc-center texto-s w-80">
            ${resume}
        </p>
    `;

    resumeContainer.innerHTML = resumeHtml;
}

async function renderChat(eventId) {
    console.log("Iniciando renderChat para eventId:", eventId); 
    try {
        const response = await fetch(`${CHAT_API_BASE}/${eventId}/messages`);
        console.log("Respuesta de la API:", response); 

        if (response.status === 401) {
            window.location.href = '/login.php';
            return;
        }
        if (response.status === 403) {
            chatContainer.innerHTML = "<p>Solo los participantes pueden ver este chat.</p>";
            return;
        }
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log("Datos de la API:", data); 

        if (!data.success) {
            console.error("Error al cargar mensajes:", data.message);
            chatContainer.innerHTML = `<p>Error al cargar el chat: ${data.message}</p>`;
            return;
        }

        const eventMessages = data.messages || [];
        if (eventMessages.length === 0) {
            console.log("No hay mensajes en el chat.");
            chatContainer.innerHTML = "<p>No hay mensajes en este chat.</p>";
            return;
        }

        const msgsMap = new Map();
        eventMessages.forEach(msg => {
            console.log("Procesando mensaje:", msg); 
            msgsMap.set(msg.id, msg);
        });

        function renderMessage(id, msg) {
            console.log("Renderizando mensaje:", id, msg); 
            const person = msg.sender;
            if (!person || !person.id || !person.full_name) {
                console.error("Datos de sender inválidos:", person);
                return '';
            }

            const containerClass = person.id === currentUserId ? "container-chat-2" : "container-chat-1";
            const replyClass = msg.reply_to ? "reply-message" : ""; 
            let buttons = person.id !== currentUserId ? `
                <div class="d-flex gap-0-5r">
                    <button class="mx-0-7r btn-none text-f tc-black reply-btn" data-id="${id}" data-sender-name="${person.full_name}">Responder</button>
                    <button class="mx-0-7r btn-none text-f tc-black report-btn" data-user="${person.id}">Reportar</button>
                </div>
            ` : '';

            let replyPreview = '';
            if (msg.reply_to) {
                const repliedMsg = msgsMap.get(msg.reply_to);
                if (repliedMsg) {
                    const repliedPerson = repliedMsg.sender;
                    replyPreview = `<p class="texto-f tc-black reply-preview-text">En respuesta a ${repliedPerson.full_name}: <b>${repliedMsg.text.substring(0, 50).trim()}${repliedMsg.text.length > 50 ? '...' : ''}</b></p>`;
                }
            }

            return `
                <div class="d-flex col ${containerClass} ${replyClass}">
                    <a href="/profile/view/${person.id}" class="d-inflex w-20 align-items-center td-none tc-black">
                        <div>
                            <img src="${person.photo_url || '/img/default-profile.png'}" class="circle circle-outline w-55px" alt="${person.full_name}">
                        </div>
                        <p class="mx-0-7r texto-s">${person.id === currentUserId ? "Tú" : person.full_name}</p>
                    </a>
                    <div class="mx-0-7r">
                        ${replyPreview}
                        <p class="tc-justify">${msg.text}</p>
                    </div>
                    ${buttons}
                </div>
            `;
        }

        let chat = "";
        eventMessages.forEach(msg => {
            const messageHtml = renderMessage(msg.id, msg);
            if (messageHtml) {
                chat += messageHtml;
            }
        });

        console.log("HTML generado para chatContainer:", chat); // Depuración
        chatContainer.innerHTML = chat;

        if (!chat) {
            console.warn("No se generó HTML para los mensajes.");
            chatContainer.innerHTML = "<p>No se pudieron renderizar los mensajes.</p>";
        }

        attachChatEventListeners(eventMessages);
    } catch (error) {
        console.error("Error en renderChat:", error);
        chatContainer.innerHTML = `<p>Error al cargar el chat: ${error.message}</p>`;
    }
}

async function addMessage(eventId, text) {
    if (!text.trim()) return;

    const payload = {
        text: text,
        reply_to: replyTo ? replyTo.id : null
    };

    try {
        const response = await fetch(`${CHAT_API_BASE}/${eventId}/messages`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (data.success) {
            inputChat.value = "";
            window.cancelReply();
            await renderChat(eventId);
        } else {
            alert("Error al enviar el mensaje: " + (data.message || "Error desconocido"));
        }
    } catch (error) {
        console.error("Error al enviar el mensaje:", error);
        alert("Ocurrió un error al enviar el mensaje: " + error.message);
    }
}

window.cancelReply = function () {
    replyTo = null;
    replyBox.innerHTML = "";
    const currentText = inputChat.value.trim();
    if (currentText.startsWith('@')) {
        const parts = currentText.split(' ', 2);
        if (parts.length > 1) {
            inputChat.value = parts[1];
        } else {
            inputChat.value = '';
        }
    }
};

function attachChatEventListeners(messages) {
    console.log("Adjuntando event listeners a", messages.length, "mensajes"); // Depuración
    document.querySelectorAll(".reply-btn").forEach(btn => {
        btn.onclick = (e) => {
            const msgId = Number(e.target.dataset.id);
            const senderName = e.target.dataset.senderName;
            const msg = messages.find(m => m.id === msgId);
            if (!msg) return;

            replyTo = { ...msg, id: msgId };
            inputChat.value = `@${senderName} `;
            inputChat.focus();

            replyBox.innerHTML = `
                <div class="reply-preview">
                    <p>Respondiendo a ${senderName}: <b>${msg.text.substring(0, 50).trim()}...</b></p>
                    <button onclick="cancelReply()">✖</button>
                </div>
            `;
        };
    });

    document.querySelectorAll(".report-btn").forEach(btn => {
        btn.onclick = (e) => {
            const userId = Number(e.target.dataset.user);
            const msg = messages.find(m => m.sender.id === userId);
            const person = msg ? msg.sender : { id: userId, full_name: 'Usuario Reportado', photo_url: 'img/default.png' };
            renderReport(person);

            const popup = document.getElementById(`report-${person.id}`);
            if (popup) {
                popup.classList.replace("d-none", "d-flex");
            }
        };
    });
}

function renderReport(person) {
    reportBox.innerHTML = `
        <div id="report-${person.id}" class="d-none flex-center col bg-cover">
            <div class="outgoing-card col bg-secondary w-400px m-1 br-10 b-sh-1">
                <div class="d-flex col flex-center">
                    <h3 class="subtitle tc-center m-0">Reportar a ${person.full_name || 'Usuario'}</h3>
                    <p class="texto">Causa del Reporte:</p>
                    <div class="d-flex col bg-primary p-1r br-5 b-sh-1">
                        <div class="d-flex m-0-5 b-s-2 p-0-25-0-5r br-5">
                            <input type="radio" name="reason-${person.id}" id="spam-${person.id}" value="spam">
                            <label for="spam-${person.id}">Spam</label>
                        </div>
                        <div class="d-flex m-0-5 b-s-2 p-0-25-0-5r br-5">
                            <input type="radio" name="reason-${person.id}" id="abuse-${person.id}" value="abuse">
                            <label for="abuse-${person.id}">Acoso o Abuso</label>
                        </div>
                        <div class="d-flex m-0-5 b-s-2 p-0-25-0-5r br-5">
                            <input type="radio" name="reason-${person.id}" id="hate-${person.id}" value="hate">
                            <label for="hate-${person.id}">Discurso de odio</label>
                        </div>
                        <div class="d-flex m-0-5 b-s-2 p-0-25-0-5r br-5">
                            <input type="radio" name="reason-${person.id}" id="fraud-${person.id}" value="fraud">
                            <label for="fraud-${person.id}">Fraude</label>
                        </div>
                    </div>
                    <br>
                    <div class="d-flex flex-center m-1 gap-1r">
                        <button id="send-report-${person.id}" data-user="${person.id}" class="tc-white tc-bold borderless p-1-1-5r br-10 btn-submit m-0-7r" disabled>
                            Reportar
                        </button>
                        <button id="close-report-${person.id}" class="tc-white tc-bold borderless p-1-1-5r br-10 btn-cancel m-0-7r">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div id="report-confirm-${person.id}" class="d-none flex-center col bg-cover">
            <div class="outgoing-card flex-center col bg-secondary m-1 br-10 b-sh-1">
                <h3 class="texto">Reporte enviado</h3>
                <p class="texto-f tc-justify">Gracias por ayudarnos a mantener la comunidad segura.</p>
                <button id="close-confirm-${person.id}" class="tc-white tc-bold borderless p-1-1-5r br-10 bg-tertiary m-0-7r">Cerrar</button>
            </div>
        </div>
    `;

    const closeBtn = document.getElementById(`close-report-${person.id}`);
    const popup = document.getElementById(`report-${person.id}`);
    const sendBtn = document.getElementById(`send-report-${person.id}`);
    const confirmPopup = document.getElementById(`report-confirm-${person.id}`);
    const closeConfirmBtn = document.getElementById(`close-confirm-${person.id}`);

    if (closeBtn && popup && sendBtn && confirmPopup && closeConfirmBtn) {
        closeBtn.addEventListener("click", () => {
            popup.classList.replace("d-flex", "d-none");
        });

        const radios = popup.querySelectorAll(`input[name="reason-${person.id}"]`);
        radios.forEach(radio => {
            radio.addEventListener("change", () => {
                sendBtn.disabled = false;
            });
        });

        sendBtn.addEventListener("click", async () => {
            const selectedReason = popup.querySelector(`input[name="reason-${person.id}"]:checked`);
            if (!selectedReason) return;

            try {
                const response = await fetch('/api/reports', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: person.id,
                        reason: selectedReason.value
                    })
                });

                const data = await response.json();
                if (data.success) {
                    popup.classList.replace("d-flex", "d-none");
                    confirmPopup.classList.replace("d-none", "d-flex");
                } else {
                    alert("Error al enviar el reporte: " + (data.message || "Error desconocido"));
                }
            } catch (error) {
                console.error("Error al enviar el reporte:", error);
                alert("Ocurrió un error al enviar el reporte: " + error.message);
            }
        });

        closeConfirmBtn.addEventListener("click", () => {
            confirmPopup.classList.replace("d-flex", "d-none");
        });
    }
}

async function initializeChat() {
    console.log("Iniciando initializeChat"); // Depuración
    const eventIdParam = getParam("id");
    const eventId = eventIdParam ? Number(eventIdParam) : null;

    if (!eventId) {
        console.error("Error: ID de evento no encontrado en la URL.");
        participantContainer.innerHTML = "<p>Error 404: ID de evento no encontrado.</p>";
        resumeContainer.innerHTML = "<p>Error 404: ID de evento no encontrado.</p>";
        chatContainer.innerHTML = "<p>Error 404: ID de evento no encontrado.</p>";
        return;
    }

    const event = await fetchAndRenderInitialData(eventId);
    if (!event) {
        console.error("No se pudo cargar el evento.");
        return;
    }

    renderParticipants(event);
    renderResume(event);
    await renderChat(eventId);

    btnSend.addEventListener("click", () => {
        console.log("Botón de enviar clicado"); // Depuración
        addMessage(eventId, inputChat.value);
    });

    inputChat.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
            console.log("Enter presionado en inputChat"); // Depuración
            addMessage(eventId, inputChat.value);
            e.preventDefault();
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    console.log("DOMContentLoaded disparado"); // Depuración
    initializeChat();
});