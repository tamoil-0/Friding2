const container = document.getElementById("pmeeting"); 
const popupsContainer = document.getElementById("ppopups"); 

async function fetchJoinedEvents() {
    try {
        const res = await fetch("/outings/join");
        if (!res.ok) throw new Error("Error al obtener mis salidas unidas");
        const events = await res.json();
        return events;
    } catch (e) {
        console.error("Error en fetchJoinedEvents:", e);
        return [];
    }
}

async function leaveEvent(eventId) {
    try {
        const res = await fetch(`/outings/leave/${eventId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const result = await res.json();
        if (!result.success) {
            return result;
        }

        return result;

    } catch (e) {
        console.error("Error de red o parsing en leaveEvent:", e.message);

        if (e.message.includes("JSON")) {
            return { success: false, message: "Error interno: El servidor respondió, pero el formato es incorrecto. (Revisa tus archivos PHP)." };
        }

        // Si es un error de red real
        return { success: false, message: "Error de conexión con el servidor. Intenta de nuevo." };
    }
}


function renderParticipantCards(participants) {
    if (!participants || participants.length === 0) {
        return `<p class="texto-s">Aún no hay más participantes.</p>`;
    }

    return participants.map(p => {
        const photoUrl = p.photo_url || '/img/default-profile.png';

        return `
            <a href="/profile/view/${p.id}" class="d-flex align-items-center my-10 td-none tc-black w-20">
                <div class="circle w-55px">
                    <img src="${photoUrl}" class="circle circle-outline w-55px" alt="${p.full_name}">
                </div>
                <p class="mx-0-7r texto-s">${p.full_name}</p>
            </a>
        `;
    }).join("");
}


async function renderEvents() {
    container.innerHTML = "";
    popupsContainer.innerHTML = "";

    const joinedEvents = await fetchJoinedEvents();

    if (joinedEvents.length === 0) {
        container.innerHTML = `
        <div class="d-flex flex-center w-h-aux-1">
            <p class="texto tc-bold">Aún No Te Has Unido A Alguna Salida</p>
        </div>`;
        return;
    }

    joinedEvents.forEach(event => {
        const id = event.id;

        const creator = event.created_by || { id: 0, full_name: 'Desconocido' };
        const participants = event.participants || [];
        const participantCards = renderParticipantCards(participants);
        const meetingPoint = event.meeting_point;
        const date = event.event_date;
        const time = event.event_time;
        const cost = event.estimated_amount;
        const description = event.description;

        container.innerHTML += `
            <div class="card m-1">
                <h4 class="texto-s"><strong>${event.title}</strong></h4>
                <ul class="list-none">
                    <li class="texto-s my-0-2"><i class="fa-solid fa-location-dot"></i> ${meetingPoint}</li>
                    <li class="texto-s my-0-2"><i class="fa-solid fa-calendar"></i> ${date}, ${time}</li>
                    <li class="texto-s my-0-2"><i class="fa-solid fa-user-group"></i> ${participants.length} Participantes</li>
                </ul>
                <div class="d-flex flex-center m-0-5">
                    <button class="tc-white tc-bold btn-secondary text-f borderless p-0-3-1 br-10 m-1"
                            id="open-join-popup-${id}">
                        Detalles
                    </button>
                     <a href="/chat.php?id=${id}" class="tc-white td-none tc-bold text-f btn-secondary p-0-3-1 br-10">
                        Coordinar
                    </a>
                </div>
            </div>
        `;

        const creatorPhotoUrl = creator.photo_url || '/img/default-profile.png';

        popupsContainer.innerHTML += `
            <div id="join-popup-${id}" class="d-none flex-center col bg-cover">
                <div class="outgoing-card bg-primary m-1 br-10 w-700px b-sh-1">
                    <div class="flex-1">
                        <h3>${event.title}</h3>
                        <p class="tc-justify">${description}</p>
                        <p><strong>Punto de encuentro:</strong> ${meetingPoint}</p>
                        <p><strong>Fecha y Hora:</strong> ${date} a las ${time}</p>
                        <p><strong>Monto estimado:</strong> ${cost}</p>
                        <h3>Creado por</h3>
                        <a href="/profile/view/${creator.id}" class="d-flex align-items-center my-10 td-none tc-black w-20">
                            <div class="circle w-55px">
                                <img src="${creatorPhotoUrl}" class="circle circle-outline w-55px" alt="${creator.full_name}">
                            </div>
                            <p class="mx-0-7r texto-s">${creator.full_name}</p>
                        </a>
                    </div>
                    <div class="divider"></div>
                    <div class="participant-section">
                        <h3>Participantes (${participants.length})</h3>
                        <div id="participant-list-${id}">
                           ${participantCards}
                        </div>
                        <br>
                        <div class="d-flex flex-center m-1r gap-1r">
                            <button id="leave-btn-${id}" class="tc-white tc-bold btn-warning borderless p-1-1-5r br-10">
                                Retirarse
                            </button>
                            <button id="close-join-popup-${id}" class="tc-white tc-bold btn-submit borderless p-1-1-5r br-10">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    joinedEvents.forEach(event => {
        const id = event.id;
        const openBtn = document.getElementById(`open-join-popup-${id}`);
        const closeBtn = document.getElementById(`close-join-popup-${id}`);
        const popup = document.getElementById(`join-popup-${id}`);
        const leaveBtn = document.getElementById(`leave-btn-${id}`);

        if (openBtn) {
            openBtn.addEventListener("click", () => {
                popup.classList.replace("d-none", "d-flex");
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener("click", () => {
                popup.classList.replace("d-flex", "d-none");
            });
        }

        if (leaveBtn) {
            leaveBtn.addEventListener("click", async () => {
                if (!confirm("¿Estás seguro de que quieres retirarte de esta salida?")) return;

                leaveBtn.disabled = true;
                leaveBtn.textContent = 'Retirándose...';

                const result = await leaveEvent(id);
                alert(result.message);

                if (result.success) {
                    renderEvents();
                } else {
                    leaveBtn.disabled = false;
                    leaveBtn.textContent = 'Retirarse';
                }
            });
        }
    });
}

renderEvents();