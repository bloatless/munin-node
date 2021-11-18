<?php

namespace Bloatless\MuninNode\Plugins;

class RedisStats implements PluginInterface
{
    private const IDENTIFIER = 'redis_stats';

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

        if (array_key_exists('keyspace_hits', $data)) {
            $configuration['graph_order'] = 'commands hits misses';
            $configuration['graph_title'] = 'Redis Command Rate';
            $configuration['graph_category'] = 'redis';
            $configuration['graph_vlabel'] = 'commands/s';
            $configuration['graph_info'] = 'Redis commands, hits, misses';
            $configuration['commands.label'] = 'commands/s';
            $configuration['commands.type'] = 'COUNTER';
            $configuration['commands.min'] = '0';
            $configuration['hits.label'] = 'key hits';
            $configuration['hits.type'] = 'COUNTER';
            $configuration['hits.min'] = '0';
            $configuration['misses.label'] = 'key misses';
            $configuration['misses.type'] = 'COUNTER';
            $configuration['misses.min'] = '0';
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
            'commands' => $data['total_commands_processed'] ?? 0,
            'hits' => $data['keyspace_hits'] ?? 0,
            'misses' => $data['keyspace_misses'] ?? 0,
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
