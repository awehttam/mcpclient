#!/usr/bin/env php
<?php

/**
 * Interactive MCP Client (CLI)
 *
 * Command-line interface for interacting with MCP servers
 */

require_once __DIR__ . '/MCPClient.php';
require_once __DIR__ . '/config.php';

class InteractiveMCPClient
{
    private MCPClient $client;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new MCPClient($config);
    }

    public function start(): void
    {
        try {
            // Display connection info
            $connectionType = $this->config['connection_type'];
            echo "=== MCP Client Starting ===\n";

            if (isset($this->config['server_display_name'])) {
                echo "Server: {$this->config['server_display_name']}\n";
                if (!empty($this->config['server_description'])) {
                    echo "Description: {$this->config['server_description']}\n";
                }
            }

            echo "Connection type: $connectionType\n";

            if ($connectionType === 'process') {
                echo "Server path: {$this->config['process']['additional_arguments']}\n";
            } else {
                echo "Server: {$this->config['socket']['host']}:{$this->config['socket']['port']}\n";
            }

            // Connect and initialize
            echo "\nConnecting...\n";
            $this->client->connect();

            echo "Initializing...\n";
            $serverInfo = $this->client->initialize();

            echo "\n=== Connection Established ===\n";
            echo "Server: " . ($serverInfo['serverInfo']['name'] ?? 'Unknown') . "\n";
            echo "Version: " . ($serverInfo['serverInfo']['version'] ?? 'Unknown') . "\n";
            echo "Protocol: " . ($serverInfo['protocolVersion'] ?? 'Unknown') . "\n";

            // List available tools
            $this->listTools();

            // Start interactive mode
            $this->interactive();

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function listTools(): void
    {
        echo "\n=== Available Tools ===\n";
        $tools = $this->client->listTools();
        echo "Found " . count($tools) . " tools:\n\n";

        foreach ($tools as $index => $tool) {
            echo ($index + 1) . ". " . $tool['name'] . "\n";
            echo "   " . $tool['description'] . "\n";

            if (!empty($tool['inputSchema']['properties'])) {
                echo "   Parameters:\n";
                foreach ($tool['inputSchema']['properties'] as $param => $schema) {
                    $required = in_array($param, $tool['inputSchema']['required'] ?? []) ? '*' : '';
                    echo "     - $param$required: " . ($schema['description'] ?? '') . "\n";
                }
            }
            echo "\n";
        }
    }

    private function interactive(): void
    {
        echo "\n=== Interactive Mode ===\n";
        echo "Commands:\n";
        echo "  list       - Show available tools\n";
        echo "  call <tool> - Call a tool (you'll be prompted for arguments)\n";
        echo "  quick      - Quick menu to select and call a tool\n";
        echo "  info       - Show server information\n";
        echo "  quit       - Exit\n\n";

        while (true) {
            echo "> ";
            $input = trim(fgets(STDIN));

            if ($input === 'quit' || $input === 'exit') {
                echo "Goodbye!\n";
                break;
            }

            if ($input === 'list') {
                $this->listTools();
                continue;
            }

            if ($input === 'info') {
                $this->showInfo();
                continue;
            }

            if ($input === 'quick') {
                $this->quickMenu();
                continue;
            }

            if (strpos($input, 'call ') === 0) {
                $toolName = trim(substr($input, 5));
                $this->promptAndCallTool($toolName);
                continue;
            }

            echo "Unknown command. Type 'list', 'call <tool>', 'quick', 'info', or 'quit'\n";
        }
    }

    private function showInfo(): void
    {
        $serverInfo = $this->client->getServerInfo();
        echo "\n=== Server Information ===\n";
        echo json_encode($serverInfo, JSON_PRETTY_PRINT) . "\n";
    }

    private function quickMenu(): void
    {
        $tools = $this->client->getAvailableTools();

        echo "\nSelect a tool:\n";
        foreach ($tools as $index => $tool) {
            echo ($index + 1) . ". " . $tool['name'] . "\n";
        }
        echo "0. Cancel\n";

        echo "\nEnter number: ";
        $choice = trim(fgets(STDIN));

        if ($choice === '0' || !is_numeric($choice)) {
            return;
        }

        $index = (int)$choice - 1;
        if (!isset($tools[$index])) {
            echo "Invalid choice\n";
            return;
        }

        $tool = $tools[$index];
        $this->promptAndCallTool($tool['name']);
    }

    private function promptAndCallTool(string $toolName): void
    {
        $tools = $this->client->getAvailableTools();

        // Find the tool
        $tool = null;
        foreach ($tools as $t) {
            if ($t['name'] === $toolName) {
                $tool = $t;
                break;
            }
        }

        if (!$tool) {
            echo "Tool not found: $toolName\n";
            return;
        }

        $arguments = [];

        // Prompt for each parameter
        if (!empty($tool['inputSchema']['properties'])) {
            echo "\nEnter parameters (press Enter for empty/default):\n";
            foreach ($tool['inputSchema']['properties'] as $param => $schema) {
                $required = in_array($param, $tool['inputSchema']['required'] ?? []);
                $requiredMark = $required ? '*' : '';

                echo "$param$requiredMark (" . ($schema['description'] ?? $schema['type']) . "): ";
                $value = trim(fgets(STDIN));

                if ($value === '' && $required) {
                    echo "Required parameter, please enter a value.\n";
                    echo "$param$requiredMark: ";
                    $value = trim(fgets(STDIN));
                }

                if ($value !== '') {
                    // Type conversion
                    switch ($schema['type']) {
                        case 'integer':
                            $arguments[$param] = (int)$value;
                            break;
                        case 'array':
                            // Parse as JSON or comma-separated
                            if ($value[0] === '[') {
                                $arguments[$param] = json_decode($value, true);
                            } else {
                                $arguments[$param] = array_map('intval', explode(',', $value));
                            }
                            break;
                        default:
                            $arguments[$param] = $value;
                    }
                }
            }
        }

        echo "\n=== Calling Tool: $toolName ===\n";
        echo "Arguments: " . json_encode($arguments, JSON_PRETTY_PRINT) . "\n\n";

        try {
            $result = $this->client->callTool($toolName, $arguments);
            echo "Result:\n";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

try {
    $mcpConfig = new MCPConfig();

    // Handle command line arguments
    $serverName = null;

    if (isset($argv[1])) {
        // Check if it's a "servers" command
        if ($argv[1] === 'servers' || $argv[1] === 'list') {
            echo "=== Available MCP Servers ===\n\n";
            $servers = $mcpConfig->listServers();
            $defaultServer = $mcpConfig->getDefaultServer();

            foreach ($servers as $key => $server) {
                $default = ($key === $defaultServer) ? ' (default)' : '';
                echo "  {$key}{$default}\n";
                echo "    Name: {$server['name']}\n";
                echo "    Type: {$server['type']}\n";
                if (!empty($server['description'])) {
                    echo "    Description: {$server['description']}\n";
                }
                echo "\n";
            }

            echo "Usage: php mcp-client.php [server-name]\n";
            echo "Example: php mcp-client.php dating-remote\n";
            exit(0);
        }

        // Assume it's a server name
        $serverName = $argv[1];
    }

    // Get configuration for the specified or default server
    $config = $mcpConfig->getServerConfig($serverName);

    // Start the interactive client
    $client = new InteractiveMCPClient($config);
    $client->start();

} catch (Exception $e) {
    echo "Configuration Error: " . $e->getMessage() . "\n\n";
    echo "Run 'php mcp-client.php servers' to list available servers.\n";
    exit(1);
}
