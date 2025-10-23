const container = document.getElementById("event");
const popupsContainer = document.getElementById("popups");

const searchPopup = document.getElementById("popup2");
const searchInput = searchPopup ? searchPopup.querySelector('.input-search') : null;
const searchButton = document.getElementById("search-button");
const closeSearchButton = document.getElementById("close-popup2");
const openSearchBtn = document.getElementById("open-popup2");

async function fetchEvents(searchTerm = null) {
    try {
        let url = "/outings/list";
        if (searchTerm) url += `?search=${encodeURIComponent(searchTerm)}`;
        const res = await fetch(url);
        if (!res.ok) throw new Error("Error al obtener salidas");
        const events = await res.json();
        return events.map(event => ({
            ...event,
            participants: event.participants || [],
            creatorName: event.created_by ? event.created_by.full_name : 'Usuario Desconocido',
            creatorPhoto: event.created_by ? event.created_by.photo_url : '/img/default-profile.png',
            creatorId: event.created_by ? event.created_by.id : null
        }));
    } catch (e) {
        console.error("Error en fetchEvents:", e);
        return [];
    }
}

async function fetchParticipants(eventId) {
    try {
        const res = await fetch(`/outings/${eventId}/participants`);
        if (!res.ok) throw new Error("Error al obtener participantes");
        return await res.json();
    } catch (e) {
        console.error("Error al obtener participantes:", e);
        return [];
    }
}

