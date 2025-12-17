/* ============================================
   Mock API - Simulates Backend Responses
   ============================================ */

const MockAPI = {
    // Simulated delay to mimic network latency
    delay: function(ms = 300) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    // Node type definitions (what appears in the palette)
    nodeTypes: {
        // Triggers
        'trigger.manual': {
            type: 'trigger.manual',
            name: 'Manual Trigger',
            description: 'Start workflow manually',
            category: 'trigger',
            icon: 'lucide-play',
            color: '#22c55e',
            inputs: [],
            outputs: ['main'],
            parameters: []
        },
        'trigger.schedule': {
            type: 'trigger.schedule',
            name: 'Schedule Trigger',
            description: 'Run workflow on a schedule',
            category: 'trigger',
            icon: 'lucide-clock',
            color: '#22c55e',
            inputs: [],
            outputs: ['main'],
            parameters: [
                { name: 'mode', type: 'select', label: 'Trigger Mode', options: ['Interval', 'Cron'], default: 'Interval' },
                { name: 'interval', type: 'number', label: 'Interval (minutes)', default: 60 },
                { name: 'cron', type: 'string', label: 'Cron Expression', placeholder: '0 * * * *' }
            ]
        },
        'trigger.webhook': {
            type: 'trigger.webhook',
            name: 'Webhook',
            description: 'Start workflow via webhook',
            category: 'trigger',
            icon: 'lucide-webhook',
            color: '#22c55e',
            inputs: [],
            outputs: ['main'],
            parameters: [
                { name: 'httpMethod', type: 'select', label: 'HTTP Method', options: ['GET', 'POST', 'PUT', 'DELETE'], default: 'POST' },
                { name: 'path', type: 'string', label: 'Path', default: '/webhook' },
                { name: 'authentication', type: 'select', label: 'Authentication', options: ['None', 'Basic Auth', 'Header Auth'], default: 'None' }
            ]
        },

        // HTTP & API
        'http.request': {
            type: 'http.request',
            name: 'HTTP Request',
            description: 'Make HTTP requests',
            category: 'action',
            icon: 'lucide-globe',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'method', type: 'select', label: 'Method', options: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], default: 'GET' },
                { name: 'url', type: 'string', label: 'URL', required: true, placeholder: 'https://api.example.com' },
                { name: 'authentication', type: 'select', label: 'Authentication', options: ['None', 'Basic Auth', 'Bearer Token', 'API Key'], default: 'None' },
                { name: 'headers', type: 'json', label: 'Headers', default: {} },
                { name: 'body', type: 'json', label: 'Body', default: {} },
                { name: 'timeout', type: 'number', label: 'Timeout (ms)', default: 30000 }
            ]
        },

        // Data Transformation
        'transform.set': {
            type: 'transform.set',
            name: 'Set',
            description: 'Set values in your data',
            category: 'action',
            icon: 'lucide-pencil',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'values', type: 'keyvalue', label: 'Values to Set' }
            ]
        },
        'transform.function': {
            type: 'transform.function',
            name: 'Code',
            description: 'Run custom JavaScript code',
            category: 'action',
            icon: 'lucide-code',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'language', type: 'select', label: 'Language', options: ['JavaScript', 'Python'], default: 'JavaScript' },
                { name: 'code', type: 'code', label: 'Code', default: '// Your code here\nreturn items;' }
            ]
        },

        // Logic
        'logic.if': {
            type: 'logic.if',
            name: 'IF',
            description: 'Route based on conditions',
            category: 'logic',
            icon: 'lucide-git-branch',
            color: '#f59e0b',
            inputs: ['main'],
            outputs: ['true', 'false'],
            parameters: [
                { name: 'conditions', type: 'conditions', label: 'Conditions' }
            ]
        },
        'logic.switch': {
            type: 'logic.switch',
            name: 'Switch',
            description: 'Route to different outputs',
            category: 'logic',
            icon: 'lucide-git-merge',
            color: '#f59e0b',
            inputs: ['main'],
            outputs: ['output0', 'output1', 'output2', 'output3'],
            parameters: [
                { name: 'mode', type: 'select', label: 'Mode', options: ['Rules', 'Expression'], default: 'Rules' },
                { name: 'rules', type: 'rules', label: 'Routing Rules' }
            ]
        },
        'logic.merge': {
            type: 'logic.merge',
            name: 'Merge',
            description: 'Merge multiple inputs',
            category: 'logic',
            icon: 'lucide-merge',
            color: '#f59e0b',
            inputs: ['input1', 'input2'],
            outputs: ['main'],
            parameters: [
                { name: 'mode', type: 'select', label: 'Mode', options: ['Append', 'Merge By Key', 'Combine'], default: 'Append' }
            ]
        },
        'logic.loop': {
            type: 'logic.loop',
            name: 'Loop Over Items',
            description: 'Loop over each item',
            category: 'logic',
            icon: 'lucide-repeat',
            color: '#f59e0b',
            inputs: ['main'],
            outputs: ['loop', 'done'],
            parameters: [
                { name: 'batchSize', type: 'number', label: 'Batch Size', default: 1 }
            ]
        },

        // Database
        'database.mysql': {
            type: 'database.mysql',
            name: 'MySQL',
            description: 'Execute MySQL queries',
            category: 'action',
            icon: 'lucide-database',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'operation', type: 'select', label: 'Operation', options: ['Execute Query', 'Insert', 'Update', 'Delete'], default: 'Execute Query' },
                { name: 'query', type: 'code', label: 'Query', language: 'sql', default: 'SELECT * FROM table' }
            ]
        },
        'database.postgres': {
            type: 'database.postgres',
            name: 'PostgreSQL',
            description: 'Execute PostgreSQL queries',
            category: 'action',
            icon: 'lucide-database',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'operation', type: 'select', label: 'Operation', options: ['Execute Query', 'Insert', 'Update', 'Delete'], default: 'Execute Query' },
                { name: 'query', type: 'code', label: 'Query', language: 'sql', default: 'SELECT * FROM table' }
            ]
        },

        // File Operations
        'file.read': {
            type: 'file.read',
            name: 'Read File',
            description: 'Read file from disk',
            category: 'action',
            icon: 'lucide-file-input',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'filePath', type: 'string', label: 'File Path', required: true },
                { name: 'encoding', type: 'select', label: 'Encoding', options: ['utf8', 'base64', 'binary'], default: 'utf8' }
            ]
        },
        'file.write': {
            type: 'file.write',
            name: 'Write File',
            description: 'Write file to disk',
            category: 'action',
            icon: 'lucide-file-output',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'filePath', type: 'string', label: 'File Path', required: true },
                { name: 'content', type: 'string', label: 'Content' },
                { name: 'append', type: 'boolean', label: 'Append', default: false }
            ]
        },

        // Communication
        'email.send': {
            type: 'email.send',
            name: 'Send Email',
            description: 'Send email via SMTP',
            category: 'action',
            icon: 'lucide-mail',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'to', type: 'string', label: 'To', required: true },
                { name: 'subject', type: 'string', label: 'Subject', required: true },
                { name: 'body', type: 'text', label: 'Body' },
                { name: 'attachments', type: 'boolean', label: 'Include Attachments', default: false }
            ]
        },
        'slack.message': {
            type: 'slack.message',
            name: 'Slack',
            description: 'Send Slack message',
            category: 'action',
            icon: 'lucide-hash',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'channel', type: 'string', label: 'Channel', required: true },
                { name: 'message', type: 'text', label: 'Message', required: true }
            ]
        },

        // Utilities
        'util.wait': {
            type: 'util.wait',
            name: 'Wait',
            description: 'Wait for specified time',
            category: 'action',
            icon: 'lucide-timer',
            color: '#3b82f6',
            inputs: ['main'],
            outputs: ['main'],
            parameters: [
                { name: 'duration', type: 'number', label: 'Duration (seconds)', default: 1 }
            ]
        },
        'util.respond': {
            type: 'util.respond',
            name: 'Respond to Webhook',
            description: 'Send response to webhook',
            category: 'output',
            icon: 'lucide-send',
            color: '#6366f1',
            inputs: ['main'],
            outputs: [],
            parameters: [
                { name: 'statusCode', type: 'number', label: 'Status Code', default: 200 },
                { name: 'body', type: 'json', label: 'Response Body' }
            ]
        },
        'util.noop': {
            type: 'util.noop',
            name: 'No Operation',
            description: 'Pass data through unchanged',
            category: 'action',
            icon: 'lucide-arrow-right',
            color: '#6b7280',
            inputs: ['main'],
            outputs: ['main'],
            parameters: []
        },

        // Sticky Note (special)
        'util.stickynote': {
            type: 'util.stickynote',
            name: 'Sticky Note',
            description: 'Add notes to your workflow',
            category: 'utility',
            icon: 'lucide-sticky-note',
            color: '#fef3c7',
            inputs: [],
            outputs: [],
            parameters: [
                { name: 'content', type: 'text', label: 'Note Content' }
            ],
            isSticky: true
        }
    },

    // Get all node types grouped by category
    getNodeTypes: async function() {
        await this.delay(100);
        
        const categories = {
            trigger: { name: 'Triggers', icon: 'lucide-zap', nodes: [] },
            action: { name: 'Actions', icon: 'lucide-play', nodes: [] },
            logic: { name: 'Flow', icon: 'lucide-git-branch', nodes: [] },
            output: { name: 'Output', icon: 'lucide-send', nodes: [] },
            utility: { name: 'Helpers', icon: 'lucide-wrench', nodes: [] }
        };

        Object.values(this.nodeTypes).forEach(nodeType => {
            if (categories[nodeType.category]) {
                categories[nodeType.category].nodes.push(nodeType);
            }
        });

        return categories;
    },

    // Get single node type
    getNodeType: async function(type) {
        await this.delay(50);
        return this.nodeTypes[type] || null;
    },

    // Search node types
    searchNodeTypes: async function(query) {
        await this.delay(100);
        
        const lowerQuery = query.toLowerCase();
        return Object.values(this.nodeTypes).filter(nodeType => 
            nodeType.name.toLowerCase().includes(lowerQuery) ||
            nodeType.description.toLowerCase().includes(lowerQuery) ||
            nodeType.type.toLowerCase().includes(lowerQuery)
        );
    },

    // Sample workflow data
    sampleWorkflow: {
        id: 'workflow_sample_1',
        name: 'Sample HTTP Workflow',
        nodes: [
            {
                id: 'node_1',
                type: 'trigger.webhook',
                name: 'Webhook',
                position: { x: 5100, y: 5100 },
                parameters: {
                    httpMethod: 'POST',
                    path: '/webhook/incoming'
                }
            },
            {
                id: 'node_2',
                type: 'http.request',
                name: 'Fetch Data',
                position: { x: 5350, y: 5100 },
                parameters: {
                    method: 'GET',
                    url: 'https://api.example.com/data'
                }
            },
            {
                id: 'node_3',
                type: 'logic.if',
                name: 'Check Status',
                position: { x: 5600, y: 5100 },
                parameters: {}
            },
            {
                id: 'node_4',
                type: 'email.send',
                name: 'Send Success Email',
                position: { x: 5850, y: 5000 },
                parameters: {
                    to: 'admin@example.com',
                    subject: 'Success!'
                }
            },
            {
                id: 'node_5',
                type: 'slack.message',
                name: 'Notify Error',
                position: { x: 5850, y: 5200 },
                parameters: {
                    channel: '#errors',
                    message: 'Something went wrong'
                }
            }
        ],
        connections: [
            { source: 'node_1', sourceHandle: 'main', target: 'node_2', targetHandle: 'main' },
            { source: 'node_2', sourceHandle: 'main', target: 'node_3', targetHandle: 'main' },
            { source: 'node_3', sourceHandle: 'true', target: 'node_4', targetHandle: 'main' },
            { source: 'node_3', sourceHandle: 'false', target: 'node_5', targetHandle: 'main' }
        ]
    },

    // Load workflow
    loadWorkflow: async function(id) {
        await this.delay(200);
        
        // Return sample workflow for demo
        if (id === 'sample' || !id) {
            return Utils.deepClone(this.sampleWorkflow);
        }
        
        // Try to load from localStorage
        const saved = Utils.storage.get('workflow_' + id);
        if (saved) {
            return saved;
        }
        
        // Return empty workflow
        return {
            id: id || Utils.generateId(),
            name: 'New Workflow',
            nodes: [],
            connections: []
        };
    },

    // Save workflow
    saveWorkflow: async function(workflow) {
        await this.delay(300);
        
        Utils.storage.set('workflow_' + workflow.id, workflow);
        
        return { success: true, id: workflow.id };
    },

    // Execute node (mock)
    executeNode: async function(nodeId, inputData) {
        await this.delay(500 + Math.random() * 1000);
        
        // Mock response based on node type
        return {
            success: true,
            data: [
                { json: { id: 1, name: 'Sample Item 1', status: 'active' } },
                { json: { id: 2, name: 'Sample Item 2', status: 'pending' } }
            ],
            executionTime: Math.floor(Math.random() * 500) + 100
        };
    },

    // Execute workflow (mock)
    executeWorkflow: async function(workflowId) {
        await this.delay(1000 + Math.random() * 2000);
        
        return {
            success: true,
            executionId: 'exec_' + Date.now(),
            status: 'completed',
            data: {
                resultCount: Math.floor(Math.random() * 100) + 1
            }
        };
    }
};

// Make available globally
window.MockAPI = MockAPI;
