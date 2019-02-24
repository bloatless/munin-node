<?php

return [
    // IP and port the munin-node will be bind to
    'bind_ip' => '127.0.0.1',
    'bind_port' => 4949,

    // Enabled/Active plugins (Class Name => Plugin Path)
    'plugins' => [
        '\Bloatless\MuninNode\Plugins\Cpu' => __DIR__ . '/../src/Plugins/Cpu.php',
        '\Bloatless\MuninNode\Plugins\If_' => __DIR__ . '/../src/Plugins/If_.php',
        '\Bloatless\MuninNode\Plugins\Load' => __DIR__ . '/../src/Plugins/Load.php',
        '\Bloatless\MuninNode\Plugins\Memory' => __DIR__ . '/../src/Plugins/Memory.php',
        '\Bloatless\MuninNode\Plugins\Uptime' => __DIR__ . '/../src/Plugins/Uptime.php',
    ],

    // Plugin specific configuration (key must match plugin identifier)
    'plugin_config' => [

        // If cores/fields are not set plugin will try to estimate the values from automatically
        'cpu' => [
            //'cpu_cores' => 1,
            //'cpu_fields' => ['system', 'user', 'nice', 'idle']
        ],

        'if_' => [
            'interface_name' => 'eno1',
        ],

        'load' => [],

        'memory' => [],

        'uptime' => [],
    ],
];
