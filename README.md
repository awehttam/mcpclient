# PHP MCP Client

A flexible PHP client for the Model Context Protocol (MCP) supporting both CLI and web interfaces with multi-server configuration.

## Features

- **Dual Interface**: CLI and web-based clients
- **Multi-Server Support**: Configure and switch between multiple MCP servers
- **Flexible Connections**: Supports both process (STDIO) and network socket connections
- **Configuration-Driven**: JSON configuration file for managing multiple servers
- **Interactive**: Full interactive mode with tool discovery
- **Modern Web UI**: Beautiful, responsive web interface with server selector

## Installation

1. Clone or download to your directory:
```bash
cd C:\devel\mcpclient
```

2. Copy the example configuration:
```bash
copy mcpconfig.json.example mcpconfig.json
```

3. Edit `mcpconfig.json` to configure your servers

## Configuration

Edit `mcpconfig.json` to define your MCP servers:

```json
{
  "default_server": "dating-local",
  "client_info": {
    "name": "php-mcp-client",
    "version": "1.0.0"
  },
  "protocol_version": "2024-11-05",
  "response_timeout": 10,
  "servers": {
    "dating-local": {
      "name": "Dating MCP Server (Local)",
      "description": "Local dating questionnaire server",
      "connection_type": "process",
      "process": {
        "server_path": "c:\\devel\\dating\\mcp-server\\index.php",
        "php_binary": "php"
      }
    },
    "dating-remote": {
      "name": "Dating MCP Server (Remote)",
      "description": "Remote dating server via socket",
      "connection_type": "socket",
      "socket": {
        "host": "localhost",
        "port": 3000,
        "timeout": 30
      }
    }
  }
}
```

### Configuration Options

- **default_server**: The server to use by default
- **client_info**: Information about your client
- **protocol_version**: MCP protocol version (default: "2024-11-05")
- **response_timeout**: Seconds to wait for server responses
- **servers**: Object containing server configurations

### Server Configuration

Each server in the `servers` object has:
- **name**: Display name for the server
- **description**: Description of what the server does
- **connection_type**: Either "process" or "socket"
- **process**: Configuration for process-based connections
  - **server_path**: Path to the MCP server PHP file
  - **php_binary**: PHP executable (default: "php")
- **socket**: Configuration for socket-based connections
  - **host**: Server hostname or IP
  - **port**: Server port
  - **timeout**: Connection timeout in seconds

## Usage

### CLI Client

#### Connect to default server
```bash
php mcp-client.php
```

#### List available servers
```bash
php mcp-client.php servers
```

#### Connect to specific server
```bash
php mcp-client.php dating-remote
```

#### CLI Commands:
Once connected, use these commands:
- `list` - Show available tools
- `info` - Show server information
- `call <tool>` - Call a specific tool (with parameter prompts)
- `quick` - Quick menu to select and call tools
- `quit` - Exit

### Web Client

1. Start a web server:
```bash
cd public_html
php -S localhost:8080
```

2. Open in browser: http://localhost:8080

3. Use the server selector dropdown to switch between configured servers

The web interface provides:
- **Server selector** dropdown in the top bar
- Visual tool browser
- Interactive forms with validation
- Real-time results display
- Connection status indicator
- Automatic reconnection when switching servers

## Architecture

### File Structure
```
C:\devel\mcpclient\
├── mcpconfig.json.example   # Example configuration
├── mcpconfig.json           # Your configuration (gitignored)
├── .gitignore               # Git ignore file
├── config.php               # Configuration loader (MCPConfig class)
├── MCPClient.php            # Core MCP client library
├── mcp-client.php           # CLI interface
├── README.md                # This file
└── public_html\
    ├── index.php            # Web interface
    └── api.php              # REST API endpoint
```

### MCPClient.php
Core library supporting both connection types:
- **Process mode**: Spawns PHP process and communicates via STDIN/STDOUT
- **Socket mode**: Connects to TCP socket server
- JSON-RPC 2.0 protocol implementation
- Tool discovery and execution

### config.php (MCPConfig class)
Configuration manager that:
- Loads and validates `mcpconfig.json`
- Provides server configuration by name
- Lists available servers
- Handles defaults and fallbacks

### mcp-client.php
Interactive CLI interface:
- Lists available servers
- Connects to selected server
- Interactive command loop
- Parameter prompting for tools

### public_html/
- **index.php**: Modern single-page web interface
- **api.php**: REST API supporting multiple servers

## Connection Types

### Process Mode
Spawns the MCP server as a child process and communicates via STDIN/STDOUT.

**Best for:**
- Local development
- Testing
- Single-user scenarios

**Example:**
```json
{
  "connection_type": "process",
  "process": {
    "server_path": "c:\\devel\\mcp-server\\index.php",
    "php_binary": "php"
  }
}
```

### Socket Mode
Connects to an MCP server running on a network socket.

**Best for:**
- Production deployments
- Remote servers
- Multi-user scenarios
- Docker containers

**Example:**
```json
{
  "connection_type": "socket",
  "socket": {
    "host": "mcp.example.com",
    "port": 3000,
    "timeout": 30
  }
}
```

## API Usage

You can also use the MCPClient library directly in your PHP code:

```php
require_once 'MCPClient.php';
require_once 'config.php';

// Load configuration
$mcpConfig = new MCPConfig();
$config = $mcpConfig->getServerConfig('dating-local');

// Create and connect client
$client = new MCPClient($config);
$client->connect();
$client->initialize();

// List available tools
$tools = $client->listTools();
print_r($tools);

// Call a tool
$result = $client->callTool('get_questions', []);
print_r($result);

// Clean up
$client->disconnect();
```

## Git Usage

The configuration file `mcpconfig.json` is gitignored to protect sensitive information. When sharing this project:

1. Always commit `mcpconfig.json.example`
2. Never commit `mcpconfig.json`
3. Users should copy the example and configure their own servers

## Requirements

- PHP 7.4 or higher
- For process mode: PHP CLI binary accessible in PATH
- For socket mode: Network access to MCP server

## Troubleshooting

### "Configuration file not found"
Copy `mcpconfig.json.example` to `mcpconfig.json` and configure your servers.

### "Server not found"
Run `php mcp-client.php servers` to see available servers, or check your `mcpconfig.json`.

### Connection timeout
- **Process mode**: Check that the server_path is correct and the file exists
- **Socket mode**: Verify the server is running and accessible at the specified host:port

### Web interface not loading servers
Ensure `public_html/api.php` can access `../mcpconfig.json` and the MCPConfig class.
