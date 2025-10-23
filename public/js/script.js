
const profileImg = document.getElementById("profile-img");

const btnAllow = document.getElementById("btn-allow-notify");
const panelPermission = document.getElementById("notify-permission");
const panelList = document.getElementById("notify-list");
const notifyItems = document.getElementById("notify-items");
const notifyEmpty = document.getElementById("notify-empty");
const clearBtn = document.getElementById("clear-notify");

function updateNotifyView() {
    if (notifyItems.children.length === 0) {
        notifyEmpty.classList.replace("d-none", "d-flex");
        clearBtn.parentElement.classList.replace("d-flex", "d-none");
    } else {
        notifyEmpty.classList.replace("d-flex", "d-none");
        clearBtn.parentElement.classList.replace("d-none", "d-flex");
    }
}

export function addNotification(text, icon, link) {
    const a = document.createElement("a");
    a.href = link;
    a.className = "td-none w-100 bg-primaryh br-10";

    a.innerHTML = `
    <p class="tc-center m-0 p-12 tc-bold text-f tc-black">
        ${text} <i class="${icon}"></i>
    </p>
    `;

    notifyItems.prepend(a);
    panelList.classList.replace("d-none", "d-flex");
    updateNotifyView();

    if ("Notification" in window && Notification.permission === "granted") {
        new Notification("Friding", {
            body: text,
            icon: "/img/logo.ico"
        });
    }
}

function renderNotification(text, icon, link) {
    const a = document.createElement("a");
    a.href = link;
    a.className = "td-none w-100 bg-primaryh br-10";

    a.innerHTML = `
    <p class="tc-center m-0 p-12 tc-bold text-f tc-black">
        ${text} <i class="${icon}"></i>
    </p>
    `;

    notifyItems.prepend(a);
    panelList.classList.replace("d-none", "d-flex");
    updateNotifyView();
}

async function fetchAndRenderProfileImage() {
    if (!profileImg) {
        console.warn("Elemento #profile-img no encontrado. (Navbar no presente)");
        return;
    }
    
    const apiEndpoint = '/profile/me';

    try {
        const response = await fetch(apiEndpoint);
        
        if (!response.ok) {
             throw new Error(`Error HTTP: ${response.status}.`);
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            const userData = result.data;
            const defaultImg = '/img/default-profile.png';
            
            const imgSrc = userData.foto || defaultImg;

            let imguser = `
                 <img src="${imgSrc}" class="circle w-55px" alt="profile">
            `;
            profileImg.innerHTML = imguser;
            
        } else {
            console.error("API Error: No se pudo obtener la foto del perfil.");
            profileImg.innerHTML = `<img src="/img/default-profile.png" class="circle w-55px" alt="default profile">`;
        }

    } catch (error) {
        console.error("Error al cargar la foto del navbar:", error);
        profileImg.innerHTML = `<img src="/img/default-profile.png" class="circle w-55px" alt="default profile">`;
    }
}


if ("Notification" in window) {
    if (Notification.permission === "granted") {
        if (panelPermission) panelPermission.style.display = "none";
        if (panelList) panelList.style.display = "flex";
    } else if (Notification.permission === "denied") {
        if (panelPermission) panelPermission.style.display = "none";
        if (panelList) panelList.style.display = "flex";
    }
}

btnAllow?.addEventListener("click", () => {
    if ("Notification" in window) {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {

                if (panelPermission) panelPermission.style.display = "none";
                if (panelList) panelList.style.display = "flex";

                new Notification("🔔 Notificaciones activadas", {
                    body: "Ya recibirás alertas en tu navegador.",
                    icon: "/img/logo.ico"
                });
            } else {
                alert("Debes permitir notificaciones para activarlas.");
                if (panelPermission) panelPermission.style.display = "none";
                if (panelList) panelList.style.display = "flex";
            }
        });
    }
});

async function loadInitialNotifications() {
    try {
        const response = await fetch('/notifications/unread');
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        const result = await response.json();
        if (result.success && result.data) {
            notifyItems.innerHTML = "";
            const notifications = result.data; 
            const ordered = notifications.slice().reverse(); 
            ordered.forEach(notif => {
                renderNotification(notif.text, notif.icon, notif.link);
            });
            updateNotifyView();
        }
    } catch (error) {
        console.error("Error al cargar notificaciones iniciales:", error);
    }
}

async function fetchNewNotifications() {
    try {
        const response = await fetch('/notifications/unread');
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        const result = await response.json();
        if (result.success && result.data) {
            const currentCount = notifyItems.children.length;
            const fetched = result.data; 
            const fetchedCount = fetched.length;
            if (fetchedCount > currentCount) {
                const newOnes = fetched.slice(0, fetchedCount - currentCount); 
                const orderedNew = newOnes.slice().reverse(); 
                orderedNew.forEach(notif => {
                    addNotification(notif.text, notif.icon, notif.link);
                });
            }
        }
    } catch (error) {
        console.error("Error al polling de notificaciones:", error);
    }
}

const pollingInterval = setInterval(fetchNewNotifications, 30000);

clearBtn.addEventListener("click", async () => {
    try {
        const response = await fetch('/notifications/read-all', {
            method: 'POST'
        });
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        const result = await response.json();
        if (result.success) {
            notifyItems.innerHTML = "";
            updateNotifyView();
        }
    } catch (error) {
        console.error("Error al marcar como leídas:", error);
    }
});

document.addEventListener("click", (e) => {
    const insideAnyDetails = e.target.closest("details");
    if (!insideAnyDetails) {
        document.querySelectorAll("details[open]").forEach(d => d.open = false);
    }
});

document.addEventListener("toggle", (e) => {
    const el = e.target;
    if (el.tagName !== "DETAILS" || !el.open) return; 
    const group = el.closest("[data-accordion]") || document;

    group.querySelectorAll("details[open]").forEach(d => {
        if (d !== el) d.open = false;
    });
}, true);

function popupFeature(openId, closeId, popupId) {
    const openbutton = document.getElementById(openId);
    const closebutton = document.getElementById(closeId);
    const popup = document.getElementById(popupId);

    if (!openbutton || !closebutton || !popup) {
        console.warn("popupFeature: faltan elementos", openId, closeId, popupId);
        return;
    }

    openbutton.addEventListener("click", () => {
        popup.classList.replace("d-none", "d-flex");
    });

    closebutton.addEventListener("click", () => {
        popup.classList.replace("d-flex", "d-none");
    });
}

document.addEventListener('DOMContentLoaded', () => {
    
    fetchAndRenderProfileImage();

    loadInitialNotifications();

    popupFeature("open-popup1", "close-popup1", "popup1");
    popupFeature("open-popup2", "close-popup2", "popup2");

    const hoy = new Date();
    const año = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    const dia = String(hoy.getDate()).padStart(2, '0');
    const fechaLocal = `${año}-${mes}-${dia}`;

    const date_input = document.getElementById("date");
    if (date_input) {
        date_input.setAttribute("min", fechaLocal);
    }

    fetchNewNotifications();
});