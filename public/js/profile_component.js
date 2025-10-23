const profileImg = document.getElementById("img_profile");
const bioText = document.getElementById("bio-text");
const createCount = document.getElementById("create-count");
const joinCount = document.getElementById("join-count");
const usertextElement = document.getElementById("user-text");

const popupEdit = document.getElementById("popup-edit");
const openBtn = document.getElementById("open-edit");

async function fetchProfileData(isOwnProfile, profileUserId) {
    const apiEndpoint = isOwnProfile ? '/profile/me' : `/profile/user/${profileUserId}`;

    try {
        const response = await fetch(apiEndpoint, {
            headers: { 'Content-Type': 'application/json' }
        });
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        const result = await response.json();

        if (result.success && result.data) {
            return result.data;
        } else {
            console.error("API Error:", result.message || "Fallo al obtener datos.");
            return null;
        }
    } catch (error) {
        console.error("Error al hacer fetch del perfil:", error);
        return null;
    }
}

function renderProfile(userData) {
    const defaultImg = '/img/default-profile.png';
    const defaultName = 'Usuario';
    const defaultBio = 'No hay biografía disponible.';

    const imgSrc = userData.foto || defaultImg;
    const userName = userData.name || defaultName;
    const bioContent = userData.bio || defaultBio;

    let imguser = `
        <img src="${imgSrc}" class="circle circle-outline w-80px mr-1r" alt="profile-img">
        <h2 id="user-text" class="tc-bold tc-secondary title">${userName}</h2>
    `;
    profileImg.innerHTML = imguser;

    if (bioText) bioText.innerText = bioContent;
    if (createCount) createCount.innerText = `${userData.create || 0} Salidas`;
    if (joinCount) joinCount.innerText = `${userData.join || 0} Salidas`;

    // Solo configurar listeners de edición si es el perfil propio
    if (isOwnProfile && openBtn) {
        setupEditListeners();
    }
}

function renderEdit() {
    popupEdit.innerHTML = `
        <div id="modal-edit" class="d-none flex-center col bg-cover">
            <div class="outgoing-card col bg-primary p-1r br-10 w-700px">
                <h3>Editar perfil</h3>
                
                <label for="photo-input" class="tc-bold tc-secondary mb-0-5">Cambiar Foto de Perfil:</label>
                <input id="photo-input" name="photo" type="file" accept="image/jpeg,image/png" class="mb-1r">

                <label for="user-input" class="tc-bold tc-secondary mb-0-5">Nombre Completo:</label>
                <input id="user-input" name="full_name" type="text" required class="user-input tc-justify w-100 mb-1r">
                
                <label for="bio-input" class="tc-bold tc-secondary mb-0-5">Biografía:</label>
                <textarea id="bio-input" name="bio" rows="4" class="bio-input tc-justify w-100"></textarea>
                
                <div class="d-flex row flex-center">
                    <button id="save-edit" class="btn-submit borderless tc-white tc-bold m-1 p-1-1-5r br-10">Guardar</button>
                    <button id="close-edit" class="btn-cancel borderless tc-white tc-bold m-1 p-1-1-5r br-10">Cancelar</button>
                </div>
            </div>
        </div>
    `;
}

function setupEditListeners() {
    const popup = document.getElementById("modal-edit");
    const userinput = document.getElementById("user-input");
    const bioInput = document.getElementById("bio-input");
    const photoInput = document.getElementById("photo-input");
    const closeBtn = document.getElementById("close-edit");
    const saveBtn = document.getElementById("save-edit");

    const usertextElement = document.getElementById("user-text");
    const bioText = document.getElementById("bio-text");
    const profileImgContainer = document.getElementById("img_profile");

    if (openBtn && closeBtn && saveBtn && popup && userinput && bioInput) {
        openBtn.addEventListener("click", () => {
            userinput.value = usertextElement.innerText.trim();
            bioInput.value = bioText.innerText.trim();
            popup.classList.replace("d-none", "d-flex");
        });

        closeBtn.addEventListener("click", () => {
            popup.classList.replace("d-flex", "d-none");
        });

        saveBtn.addEventListener("click", async () => {
            const formData = new FormData();
            formData.append('full_name', userinput.value);
            formData.append('bio', bioInput.value);

            if (photoInput.files.length > 0) {
                formData.append('photo', photoInput.files[0]);
            }

            try {
                const response = await fetch('/profile/update', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    bioText.innerText = bioInput.value;
                    profileImgContainer.querySelector('h2').innerText = userinput.value;

                    if (result.new_photo_url) {
                        let imgElement = profileImgContainer.querySelector('img');
                        if (imgElement) {
                            imgElement.src = result.new_photo_url + '?' + new Date().getTime();
                        }
                    }

                    alert(result.message);
                    popup.classList.replace("d-flex", "d-none");
                } else {
                    alert("Error al guardar: " + result.message);
                }
            } catch (error) {
                console.error("Fallo la petición de actualización:", error);
                alert("Ocurrió un error de conexión al servidor.");
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Renderizar el popup de edición solo si es el perfil propio
    if (isOwnProfile && openBtn) {
        renderEdit();
    }

    // Cargar los datos del perfil
    fetchProfileData(isOwnProfile, profileUserId).then(userData => {
        if (userData) {
            renderProfile(userData);
        } else {
            renderProfile({});
        }
    });
});