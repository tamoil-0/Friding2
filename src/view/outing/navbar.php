<nav class="d-flex flex-space bg-secondary p-1r d-sticky z-i-10">
    <div>
        <a href="/outgoing">
            <img class="logo" src="/img/logo.svg" alt="">
        </a>
    </div>
    <div class="d-flex flex-space ">
        <details id="notify-toggle" class="btn-none borderless mx-1">
            <summary class="tc-white list-none">
                <i class="fa-solid fa-bell"></i>
            </summary>

            <div id="notify-permission" class="d-flex b-s-2 br-5 bg-primary notify d-absol p-0-5-0-25r z-i-10 ">
                <div class="d-flex flex-center col">
                    <h4 class="m-0 br-10 p-12 text-f tc-black">
                        ¿Quieres activar notificaciones?
                    </h4>
                    <button id="btn-allow-notify" class="text-f borderless br-10 p-10 btn-secondary tc-white">
                        Activar
                    </button>
                </div>
            </div>

            <div id="notify-list" class="d-none bg-primary b-s-2 max-w-250 col notify d-absol z-i-10">
                <div id="notify-items"></div>

                <div id="notify-empty" class="d-flex flex-center w-100 br-10">
                    <p class="tc-center m-0 p-10 tc-bold text-f tc-black">
                        Aún no has recibido ninguna notificación
                    </p>
                </div>

                <div class="d-none flex-center w-100 br-10">
                    <button id="clear-notify" class="btn-tertiary m-0-5 p-10 br-10 tc-center tc-bold text-f tc-black">
                        Marcar todas como leído
                    </button>
                </div>
            </div>
        </details>

        <details class="btn-none borderless flex-1">
            <summary id="profile-img" class="list-none d-flex align-items-center circle w-55px">
            </summary>
            <div class="d-flex col profile d-absol m-0 z-i-10">
                <?php if ($navbarMode == 'dashboard'): ?>
                    <div class="bg-secondary">
                        <a href="/profile/view" class="d-block m-0-5 br-10 p-10 tc-black td-none bg-secondaryh">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Perfil
                        </a>
                    </div>
                    
                <?php endif; ?>
                <div class="bg-secondary">
                        <a href="https://wa.me/51997060775?" target="_blank" class="d-block m-0-5 br-10 p-10 tc-black td-none bg-secondaryh">
                            <i class="fa-brands fa-whatsapp"></i>
                                Contáctanos
                        </a>
                </div>
                <div class="bg-secondary">
                    <a  href="/logout" class="d-block m-0-5 br-10 p-10 tc-black td-none bg-secondaryh">
                        <i class="fa-solid fa-door-open"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </details>
    </div>
</nav>