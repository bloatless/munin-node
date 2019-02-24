<?php

declare(strict_types=1);

namespace Bloatless\MuninNode\Plugins;

class Cpu implements PluginInterface
{
    private const IDENTIFIER = 'cpu';

    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * CPU core count
     *
     * @var int $cpuCores
     */
    protected $cpuCores = 1;

    /**
     * Available cpu fields (depending on linux version)
     *
     * @var array $cpuFields
     */
    protected $cpuFields = [];

    /**
     * Number of available CPU fields (depending on linux version)
     *
     * @var int $cpuFieldCount
     */
    protected $cpuFieldCount = 0;

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
        $this->provideCpuCores();
        $this->provideCpuFields();
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        // graph config
        $configuration = [
            'graph_title' => 'CPU usage',
            'graph_order' => implode($this->cpuFields, ' '),
            'graph_args' => '--base 1000 -r --lower-limit 0 --upper-limit '.  $this->cpuCores * 100,

            'graph_vlabel' => '%',
            'graph_scale' => 'no',
            'graph_info' => 'This graph shows how CPU time is spent.',
            'graph_category' => 'system',
            'graph_period' => 'second',
        ];

        // value config
        $configuration += [
            'system.label' => 'system',
            'system.draw' => 'AREA',
            'system.min' => '0',
            'system.type' => 'DERIVE',
            'system.info' => 'CPU time spent by the kernel in system activities',
            'user.label' => 'user',
            'user.draw' => 'STACK',
            'user.min' => '0',
            'user.type' => 'DERIVE',
            'user.info' => 'CPU time spent by normal programs and daemons',
            'nice.label' => 'nice',
            'nice.draw' => 'STACK',
            'nice.min' => '0',
            'nice.type' => 'DERIVE',
            'nice.info' => 'CPU time spent by nice(1)d programs',
            'idle.label' => 'idle',
            'idle.draw' => 'STACK',
            'idle.min' => '0',
            'idle.type' => 'DERIVE',
            'idle.info' => 'Idle CPU time',
        ];

        if (in_array('iowait', $this->cpuFields)) {
            $configuration += [
                'iowait.label' => 'iowait',
                'iowait.draw' => 'STACK',
                'iowait.min' => '0',
                'iowait.type' => 'DERIVE',
                'iowait.info' =>
                    'CPU time spent waiting for I/O operations to finish when there is nothing else to do.',
                'irq.label' => 'irq',
                'irq.draw' => 'STACK',
                'irq.min' => '0',
                'irq.type' => 'DERIVE',
                'irq.info' => 'CPU time spent handling interrupts',
                'softirq.label' => 'softirq',
                'softirq.draw' => 'STACK',
                'softirq.min' => '0',
                'softirq.type' => 'DERIVE',
                'softirq.info' => 'CPU time spent handling "batched" interrupts',
            ];
        }

        if (in_array('steal', $this->cpuFields)) {
            $configuration += [
                'steal.label' => 'steal',
                'steal.draw' => 'STACK',
                'steal.min' => '0',
                'steal.type' => 'DERIVE',
                'steal.info' =>
                    'The time that a virtual CPU had runnable tasks, but the virtual CPU itself was not running',
            ];
        }

        if (in_array('guest', $this->cpuFields)) {
            $configuration += [
                'guest.label' => 'guest',
                'guest.draw' => 'STACK',
                'guest.min' => '0',
                'guest.type' => 'DERIVE',
                'guest.info' =>
                    'The time spent running a virtual CPU for guest operating systems under the control of the Linux kernel.',
            ];
        }

        if (in_array('guest_nice', $this->cpuFields)) {
            $configuration += [
                'guest_nice.label' => 'guest_nice',
                'guest_nice.draw' => 'STACK',
                'guest_nice.min' => '0',
                'guest_nice.type' => 'DERIVE',
                'guest_nice.info' => 'The time spent in guest nice state.',
            ];
        }

        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getValues(): array
    {
        $values = [];
        $cpuUsageValues = $this->getCpuUsage();
        foreach ($cpuUsageValues as $valueName => $value) {
            $values[$valueName] = (float) sprintf('%.0f', $value);
        }
        return $values;
    }

    /**
     * Sets the number of cpu cores on current system.
     * Uses either value from config file or tries to determine the current cpu core count.
     *
     * @return void
     */
    protected function provideCpuCores(): void
    {
        // use core-count from config
        if (isset($this->config['cpu_cores'])) {
            $this->cpuCores = (int) $this->config['cpu_cores'];
            return;
        }

        // estimate core count
        $statContent = file_get_contents('/proc/stat');
        $coreCount = preg_match_all('/^cpu[0-9]+/mU', $statContent, $matches);
        if (!empty($coreCount)) {
            $this->cpuCores = $coreCount;
        }
    }

    /**
     * Sets CPU fields (and number) available on current system.
     * Uses value from config or tries to determine cpu fields of current linux version.
     *
     * @return void
     */
    protected function provideCpuFields(): void
    {
        if (!empty($this->config['cpu_fields'])) {
            $this->cpuFields = $this->config['cpu_fields'];
            return;
        }

        // add the basic fields:
        $this->cpuFields = ['system', 'user', 'nice', 'idle'];

        // add extended fields depending on system/version:
        $statsContent = file_get_contents('/proc/stat');
        if (preg_match('/^cpu\s+([0-9]+\s){6}[0-9]+/', $statsContent) === 1) {
            $this->cpuFields = array_merge($this->cpuFields, ['iowait',  'irq',  'softirq']);
        }
        if (preg_match('/^cpu\s+([0-9]+\s){7}[0-9]+/', $statsContent) === 1) {
            array_push($this->cpuFields, 'steal');
        }
        if (preg_match('/^cpu\s+([0-9]+\s){8}[0-9]+/', $statsContent) === 1) {
            array_push($this->cpuFields, 'guest');
        }
        if (preg_match('/^cpu\s+([0-9]+\s){9}[0-9]+/', $statsContent) === 1) {
            array_push($this->cpuFields, 'guest_nice');
        }

        $this->cpuFieldCount = count($this->cpuFields);
    }

    /**
     * Returns current CPU usage values.
     *
     * @return array
     */
    protected function getCpuUsage() : array
    {
        $statContent = file_get_contents('/proc/stat');
        preg_match('/^cpu\s+([0-9]+\s){'.($this->cpuFieldCount - 1).'}[0-9]+/U', $statContent, $matches);
        $valuesRaw = preg_replace('/[^0-9 ]/', '', $matches[0]);
        $values = explode(' ', trim($valuesRaw));
        $valuesNamed = array_combine($this->cpuFields, $values);
        return $valuesNamed;
    }
}