async function requestJoinEvent(eventId) {
    try {
        const res = await fetch(`/outings/request-join/${eventId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await res.json();
        return result;
    } catch (e) {
        console.error("Error de red o parsing:", e.message);
        return { success: false, message: "Error de conexión. Inténtalo de nuevo." };
    }
}

async function checkRequestStatus(eventId) {
    try {
        const res = await fetch(`/outings/${eventId}/request-status`);
        if (!res.ok) throw new Error("Error al obtener estado de solicitud");
        const data = await res.json();
        return data.status;
    } catch (e) {
        console.error("Error al verificar estado de solicitud:", e);
        return null;
    }
}

async function checkIsCreator(eventId) {
    try {
        const res = await fetch(`/outings/${eventId}/is-creator`);
        if (!res.ok) throw new Error("Error al verificar si es creador");
        const data = await res.json();
        return data.isCreator === true;
    } catch (e) {
        console.error("Error al verificar creador:", e);
        return false;
    }
}

async function updateParticipantsDOM(eventId) {
    const participants = await fetchParticipants(eventId);
    const count = participants.length;

    const spanCard = document.getElementById(`participants-${eventId}`);
    if (spanCard) spanCard.textContent = count;

    const cardsContainer = document.getElementById(`participant-cards-${eventId}`);
    if (cardsContainer) {
        const participantCards = participants.map(p => `
            <a href="" class="d-flex align-items-center my-10 td-none tc-black w-20">
                <div class="circle w-55px">
                    <img src="${p.photo_url || '/img/default-profile.png'}" class="circle circle-outline w-55px" alt="${p.full_name}">
                </div>
                <p class="mx-0-7r texto-s">${p.full_name}</p>
            </a>
        `).join("");
        cardsContainer.innerHTML = participantCards || '<p>No hay participantes inscritos.</p>';
    }
}

async function renderActionButtons(popupId, eventId, closeListener) {
    const actionsContainer = document.getElementById(`popup-actions-${eventId}`);
    if (!actionsContainer) return;

    const closeHtml = `<button id="close-event-${eventId}" class="tc-white tc-bold btn-cancel borderless p-1-1-5r br-10">Cerrar</button>`;

    const isCreator = await checkIsCreator(eventId);
    if (isCreator) {
        actionsContainer.innerHTML = `<span class="tc-black tc-bold p-1-1-5r br-10 bg-primary">¡Has creado esta salida!</span>${closeHtml}`;
        const newCloseBtn = document.getElementById(`close-event-${eventId}`);
        if (newCloseBtn) newCloseBtn.addEventListener("click", closeListener);
        return;
    }

    const status = await checkRequestStatus(eventId);

    if (status === 'aceptado') {
        actionsContainer.innerHTML = `<span class="tc-black tc-bold p-1-1-5r br-10 bg-success">¡Ya estás inscrito!</span>${closeHtml}`;
    } else if (status === 'pendiente') {
        actionsContainer.innerHTML = `<span class="tc-black tc-bold p-1-1-5r br-10 bg-warning">Solicitud pendiente</span>${closeHtml}`;
    } else if (status === 'rechazado') {
        actionsContainer.innerHTML = `<span class="tc-black tc-bold p-1-1-5r br-10 bg-danger">Solicitud rechazada</span>${closeHtml}`;
    } else {
        actionsContainer.innerHTML = `<button id="join-event-${eventId}" class="tc-white tc-bold btn-secondary borderless p-1-1-5r br-10">Enviar solicitud</button>${closeHtml}`;
        const joinBtn = document.getElementById(`join-event-${eventId}`);
        if (joinBtn) {
            joinBtn.addEventListener("click", async () => {
                joinBtn.disabled = true;
                joinBtn.textContent = 'Enviando...';
                const result = await requestJoinEvent(eventId);
                alert(result.message);
                await renderActionButtons(popupId, eventId, closeListener);
                await updateParticipantsDOM(eventId);
            });
        }
    }

    const newCloseBtn = document.getElementById(`close-event-${eventId}`);
    if (newCloseBtn) newCloseBtn.addEventListener("click", closeListener);
}

async function renderEvents(searchTerm = null) {
    container.innerHTML = "";
    popupsContainer.innerHTML = "";

    const allEvents = await fetchEvents(searchTerm);

    if (allEvents.length === 0) {
        let message = "No hay salidas disponibles en este momento.";
        if (searchTerm) message = `No se encontraron salidas para: <span class="tc-bold">"${searchTerm}"</span>.`;
        container.innerHTML = `<p class="tc-center">${message}</p>`;
        return;
    }

    allEvents.forEach(event => {
        const participantCount = Array.isArray(event.participants) ? event.participants.length : 0;
        const creatorName = event.creatorName || 'Usuario Desconocido';
        const creatorPhoto = event.creatorPhoto || '/img/default-profile.png';
        const creatorId = event.creatorId || null;

        const participantCards = Array.isArray(event.participants) ? event.participants.map(p => {
            const participantId = p.id || null;
            const participantName = p.full_name || 'Usuario Desconocido';
            const participantPhoto = p.photo_url || '/img/default-profile.png';

            return `
            <a href="${participantId ? `/profile/view/${participantId}` : '#'}" class="d-flex align-items-center my-10 td-none tc-black w-20">
                <div class="circle w-55px">
                    <img src="${participantPhoto}" class="circle circle-outline w-55px" alt="${participantName}">
                </div>
                <p class="mx-0-7r texto-s">${participantName}</p>
            </a>`;
        }).join("") : '<p>No hay participantes inscritos.</p>';

        container.innerHTML += `
            <div class="card m-1">
                <h4 class="texto-s"><strong>${event.title || 'Sin título'}</strong></h4>
                <ul class="list-none">
                    <li class="texto-s my-0-2"><i class="fa-solid fa-location-dot"></i> ${event.meetingpoint || 'Sin ubicación'}</li>
                    <li class="texto-s my-0-2"><i class="fa-solid fa-calendar"></i> ${event.date || 'Sin fecha'}, ${event.time || 'Sin hora'}</li>
                    <li class="texto-s my-0-2"><i class="fa-solid fa-user-group"></i> Participantes: 
                        <span id="participants-${event.id}">${participantCount}</span>
                    </li>
                </ul>
                <div class="d-flex flex-right m-0-5">
                    <button class="tc-white tc-bold btn-secondary borderless p-0-3-1 br-10" id="open-event-${event.id}">Ver</button>
                </div>
            </div>
        `;

        popupsContainer.innerHTML += `
            <div id="event-${event.id}" class="d-none flex-center col bg-cover">
                <div class="outgoing-card bg-primary m-1 br-10 w-700px b-sh-1 d-flex">
                    <div class="details-section flex-1 p-1">
                        <h3>${event.title || 'Sin título'}</h3>
                        <p class="tc-justify">${event.description || 'Sin descripción'}</p>
                        <p><strong>Punto de encuentro:</strong> ${event.meetingpoint || 'Sin ubicación'}</p>
                        <p><strong>Fecha y Hora:</strong> ${event.date || 'Sin fecha'} a las ${event.time || 'Sin hora'}</p>
                        <p><strong>Monto estimado:</strong> ${event.estimatedamount || 'No especificado'}</p>
                        <h3>Creado por</h3>
                        <a href="${creatorId ? `/profile/view/${creatorId}` : '#'}" class="d-flex align-items-center my-10 td-none tc-black w-20">
                            <div class="circle w-55px">
                                <img src="${creatorPhoto}" class="circle circle-outline w-55px" alt="${creatorName}">
                            </div>
                            <p class="mx-0-7r texto-s">${creatorName}</p>
                        </a>
                    </div>
                    <div class="divider vertical"></div>
                    <div class="participant-section flex-1 p-1">
                        <h3>Participantes</h3>
                        <div id="participant-cards-${event.id}">
                            ${participantCards}
                        </div>
                        <br>
                        <div class="d-flex flex-center m-1r gap-1r" id="popup-actions-${event.id}"></div>
                    </div>
                </div>
            </div>
        `;
    });

    allEvents.forEach(async event => {
        const openBtn = document.getElementById(`open-event-${event.id}`);
        const popup = document.getElementById(`event-${event.id}`);

        const closeListener = () => popup.classList.replace("d-flex", "d-none");
        await renderActionButtons(popup.id, event.id, closeListener);

        if (openBtn && popup) {
            openBtn.addEventListener("click", () => popup.classList.replace("d-none", "d-flex"));
        }
    });
}
async function performSearch() {
    if (!searchInput || !searchPopup) return;
    const searchTerm = searchInput.value.trim();
    container.innerHTML = `<p class="tc-center tc-bold">Buscando salidas para: "${searchTerm}"...</p>`;
    popupsContainer.innerHTML = "";
    searchPopup.classList.replace("d-flex", "d-none");
    await renderEvents(searchTerm);
}

document.addEventListener('DOMContentLoaded', () => {
    if (openSearchBtn && searchPopup) openSearchBtn.addEventListener("click", () => { searchPopup.classList.replace("d-none", "d-flex"); searchInput?.focus(); });
    if (closeSearchButton && searchPopup) closeSearchButton.addEventListener("click", () => searchPopup.classList.replace("d-flex", "d-none"));
    if (searchButton) searchButton.addEventListener("click", performSearch);
    if (searchInput) searchInput.addEventListener("keypress", (e) => { if (e.key === 'Enter') { e.preventDefault(); performSearch(); } });

    renderEvents();
});