<!DOCTYPE html>
<html lang="es" x-data="{ darkMode: localStorage.getItem('dark') === 'true', sidebarOpen: false }"
      x-init="$watch('darkMode', val => localStorage.setItem('dark', val))"
      x-bind:class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Prestamos Dashboard' ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        glass: 'rgba(30, 41, 59, 0.6)',
                        neon: '#00FF87',
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-slate-50 font-['Inter'] text-slate-900 antialiased transition-colors duration-300 dark:bg-slate-900 dark:text-slate-100">
    <div class="min-h-screen lg:flex">
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

        <?= $this->include('components/sidebar') ?>

        <div class="relative flex min-h-screen min-w-0 flex-1 flex-col overflow-x-hidden">
            <?= $this->include('components/topbar') ?>

            <main class="flex-1 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
                <?php if (session()->getFlashdata('message')): ?>
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">
                        <?= esc(session()->getFlashdata('message')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
                        <?= implode('<br>', array_map('esc', session()->getFlashdata('errors'))) ?>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
