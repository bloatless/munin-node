<?php

declare(strict_types=1);

namespace Bloatless\MuninNode\Plugins;

interface PluginInterface
{
    public function getIdentifier(): string;

    public function setConfig(array $config): void;

    public function getConfiguration(): array;

    public function getValues(): array;
}
