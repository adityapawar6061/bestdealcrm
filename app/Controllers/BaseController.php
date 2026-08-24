<?php
/**
 * Base Controller
 * Common functionality for all controllers
 */

require_once ROOT_PATH . '/app/Helpers/Session.php';

class BaseController
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Render a view with layout
     */
    protected function view(string $viewPath, array $data = [], string $layout = 'layouts/main'): void
    {
        extract($data);
        ob_start();
        
        $fullPath = VIEWS_PATH . '/' . $viewPath . '.php';
        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            echo "<p>View not found: {$viewPath}</p>";
        }
        
        $content = ob_get_clean();
        
        require VIEWS_PATH . '/' . $layout . '.php';
    }

    /**
     * Render a partial view (no layout)
     */
    protected function partial(string $viewPath, array $data = []): void
    {
        extract($data);
        $fullPath = VIEWS_PATH . '/' . $viewPath . '.php';
        if (file_exists($fullPath)) {
            require $fullPath;
        }
    }

    /**
     * Send JSON response
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url, string $flashType = '', string $flashMessage = ''): void
    {
        if ($flashType && $flashMessage) {
            setFlash($flashType, $flashMessage);
        }
        header("Location: {$url}");
        exit;
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get POST data with optional trimming
     */
    protected function input(string $key, $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Get multiple POST data
     */
    protected function inputs(array $keys): array
    {
        $data = [];
        foreach ($keys as $key => $default) {
            if (is_int($key)) {
                $data[$default] = $this->input($default);
            } else {
                $data[$key] = $this->input($key, $default);
            }
        }
        return $data;
    }

    /**
     * Validate required fields
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $rulesArray = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            
            foreach ($rulesArray as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                    $rule = $ruleName;
                }
                
                switch ($rule) {
                    case 'required':
                        if (empty($value) && $value !== '0' && $value !== 0) {
                            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                        }
                        break;
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = 'Invalid email address.';
                        }
                        break;
                    case 'min':
                        if (!empty($value) && strlen($value) < (int)$params[0]) {
                            $errors[$field] = "Minimum {$params[0]} characters required.";
                        }
                        break;
                    case 'max':
                        if (!empty($value) && strlen($value) > (int)$params[0]) {
                            $errors[$field] = "Maximum {$params[0]} characters allowed.";
                        }
                        break;
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field] = 'Must be a number.';
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }

    /**
     * Sanitize input data
     */
    protected function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[$key] = is_string($value) ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : $value;
        }
        return $sanitized;
    }

    /**
     * Pagination helper
     */
    protected function paginate(string $table, string $where = '1=1', array $params = [], int $perPage = 25, int $currentPage = 1): array
    {
        $total = $this->db->count($table, $where, $params);
        $totalPages = max(1, ceil($total / $perPage));
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;
        
        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->db->fetchAll($sql, $params);
        
        return [
            'data'         => $data,
            'total'        => $total,
            'current_page' => $currentPage,
            'total_pages'  => $totalPages,
            'per_page'     => $perPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }
}
