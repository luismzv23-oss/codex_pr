<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('money')) {
    function money($amount, string $currency = 'ARS'): string
    {
        return $currency . ' ' . number_format((float) $amount, 2, ',', '.');
    }
}

if (! function_exists('interest_percent')) {
    function interest_percent($rate, int $decimals = 2): string
    {
        return number_format((float) $rate * 100, $decimals, ',', '.');
    }
}

if (! function_exists('amortization_system_options')) {
    function amortization_system_options(): array
    {
        return [
            'french' => 'Frances',
            'german' => 'Aleman',
            'american' => 'Americano',
        ];
    }
}

if (! function_exists('amortization_system_label')) {
    function amortization_system_label(?string $code): string
    {
        $options = amortization_system_options();
        $normalized = strtolower(trim((string) $code));

        return $options[$normalized] ?? ucfirst((string) $code);
    }
}

if (! function_exists('customer_initials')) {
    function customer_initials(?string $fullName): string
    {
        $parts = preg_split('/\s+/', trim((string) $fullName)) ?: [];
        $parts = array_values(array_filter($parts, static fn(string $item): bool => $item !== ''));

        if ($parts === []) {
            return 'CL';
        }

        $first = $parts[0];
        $last = $parts[count($parts) - 1];

        if (count($parts) === 1) {
            return strtoupper(substr($first, 0, 2));
        }

        return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
    }
}

if (! function_exists('loan_alias')) {
    function loan_alias(int $sequence, ?string $customerName): string
    {
        return sprintf('PS-%03d-%s', $sequence, customer_initials($customerName));
    }
}

if (! function_exists('code39_svg')) {
    function code39_svg(string $value, int $height = 54, int $narrow = 2, int $wide = 5, int $gap = 2): string
    {
        $patterns = [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
        ];

        $encoded = '*' . strtoupper(trim($value)) . '*';
        $x = 10;
        $bars = [];

        foreach (str_split($encoded) as $char) {
            if (! isset($patterns[$char])) {
                $char = '-';
            }

            foreach (str_split($patterns[$char]) as $index => $type) {
                $width = $type === 'w' ? $wide : $narrow;
                if ($index % 2 === 0) {
                    $bars[] = '<rect x="' . $x . '" y="6" width="' . $width . '" height="' . $height . '" fill="#0f172a"/>';
                }
                $x += $width;
            }

            $x += $gap;
        }

        $labelY = $height + 28;
        $width = $x + 10;

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . ($height + 36) . '" viewBox="0 0 ' . $width . ' ' . ($height + 36) . '">' .
            implode('', $bars) .
            '<text x="' . ($width / 2) . '" y="' . $labelY . '" text-anchor="middle" font-family="monospace" font-size="14" fill="#0f172a">' . esc($encoded) . '</text>' .
            '</svg>';
    }
}

if (! function_exists('status_badge')) {
    function status_badge(?string $status): string
    {
        $map = [
            'verified' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            'rejected' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
            'draft' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
            'evaluation' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
            'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'disbursed' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300',
            'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'paid_off' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'partial' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            'overdue' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
            'defaulted' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
            'restricted' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            'disabled' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        ];

        return $map[$status ?? ''] ?? 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200';
    }
}

if (! function_exists('status_label')) {
    function status_label(?string $status): string
    {
        $map = [
            'verified' => 'Verificado',
            'pending' => 'Pendiente',
            'rejected' => 'Rechazado',
            'draft' => 'Borrador',
            'evaluation' => 'En evaluacion',
            'approved' => 'Aprobado',
            'disbursed' => 'Desembolsado',
            'active' => 'Activo',
            'paid' => 'Pagado',
            'paid_off' => 'Pagado',
            'partial' => 'Parcial',
            'overdue' => 'En mora',
            'defaulted' => 'Incobrable',
            'restricted' => 'Restringido',
            'disabled' => 'Deshabilitado',
        ];

        return $map[$status ?? ''] ?? ucfirst((string) $status);
    }
}

