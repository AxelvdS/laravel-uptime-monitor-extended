<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uptime Monitor Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #2d3748;
            margin-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #718096;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
        }
        .stat-card.up .value { color: #48bb78; }
        .stat-card.down .value { color: #f56565; }
        .stat-card.ssl .value { color: #ed8936; }
        .widget {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .widget h2 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
            font-size: 12px;
            text-transform: uppercase;
        }
        table td {
            color: #2d3748;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.up { background: #c6f6d5; color: #22543d; }
        .status-badge.down { background: #fed7d7; color: #742a2a; }
        .status-badge.ssl_issue { background: #feebc8; color: #7c2d12; }
        .loading {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Uptime Monitor Dashboard</h1>
            <p>Real-time monitoring of your devices and services</p>
        </div>

        <div class="stats-grid" id="stats-grid">
            <div class="loading">Loading statistics...</div>
        </div>

        <div class="widget">
            <h2>Uptime Over Time</h2>
            <div class="chart-container">
                <canvas id="uptimeChart"></canvas>
            </div>
        </div>

        <div class="widget">
            <h2>Devices Down</h2>
            <div id="devices-down-table">
                <div class="loading">Loading devices...</div>
            </div>
        </div>
    </div>

    <script>
        // API endpoints
        const API_BASE = '{{ config("uptime-monitor-extended.route_prefix", "uptime-monitor") }}';
        
        // Load statistics
        async function loadStats() {
            try {
                const response = await fetch(`/${API_BASE}/api/up-down-stats`);
                const data = await response.json();
                
                const statsGrid = document.getElementById('stats-grid');
                statsGrid.innerHTML = `
                    <div class="stat-card up">
                        <h3>Devices Up</h3>
                        <div class="value">${data.up}</div>
                        <div style="margin-top: 8px; color: #718096; font-size: 14px;">${data.percentage_up}% of ${data.total} total</div>
                    </div>
                    <div class="stat-card down">
                        <h3>Devices Down</h3>
                        <div class="value">${data.down}</div>
                    </div>
                    <div class="stat-card ssl">
                        <h3>SSL Issues</h3>
                        <div class="value">${data.ssl_issue}</div>
                    </div>
                `;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Load devices down table
        async function loadDevicesDown() {
            try {
                const response = await fetch(`/${API_BASE}/api/devices-down`);
                const data = await response.json();
                
                const tableContainer = document.getElementById('devices-down-table');
                
                if (data.length === 0) {
                    tableContainer.innerHTML = '<p style="color: #718096; text-align: center; padding: 20px;">All devices are up!</p>';
                    return;
                }
                
                let tableHTML = '<table><thead><tr><th>ID</th><th>URL/IP</th><th>Type</th><th>Status</th><th>Error</th><th>Last Checked</th></tr></thead><tbody>';
                
                data.forEach(device => {
                    const statusClass = device.status === 'up' ? 'up' : device.status === 'ssl_issue' ? 'ssl_issue' : 'down';
                    const lastChecked = device.last_checked ? new Date(device.last_checked).toLocaleString() : 'Never';
                    
                    tableHTML += `
                        <tr>
                            <td>${device.id}</td>
                            <td>${device.url}</td>
                            <td>${device.type}</td>
                            <td><span class="status-badge ${statusClass}">${device.status}</span></td>
                            <td>${device.error_message || '-'}</td>
                            <td>${lastChecked}</td>
                        </tr>
                    `;
                });
                
                tableHTML += '</tbody></table>';
                tableContainer.innerHTML = tableHTML;
            } catch (error) {
                console.error('Error loading devices down:', error);
            }
        }

        // Load uptime graph
        let uptimeChart = null;
        async function loadUptimeGraph() {
            try {
                const response = await fetch(`/${API_BASE}/api/uptime-graph?hours=24`);
                const data = await response.json();
                
                const ctx = document.getElementById('uptimeChart').getContext('2d');
                
                if (uptimeChart) {
                    uptimeChart.destroy();
                }
                
                uptimeChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(d => new Date(d.time).toLocaleTimeString()),
                        datasets: [
                            {
                                label: 'Up',
                                data: data.map(d => d.up),
                                borderColor: '#48bb78',
                                backgroundColor: 'rgba(72, 187, 120, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Down',
                                data: data.map(d => d.down),
                                borderColor: '#f56565',
                                backgroundColor: 'rgba(245, 101, 101, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'SSL Issues',
                                data: data.map(d => d.ssl_issue),
                                borderColor: '#ed8936',
                                backgroundColor: 'rgba(237, 137, 54, 0.1)',
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error loading uptime graph:', error);
            }
        }

        // Initial load
        loadStats();
        loadDevicesDown();
        loadUptimeGraph();

        // Auto-refresh every 60 seconds
        setInterval(() => {
            loadStats();
            loadDevicesDown();
            loadUptimeGraph();
        }, 60000);
    </script>
</body>
</html>

