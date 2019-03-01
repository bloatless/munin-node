<?php

namespace Bloatless\MuninNode\Plugins;

class Df implements PluginInterface
{
    private const IDENTIFIER = 'df';

    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * @var array $exclude
     */
    protected $exclude = [
        'none',
        'unknown',
        'rootfs',
        'iso9660',
        'squashfs',
        'udf',
        'romfs',
        'ramfs',
        'debugfs',
        'cgroup_root',
        'devtmpfs'
    ];

    /**
     * @var array $dfData
     */
    protected $dfData = [];

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
        if (!empty($config['exclude'])) {
            $this->exclude = $config['exclude'];
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        $this->provideDfData();

        $configuration = [
            // graph config
            'graph_title' => 'Disk usage in percent',
            'graph_args' => '--upper-limit 100 -l 0',
            'graph_vlabel' => '%',
            'graph_scale' => 'no',
            'graph_category' => 'disk',
        ];

        // value config
        foreach ($this->dfData as $dfItem) {
            $configuration[$dfItem['name'] . '.label'] = $dfItem['mountpoint'];
        }

        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getValues(): array
    {
        $this->provideDfData();

        $values = [];
        foreach ($this->dfData as $dfItem) {
            $values[$dfItem['name']] = $dfItem['usage'];
        }

        return $values;
    }

    /**
     * Executes df command ad prepares data for usage.
     *
     * @return void
     */
    protected function provideDfData(): void
    {
        $cmd = 'df -P -l';
        if (!empty($this->exclude)) {
            $cmd .= ' -x ' . implode(',', $this->exclude);
        }
        exec($cmd, $output);

        // remove header
        unset($output[0]);

        $data = [];
        foreach ($output as $line) {
            $cols = preg_split('/\s+/', $line);
            $name = preg_replace('/[^a-zA-Z0-9_]/', '', $cols[0]);
            $usage = 0;
            $used = $cols[2];
            $available = $cols[3];
            if ($used > 0) {
                $usage = ($used / ($used + $available)) * 100;
            }
            $data[] = [
                'name' => $name,
                'mountpoint' => $cols[5],
                'usage' => $usage,
            ];
        }

        $this->dfData = $data;
    }
}
