<?php

namespace Bloatless\MuninNode\Plugins;

class If_ implements PluginInterface
{
    private const IDENTIFIER = 'if_';

    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * Holds the name of the network interface to monitor.
     *
     * @var string $interfaceName
     */
    protected $interfaceName = 'eth0';

    /**
     * Holds the maximum speed of the interface to monitor.
     *
     * @var int $interfaceSpeed
     */
    protected $interfaceSpeed = 1000;

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
        $this->interfaceName = $config['interface_name'] ?? 'eth0';
        $this->interfaceSpeed = $config['interface_speed'] ?? 1000;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        // graph config
        $configuration = [
            'graph_order' => 'down up',
            'graph_title' => $this->interfaceName . ' traffic',
            'graph_args' => '--base 1000',
            'graph_vlabel' => 'bits in (-) / out (+) per second',
            'graph_category' => 'network',
            'graph_info' => 'This graph shows the traffic of the ' . $this->interfaceName . ' network interface. Please note that the traffic is shown in bits per second, not bytes. IMPORTANT: On 32-bit systems the data source for this plugin uses 32-bit counters, which makes the plugin unreliable and unsuitable for most 100-Mb/s (or faster) interfaces, where traffic is expected to exceed 50 Mb/s over a 5 minute period.  This means that this plugin is unsuitable for most 32-bit production environments. To avoid this problem, use the ip_ plugin instead.  There should be no problems on 64-bit systems running 64-bit kernels.',
        ];

        // value config
        $configuration += [
            'down.label' => 'received',
            'down.type' => 'DERIVE',
            'down.graph' => 'no',
            'down.cdef' => 'down,8,*',
            'down.min' => '0',

            'up.label' => 'send',
            'up.type' => 'DERIVE',
            'up.negative' => 'down',
            'up.cdef' => 'up,8,*',
            'up.min' => '0',
        ];

        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getValues(): array
    {
        $pathIfStatistics = '/sys/class/net/' . $this->interfaceName . '/statistics';
        if (!file_exists($pathIfStatistics)) {
            return ['up' => 0, 'down' => 0];
        }
        return [
            'up' => (int) file_get_contents($pathIfStatistics . '/tx_bytes'),
            'down' => (int) file_get_contents($pathIfStatistics . '/rx_bytes'),
        ];
    }
}
