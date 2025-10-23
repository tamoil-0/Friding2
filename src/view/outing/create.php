<div id="popup1" class="d-none flex-center col bg-cover">
    <div class="d-flex col bg-primary br-10 m-1">

        <form action="/outings/create" class="p-0.5r" id="form">
            <h2 class="tc-center">Crear Salida</h2>
            <div class="d-flex m-1 mb-1r">
                <label for="name" class="lbl-modal">Nombra la Salida:</label>
                <input class="ipt-modal" type="text" name="name" id="name" autofocus required>
            </div>
            <div class="d-flex m-1 mb-1r">
                <label for="description" class="lbl-modal">Describe la Salida:</label>
                <textarea class="ipt-modal no-resize" name="description" id="description" rows="3" cols="40" required></textarea>
            </div>
            <div class="d-flex m-1 mb-1r">
                <label for="date" class="lbl-modal">Fecha:</label>
                <input class="ipt-modal" type="date" name="date" id="date" required>
            </div>
            <div class="d-flex m-1 mb-1r">
                <label class="lbl-modal">Hora:</label>
                <input class="ipt-modal" type="time" name="time" id="time" required>
            </div>
            <div class="d-flex m-1 mb-1r">
                <label class="lbl-modal">Lugar:</label>
                <input class="ipt-modal" type="text" name="place" id="place" required>
            </div>
            <div class="d-flex m-1 mb-1r">
                <label class="lbl-modal">Monto Estimado:</label>
                <input class="ipt-modal" type="number" name="amount" id="amount" value="0.00" step="0.01">
            </div>
            <div class="d-flex flex-right mx-2 mb-1r gap-1r"> 
                <button type="Submit" id="create-btn" class="tc-white tc-bold btn-submit borderless p-1-1-5r br-10">
                    Crear
                </button>
                <button id="close-popup1" type="button" class="tc-white tc-bold btn-cancel borderless p-1-1-5r br-10">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Obtener los elementos del DOM
    const form = document.getElementById('form');
    const createOutingButton = document.getElementById('create-btn');
    const closePopupButton = document.getElementById('close-popup1');
    const popup = document.getElementById('popup1');

    // Función auxiliar para mostrar errores
    const showError = (message) => {
        alert("Error de Validación:\n" + message);
    };
    
    // Función de recarga simulada/externa
    const renderMeetings = typeof window.renderMeetings === 'function' ? window.renderMeetings : () => {
        console.log("Recarga simulada. Añade la función renderMeetings() al scope global.");
    };

    // 2. Listener para cerrar el popup
    if (closePopupButton) {
        closePopupButton.addEventListener('click', () => {
            popup.classList.replace("d-flex", "d-none");
            form.reset();
        });
    }

    // 3. Listener para el envío del formulario con VALIDACIÓN
    form.addEventListener('submit', async (e) => {
        e.preventDefault(); // Detener el envío por defecto

        // Mapear y preparar los datos
        const formData = new FormData(form);
        const data = {
            title: formData.get('name').trim(),
            description: formData.get('description').trim(),
            event_date: formData.get('date').trim(),
            event_time: formData.get('time').trim(),
            meeting_point: formData.get('place').trim(),
            // Asegurarse de que el monto es un string válido o usar 0.00
            estimated_amount: formData.get('amount').trim() || '0.00', 
            status: 'pending' 
        };

        // =========================================================
        // 🚨 VALIDACIONES
        // =========================================================

        if (data.title.length < 5 || data.title.length > 50) {
            showError("El Título debe tener entre 5 y 50 caracteres.");
            return;
        }

        if (data.description.length < 10 || data.description.length > 500) {
            showError("La Descripción debe tener entre 10 y 500 caracteres.");
            return;
        }
        
        if (!data.meeting_point) {
            showError("El Punto de encuentro es obligatorio.");
            return;
        }

        if (!data.event_date || !data.event_time) {
            showError("Debes seleccionar tanto la Fecha como la Hora del evento.");
            return;
        }
        
        // **VALIDACIÓN DE FECHA FUTURA ELIMINADA**
        /* const eventDateTime = new Date(`${data.event_date}T${data.event_time}`);
        const currentDateTime = new Date();
        if (eventDateTime < currentDateTime) {
            showError("La Fecha y Hora del evento no pueden ser en el pasado.");
            return;
        }
        */

        // Validación de monto estimado: Solo debe ser un número NO negativo (0 incluido)
        const amount = parseFloat(data.estimated_amount);
        if (isNaN(amount) || amount < 0) {
            showError("El Monto estimado debe ser un número positivo o cero (0).");
            return;
        }
        // Si el monto es '0' o una cadena vacía, se permite gracias a la lógica anterior.

        // =========================================================
        // FIN VALIDACIONES - PROCEDER CON EL ENVÍO
        // =========================================================
        
        // Bloquear el botón y cambiar texto
        createOutingButton.disabled = true;
        createOutingButton.textContent = 'Creando...';

        // Convertir a formato x-www-form-urlencoded
        const urlEncodedData = new URLSearchParams(data).toString();

        try {
            const res = await fetch('/outings/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded' 
                },
                body: urlEncodedData
            });

            const result = await res.json();

            if (res.ok && result.success) {
                alert(result.message || '🎉 ¡Salida creada con éxito!');
                
                // Éxito: Cerrar, limpiar y recargar la lista
                popup.classList.replace("d-flex", "d-none");
                form.reset();
                renderMeetings();
            } else {
                // Error del servidor o validación fallida en PHP
                alert(result.message || `🚫 Error (${res.status}) al crear la salida. Revísala.`);
            }

        } catch (error) {
            console.error('Error de red:', error);
            alert('❌ Error de conexión. Verifica la API /outings/create.');
        } finally {
            // Desbloquear el botón
            createOutingButton.disabled = false;
            createOutingButton.textContent = 'Crear';
        }
    });
});
</script>