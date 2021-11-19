<?php

declare(strict_types=1);

namespace Bloatless\MuninNode;

class MuninNode
{
    const VERSION = '1.0.0';

    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * @var array $plugins
     */
    protected $plugins = [];

    /**
     * Sets configuration and initializes plugins.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->loadPlugins();
    }

    /**
     * Main application loop. Creates the socket server, listens for connections, handles commands and responds
     * to client.
     *
     * @return void
     */
    public function run(): void
    {
        // Create socket
        $ip = $this->config['bind_ip'] ?? '127.0.0.1';
        $port = $this->config['bind_port'] ?? 4949;
        $url = sprintf('tcp://%s:%d', $ip, $port);
        ob_implicit_flush(true);
        $socket = stream_socket_server($url, $errno, $err);
        if ($socket === false) {
            throw new \RuntimeException(sprintf('Could not create socket server. (Error: %s)', $err));
        }

        // Disconnect client after 10 seconds of inactivity
        stream_set_timeout($socket, 10);

        // Main loop: Listen for connections/commands and generate response
        $client = null;
        while (true) {
            if (empty($client)) {
                $client = @stream_socket_accept($socket, 5);
                if ($client === false) {
                    continue;
                }

                // Send welcome message
                $this->writeToStream($client, '# Bloatless Munin Node');
            }

            // Read input from stream
            try {
                $input = $this->readFromStream($client);
                $action = $this->parseInput($input);
            } catch (\RuntimeException $e) {
                fclose($client);
                $client = null;
                continue;
            }

            // Handle input and generate response
            $data = [];
            switch ($action['command']) {
                case 'list':
                    $data = [implode(' ', array_keys($this->plugins))];
                    break;
                case 'config':
                    $data = $this->getPluginConfig($action['argument']);
                    break;
                case 'fetch':
                    $data = $this->getPluginValues($action['argument']);
                    break;
                case 'version':
                    $data = ['Bloatless Munin Node v' . self::VERSION];
                    break;
                case 'quit':
                case '.':
                    fclose($client);
                    $client = null;
                    break;
                default:
                    $data = ['# Unknown command. Try list, config, fetch, version or quit'];
                    break;
            }

            if (empty($data)) {
                continue;
            }

            // Send response to client
            try {
                $output = $this->toOutputString($data);
                $this->writeToStream($client, $output);
            } catch (\RuntimeException $e) {
                $client = null;
                continue;
            }
        }
    }

    /**
     * Fetches configuration values from plugin (config command).
     *
     * @param string $identifier
     * @return array
     */
    public function getPluginConfig(string $identifier): array
    {
        if (!isset($this->plugins[$identifier])) {
            return ['# Unknown service.'];
        }
        return $this->plugins[$identifier]->getConfiguration();
    }

    /**
     * Fetches actual values from plugin (fetch command).
     *
     * @param string $identifier
     * @return array
     */
    public function getPluginValues(string $identifier): array
    {
        if (!isset($this->plugins[$identifier])) {
            return ['# Unknown service.'];
        }
        $values = $this->plugins[$identifier]->getValues();
        $valuesForOutput = [];
        foreach ($values as $key => $value) {
            $key = str_replace('.value', '', $key) . '.value';
            $valuesForOutput[$key] = $value;
        }
        return $valuesForOutput;
    }

    /**
     * Loads/Initializes all plugins enabled in config file.
     *
     * @return void
     */
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
            $plugin->setConfig($this->config['plugin_config'][$id] ?? []);
            $this->plugins[$id] = $plugin;
        }
    }

    /**
     * Reads data from given resource/stream.
     *
     * @param Resource $resource
     * @return string
     * @throws \RuntimeException
     */
    protected function readFromStream($resource): string
    {
        $buffer = '';
        $bytesToRead = 128;
        $metadata['unread_bytes'] = 0;
        do {
            if (feof($resource)) {
                throw new \RuntimeException('Client disconnected.');
            }
            $result = fgets($resource, $bytesToRead);
            if ($result === false || feof($resource)) {
                throw new \RuntimeException('Client disconnected.');
            }
            $buffer .= $result;
            $metadata = stream_get_meta_data($resource);
            $bytesToRead = ($metadata['unread_bytes'] > $bytesToRead) ? $bytesToRead : $metadata['unread_bytes'];
        } while ($metadata['unread_bytes'] > 0);

        return $buffer;
    }

    /**
     * Writes data to given resourse/stream.
     *
     * @param Resource $resource
     * @param string $output
     * @return bool
     * @throws \RuntimeException
     */
    protected function writeToStream($resource, string $output = ''): bool
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

    /**
     * Separates client input into command and argument(s).
     *
     * @param string $input
     * @return array
     */
    protected function parseInput(string $input): array
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

    /**
     * Converts data-array to a string which can be written to stream.
     *
     * @param array $data
     * @return string
     */
    protected function toOutputString(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        // single line response
        if (count($data) === 1 && key($data) === 0) {
            return $data[0];
        }

        // multi-line key-value response
        $output = '';
        foreach ($data as $key => $value) {
            $output .= $key . ' ' . $value . PHP_EOL;
        }
        $output .= '.';

        return $output;
    }
}
