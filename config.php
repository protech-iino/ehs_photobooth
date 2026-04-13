<?php
// config.php

function config_default(): array {
    return [
        'template_path'     => 'assets/template.png',
        'event_title'       => 'Edustria Photobooth',
        'countdown_seconds' => 5,
        'slots' => [
            ['x'=>30,  'y'=>60,  'w'=>360, 'h'=>240], // top big
            ['x'=>30,  'y'=>290, 'w'=>200, 'h'=>140], // bottom left
            ['x'=>250, 'y'=>290, 'w'=>200, 'h'=>140], // bottom middle
            ['x'=>470, 'y'=>290, 'w'=>200, 'h'=>140], // bottom right
        ],
    ];
}

function load_config(): array {
    $path = __DIR__.'/config.json';
    if (!file_exists($path)) {
        return config_default();
    }
    $data = json_decode((string)file_get_contents($path), true);
    if (!is_array($data)) {
        return config_default();
    }
    // merge with defaults to avoid missing keys
    return array_replace_recursive(config_default(), $data);
}

function save_config(array $cfg): bool {
    $path = __DIR__.'/config.json';
    $json = json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return (bool)file_put_contents($path, $json);
}
