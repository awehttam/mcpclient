<?php

/**
 * MCP Client API Endpoint
 *
 * Handles AJAX requests from the web interface and communicates with the MCP server
 */

require_once __DIR__ . '/../MCPClient.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Load configuration
$mcpConfig = new MCPConfig();

// Handle API requests
try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list_servers':
            $servers = $mcpConfig->listServers();
            $defaultServer = $mcpConfig->getDefaultServer();
            echo json_encode([
                'success' => true,
                'data' => [
                    'servers' => $servers,
                    'default' => $defaultServer
                ]
            ]);
            break;

        case 'initialize':
            $serverName = $_GET['server'] ?? null;
            $config = $mcpConfig->getServerConfig($serverName);

            $client = new MCPClient($config);
            $client->connect();
            $result = $client->initialize();

            // Include server info
            $result['selected_server'] = [
                'key' => $config['server_name'] ?? 'unknown',
                'name' => $config['server_display_name'] ?? 'Unknown',
                'description' => $config['server_description'] ?? ''
            ];

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'list_tools':
            $serverName = $_GET['server'] ?? null;
            $config = $mcpConfig->getServerConfig($serverName);

            $client = new MCPClient($config);
            $client->connect();
            $client->initialize();
            $tools = $client->listTools();
            echo json_encode([
                'success' => true,
                'data' => $tools
            ]);
            break;

        case 'call_tool':
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['tool'])) {
                throw new Exception("Tool name is required");
            }

            $serverName = $input['server'] ?? null;
            $config = $mcpConfig->getServerConfig($serverName);

            $client = new MCPClient($config);
            $client->connect();
            $client->initialize();

            $result = $client->callTool(
                $input['tool'],
                $input['arguments'] ?? []
            );

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'get_config_info':
            $serverName = $_GET['server'] ?? null;
            $config = $mcpConfig->getServerConfig($serverName);

            // Return safe config info (no sensitive data)
            echo json_encode([
                'success' => true,
                'data' => [
                    'connection_type' => $config['connection_type'],
                    'client_info' => $config['client_info'],
                    'server_name' => $config['server_name'] ?? 'unknown',
                    'server_display_name' => $config['server_display_name'] ?? 'Unknown'
                ]
            ]);
            break;

        default:
            throw new Exception("Unknown action: $action");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
