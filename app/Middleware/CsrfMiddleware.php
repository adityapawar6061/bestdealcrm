<?php
namespace Middleware;

class CsrfMiddleware
{
    public function handle(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            if (!verifyCsrf()) {
                http_response_code(403);
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    echo json_encode(['error' => 'Invalid CSRF token']);
                } else {
                    echo 'CSRF token validation failed.';
                }
                exit;
            }
        }
        return true;
    }
}
