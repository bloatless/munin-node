<?php

namespace Bloatless\MuninNode\Plugins;

class MysqlCommands implements PluginInterface
{
    private const IDENTIFIER = 'mysql_commands';

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
        $stats = $this->getMysqlStats();

        $configuration = [];
        if (array_key_exists('Com_select', $stats)) {
            $configuration['graph_title'] = 'MySQL Command Counters';
            $configuration['graph_category'] = 'mysql';
            $configuration['graph_vlabel'] = 'commands/s';
            $configuration['graph_info'] = 'Command breakdown by user';
            $configuration['graph_args'] = '--base 1000 -l 0';

            $configuration['select.label'] = 'Select';
            $configuration['select.info'] = 'Select commands';
            $configuration['select.type'] = 'DERIVE';
            $configuration['select.draw'] = 'LINE2';
            $configuration['select.min'] = '0';
            $configuration['insert.label'] = 'Insert';
            $configuration['insert.info'] = 'Insert commands';
            $configuration['insert.type'] = 'DERIVE';
            $configuration['insert.draw'] = 'LINE2';
            $configuration['insert.min'] = '0';
            $configuration['update.label'] = 'Update';
            $configuration['update.info'] = 'Update commands';
            $configuration['update.type'] = 'DERIVE';
            $configuration['update.draw'] = 'LINE2';
            $configuration['update.min'] = '0';
            $configuration['delete.label'] = 'Delete';
            $configuration['delete.info'] = 'Delete commands';
            $configuration['delete.type'] = 'DERIVE';
            $configuration['delete.draw'] = 'LINE2';
            $configuration['delete.min'] = '0';
            $configuration['other.label'] = 'Other';
            $configuration['other.info'] = 'Other commands';
            $configuration['other.type'] = 'DERIVE';
            $configuration['other.draw'] = 'LINE2';
            $configuration['other.min'] = '0';
        }

        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getValues(): array
    {
        $values = [
            'select' => 0,
            'insert' => 0,
            'update' => 0,
            'delete' => 0,
            'other' => 0,
        ];

        $stats = $this->getMysqlStats();
        if (isset($stats['Com_select'])) {
            $values['select'] += $stats['Com_select'];
        }
        if (isset($stats['Com_insert'])) {
            $values['insert'] += $stats['Com_insert'];
        }
        if (isset($stats['Com_update'])) {
            $values['update'] += $stats['Com_update'];
        }
        if (isset($stats['Com_delete'])) {
            $values['delete'] += $stats['Com_delete'];
        }

        if (isset($stats['Com_load'])) {
            $values['other'] += $stats['Com_load'];
        }
        if (isset($stats['Com_replace'])) {
            $values['other'] += $stats['Com_replace'];
        }
        if (isset($stats['Com_insert_select'])) {
            $values['other'] += $stats['Com_insert_select'];
        }
        if (isset($stats['Com_replace_select'])) {
            $values['other'] += $stats['Com_replace_select'];
        }
        if (isset($stats['Com_update_multi'])) {
            $values['other'] += $stats['Com_update_multi'];
        }

        return $values;
    }


    protected function getMysqlStats(): array
    {
        $stats = [];

        try {
            $dbh = new \PDO($this->config['dsn'], $this->config['username'], $this->config['password']);
            foreach ($dbh->query('SHOW GLOBAL STATUS') as $row) {
                $stats[$row[0]] = $row[1];
            }
            $dbh = null;
        } catch (\PDOException $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
        }

        return $stats;
    }
}
