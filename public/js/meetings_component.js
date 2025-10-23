
const cardsContainer = document.getElementById("cards");
const popupsmContainer = document.getElementById("popups");
const popupsContainer = document.getElementById("cpopups"); 

async function fetchCreatedMeetings() {
    try {
        const res = await fetch("/outings/created");
        if (!res.ok) throw new Error("Error al obtener mis salidas creadas");
        const meetings = await res.json();
        return meetings;
    } catch (e) {
        console.error("Error en fetchCreatedMeetings:", e);
        return [];
    }
}

async function fetchEventRequests(outingId) {
    try {
        const res = await fetch(`/outings/${outingId}/requests`);
        if (!res.ok) throw new Error("Error al obtener solicitudes");
        return await res.json(); 
    } catch (e) {
        console.error(`Error al cargar solicitudes para ${outingId}:`, e);
        return [];
    }
}

async function acceptParticipant(outingId, userId) {
    try {
        const res = await fetch(`/outings/accept/${outingId}/${userId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        return await res.json();
    } catch (e) {
        console.error("Error al aceptar participante:", e.message);
        return { success: false, message: "Error de conexión o datos." };
    }
}

async function declineParticipant(outingId, userId) {
    try {
        const res = await fetch(`/outings/decline/${outingId}/${userId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        return await res.json();
    } catch (e) {
        console.error("Error al declinar participante:", e.message);
        return { success: false, message: "Error de conexión o datos." };
    }
}

async function deleteOuting(outingId) {
    try {
        const res = await fetch(`/outings/delete/${outingId}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await res.json();

        if (!res.ok) {
            throw new Error(result.message || `Error ${res.status} al intentar eliminar.`);
        }

        return result; 

    } catch (e) {
        console.error("Error al eliminar salida:", e.message);

        return { success: false, message: e.message || "Error de conexión o al procesar la eliminación." };
    }
}

function renderParticipantCards(participants) {
    if (!participants || participants.length === 0) {
        return `<p class="texto-s">Aún no hay participantes confirmados.</p>`;
    }
    return participants.map(p => {
        const photoUrl = p.photo_url || '/img/default-profile.png';
        return `
            <a href="/profile/view/${p.id}" class="d-flex align-items-center my-10 td-none tc-black w-20">
                <div class="circle w-55px">
                    <img src="${photoUrl}" class="circle circle-outline w-55px" alt="${p.full_name}">
                </div>
                <p class="mx-0-7r texto-s">${p.full_name}</p>
            </a>`;
    }).join("");
}

async function renderMeetings() {
    cardsContainer.innerHTML = "";
    popupsmContainer.innerHTML = "";
    popupsContainer.innerHTML = "";

    const myMeetings = await fetchCreatedMeetings();

    if (myMeetings.length === 0) {
        cardsContainer.innerHTML = `
        <div class="d-flex flex-center w-h-aux-1">
            <p class="texto tc-bold">Aún No Has Creado Salidas</p>
        </div>`;
        return;
    }

    for (const event of myMeetings) {
        const id = event.id;
        const participants = event.participants || [];
        const creator = event.created_by || {};

        const requests = await fetchEventRequests(id);

        const participantCards = renderParticipantCards(participants);

        let requestsCards = "";
        if (requests.length === 0) {
            requestsCards = `<p class="texto tc-bold">Aún no hay Solicitudes</p>`;
        } else {
            requestsCards = requests.map(r => {
                const photoUrl = r.photo_url || '/img/default-profile.png';
                return `
        <div class="d-flex align-items-center my-10 gap-1r">
            <a href="/profile/view/${r.id}" class="d-flex align-items-center td-none tc-black">
                <div>
                    <img src="${photoUrl}" class="circle circle-outline w-55px" alt="${r.full_name}">
                </div>
                <p class="mx-0-7r texto-s">${r.full_name}</p>
            </a>
            <div class="d-flex align-items-center gap-0-5r">
                <button type="button" data-request-id="${r.request_id}" class="l-button-check borderless">
                    <i class="fa-solid fa-circle-check"></i>
                </button>
                <button type="button" data-request-id="${r.request_id}" class="l-button-x borderless">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>
        </div>`;
            }).join("");
        }

        cardsContainer.innerHTML += `
            <div class="d-flex col flex-center card m-1 bg-primary br-10 p-1">
                <h3 class="my-0-2">${event.title}</h3>
                <p><i class="fa-solid fa-user-group"></i> ${participants.length} participantes</p>
                <div class="d-flex flex-center col gap-0-5r">
                    <button id="open-join-${id}" class="tc-white tc-bold text-f btn-secondary borderless p-0-3-1 br-10 p-2-6">
                        Detalles
                    </button>
                    <button id="open-popup-${id}" class="tc-white tc-bold text-f btn-secondary borderless p-0-3-1 br-10">
                        Solicitudes
                        <span class="circle texto-f tc-black p-0-5-4">
                            ${requests.length}
                        </span>
                    </button>
                    <a href="/chat.php?id=${id}" class="tc-white td-none tc-bold text-f btn-secondary p-0-3-1 br-10">
                        Coordinar
                    </a>
                </div>
            </div>
        `;

        popupsmContainer.innerHTML += `
            <div id="popup-${id}" class="d-none flex-center bg-cover">
                <div class="outgoing-card bg-primary m-1 br-10 b-sh-1">
                    <div class="participant-section">
                        <h3>Participantes</h3>
                        ${participantCards}
                    </div>
                    <div class="divider"></div>
                    <div class="flex-1">
                        <h3>Solicitudes (${requests.length})</h3>
                        <div id="requests-container-${id}">
                            ${requestsCards}
                        </div>
                        <br>
                        <div class="d-flex flex-center m-1r gap-1r">
                            <button id="close-popup-${id}" class="tc-white tc-bold btn-cancel borderless p-1-1-5r br-10">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const creatorPhotoUrl = creator.photo_url || '/img/default-profile.png';
        const meetingPoint = event.meeting_point;
        const date = event.event_date;
        const time = event.event_time;
        const cost = event.estimated_amount;
        const description = event.description;

        popupsContainer.innerHTML += `
            <div id="join-${id}" class="d-none flex-center col bg-cover">
                <div class="outgoing-card bg-primary m-1 br-10 w-700px b-sh-1">
                    <div class="flex-1">
                        <h3>${event.title}</h3>
                        <p class="tc-justify">${description}</p>
                        <p><strong>Punto de encuentro:</strong> ${meetingPoint}</p>
                        <p><strong>Fecha y Hora:</strong> ${date} a las ${time}</p>
                        <p><strong>Monto estimado:</strong> ${cost}</p>
                        <h3>Creado por</h3>
                        <div class="d-flex align-items-center my-10 w-20">
                            <div class="circle w-55px">
                                <img src="${creatorPhotoUrl}" class="circle circle-outline w-55px" alt="${creator.full_name}">
                            </div>
                            <p class="mx-0-7r texto-s">${creator.full_name}</p>
                        </div>
                    </div>
                    <div class="divider"></div>
                    <div class="participant-section">
                        <h3>Participantes (${participants.length})</h3>
                        ${participantCards}
                        <br>
                        <div class="d-flex flex-center m-1r gap-1r">
                            <button id="delete-outing-${id}" class="tc-white tc-bold btn-warning borderless p-1-1-5r br-10">
                                Eliminar
                            </button>
                            <button id="close-join-${id}" class="tc-white tc-bold btn-cancel borderless p-1-1-5r br-10">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    myMeetings.forEach(event => {
        const id = event.id;

        const openReqBtn = document.getElementById(`open-popup-${id}`);
        const closeReqBtn = document.getElementById(`close-popup-${id}`);
        const reqPopup = document.getElementById(`popup-${id}`);

        if (openReqBtn && closeReqBtn && reqPopup) {
            openReqBtn.addEventListener("click", () => reqPopup.classList.replace("d-none", "d-flex"));
            closeReqBtn.addEventListener("click", () => reqPopup.classList.replace("d-flex", "d-none"));
        }

        const openDetBtn = document.getElementById(`open-join-${id}`);
        const closeDetBtn = document.getElementById(`close-join-${id}`);
        const deleteBtn = document.getElementById(`delete-outing-${id}`); // ID cambiado para claridad
        const detPopup = document.getElementById(`join-${id}`);

        if (openDetBtn && detPopup) {
            openDetBtn.addEventListener("click", () => detPopup.classList.replace("d-none", "d-flex"));
        }

        if (closeDetBtn && detPopup) {
            closeDetBtn.addEventListener("click", () => detPopup.classList.replace("d-flex", "d-none"));
        }

        if (deleteBtn) {
            deleteBtn.addEventListener("click", async () => {
                if (!confirm("ADVERTENCIA: ¿Estás seguro de que quieres eliminar esta salida? Esta acción es permanente.")) return;

                deleteBtn.disabled = true;
                deleteBtn.textContent = 'Eliminando...';

                const result = await deleteOuting(id);
                alert(result.message);

                if (result.success) {
                    renderMeetings(); 
                } else {
                    deleteBtn.disabled = false;
                    deleteBtn.textContent = 'Eliminar Salida';
                }
            });
        }


        const requestsContainer = document.getElementById(`requests-container-${id}`);

        if (requestsContainer) {
            requestsContainer.querySelectorAll('.l-button-check').forEach(acceptBtn => {
                const requestId = acceptBtn.dataset.requestId;
                acceptBtn.addEventListener("click", async () => {
                    const result = await fetch(`/outings/request/accept/${requestId}`, { method: 'POST' })
                        .then(r => r.json());
                    alert(result.message);
                    if (result.success) renderMeetings(); // recarga lista
                });
            });

            requestsContainer.querySelectorAll('.l-button-x').forEach(declineBtn => {
                const requestId = declineBtn.dataset.requestId;
                declineBtn.addEventListener("click", async () => {
                    const result = await fetch(`/outings/request/reject/${requestId}`, { method: 'POST' })
                        .then(r => r.json());
                    alert(result.message);
                    if (result.success) renderMeetings();
                });
            });
        }
    });

}

renderMeetings();