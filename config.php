<?php

/**
 * MCP Client Configuration Loader
 *
 * Loads configuration from mcpconfig.json and provides helper functions
 */

class MCPConfig
{
    private array $config;

    public function __construct(string $configPath = null)
    {
        if ($configPath === null) {
            $configPath = __DIR__ . '/mcpconfig.json';
        }

        if (!file_exists($configPath)) {
            // Try the example file
            $examplePath = __DIR__ . '/mcpconfig.json.example';
            if (file_exists($examplePath)) {
                throw new Exception(
                    "Configuration file not found: $configPath\n" .
                    "Copy mcpconfig.json.example to mcpconfig.json and configure your servers."
                );
            }
            throw new Exception("Configuration file not found: $configPath");
        }

        $json = file_get_contents($configPath);
        $this->config = json_decode($json, true);

        if ($this->config === null) {
            throw new Exception("Invalid JSON in configuration file: " . json_last_error_msg());
        }

        if (!isset($this->config['servers']) || empty($this->config['servers'])) {
            throw new Exception("No servers defined in configuration file");
        }
    }

    /**
     * Get configuration for a specific server
     */
    public function getServerConfig(string $serverName = null): array
    {
        if ($serverName === null) {
            $serverName = $this->config['default_server'] ?? null;
        }

        if ($serverName === null) {
            throw new Exception("No server name specified and no default_server configured");
        }

        if (!isset($this->config['servers'][$serverName])) {
            throw new Exception("Server not found: $serverName");
        }

        $serverConfig = $this->config['servers'][$serverName];

        // Merge with global settings
        return [
            'connection_type' => $serverConfig['connection_type'],
            'process' => $serverConfig['process'] ?? [],
            'socket' => $serverConfig['socket'] ?? [],
            'client_info' => $this->config['client_info'] ?? [
                'name' => 'php-mcp-client',
                'version' => '1.0.0'
            ],
            'protocol_version' => $this->config['protocol_version'] ?? '2024-11-05',
            'response_timeout' => $this->config['response_timeout'] ?? 10,
            'server_name' => $serverName,
            'server_display_name' => $serverConfig['name'] ?? $serverName,
            'server_description' => $serverConfig['description'] ?? ''
        ];
    }

    /**
     * List all available servers
     */
    public function listServers(): array
    {
        $servers = [];
        foreach ($this->config['servers'] as $key => $server) {
            $servers[$key] = [
                'key' => $key,
                'name' => $server['name'] ?? $key,
                'description' => $server['description'] ?? '',
                'type' => $server['connection_type'] ?? 'unknown'
            ];
        }
        return $servers;
    }

    /**
     * Get the default server name
     */
    public function getDefaultServer(): ?string
    {
        return $this->config['default_server'] ?? null;
    }

    /**
     * Get all servers configuration
     */
    public function getAllServers(): array
    {
        return $this->config['servers'] ?? [];
    }
}

// Legacy compatibility: return default server config
try {
    $mcpConfig = new MCPConfig();
    return $mcpConfig->getServerConfig();
} catch (Exception $e) {
    // Return a basic config if JSON doesn't exist yet
    return [
        'connection_type' => 'process',
        'process' => [
            'additional_arguments' => 'c:\mcp-server\index.php',
            'command_line' => 'php',
        ],
        'socket' => [
            'host' => 'localhost',
            'port' => 3000,
            'timeout' => 30,
        ],
        'client_info' => [
            'name' => 'php-mcp-client',
            'version' => '1.0.0',
        ],
        'protocol_version' => '2024-11-05',
        'response_timeout' => 10,
    ];
}
