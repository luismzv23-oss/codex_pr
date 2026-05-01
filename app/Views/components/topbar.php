<header class="sticky top-0 z-20 border-b border-slate-200 bg-slate-50/90 px-4 py-3 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <button type="button" class="rounded-2xl border border-slate-200 bg-white p-2 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 lg:hidden"
                    @click="sidebarOpen = true" title="Abrir menu" aria-label="Abrir menu">
                <?= app_icon('menu') ?>
            </button>
            <div>
                <p class="text-xs uppercase tracking-[0.28em] text-slate-400">Sistema</p>
                <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= esc($title ?? 'Dashboard') ?></p>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <button type="button" @click="darkMode = !darkMode"
                    class="rounded-2xl border border-slate-200 bg-white p-2 text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                    title="Cambiar tema" aria-label="Cambiar tema">
                <span x-show="!darkMode"><?= app_icon('moon') ?></span>
                <span x-show="darkMode" x-cloak><?= app_icon('sun') ?></span>
            </button>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @click.outside="open = false"
                        class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-2 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    <div class="hidden text-right sm:block">
                        <p class="font-medium"><?= auth()->user()->username ?? 'Usuario' ?></p>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-600 font-bold text-white">
                        <?= strtoupper(substr(auth()->user()->username ?? 'U', 0, 1)) ?>
                    </div>
                </button>

                <div x-show="open" x-cloak class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200 bg-white py-2 shadow-xl dark:border-slate-700 dark:bg-slate-800">
                    <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                        <p class="text-sm font-semibold"><?= auth()->user()->username ?? 'Usuario' ?></p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Sesion activa</p>
                    </div>
                    <a href="/perfil" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-100 dark:hover:bg-slate-700">
                        <?= app_icon('users', 'h-4 w-4') ?>
                        <span>Mi perfil</span>
                    </a>
                    <a href="/logout" class="flex items-center gap-3 px-4 py-3 text-sm text-rose-600 transition hover:bg-slate-50 dark:hover:bg-slate-700">
                        <?= app_icon('logout', 'h-4 w-4') ?>
                        <span>Cerrar sesion</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
