<?php

return [
    // IP and port the munin-node will be bind to
    'bind_ip' => '127.0.0.1',
    'bind_port' => 4949,

    // Enabled/Active plugins (Class Name => Plugin Path)
    'plugins' => [
        '\Bloatless\MuninNode\Plugins\Load' => __DIR__ . '/../src/Plugins/Load.php',
    ],

    // Plugin specific configuration (key must match plugin identifier)
    'plugin_config' => [
        'load' => [

        ]
    ],
];
