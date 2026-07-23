<?php

function bt_icon(string $name, string $class = 'w-4 h-4'): string
{
    $paths = [
        'arrow-up-right' => '<path d="M7 17 17 7"/><path d="M8.5 7H17v8.5"/>',
        'sparkles'       => '<path d="M12 3c.6 3.9 1.6 4.9 5.5 5.5-3.9.6-4.9 1.6-5.5 5.5-.6-3.9-1.6-4.9-5.5-5.5 3.9-.6 4.9-1.6 5.5-5.5Z"/><path d="M18.5 14.5c.25 1.5.75 2 2.25 2.25-1.5.25-2 .75-2.25 2.25-.25-1.5-.75-2-2.25-2.25 1.5-.25 2-.75 2.25-2.25Z"/>',
        'mug-hot'        => '<path d="M4 9h13v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"/><path d="M17 9h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M7.5 3c-.6.9-.6 1.9 0 2.8M11.5 3c-.6.9-.6 1.9 0 2.8"/>',
        'medal'          => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14.5 7 21l5-3 5 3-1.5-6.5"/>',
        'bag'            => '<path d="M6 8h12l-1 11.5H7z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
    ];

    $inner = $paths[$name] ?? '';

    return '<svg class="' . htmlspecialchars($class) . '" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" '
        . 'aria-hidden="true">' . $inner . '</svg>';
}
