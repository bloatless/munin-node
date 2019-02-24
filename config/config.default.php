<?php

return [
    // IP and port the munin-node will be bind to
    'bind_ip' => '127.0.0.1',
    'bind_port' => 4949,

    // Enabled/Active plugins (Class Name => Plugin Path)
    'plugins' => [
        '\Bloatless\MuninNode\Plugins\Load' => __DIR__ . '/../src/Plugins/Load.php',
        '\Bloatless\MuninNode\Plugins\Cpu' => __DIR__ . '/../src/Plugins/Cpu.php',
    ],

    // Plugin specific configuration (key must match plugin identifier)
    'plugin_config' => [
        'load' => [],

        // If cores/fields are not set plugin will try to estimate the values from automatically
        'cpu' => [
            //'cpu_cores' => 1,
            //'cpu_fields' => ['system', 'user', 'nice', 'idle']
        ],
    ],
];
