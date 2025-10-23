
const container = document.getElementById("event-nolog");
const popupsContainer = document.getElementById("popups");

// Elementos de Búsqueda
const searchPopup = document.getElementById("popup2"); // Asumo que el ID es 'popup2' como en tu código original
const searchInput = searchPopup ? searchPopup.querySelector('.input-search') : null;
const searchButton = document.getElementById("search-button");
const closeSearchButton = document.getElementById("close-popup2");
const openSearchBtn = document.getElementById("open-popup2"); // Botón para abrir el popup de búsqueda

async function fetchEvents(searchTerm = null) {
    try {
        let url = "/outings/list/nolog";
        if (searchTerm) url += `?search=${encodeURIComponent(searchTerm)}`;
        
        const res = await fetch(url);
        if (!res.ok) throw new Error("Error al obtener salidas");
        const events = await res.json();
        
        return events.map(event => ({
            id: event.id,
            title: event.title,
            description: event.description,
            meetingpoint: event.meetingpoint,
            date: event.date,
            time: event.time,
            estimatedamount: event.estimatedamount,
            participants: event.participants || [], 
            creatorName: event.created_by ? event.created_by.full_name : 'Usuario Desconocido',
            creatorId: event.created_by ? event.created_by.id : null,
            creatorfoto: event.created_by ? event.created_by.photo_url : '/img/default-profile.png'
        }));
    } catch (e) {
        console.error("Error en fetchEvents:", e);
        return [];
    }
}

async function renderEventsNoLog(searchTerm = null) {
    container.innerHTML = "";
    popupsContainer.innerHTML = ""; // Limpiar popups

    const allEvents = await fetchEvents(searchTerm);

    if (allEvents.length === 0) {
        let message = "No hay salidas disponibles en este momento.";
        if (searchTerm) message = `No se encontraron salidas para: <span class="tc-bold">"${searchTerm}"</span>.`;
        container.innerHTML = `
        <div class="d-flex flex-center w-h-aux">
          <p class="title">${message}</p>
        </div>`;
        return;
    }

    allEvents.forEach(event => {
        const id = event.id;
        const participantCards = event.participants.map(p => {
          return `
            <a href="" class="d-flex align-items-center my-10 td-none tc-black w-20">
              <div class="circle w-55px">
                <img src="${p.photo_url || '/img/default-profile.png'}" class="circle circle-outline w-55px">
              </div>
              <p class="mx-0-7r texto-s">${p.full_name}</p>
            </a>`;
        }).join("");

        container.innerHTML += `
          <div class="card m-1">
            <h4 class="texto-s"><strong>${event.title}</strong></h4>
            <ul class="list-none m-0-5">
              <li class="texto-s my-0-2"><i class="fa-solid fa-location-dot"></i> ${event.meetingpoint}</li>
              <li class="texto-s my-0-2"><i class="fa-solid fa-calendar"></i> ${event.date}, ${event.time}</li>
              <li class="texto-s my-0-2"><i class="fa-solid fa-user-group"></i> ${event.participants.length} Participantes</li>
            </ul>
            <div class="d-flex flex-center my-10">
              <button id="open-event-${id}" class="tc-white tc-bold btn-secondary borderless p-0-3-1 br-10">
                Detalles
              </button>
            </div>
          </div>
        `;
        popupsContainer.innerHTML += `
          <div id="event-${id}" class="d-none flex-center col bg-cover">
            <div class="outgoing-card bg-primary m-1 br-10 w-700px b-sh-1">
              <div class="flex-1">
                <h3>${event.title}</h3>
                <p class="tc-justify">${event.description}</p>
                <p><strong>Punto de encuentro:</strong> ${event.meetingpoint}</p>
                <p><strong>Fecha y Hora:</strong> ${event.date} a las ${event.time}</p>
                <p><strong>Monto estimado:</strong> ${event.estimatedamount}</p>
                <h3>Creado por</h3>
                <a href="" class="d-flex align-items-center my-10 td-none tc-black w-20">
                  <div class="circle w-55px">
                    <img src="${event.creatorfoto || '/img/default-profile.png'}" class="circle circle-outline w-55px" alt="${event.creatorName}">
                  </div>
                  <p class="mx-0-7r texto-s">${event.creatorName}</p>
                </a>
              </div>
              <div class="divider"></div>
              <div class="participant-section">
                <h3>Participantes</h3>
                ${participantCards}
                <br>
                <div class="d-flex flex-center m-1r gap-1r">
                  <button class="tc-white tc-bold btn-submit text-f borderless p-1-1-5r br-10"
                          onclick="window.location.href='/login'">
                    Únete aquí
                  </button>
                  <button id="close-event-${id}" class="tc-white tc-bold btn-cancel text-f borderless p-1-1-5r br-10">
                    Cerrar
                  </button>
                </div>
              </div>
            </div>
          </div>
        `;
    });

    allEvents.forEach(event => {
      const id = event.id;
      const openBtn = document.getElementById(`open-event-${id}`);
      const closeBtn = document.getElementById(`close-event-${id}`);
      const popup = document.getElementById(`event-${id}`);

      if (openBtn && closeBtn && popup) {
        openBtn.addEventListener("click", () => {
          popup.classList.replace("d-none", "d-flex");
        });

        closeBtn.addEventListener("click", () => {
          popup.classList.replace("d-flex", "d-none");
        });
      }
    });
}

async function performSearch() {
    if (!searchInput || !searchPopup) return;
    const searchTerm = searchInput.value.trim();
    
    container.innerHTML = `<p class="tc-center tc-bold">Buscando salidas para: "${searchTerm}"...</p>`;
    popupsContainer.innerHTML = "";
    
    searchPopup.classList.replace("d-flex", "d-none");
    
    await renderEventsNoLog(searchTerm);
}

document.addEventListener('DOMContentLoaded', () => {
    if (openSearchBtn && searchPopup) openSearchBtn.addEventListener("click", () => { 
        searchPopup.classList.replace("d-none", "d-flex"); 
        searchInput?.focus(); 
    });
    if (closeSearchButton && searchPopup) closeSearchButton.addEventListener("click", () => 
        searchPopup.classList.replace("d-flex", "d-none")
    );

    if (searchButton) searchButton.addEventListener("click", performSearch);
    
    if (searchInput) searchInput.addEventListener("keypress", (e) => { 
        if (e.key === 'Enter') { 
            e.preventDefault(); 
            performSearch(); 
        } 
    });
    renderEventsNoLog();
});