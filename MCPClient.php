<?php

/**
 * MCP Client Library
 *
 * Supports both process-based (STDIO) and socket-based connections to MCP servers
 */

class MCPClient
{
    private $config;
    private $connectionType;
    private int $requestId = 0;

    // Process connection
    private $process;
    private $pipes;

    // Socket connection
    private $socket;

    // Server state
    private array $availableTools = [];
    private array $serverInfo = [];
    private bool $initialized = false;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connectionType = $config['connection_type'] ?? 'process';

        if (!in_array($this->connectionType, ['process', 'socket'])) {
            throw new Exception("Invalid connection type: {$this->connectionType}. Must be 'process' or 'socket'");
        }
    }

    /**
     * Connect to the MCP server
     */
    public function connect(): void
    {
        if ($this->connectionType === 'process') {
            $this->connectProcess();
        } else {
            $this->connectSocket();
        }
    }

    /**
     * Connect via process (STDIO)
     */
    private function connectProcess(): void
    {
        $serverPath = $this->config['process']['additional_arguments'] ?? null;
        if (!$serverPath || !file_exists($serverPath)) {
            //throw new Exception("Server file not found: $serverPath");
        }

        $phpBinary = $this->config['process']['command_line'] ?? 'php';

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];

        $this->process = proc_open("$phpBinary " . escapeshellarg($serverPath), $descriptorspec, $this->pipes);

        error_log("$phpBinary process is ".$this->process);
        if (!is_resource($this->process)) {
            throw new Exception("Failed to start server process");
        }

        stream_set_blocking($this->pipes[1], false);
    }

    /**
     * Connect via TCP socket
     */
    private function connectSocket(): void
    {
        $host = $this->config['socket']['host'] ?? 'localhost';
        $port = $this->config['socket']['port'] ?? 3000;
        $timeout = $this->config['socket']['timeout'] ?? 30;

        $this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if (!$this->socket) {
            throw new Exception("Failed to connect to socket $host:$port - $errstr ($errno)");
        }

        stream_set_blocking($this->socket, false);
    }

    /**
     * Initialize the MCP connection
     */
    public function initialize(): array
    {
        if ($this->initialized) {
            return $this->serverInfo;
        }

        $response = $this->sendRequest('initialize', [
            'protocolVersion' => $this->config['protocol_version'] ?? '2024-11-05',
            'capabilities' => new stdClass(),
            'clientInfo' => $this->config['client_info'] ?? [
                'name' => 'php-mcp-client',
                'version' => '1.0.0'
            ]
        ]);

        if (isset($response['result'])) {
            $this->serverInfo = $response['result'];
            $this->initialized = true;

            // Send initialized notification
            $notification = [
                'jsonrpc' => '2.0',
                'method' => 'notifications/initialized'
            ];
            $this->writeMessage(json_encode($notification) . "\n");

            return $this->serverInfo;
        }

        throw new Exception("Failed to initialize: " . json_encode($response));
    }

    /**
     * List available tools from the MCP server
     */
    public function listTools(): array
    {
        $response = $this->sendRequest('tools/list');

        if (isset($response['result']['tools'])) {
            $this->availableTools = $response['result']['tools'];
            return $this->availableTools;
        }

        throw new Exception("Failed to list tools: " . json_encode($response));
    }

    /**
     * Call a tool on the MCP server
     */
    public function callTool(string $toolName, array $arguments = []): array
    {
        $response = $this->sendRequest('tools/call', [
            'name' => $toolName,
            'arguments' => $arguments
        ]);

        if (isset($response['result'])) {
            // Extract text content
            if (isset($response['result']['content'])) {
                $textContent = '';
                foreach ($response['result']['content'] as $content) {
                    if ($content['type'] === 'text') {
                        $textContent .= $content['text'];
                    }
                }
                // Try to parse as JSON for better display
                $parsed = json_decode($textContent, true);
                return $parsed !== null ? $parsed : ['text' => $textContent];
            }
            return $response['result'];
        }

        if (isset($response['error'])) {
            throw new Exception($response['error']['message']);
        }

        throw new Exception("Unknown response: " . json_encode($response));
    }

    /**
     * Send a JSON-RPC request to the server
     */
    private function sendRequest(string $method, $params = null): array
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'id' => ++$this->requestId
        ];

        if ($params !== null) {
            $request['params'] = $params;
        }

        $json = json_encode($request) . "\n";
        $this->writeMessage($json);

        return $this->readResponse();
    }

    /**
     * Write a message to the connection
     */
    private function writeMessage(string $message): void
    {
        if ($this->connectionType === 'process') {
            if (!is_resource($this->pipes[0])) {
                throw new Exception("Process not connected");
            }
            fwrite($this->pipes[0], $message);
            error_log("Wrote $message");
            fflush($this->pipes[0]);
        } else {
            if (!is_resource($this->socket)) {
                throw new Exception("Socket not connected");
            }
            fwrite($this->socket, $message);
            fflush($this->socket);
        }
    }

    /**
     * Read a response from the connection
     */
    private function readResponse(): array
    {
        $timeout = $this->config['response_timeout'] ?? 10;
        $startTime = time();
        $buffer = '';

        $stream = $this->connectionType === 'process' ? $this->pipes[1] : $this->socket;

        while (true) {
            if (time() - $startTime > $timeout) {
                throw new Exception("Timeout waiting for server response");
            }

            $line = fgets($stream);

            if ($line !== false) {
                error_log("Got $line");
                $buffer .= $line;

                // Check if we have a complete JSON object
                $response = json_decode($buffer, true);
                if ($response !== null) {
                    return $response;
                }
            }

            usleep(10000); // 10ms
        }
    }

    /**
     * Get server information
     */
    public function getServerInfo(): array
    {
        return $this->serverInfo;
    }

    /**
     * Get available tools
     */
    public function getAvailableTools(): array
    {
        return $this->availableTools;
    }

    /**
     * Check if initialized
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Disconnect from the server
     */
    public function disconnect(): void
    {
        if ($this->connectionType === 'process') {
            if (is_resource($this->process)) {
                fclose($this->pipes[0]);
                fclose($this->pipes[1]);
                fclose($this->pipes[2]);
                proc_terminate($this->process);
                proc_close($this->process);
            }
        } else {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
        }

        $this->initialized = false;
    }

    /**
     * Destructor - ensure cleanup
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
