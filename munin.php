<?php

require __DIR__ . '/src/MuninNode.php';

// load configuration
$pathConfig = __DIR__ . '/config/config.php';
$pathDefaultConfig = __DIR__ . '/config/config.default.php';
if (file_exists($pathConfig)) {
    $config = include $pathConfig;
} elseif (file_exists($pathDefaultConfig)) {
    $config = include $pathDefaultConfig;
}
if (!isset($config)) {
    exit('Could not find valid config file.');
}

// run node
$node = new \Bloatless\MuninNode\MuninNode($config);
$node->run();
