<?php

namespace Bloatless\MuninNode\Plugins;

class RedisMemory implements PluginInterface
{
    private const IDENTIFIER = 'redis_memory';

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
        $data = $this->getRedisCliData();

        $configuration = [];

        if (array_key_exists('used_memory', $data)) {
            $configuration['graph_title'] = 'Redis memory usage ' . $this->config['port'];
            $configuration['graph_category'] = 'redis';
            $configuration['graph_vlabel'] = 'memory used';
            $configuration['graph_info'] = 'Memory allocated by Redis';
            $configuration['graph_args'] = '--base 1024 -l 0';
            $configuration['memory.label'] = 'memory';
            $configuration['memory.info'] = 'Amount of mem used by Redis';
            $configuration['memory.type'] = 'GAUGE';
            $configuration['memory.min'] = '0';
        }

        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getValues(): array
    {
        $data = $this->getRedisCliData();

        $values = [
            'memory' => $data['used_memory'] ?? 0,
        ];

        return $values;
    }


    protected function getRedisCliData(): array
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 6379;
        $password = (!empty($this->config['password'])) ? '-a ' . $this->config['password'] : '';
        $cmd = sprintf('redis-cli -h %s -p %d %s info', $host, $port, $password);
        exec($cmd, $output);

        $data = [];
        foreach ($output as $line) {
            $line = trim($line);
            if (substr($line, 0, 1) === '#') {
                continue;
            }
            if ($line === '') {
                continue;
            }
            if (strpos($line, ':') === false) {
                continue;
            }
            $cols = preg_split('/:/', $line, 2);
            $data[$cols[0]] = $cols[1];
        }

        return $data;
    }
}
