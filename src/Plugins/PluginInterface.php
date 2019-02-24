<?php

declare(strict_types=1);

namespace Bloatless\MuninNode\Plugins;

interface PluginInterface
{
    /**
     * Returns a unique identifier for the plugin.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Injects relevant config-values from main config file into plugin.
     *
     * @param array $config
     */
    public function setConfig(array $config): void;

    /**
     * Returns plugins configuration values. (config command)
     *
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * Returns actual plugin values. (fetch command)
     *
     * @return array
     */
    public function getValues(): array;
}
