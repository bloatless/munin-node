<?php

namespace Bloatless\MuninNode\Plugins;

class Load implements PluginInterface
{
    private const IDENTIFIER = 'load';

    protected $config = [];

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getConfiguration(): array
    {
        return [
            // graph config
            'graph_title' => 'Load average',
            'graph_args' => '--base 1000 -l 0',
            'graph_vlabel' => 'load',
            'graph_scale' => 'no',
            'graph_category' => 'system',
            'graph_info' => 'The load average of the machine describes how many processes are in the run-queue 
                (scheduled to run "immediately").',

            // value config
            'load.label' => 'load',
            'load.info' => '5 minute load average'
        ];
    }

    public function getValues(): array
    {
        $loadavgContent = file_get_contents('/proc/loadavg');
        $loadavgValues = explode(' ', $loadavgContent);
        $load = (float) $loadavgValues[1];

        return ['load' => $load];
    }
}
