<?php
namespace Middleware;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!isAuthenticated()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthenticated', 'redirect' => '/login']);
                exit;
            }
            header('Location: /login');
            exit;
        }
        return true;
    }
}
