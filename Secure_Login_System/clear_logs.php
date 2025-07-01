<?php
// clear_logs.php - Clear all victim logs
// FOR EDUCATIONAL PURPOSES ONLY!

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$response = ['status' => 'error', 'message' => 'Failed to clear logs'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logFile = 'victims_log.json';
    
    try {
        if (file_exists($logFile)) {
            if (unlink($logFile)) {
                $response = [
                    'status' => 'success', 
                    'message' => 'All victim logs cleared successfully'
                ];
            } else {
                $response['message'] = 'Could not delete log file';
            }
        } else {
            $response = [
                'status' => 'success', 
                'message' => 'No log file to clear'
            ];
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Only POST method allowed';
}

echo json_encode($response);
?>