if (! function_exists('app_icon')) {
    function app_icon(string $name, string $classes = 'h-5 w-5'): string
    {
        $icons = [
            'menu' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/>',
            'close' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/>',
            'add' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14m-7-7h14"/>',
            'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 4.487a2.1 2.1 0 113.03 2.908L9.52 17.767 5 19l1.233-4.52 10.63-9.993z"/>',
            'save' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 5.75A1.75 1.75 0 016.75 4h8.69a1.75 1.75 0 011.24.514l2.806 2.806A1.75 1.75 0 0120 8.56v8.69A1.75 1.75 0 0118.25 19h-11.5A1.75 1.75 0 015 17.25V5.75z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 4v4h7V4M8.5 19v-6h7v6"/>',
            'back' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 6l-6 6 6 6"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 12h16"/>',
            'view' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><circle cx="12" cy="12" r="3" stroke-width="1.8"/>',
            'user-plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19a6 6 0 00-12 0"/><circle cx="9" cy="7" r="4" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 8v6m-3-3h6"/>',
            'document-plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8zm0 0v5h5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 11v6m-3-3h6"/>',
            'cash' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5h18v9H3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12a4 4 0 01-8 0 4 4 0 018 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10h.01M17 14h.01"/>',
            'chart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16V9m5 7V5m5 11v-4"/>',
            'approve' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12l4 4L19 6"/>',
            'reject' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/>',
            'loan' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v12m4-9.5A3.5 3.5 0 0012.5 5H11a3 3 0 000 6h2a3 3 0 010 6h-1.5A3.5 3.5 0 018 15.5"/>',
            'logout' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 16l4-4-4-4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 12H9"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 19H6a2 2 0 01-2-2V7a2 2 0 012-2h6"/>',
            'sun' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v2.25M12 18.75V21M4.22 4.22l1.59 1.59M18.19 18.19l1.59 1.59M3 12h2.25M18.75 12H21M4.22 19.78l1.59-1.59M18.19 5.81l1.59-1.59"/><circle cx="12" cy="12" r="4" stroke-width="1.8"/>',
            'moon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.354 15.354A8.5 8.5 0 018.646 3.646 9 9 0 1012 21a8.96 8.96 0 008.354-5.646z"/>',
            'settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1 1 0 011.35-.936l.88.352a1 1 0 00.89 0l.88-.352a1 1 0 011.35.936l.06.946a1 1 0 00.53.81l.82.47a1 1 0 01.37 1.33l-.47.82a1 1 0 000 .89l.47.82a1 1 0 01-.37 1.33l-.82.47a1 1 0 00-.53.81l-.06.946a1 1 0 01-1.35.936l-.88-.352a1 1 0 00-.89 0l-.88.352a1 1 0 01-1.35-.936l-.06-.946a1 1 0 00-.53-.81l-.82-.47a1 1 0 01-.37-1.33l.47-.82a1 1 0 000-.89l-.47-.82a1 1 0 01.37-1.33l.82-.47a1 1 0 00.53-.81z"/><circle cx="12" cy="12" r="2.75" stroke-width="1.8"/>',
            'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 19a4 4 0 00-8 0"/><circle cx="12" cy="9" r="3" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 19a4 4 0 013-3.87M19 19a4 4 0 00-3-3.87"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.5 9a2.5 2.5 0 01-2.5 2.5M16.5 9A2.5 2.5 0 0019 11.5"/>',
            'filter' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M7 12h10M10 18h4"/>',
            'statement' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 4.75h10A1.25 1.25 0 0118.25 6v12A1.25 1.25 0 0117 19.25H7A1.25 1.25 0 015.75 18V6A1.25 1.25 0 017 4.75z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.5 9.25h7M8.5 12h7M8.5 14.75h4.5"/>',
            'disable' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 9l6 6M15 9l-6 6"/><circle cx="12" cy="12" r="8" stroke-width="1.8"/>',
            'delete' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 4.75h6m-8 3h10m-8.25 0v8.5m4.5-8.5v8.5m4.5-8.5v8.5M8 19.25h8A1.75 1.75 0 0017.75 17.5v-9.75h-11.5v9.75A1.75 1.75 0 008 19.25z"/>',
            'pdf' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8zm0 0v5h5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.5 13h2.5a1.5 1.5 0 010 3H8.5v-6H11a1.5 1.5 0 010 3H8.5m6-3h2m-2 0v6m0-3h1.5"/>',
        ];

        $path = $icons[$name] ?? $icons['view'];

        return '<svg class="' . $classes . '" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">' . $path . '</svg>';
    }
}

if (! function_exists('icon_button_classes')) {
    function icon_button_classes(string $tone = 'dark'): string
    {
        $map = [
            'dark' => 'border-slate-900 bg-slate-900 text-white hover:bg-slate-800 dark:border-white dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100',
            'accent' => 'border-emerald-300 bg-emerald-300 text-slate-950 hover:bg-emerald-200',
            'ghost' => 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800',
            'sky' => 'border-sky-600 bg-sky-600 text-white hover:bg-sky-500',
            'rose' => 'border-rose-600 bg-rose-600 text-white hover:bg-rose-500',
            'emerald' => 'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-500',
        ];

        return $map[$tone] ?? $map['dark'];
    }
}
