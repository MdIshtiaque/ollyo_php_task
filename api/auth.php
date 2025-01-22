<?php
class APIAuth {
    private static $api_keys = [
        // In production, these should be stored in a database
        'test_key_123' => 'test_client'
    ];
    
    public static function validateAPIKey() {
        $headers = getallheaders();
        $api_key = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : null;
        
        if (!$api_key) {
            self::sendError('API key is required', 401);
            return false;
        }
        
        if (!isset(self::$api_keys[$api_key])) {
            self::sendError('Invalid API key', 401);
            return false;
        }
        
        return true;
    }
    
    public static function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit();
    }
    
    public static function sendResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data);
        exit();
    }
} 