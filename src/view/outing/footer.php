<footer class="foot bg-secondary">
    <div class="d-flex flex-space-a">
        <div class="bg-secondaryh flex-1 tc-center">
            <a href="/outgoing" class="d-flex col flex-center tc-white texto td-none p-10">
                <i class="fa-solid fa-map-location-dot"></i>Salidas</a>
        </div>
        <div class="bg-secondaryh flex-1 tc-center">
            <a href="/myoutgoing" class="d-flex col flex-center tc-white texto td-none p-10">
                <i class="fa-solid fa-face-smile"></i>Mis Salidas</a>
        </div>
        <?php if ($footer == 'dashboard'): ?>
        <div class="d-flex flex-center bg-secondaryh flex-1">
            <button id="open-popup1" type="button" class="d-flex flex-center flex-1 col btn-none borderless tc-white texto p-10">
                <i class="fa-solid fa-plus"></i>Crear Salida</button>
        </div>
        <?php if ($footer2 == 'outgoing'): ?>
        <div class="d-flex flex-center bg-secondaryh flex-1">
            <button id="open-popup2" type="button" class="d-flex flex-center flex-1 col btn-none borderless tc-white texto p-10">
                <i class="fa-solid fa-magnifying-glass"></i>Buscar</button>
            </button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</footer>