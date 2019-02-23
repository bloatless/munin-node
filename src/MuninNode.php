<?php

declare(strict_types=1);

namespace Bloatless\MuninNode;

class MuninNode
{
    const VERSION = '0.1';

    protected $config = [];

    protected $plugins = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->loadPlugins();
    }

    public function run()
    {
        ob_implicit_flush(1);
        $url = 'tcp://127.0.0.1:4949';
        $context = stream_context_create();
        $socket = stream_socket_server(
            $url,
            $errno,
            $err,
            STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
            $context
        );

        if ($socket === false) {
            throw new \RuntimeException(sprintf('Could not create socket server. (Error: %s)', $err));
        }

        $client = null;
        while (true) {
            if (empty($client)) {
                $client = @stream_socket_accept($socket, 5);
                continue;
            }

            try {
                $input = $this->readFromStream($client);
                $action = $this->parseInput($input);
            } catch (\RuntimeException $e) {
                $client = null;
                continue;
            }

            switch ($action['command']) {
                case 'list':
                    $list = implode(' ', array_keys($this->plugins));
                    $this->writeToStream($client, $list);
                    break;
                case 'config':
                    break;
                case 'fetch':
                    break;
                case 'version':
                    $this->writeToStream($client, 'Bloatless Munin Node v' . self::VERSION);
                    break;
                case 'quit':
                case '.':
                    fclose($client);
                    $client = null;
                    break;
                default:
                    $this->writeToStream($client, 'Unknown command. Try list, config, fetch, version or quit');
                    break;
            }
        }
    }

    protected function loadPlugins(): void
    {
        if (empty($this->config['plugins'])) {
            return;
        }

        require __DIR__ . '/Plugins/PluginInterface.php';

        foreach ($this->config['plugins'] as $class => $path) {
            require $path;
            /** @var \Bloatless\MuninNode\Plugins\PluginInterface $plugin */
            $plugin = new $class;
            $id = $plugin->getIdentifier();
            $this->plugins[$id] = $plugin;
        }
    }

    protected function readFromStream($resource): string
    {
        $buffer = '';
        $bytesToRead = 128;
        $metadata['unread_bytes'] = 0;
        do {
            if (feof($resource)) {
                throw new \RuntimeException('Client disconnected.');
            }
            $result = fread($resource, $bytesToRead);
            if ($result === false || feof($resource)) {
                throw new \RuntimeException('Client disconnected.');
            }
            $buffer .= $result;
            $metadata = stream_get_meta_data($resource);
            $bytesToRead = ($metadata['unread_bytes'] > $bytesToRead) ? $bytesToRead : $metadata['unread_bytes'];
        } while ($metadata['unread_bytes'] > 0);

        return $buffer;
    }

    public function writeToStream($resource, string $output = ''): bool
    {
        if (empty($output)) {
            return true;
        }
        $written = @fwrite($resource, $output . PHP_EOL);
        if (empty($written)) {
            throw new \RuntimeException('Could not write to stream.');
        }
        return true;
    }

    public function parseInput(string $input): array
    {
        $input = trim($input);
        if (in_array($input, ['list', 'version', 'quit', '.'])) {
            return ['command' => $input];
        }
        $inputParts = explode(' ', $input);
        if (count($inputParts) !== 2) {
            return ['command' => ''];
        }
        return [
            'command' => $inputParts[0],
            'argument' => $inputParts[1],
        ];
    }
}
