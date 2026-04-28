<aside class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] -translate-x-full flex-col border-r border-slate-800 bg-slate-900 px-4 py-6 transition-transform duration-300 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
       :class="{ 'translate-x-0': sidebarOpen }">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-white">Pres<span class="text-[#00FF87]">Tamos</span></h2>
        <button type="button" class="rounded-xl border border-slate-700 p-2 text-slate-300 lg:hidden" @click="sidebarOpen = false" title="Cerrar menu" aria-label="Cerrar menu">
            <?= app_icon('close') ?>
        </button>
    </div>

    <nav class="mt-8 flex flex-1 flex-col gap-2 text-sm">
        <a class="flex items-center gap-3 rounded-2xl px-4 py-3 text-slate-100 transition hover:bg-slate-800"
           href="/">
            <?= app_icon('menu', 'h-5 w-5 text-[#00FF87]') ?>
            <span>Panel principal</span>
        </a>
        <a class="flex items-center gap-3 rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-slate-800 hover:text-white" href="/clientes">
            <?= app_icon('user-plus', 'h-5 w-5 text-sky-400') ?>
            <span>Clientes</span>
        </a>
        <a class="flex items-center gap-3 rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-slate-800 hover:text-white" href="/solicitudes">
            <?= app_icon('document-plus', 'h-5 w-5 text-amber-400') ?>
            <span>Solicitudes</span>
        </a>
        <a class="flex items-center gap-3 rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-slate-800 hover:text-white" href="/prestamos">
            <?= app_icon('loan', 'h-5 w-5 text-emerald-400') ?>
            <span>Prestamos</span>
        </a>
        <a class="flex items-center gap-3 rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-slate-800 hover:text-white" href="/pagos">
            <?= app_icon('cash', 'h-5 w-5 text-fuchsia-400') ?>
            <span>Pagos</span>
        </a>

        <div x-data="{ open: false }" class="pt-2">
            <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-slate-800 hover:text-white">
                <span class="flex items-center gap-3">
                    <?= app_icon('chart', 'h-5 w-5 text-rose-400') ?>
                    <span>Reportes</span>
                </span>
                <span class="transition-transform" :class="{ 'rotate-180': open }"><?= app_icon('back', 'h-4 w-4 rotate-90') ?></span>
            </button>
            <div x-show="open" x-cloak class="mt-2 space-y-1 pl-12">
                <a href="/reportes/dashboard" class="block rounded-xl px-3 py-2 text-slate-400 transition hover:bg-slate-800 hover:text-white">Metricas globales</a>
                <a href="/reportes/mora" class="block rounded-xl px-3 py-2 text-slate-400 transition hover:bg-slate-800 hover:text-white">Analisis de mora</a>
                <a href="/reportes/auditoria" class="block rounded-xl px-3 py-2 text-slate-400 transition hover:bg-slate-800 hover:text-white">Auditoria</a>
            </div>
        </div>

        <div x-data="{ open: false }" class="pt-2">
            <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-slate-800 hover:text-white">
                <span class="flex items-center gap-3">
                    <?= app_icon('settings', 'h-5 w-5 text-cyan-400') ?>
                    <span>Configuracion</span>
                </span>
                <span class="transition-transform" :class="{ 'rotate-180': open }"><?= app_icon('back', 'h-4 w-4 rotate-90') ?></span>
            </button>
            <div x-show="open" x-cloak class="mt-2 space-y-1 pl-12">
                <a href="/configuracion/usuarios" class="block rounded-xl px-3 py-2 text-slate-400 transition hover:bg-slate-800 hover:text-white">Usuarios</a>
                <a href="/configuracion/amortizacion" class="block rounded-xl px-3 py-2 text-slate-400 transition hover:bg-slate-800 hover:text-white">Sistemas de amortizacion</a>
                <a href="/configuracion/cobros" class="block rounded-xl px-3 py-2 text-slate-400 transition hover:bg-slate-800 hover:text-white">Cobros</a>
            </div>
        </div>
    </nav>
</aside>
