
function renderNotification(text, icon , link) {
    const notifyItems = document.getElementById("notify-items");
    
    if (!notifyItems) return; 

    const a = document.createElement("a");
    a.href = link;
    a.className = "td-none w-100 bg-primaryh br-10";

    a.innerHTML = `
        <p class="tc-center m-0 p-12 tc-bold text-f tc-black">
            ${text} <i class="${icon}"></i>
        </p>
    `;
    notifyItems.prepend(a); 
}

function updateNotifyView() {
    const notifyItems = document.getElementById("notify-items");
    const notifyEmpty = document.getElementById("notify-empty");
    const clearBtn = document.getElementById("clear-notify");

    if (!notifyItems || !notifyEmpty || !clearBtn) return;

    if (notifyItems.children.length === 0) {
        notifyEmpty.classList.replace("d-none","d-flex");
        clearBtn.parentElement.classList.replace("d-flex","d-none"); 
    } else {
        notifyEmpty.classList.replace("d-flex","d-none");
        clearBtn.parentElement.classList.replace("d-none","d-flex"); 
    }
}

async function clearAllNotifications() {
    const notifyItems = document.getElementById("notify-items");

    try {
        const response = await fetch('/notifications/read-all', { 
            method: 'POST'
        });
        
        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                if (notifyItems) notifyItems.innerHTML = "";
                updateNotifyView();
            } else {
                console.error('Error lógico al limpiar notificaciones en el servidor.');
                alert('Error al limpiar notificaciones. Intente de nuevo.');
            }
        } else {
            console.error('Error HTTP al limpiar notificaciones:', response.status);
            alert('Error de conexión al limpiar notificaciones.');
        }
    } catch (error) {
        console.error('Error de red al limpiar notificaciones:', error);
        alert('Error de conexión al limpiar notificaciones.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const clearBtn = document.getElementById("clear-notify");
    if (clearBtn) {
        clearBtn.addEventListener("click", clearAllNotifications);
    }
    updateNotifyView();
});

export { renderNotification, updateNotifyView };