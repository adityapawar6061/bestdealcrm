<?php
/**
 * Base Controller
 */
// Load Session using correct path: app/Controllers -> app/Helpers
$sessionFile = dirname(__DIR__) . '/Helpers/Session.php';
if (file_exists($sessionFile) && !class_exists('Session') && !function_exists('isAuthenticated')) {
    require_once $sessionFile;
}

class BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    protected function view($viewPath, $data = [], $layout = 'layouts/main')
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

    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect($url, $flashType = '', $flashMessage = '')
    {
        if ($flashType && $flashMessage && function_exists('setFlash')) {
            setFlash($flashType, $flashMessage);
        }
        header("Location: {$url}");
        exit;
    }

    protected function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function input($key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function validate($data, $rules)
    {
        $errors = [];
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $rulesArray = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            foreach ($rulesArray as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $paramStr) = explode(':', $rule, 2);
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
                }
            }
        }
        return $errors;
    }

    protected function sanitize($data)
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[$key] = is_string($value) ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : $value;
        }
        return $sanitized;
    }

    protected function paginate($table, $where = '1=1', $params = [], $perPage = 25, $currentPage = 1)
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
