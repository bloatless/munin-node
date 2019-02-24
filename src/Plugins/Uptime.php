<?php

namespace Bloatless\MuninNode\Plugins;

class Uptime implements PluginInterface
{
    private const IDENTIFIER = 'uptime';

    private const SECONDS_IN_DAY = 86400;

    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @inheritdoc
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        return [
            // graph config
            'graph_title' => 'Uptime',
            'graph_args' => '--base 1000 -l 0',
            'graph_scale' => 'no',
            'graph_vlabel' => 'uptime in days',
            'graph_category' => 'system',

            // value config
            'uptime.label' => 'uptime',
            'uptime.draw' => 'AREA',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getValues(): array
    {
        $uptimeContent = file_get_contents('/proc/uptime');
        $uptimeValues = explode(' ', $uptimeContent);
        $uptime = (float) sprintf('%.2f', ($uptimeValues[0] / self::SECONDS_IN_DAY));
        return ['uptime' => $uptime];
    }
}
