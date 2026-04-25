<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Prestamos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 antialiased flex items-center justify-center min-h-screen">
    <div class="glass-card w-full max-w-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white">Pres<span class="text-[#00FF87]">Tamos</span></h1>
            <p class="text-slate-400 mt-2">Bienvenido, por favor inicia sesión</p>
        </div>

        <?php if (session('error') !== null) : ?>
            <div class="bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded mb-4 text-sm">
                <?= session('error') ?>
            </div>
        <?php elseif (session('errors') !== null) : ?>
            <div class="bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded mb-4 text-sm">
                <?php if (is_array(session('errors'))) : ?>
                    <ul class="list-disc list-inside">
                    <?php foreach (session('errors') as $error) : ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach ?>
                    </ul>
                <?php else : ?>
                    <?= esc(session('errors')) ?>
                <?php endif ?>
            </div>
        <?php endif ?>

        <?php if (session('message') !== null) : ?>
            <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded mb-4 text-sm">
                <?= session('message') ?>
            </div>
        <?php endif ?>

        <form action="<?= url_to('login') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-slate-300 mb-1">Nombre de Usuario</label>
                <input type="text" id="username" name="username" class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-[#00FF87] focus:border-transparent text-white outline-none transition" value="<?= old('username') ?>" required autofocus>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-slate-300 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-[#00FF87] focus:border-transparent text-white outline-none transition" required>
            </div>

            <button type="submit" title="Iniciar Sesión" class="w-full h-12 flex items-center justify-center bg-[#00FF87] hover:bg-[#00cc6a] text-slate-900 rounded-lg shadow-lg transition duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
            </button>
        </form>
    </div>
</body>
</html>
