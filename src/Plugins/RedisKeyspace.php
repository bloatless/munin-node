<?php

namespace Bloatless\MuninNode\Plugins;

class RedisKeyspace implements PluginInterface
{
    private const IDENTIFIER = 'redis_keyspace';

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
        $data = $this->getRedisKeyspaceData();

        $configuration = [];

        $configuration['graph_title'] = 'Redis DBs Port ' . $this->config['port'];
        $configuration['graph_category'] = 'redis';
        $configuration['graph_vlabel'] = 'Keys / Expires per DB';
        $configuration['graph_info'] = 'Keys / Expires per DB';
        foreach ($data as $dbData) {
            $db = $dbData['db'];
            $configuration['db' . $db . '_keys.label'] = 'db' . $db . ' keys';
            $configuration['db' . $db . '_keys.info'] = 'Number of keys in db' . $db;
            $configuration['db' . $db . '_expires.label'] = 'db' . $db . ' expires';
            $configuration['db' . $db . '_expires.info'] = 'Number of keys with expiration in db' . $db;
        }

        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getValues(): array
    {
        $data = $this->getRedisKeyspaceData();

        $values = [];
        foreach ($data as $dbData) {
            $db = $dbData['db'];
            $values['db' . $db . '_keys'] = $dbData['keys'];
            $values['db' . $db . '_expires'] = $dbData['expires'];
        }

        return $values;
    }


    protected function getRedisKeyspaceData(): array
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
            $matchCount = preg_match('/db([0-9+]):keys=([0-9]+),expires=([0-9]+)/', $line, $match);
            if ($matchCount !== 1) {
                continue;
            }

            $data[] = [
                'db' => $match[1],
                'keys' => $match[2],
                'expires' => $match[3],
            ];
        }

        return $data;
    }
}
