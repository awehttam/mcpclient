<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCP Client - Web Interface</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .status {
            padding: 15px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-indicator.connected {
            background: #28a745;
            animation: pulse 2s infinite;
        }

        .status-indicator.disconnected {
            background: #dc3545;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .main-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            min-height: 600px;
        }

        .sidebar {
            background: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
            max-height: 600px;
        }

        .sidebar h2 {
            font-size: 1.2em;
            margin-bottom: 15px;
            color: #333;
        }

        .tool-list {
            list-style: none;
        }

        .tool-item {
            background: white;
            margin-bottom: 10px;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .tool-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }

        .tool-item.selected {
            border-color: #667eea;
            background: #e7e9fc;
        }

        .tool-name {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .tool-description {
            font-size: 0.85em;
            color: #666;
        }

        .content {
            padding: 30px;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group label .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group .help-text {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }

        .result-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            display: none;
        }

        .result-section.show {
            display: block;
        }

        .result-section h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .result-content {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            overflow-x: auto;
        }

        .result-content pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .error {
            border-left-color: #dc3545;
        }

        .error h3 {
            color: #dc3545;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .welcome {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .welcome h2 {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MCP Client Web Interface</h1>
            <p>Interactive Model Context Protocol Client</p>
        </div>

        <div class="status">
            <span class="status-indicator disconnected" id="statusIndicator"></span>
            <span id="statusText">Connecting to MCP Server...</span>
            <span id="connectionInfo" style="margin-left: 20px; color: #666; font-size: 0.9em;"></span>
            <div style="float: right;">
                <label for="serverSelect" style="margin-right: 8px;">Server:</label>
                <select id="serverSelect" style="padding: 5px; border-radius: 5px; border: 1px solid #dee2e6;">
                    <option value="">Loading...</option>
                </select>
            </div>
        </div>

        <div class="main-content">
            <div class="sidebar">
                <h2>Available Tools</h2>
                <ul class="tool-list" id="toolList">
                    <li style="padding: 20px; text-align: center; color: #666;">Loading tools...</li>
                </ul>
            </div>

            <div class="content">
                <div class="welcome" id="welcomeSection">
                    <h2>Welcome to MCP Client</h2>
                    <p>Select a tool from the sidebar to get started</p>
                </div>

                <div class="form-section" id="formSection">
                    <h2 id="selectedToolName"></h2>
                    <p id="selectedToolDescription" style="color: #666; margin-bottom: 20px;"></p>
                    <form id="toolForm">
                        <div id="formFields"></div>
                        <div style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <span id="btnText">Execute Tool</span>
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearSelection()">Clear</button>
                        </div>
                    </form>
                </div>

                <div class="result-section" id="resultSection">
                    <h3>Result</h3>
                    <div class="result-content" id="resultContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let tools = [];
        let selectedTool = null;
        let serverInfo = null;
        let configInfo = null;
        let currentServer = null;
        let availableServers = {};

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadServers();
        });

        async function loadServers() {
            try {
                const response = await fetch('api.php?action=list_servers');
                const data = await response.json();
                if (data.success) {
                    availableServers = data.data.servers;
                    currentServer = data.data.default;

                    const select = document.getElementById('serverSelect');
                    select.innerHTML = '';

                    for (const [key, server] of Object.entries(availableServers)) {
                        const option = document.createElement('option');
                        option.value = key;
                        option.textContent = server.name;
                        if (key === currentServer) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    }

                    select.addEventListener('change', function() {
                        currentServer = this.value;
                        initializeConnection();
                    });

                    initializeConnection();
                }
            } catch (error) {
                updateStatus(false, 'Failed to load servers: ' + error.message);
            }
        }

        async function initializeConnection() {
            try {
                updateStatus(false, 'Connecting...');

                const response = await fetch(`api.php?action=initialize&server=${currentServer}`);
                const data = await response.json();

                if (data.success) {
                    serverInfo = data.data;
                    const serverName = serverInfo.selected_server?.name || serverInfo.serverInfo.name;
                    updateStatus(true, `Connected to ${serverName} v${serverInfo.serverInfo.version}`);

                    const connectionType = availableServers[currentServer]?.type === 'socket' ? 'Socket' : 'Process';
                    document.getElementById('connectionInfo').textContent = `(${connectionType} mode)`;

                    await loadTools();
                } else {
                    updateStatus(false, 'Failed to connect: ' + data.error);
                }
            } catch (error) {
                updateStatus(false, 'Connection error: ' + error.message);
            }
        }

        async function loadTools() {
            try {
                const response = await fetch(`api.php?action=list_tools&server=${currentServer}`);
                const data = await response.json();

                if (data.success) {
                    tools = data.data;
                    renderTools();
                } else {
                    console.error('Failed to load tools:', data.error);
                }
            } catch (error) {
                console.error('Error loading tools:', error);
            }
        }

        function renderTools() {
            const toolList = document.getElementById('toolList');
            toolList.innerHTML = '';

            tools.forEach((tool, index) => {
                const li = document.createElement('li');
                li.className = 'tool-item';
                li.innerHTML = `
                    <div class="tool-name">${tool.name}</div>
                    <div class="tool-description">${tool.description}</div>
                `;
                li.onclick = () => selectTool(tool);
                toolList.appendChild(li);
            });
        }

        function selectTool(tool) {
            selectedTool = tool;

            // Update UI
            document.querySelectorAll('.tool-item').forEach(item => item.classList.remove('selected'));
            event.currentTarget.classList.add('selected');

            document.getElementById('welcomeSection').style.display = 'none';
            document.getElementById('formSection').classList.add('active');
            document.getElementById('resultSection').classList.remove('show');

            document.getElementById('selectedToolName').textContent = tool.name;
            document.getElementById('selectedToolDescription').textContent = tool.description;

            renderForm(tool);
        }

        function renderForm(tool) {
            const formFields = document.getElementById('formFields');
            formFields.innerHTML = '';

            const properties = tool.inputSchema?.properties || {};
            const required = tool.inputSchema?.required || [];

            for (const [param, schema] of Object.entries(properties)) {
                const isRequired = required.includes(param);
                const div = document.createElement('div');
                div.className = 'form-group';

                let inputHtml = '';
                if (schema.type === 'array') {
                    inputHtml = `<textarea name="${param}" rows="3" placeholder="Enter as JSON array [1,2,3] or comma-separated values"${isRequired ? ' required' : ''}></textarea>`;
                } else {
                    inputHtml = `<input type="${schema.type === 'integer' ? 'number' : 'text'}" name="${param}"${isRequired ? ' required' : ''}>`;
                }

                div.innerHTML = `
                    <label>
                        ${param}
                        ${isRequired ? '<span class="required">*</span>' : ''}
                    </label>
                    ${inputHtml}
                    ${schema.description ? `<div class="help-text">${schema.description}</div>` : ''}
                `;

                formFields.appendChild(div);
            }

            if (Object.keys(properties).length === 0) {
                formFields.innerHTML = '<p style="color: #666;">This tool requires no parameters</p>';
            }
        }

        document.getElementById('toolForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!selectedTool) return;

            const formData = new FormData(e.target);
            const arguments = {};

            // Process form data
            for (const [key, value] of formData.entries()) {
                if (value === '') continue;

                const schema = selectedTool.inputSchema?.properties[key];
                if (!schema) continue;

                switch (schema.type) {
                    case 'integer':
                        arguments[key] = parseInt(value);
                        break;
                    case 'array':
                        // Try to parse as JSON, fallback to comma-separated
                        try {
                            if (value.trim().startsWith('[')) {
                                arguments[key] = JSON.parse(value);
                            } else {
                                arguments[key] = value.split(',').map(v => parseInt(v.trim()));
                            }
                        } catch (e) {
                            arguments[key] = value.split(',').map(v => v.trim());
                        }
                        break;
                    default:
                        arguments[key] = value;
                }
            }

            // Show loading state
            const btnText = document.getElementById('btnText');
            const originalText = btnText.textContent;
            btnText.innerHTML = '<span class="loading"></span> Executing...';

            try {
                const response = await fetch('api.php?action=call_tool', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tool: selectedTool.name,
                        arguments: arguments,
                        server: currentServer
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showResult(data.data, false);
                } else {
                    showResult(data.error, true);
                }
            } catch (error) {
                showResult('Error: ' + error.message, true);
            } finally {
                btnText.textContent = originalText;
            }
        });

        function showResult(data, isError = false) {
            const resultSection = document.getElementById('resultSection');
            const resultContent = document.getElementById('resultContent');

            resultSection.classList.add('show');
            if (isError) {
                resultSection.classList.add('error');
                resultSection.querySelector('h3').textContent = 'Error';
            } else {
                resultSection.classList.remove('error');
                resultSection.querySelector('h3').textContent = 'Result';
            }

            if (typeof data === 'object') {
                resultContent.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } else {
                resultContent.innerHTML = `<pre>${data}</pre>`;
            }

            resultSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function updateStatus(connected, message) {
            const indicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');

            if (connected) {
                indicator.classList.remove('disconnected');
                indicator.classList.add('connected');
            } else {
                indicator.classList.remove('connected');
                indicator.classList.add('disconnected');
            }

            statusText.textContent = message;
        }

        function clearSelection() {
            selectedTool = null;
            document.querySelectorAll('.tool-item').forEach(item => item.classList.remove('selected'));
            document.getElementById('welcomeSection').style.display = 'block';
            document.getElementById('formSection').classList.remove('active');
            document.getElementById('resultSection').classList.remove('show');
        }
    </script>
</body>
</html>
