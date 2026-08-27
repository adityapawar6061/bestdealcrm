<?php
namespace Controllers;

class AdminController extends BaseController
{
    private \Models\User $userModel;
    private \Models\Lead $leadModel;
    private \Models\Workflow $workflowModel;
    private \Models\DynamicForm $formModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new \Models\User();
        $this->leadModel = new \Models\Lead();
        $this->workflowModel = new \Models\Workflow();
        $this->formModel = new \Models\DynamicForm();
    }

    // ========== DASHBOARD ==========

    public function dashboard(): void
    {
        $user = currentUser();

        // Get lead stats by stage
        $stats = [
            'total_leads'       => $this->db->count('leads'),
            'unassigned'        => $this->db->count('leads', 'assigned_to IS NULL'),
            'assigned'          => $this->db->count('leads', 'assigned_to IS NOT NULL'),
            'agent_draft'       => $this->db->count('leads', 'workflow_stage = ?', ['AGENT_DRAFT']),
            'pending_review_1'  => $this->db->count('leads', 'workflow_stage = ?', ['ADMIN_REVIEW_1']),
            'login_pending'     => $this->db->count('leads', 'workflow_stage = ?', ['LOGIN_AGENT_ASSIGNED']),
            'login_draft'       => $this->db->count('leads', 'workflow_stage = ?', ['LOGIN_AGENT_DRAFT']),
            'pending_review_2'  => $this->db->count('leads', 'workflow_stage = ?', ['ADMIN_REVIEW_2']),
            'approved'          => $this->db->count('leads', 'workflow_stage = ?', ['LOGIN_APPROVED']),
            'rejected'          => $this->db->count('leads', 'workflow_stage = ?', ['REJECTED']),
            'underwriting'      => $this->db->count('leads', 'workflow_stage = ?', ['UNDERWRITING']),
            'dispatch'          => $this->db->count('leads', 'workflow_stage = ?', ['DISPATCH']),
            'completed'         => $this->db->count('leads', 'workflow_stage = ?', ['COMPLETED']),
            'total_users'       => $this->db->count('users', "status = 'active'"),
            'total_agents'      => $this->db->count('users', "role_id = (SELECT id FROM roles WHERE name = 'agent') AND status = 'active'"),
        ];

        // Recent activity
        $recentLeads = $this->db->fetchAll(
            "SELECT l.*, u.name as assigned_to_name 
             FROM leads l 
             LEFT JOIN users u ON l.assigned_to = u.id 
             ORDER BY l.created_at DESC LIMIT 10"
        );

        $recentActivity = $this->db->fetchAll(
            "SELECT al.*, u.name as user_name 
             FROM activity_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             ORDER BY al.created_at DESC LIMIT 15"
        );

        $this->view('admin/dashboard', [
            'title'          => 'Admin Dashboard',
            'stats'          => $stats,
            'recentLeads'    => $recentLeads,
            'recentActivity' => $recentActivity,
        ]);
    }

    // ========== USER MANAGEMENT ==========

    public function users(): void
    {
        $filters = [
            'search'          => $_GET['search'] ?? '',
            'role_id'         => $_GET['role_id'] ?? '',
            'status'          => $_GET['status'] ?? '',
            'team_leader_id'  => $_GET['team_leader_id'] ?? '',
        ];
        $page = (int)($_GET['page'] ?? 1);

        $users = $this->userModel->getAll($filters, $page);
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY name");
        $teamLeaders = $this->userModel->getTeamLeaders();

        $this->view('admin/users', [
            'title'       => 'Manage Users',
            'users'       => $users,
            'roles'       => $roles,
            'teamLeaders' => $teamLeaders,
            'filters'     => $filters,
        ]);
    }

    public function createUser(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request method.'], 405);
                return;
            }

            $data = $this->sanitize($_POST);

            // Filter to only valid user fields
            $allowedFields = ['name', 'email', 'username', 'password', 'mobile', 'role_id', 'team_leader_id'];
            $filtered = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $filtered[$field] = $data[$field];
                }
            }

            $errors = $this->validate($filtered, [
                'name'     => 'required|max:255',
                'email'    => 'required|email',
                'username' => 'required|min:4|max:50',
                'password' => 'required|min:6',
                'role_id'  => 'required',
            ]);

            if (!empty($errors)) {
                $this->json(['errors' => $errors], 422);
                return;
            }

            // Check unique username/email
            $existing = $this->db->fetchOne(
                "SELECT id FROM users WHERE username = ? OR email = ?",
                [$filtered['username'], $filtered['email']]
            );
            if ($existing) {
                $this->json(['errors' => ['username' => 'Username or email already exists.']], 422);
                return;
            }

            // Clean team_leader_id
            if (empty($filtered['team_leader_id'])) {
                unset($filtered['team_leader_id']);
            }

            $id = $this->userModel->create($filtered);
            logActivity(currentUser()['id'], 'user_created', 'user', (int)$id, null, $filtered['name']);

            $this->json(['success' => true, 'message' => 'User created successfully.']);
        } catch (\Exception $e) {
            error_log('createUser error: ' . $e->getMessage());
            $this->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function updateUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['user_id'] ?? 0);
            if (!$id) {
                $this->json(['error' => 'Invalid user ID.'], 400);
                return;
            }

            $data = $this->sanitize($_POST);
            $errors = $this->validate($data, [
                'name'     => 'required',
                'email'    => 'required|email',
                'role_id'  => 'required',
            ]);

            if (!empty($errors)) {
                $this->json(['errors' => $errors], 422);
                return;
            }

            $updateData = [
                'name'           => $data['name'],
                'email'          => $data['email'],
                'mobile'         => $data['mobile'] ?? null,
                'role_id'        => $data['role_id'],
                'team_leader_id' => $data['team_leader_id'] ?: null,
            ];

            $this->userModel->update($id, $updateData);
            logActivity(currentUser()['id'], 'user_updated', 'user', $id);

            $this->json(['success' => true, 'message' => 'User updated successfully.']);
        }
    }

    public function toggleUserStatus(): void
    {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id) {
            $this->userModel->toggleStatus($id);
            logActivity(currentUser()['id'], 'user_status_changed', 'user', $id);
            $this->json(['success' => true, 'message' => 'Status updated.']);
        }
    }

    public function resetUserPassword(): void
    {
        $id = (int)($_POST['user_id'] ?? 0);
        $password = $_POST['new_password'] ?? '';
        
        if ($id && strlen($password) >= 6) {
            $this->userModel->resetPassword($id, $password);
            logActivity(currentUser()['id'], 'password_reset', 'user', $id);
            $this->json(['success' => true, 'message' => 'Password reset successfully.']);
        } else {
            $this->json(['error' => 'Invalid data.'], 400);
        }
    }

    public function userProfile(int $id): void
    {
        $user = $this->userModel->findById($id);
        $loginHistory = $this->userModel->getLoginHistory($id);

        $this->view('admin/user_profile', [
            'title'         => 'User Profile',
            'profileUser'   => $user,
            'loginHistory'  => $loginHistory,
        ]);
    }

    // ========== LEAD MANAGEMENT ==========

    public function leads(): void
    {
        $filters = [
            'search'         => $_GET['search'] ?? '',
            'workflow_stage' => $_GET['workflow_stage'] ?? '',
            'assigned_to'    => $_GET['assigned_to'] ?? '',
            'bank_name'      => $_GET['bank_name'] ?? '',
        ];
        $page = (int)($_GET['page'] ?? 1);

        $leads = $this->leadModel->getAll($filters, $page);
        $agents = $this->userModel->getAgents();

        $this->view('admin/leads', [
            'title'  => 'All Leads',
            'leads'  => $leads,
            'agents' => $agents,
            'filters'=> $filters,
        ]);
    }

    public function leadDetail(int $id): void
    {
        $lead = $this->leadModel->findById($id);
        if (!$lead) {
            $this->redirect('/admin/leads', 'error', 'Lead not found.');
            return;
        }

        $timeline = $this->leadModel->getTimeline($id);
        $assignments = $this->leadModel->getAssignmentHistory($id);
        $submissions = $this->formModel->getSubmissionsForLead($id);
        $documents = $this->db->fetchAll(
            "SELECT * FROM documents WHERE lead_id = ? ORDER BY created_at DESC",
            [$id]
        );

        $this->view('admin/lead_detail', [
            'title'       => 'Lead #' . $id,
            'lead'        => $lead,
            'timeline'    => $timeline,
            'assignments' => $assignments,
            'submissions' => $submissions,
            'documents'   => $documents,
        ]);
    }

    // ========== LEAD UPLOAD ==========

    public function uploadLeads(): void
    {
        $this->view('admin/upload_leads', [
            'title' => 'Upload Leads',
        ]);
    }

    public function processUpload(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request.'], 405);
                return;
            }

            if (!isset($_FILES['lead_file'])) {
                $this->json(['error' => 'No file uploaded.'], 400);
                return;
            }

            $file = $_FILES['lead_file'];

            // Check upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                    UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
                    UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Server failed to write file.',
                ];
                $msg = $errorMessages[$file['error']] ?? 'Unknown upload error (' . $file['error'] . ').';
                $this->json(['error' => $msg], 400);
                return;
            }

            // Check file size (10MB max)
            if ($file['size'] > 10 * 1024 * 1024) {
                $this->json(['error' => 'File too large. Maximum 10MB allowed.'], 400);
                return;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv'])) {
                $this->json(['error' => 'Only CSV files are allowed.'], 400);
                return;
            }

            // Ensure lead_uploads table exists
            $this->db->query("CREATE TABLE IF NOT EXISTS `lead_uploads` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `filename` VARCHAR(255),
                `uploaded_by` INT UNSIGNED,
                `status` ENUM('processing', 'completed', 'failed', 'empty') DEFAULT 'processing',
                `total_rows` INT DEFAULT 0,
                `imported` INT DEFAULT 0,
                `skipped` INT DEFAULT 0,
                `error_log` TEXT,
                `completed_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Save upload record
            $userId = currentUser()['id'] ?? 1;
            $uploadId = $this->db->insert('lead_uploads', [
                'filename'     => $file['name'],
                'uploaded_by'  => $userId,
                'status'       => 'processing',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            // Read CSV file
            $filePath = $file['tmp_name'];
            $rows = [];

            if (($handle = fopen($filePath, 'r')) !== false) {
                // Handle BOM
                $bom = fread($handle, 3);
                if ($bom !== "\xef\xbb\xbf") {
                    rewind($handle);
                }
                $headers = fgetcsv($handle);
                if ($headers === false || empty($headers)) {
                    fclose($handle);
                    $this->json(['error' => 'Could not read CSV headers.'], 400);
                    return;
                }
                // Clean BOM from first header
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) === count($headers)) {
                        $rows[] = array_combine($headers, $row);
                    }
                }
                fclose($handle);
            }

            if (empty($rows)) {
                $this->db->update('lead_uploads', ['status' => 'empty'], 'id = ?', [$uploadId]);
                $this->json(['error' => 'No data rows found in CSV. Check that the file has data rows with matching column counts.'], 400);
                return;
            }

            // Return columns for mapping
            $columns = array_keys($rows[0]);
            $_SESSION['upload_rows'] = $rows;
            $_SESSION['upload_id'] = $uploadId;

            // Also save uploaded file to disk as backup (for session recovery)
            $uploadDir = ROOT_PATH . '/public/uploads/leads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            if (is_dir($uploadDir)) {
                @copy($file['tmp_name'], $uploadDir . '/upload_' . $uploadId . '.csv');
            }

            $this->json([
                'success' => true,
                'columns' => $columns,
                'sample'  => array_slice($rows, 0, 5),
                'total'   => count($rows),
                'upload_id' => $uploadId,
            ]);

        } catch (\Throwable $e) {
            error_log('processUpload ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    public function processMapping(): void
    {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method.'], 405);
            return;
        }

        $mapping = $_POST['mapping'] ?? [];
        $uploadId = $_SESSION['upload_id'] ?? null;
        $rows = $_SESSION['upload_rows'] ?? [];

        error_log('processMapping: upload_id=' . var_export($uploadId, true) . ' rows=' . count($rows) . ' mapping=' . count($mapping));

        // Try to recover from disk if session data is lost
        if (empty($rows) && $uploadId) {
            $diskFile = ROOT_PATH . '/public/uploads/leads/upload_' . $uploadId . '.csv';
            if (file_exists($diskFile)) {
                if (($handle = fopen($diskFile, 'r')) !== false) {
                    $headers = fgetcsv($handle);
                    while (($row = fgetcsv($handle)) !== false) {
                        if (count($row) === count($headers)) {
                            $rows[] = array_combine($headers, $row);
                        }
                    }
                    fclose($handle);
                    $_SESSION['upload_rows'] = $rows;
                    error_log('processMapping: recovered ' . count($rows) . ' rows from disk');
                }
            }
        }

        if (empty($rows)) {
            $this->json(['error' => 'Upload session expired. Please upload the file again.'], 400);
            return;
        }
        if (empty($mapping)) {
            $this->json(['error' => 'No column mappings provided. Map at least one column.'], 400);
            return;
        }

        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        $this->db->beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $leadData = [];
                foreach ($mapping as $csvColumn => $dbField) {
                    if (!empty($dbField) && isset($row[$csvColumn])) {
                        $leadData[$dbField] = $row[$csvColumn];
                    }
                }

                // Check duplicate mobile
                if (!empty($leadData['mobile_number'])) {
                    $existing = $this->leadModel->checkDuplicateMobile($leadData['mobile_number']);
                    if ($existing) {
                        $skippedCount++;
                        $errors[] = "Row " . ($index + 2) . ": Duplicate mobile ({$leadData['mobile_number']})";
                        continue;
                    }
                }

                $this->leadModel->create($leadData);
                $importedCount++;
            }

            $this->db->commit();

            // Update upload record
            if ($uploadId) {
                $this->db->update('lead_uploads', [
                    'status'       => 'completed',
                    'total_rows'   => count($rows),
                    'imported'     => $importedCount,
                    'skipped'      => $skippedCount,
                    'completed_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$uploadId]);
            }

            unset($_SESSION['upload_rows'], $_SESSION['upload_id']);

            logActivity(currentUser()['id'], 'leads_uploaded', 'lead', null, null, 
                json_encode(['imported' => $importedCount, 'skipped' => $skippedCount]));

            $this->json([
                'success'    => true,
                'imported'   => $importedCount,
                'skipped'    => $skippedCount,
                'errors'     => array_slice($errors, 0, 20),
                'message'    => "{$importedCount} leads imported. {$skippedCount} skipped.",
            ]);

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Upload processing error: " . $e->getMessage() . " in " . $e->getFile() . ':' . $e->getLine());
            $this->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
        }
    } catch (\Throwable $e) {
        error_log('processMapping FATAL: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        $this->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
    }
    }

    // ========== LEAD ASSIGNMENT ==========

    public function assignLeads(): void
    {
        // Get all active users (agents + login agents + team leaders)
        $agents = $this->db->fetchAll(
            "SELECT u.id, u.name, r.display_name as role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.status = 'active' AND r.name IN ('agent', 'login_agent', 'team_leader') 
             ORDER BY r.display_name, u.name"
        );
        $this->view('admin/assign_leads', [
            'title'  => 'Assign Leads',
            'agents' => $agents,
        ]);
    }

    public function assignData(): void
    {
    try {
        // Return filter options or filtered lead data
        if (isset($_GET['get_filters'])) {
            $locations = $this->db->fetchAll("SELECT DISTINCT location FROM leads WHERE location IS NOT NULL AND location != '' ORDER BY location");
            $states = $this->db->fetchAll("SELECT DISTINCT state FROM leads WHERE state IS NOT NULL AND state != '' ORDER BY state");
            $dataTypes = $this->db->fetchAll("SELECT DISTINCT data_type FROM leads WHERE data_type IS NOT NULL AND data_type != '' ORDER BY data_type");
            $bankNames = $this->db->fetchAll("SELECT DISTINCT bank_name FROM leads WHERE bank_name IS NOT NULL AND bank_name != '' ORDER BY bank_name");
            $responseDates = $this->db->fetchAll("SELECT DISTINCT response_date FROM leads WHERE response_date IS NOT NULL AND response_date != '' AND response_date != '0000-00-00' ORDER BY response_date");

            $this->json([
                'success' => true,
                'locations' => array_column($locations, 'location'),
                'states' => array_column($states, 'state'),
                'response_dates' => array_column($responseDates, 'response_date'),
                'data_types' => array_column($dataTypes, 'data_type'),
                'bank_names' => array_column($bankNames, 'bank_name'),
            ]);
            return;
        }

        // Filtered leads data - only UNASSIGNED leads
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(500, max(1, (int)($_GET['per_page'] ?? 50)));
        $where = 'l.assigned_to IS NULL';
        $params = [];

        if (!empty($_GET['location'])) {
            $where .= ' AND l.location = ?';
            $params[] = $_GET['location'];
        }
        if (!empty($_GET['state'])) {
            $where .= ' AND l.state = ?';
            $params[] = $_GET['state'];
        }
        if (!empty($_GET['response_date'])) {
            $where .= ' AND l.response_date = ?';
            $params[] = $_GET['response_date'];
        }
        if (!empty($_GET['data_type'])) {
            $where .= ' AND l.data_type = ?';
            $params[] = $_GET['data_type'];
        }
        if (!empty($_GET['bank_name'])) {
            $where .= ' AND l.bank_name = ?';
            $params[] = $_GET['bank_name'];
        }
        if (!empty($_GET['search'])) {
            $s = '%' . $_GET['search'] . '%';
            $where .= ' AND (l.customer_name LIKE ? OR l.mobile_number LIKE ? OR l.id = ?)';
            $params[] = $s;
            $params[] = $s;
            $params[] = (int)$_GET['search'];
        }

        $total = $this->db->count('leads l', $where, $params);
        $totalPages = max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT l.id, l.customer_name, l.mobile_number, l.location, l.state,
                       l.existing_la, l.salary, l.actual_salary, l.dtmf_input,
                       l.response_date, l.data_type, l.bank_name, l.current_status,
                       l.update_status, l.remark, l.workflow_stage, l.created_at
                FROM leads l
                WHERE {$where}
                ORDER BY l.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $data = $this->db->fetchAll($sql, $params);

        $this->json([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'total_pages' => $totalPages,
            'page' => $page,
        ]);
    } catch (\Throwable $e) {
        error_log('assignData ERROR: ' . $e->getMessage());
        $this->json(['success' => true, 'data' => [], 'total' => 0, 'total_pages' => 1, 'page' => 1]);
    }
    }

    public function processAssignment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadIds = $_POST['lead_ids'] ?? [];
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);

        if (empty($leadIds) || !$assignedTo) {
            $this->json(['error' => 'Please select leads and a user to assign to.'], 400);
            return;
        }

        // Verify target user exists and is active
        $targetUser = $this->db->fetchOne("SELECT id, name FROM users WHERE id = ? AND status = 'active'", [$assignedTo]);
        if (!$targetUser) {
            $this->json(['error' => 'Selected user not found or inactive.'], 400);
            return;
        }

        $count = 0;
        $errors = [];
        $this->db->beginTransaction();
        try {
            foreach ($leadIds as $leadId) {
                $leadId = (int)$leadId;
                if ($leadId <= 0) continue;
                try {
                    $this->leadModel->assign($leadId, $assignedTo);
                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "Lead #{$leadId}: " . $e->getMessage();
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->json(['error' => 'Assignment failed: ' . $e->getMessage()], 500);
            return;
        }

        logActivity(currentUser()['id'], 'leads_assigned', 'lead', null, null, 
            json_encode(['count' => $count, 'assigned_to' => $assignedTo, 'target' => $targetUser['name']]));

        $msg = "{$count} leads assigned to {$targetUser['name']}.";
        if (!empty($errors)) {
            $msg .= ' ' . count($errors) . ' leads had errors.';
        }
        $this->json(['success' => true, 'message' => $msg, 'errors' => $errors]);
    }

    // ========== ADMIN REVIEW 1 ==========

    public function review1(): void
    {
        $leads = $this->workflowModel->getPendingApprovals('ADMIN_REVIEW_1');

        $this->view('admin/review1', [
            'title'  => 'Admin Review (Stage 1)',
            'leads'  => $leads,
        ]);
    }

    public function review1Detail(int $id): void
    {
        $lead = $this->leadModel->findById($id);
        $submissions = $this->formModel->getSubmissionsForLead($id);
        $timeline = $this->leadModel->getTimeline($id);
        $loginAgents = $this->userModel->getLoginAgents();

        $this->view('admin/review1_detail', [
            'title'       => 'Review Lead #' . $id,
            'lead'        => $lead,
            'submissions' => $submissions,
            'timeline'    => $timeline,
            'loginAgents' => $loginAgents,
        ]);
    }

    public function processReview1(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $remark = $_POST['admin_approval1_remark'] ?? '';
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);

        $lead = $this->leadModel->findById($leadId);
        if (!$lead) {
            $this->json(['error' => 'Lead not found.'], 404);
            return;
        }

        $user = currentUser();
        $newStage = null;
        switch ($action) {
            case 'approve': $newStage = 'LOGIN_AGENT_ASSIGNED'; break;
            case 'reject': $newStage = 'REJECTED'; break;
            case 'reassign': $newStage = 'LEAD_ASSIGNED'; break;
        };

        if (!$newStage) {
            $this->json(['error' => 'Invalid action.'], 400);
            return;
        }

        // Store remark
        $this->db->insert('remarks', [
            'lead_id'    => $leadId,
            'user_id'    => $user['id'],
            'stage'      => 'ADMIN_REVIEW_1',
            'remark'     => $remark,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Transition workflow
        $this->workflowModel->transition(
            $leadId,
            'ADMIN_REVIEW_1',
            $newStage,
            $user['id'],
            $user['role_name'],
            $remark,
            $action
        );

        // Assign to login agent if approved
        if ($action === 'approve' && $assignedTo) {
            $this->leadModel->assign($leadId, $assignedTo, $user['id'], $remark);
            $this->db->update('leads', [
                'workflow_stage' => $newStage,
                'updated_at'     => date('Y-m-d H:i:s'),
            ], 'id = ?', [$leadId]);
        }

        $this->json(['success' => true, 'message' => "Lead review completed. Status: " . humanStatus($newStage)]);
    }

    // ========== ADMIN REVIEW 2 ==========

    public function review2(): void
    {
        $leads = $this->workflowModel->getPendingApprovals('ADMIN_REVIEW_2');

        $this->view('admin/review2', [
            'title'  => 'Admin Review (Stage 2)',
            'leads'  => $leads,
        ]);
    }

    public function review2Detail(int $id): void
    {
        $lead = $this->leadModel->findById($id);
        $submissions = $this->formModel->getSubmissionsForLead($id);
        $timeline = $this->leadModel->getTimeline($id);

        $this->view('admin/review2_detail', [
            'title'       => 'Review Lead #' . $id,
            'lead'        => $lead,
            'submissions' => $submissions,
            'timeline'    => $timeline,
        ]);
    }

    public function processReview2(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $remark = $_POST['admin_approval2_remark'] ?? '';

        $user = currentUser();
        $newStage = null;
        switch ($action) {
            case 'approve': $newStage = 'LOGIN_APPROVED'; break;
            case 'reject': $newStage = 'REJECTED'; break;
            case 'send_back': $newStage = 'RETURNED_TO_AGENT'; break;
        };

        if (!$newStage) {
            $this->json(['error' => 'Invalid action.'], 400);
            return;
        }

        $this->db->insert('remarks', [
            'lead_id'    => $leadId,
            'user_id'    => $user['id'],
            'stage'      => 'ADMIN_REVIEW_2',
            'remark'     => $remark,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->workflowModel->transition(
            $leadId,
            'ADMIN_REVIEW_2',
            $newStage,
            $user['id'],
            $user['role_name'],
            $remark,
            $action
        );

        $this->json(['success' => true, 'message' => "Review 2 completed. Status: " . humanStatus($newStage)]);
    }

    // ========== ROLE & PERMISSION MANAGEMENT ==========

    public function roles(): void
    {
        $roleModel = new \Models\Role();
        $roles = $roleModel->getAll();
        $permissions = $roleModel->getAllPermissions();

        $this->view('admin/roles', [
            'title'       => 'Roles & Permissions',
            'roles'       => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function rolePermissions(int $id): void
    {
        $roleModel = new \Models\Role();
        $role = $roleModel->findById($id);
        $allPermissions = $roleModel->getAllPermissions();
        $currentPermissions = $roleModel->getPermissions($id);
        $currentPermIds = array_column($currentPermissions, 'id');

        $this->view('admin/role_permissions', [
            'title'             => 'Manage Permissions: ' . $role['name'],
            'role'              => $role,
            'allPermissions'    => $allPermissions,
            'currentPermIds'    => $currentPermIds,
        ]);
    }

    public function savePermissions(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $roleId = (int)($_POST['role_id'] ?? 0);
        $permissionIds = $_POST['permissions'] ?? [];

        $roleModel = new \Models\Role();
        $roleModel->setPermissions($roleId, $permissionIds);

        logActivity(currentUser()['id'], 'permissions_updated', 'role', $roleId);

        $this->json(['success' => true, 'message' => 'Permissions updated.']);
    }

    // ========== WORKFLOW MANAGEMENT ==========

    public function workflowStages(): void
    {
        $stages = $this->workflowModel->getAllStages();
        $this->view('admin/workflow_stages', [
            'title'  => 'Workflow Stages',
            'stages' => $stages,
        ]);
    }

    // ========== NOTIFICATIONS ==========

    public function notifications(): void
    {
        $user = currentUser();
        $notifications = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
            [$user['id']]
        );

        $this->view('admin/notifications', [
            'title'         => 'Notifications',
            'notifications' => $notifications,
        ]);
    }

    public function readNotification(): void
    {
        $id = (int)($_POST['notification_id'] ?? 0);
        if ($id) {
            $this->db->update('notifications', ['is_read' => 1], 'id = ?', [$id]);
            $this->json(['success' => true]);
        }
    }

    // ========== ACTIVITY LOGS ==========

    public function activityLogs(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $logs = $this->db->fetchAll(
            "SELECT al.*, u.name as user_name 
             FROM activity_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             ORDER BY al.created_at DESC 
             LIMIT 50 OFFSET " . (($page - 1) * 50)
        );

        $this->view('admin/activity_logs', [
            'title' => 'Activity Logs',
            'logs'  => $logs,
        ]);
    }

    // ========== AJAX LEAD DATA (Server-side) ==========

    public function leadsAjax(): void
    {
        $draw = (int)($_GET['draw'] ?? 1);
        $start = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 25);
        $search = $_GET['search']['value'] ?? '';
        $stage = $_GET['workflow_stage'] ?? '';
        $agent = $_GET['assigned_to'] ?? '';
        $bank = $_GET['bank_name'] ?? '';

        $where = '1=1';
        $params = [];

        if ($search) {
            $where .= ' AND (l.id = ? OR l.customer_name LIKE ? OR l.mobile_number LIKE ? OR l.pan_number LIKE ? OR l.bank_name LIKE ?)';
            $params[] = $search;
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($stage) {
            $where .= ' AND l.workflow_stage = ?';
            $params[] = $stage;
        }
        if ($agent) {
            $where .= ' AND l.assigned_to = ?';
            $params[] = $agent;
        }
        if ($bank) {
            $where .= ' AND l.bank_name = ?';
            $params[] = $bank;
        }

        $total = $this->db->count('leads l', $where, $params);
        $sql = "SELECT l.id, l.customer_name, l.mobile_number, l.location, l.state,
                       l.existing_la, l.salary, l.actual_salary, l.bank_name,
                       l.current_status, l.workflow_stage, l.created_at,
                       l.assigned_to, u.name as assigned_to_name
                FROM leads l
                LEFT JOIN users u ON l.assigned_to = u.id
                WHERE {$where}
                ORDER BY l.created_at DESC
                LIMIT {$length} OFFSET {$start}";
        $data = $this->db->fetchAll($sql, $params);

        $this->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    // ========== DOCUMENT UPLOAD/DOWNLOAD ==========

    public function uploadDocument(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        if (!$leadId) {
            $this->json(['error' => 'Lead ID required.'], 400);
            return;
        }

        if (!isset($_FILES['document'])) {
            $this->json(['error' => 'No file uploaded.'], 400);
            return;
        }

        $file = $_FILES['document'];
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            $this->json(['error' => 'File type not allowed.'], 400);
            return;
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            $this->json(['error' => 'File too large (max 10MB).'], 400);
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $uploadDir = ROOT_PATH . '/public/uploads/documents/' . $leadId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $uploadDir . $safeFilename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $docId = $this->db->insert('documents', [
                'lead_id'        => $leadId,
                'uploaded_by'    => currentUser()['id'],
                'filename'       => $safeFilename,
                'original_name'  => $file['name'],
                'mime_type'      => $mimeType,
                'file_size'      => $file['size'],
                'document_type'  => $_POST['document_type'] ?? 'general',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);

            logActivity(currentUser()['id'], 'document_uploaded', 'document', (int)$docId, null, $file['name']);

            $this->json(['success' => true, 'message' => 'Document uploaded.', 'id' => $docId]);
        } else {
            $this->json(['error' => 'Failed to save file.'], 500);
        }
    }

    public function downloadDocument(int $id): void
    {
        $doc = $this->db->fetchOne(
            "SELECT d.*, l.id as lead_num FROM documents d JOIN leads l ON d.lead_id = l.id WHERE d.id = ?",
            [$id]
        );

        if (!$doc) {
            $this->redirect('/admin/leads', 'error', 'Document not found.');
            return;
        }

        $filePath = ROOT_PATH . '/public/uploads/documents/' . $doc['lead_id'] . '/' . $doc['filename'];
        if (!file_exists($filePath)) {
            $this->redirect('/admin/leads/' . $doc['lead_id'], 'error', 'File not found on disk.');
            return;
        }

        logActivity(currentUser()['id'], 'document_downloaded', 'document', $id);

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . $doc['original_name'] . '"');
        header('Content-Length: ' . $doc['file_size']);
        readfile($filePath);
        exit;
    }

    // ========== ADMIN REVIEW 3 (Post-Login → Underwriting) ==========

    public function review3(): void
    {
        $search = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;

        $where = "l.workflow_stage IN ('POST_LOGIN', 'UNDERWRITING')";
        $params = [];

        if ($search) {
            $s = '%' . $search . '%';
            $where .= ' AND (l.id = ? OR l.customer_name LIKE ? OR l.mobile_number LIKE ?)';
            $params[] = $search;
            $params[] = $s;
            $params[] = $s;
        }

        $total = $this->db->count('leads l', $where, $params);
        $totalPages = max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $leads = $this->db->fetchAll(
            "SELECT l.id, l.customer_name, l.mobile_number, l.bank_name, l.workflow_stage, l.updated_at,
                    l.assigned_to, u.name as assigned_to_name
             FROM leads l
             LEFT JOIN users u ON l.assigned_to = u.id
             WHERE {$where}
             ORDER BY l.updated_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('admin/review3', [
            'title'       => 'Review 3 - Post Login Decision',
            'leads'       => $leads,
            'total'       => $total,
            'totalPages'  => $totalPages,
            'page'        => $page,
            'search'      => $search,
        ]);
    }

    public function review3Detail(int $id): void
    {
        $lead = $this->leadModel->findById($id);
        if (!$lead) {
            $this->redirect('/admin/review3', 'error', 'Lead not found.');
            return;
        }
        $timeline = $this->leadModel->getTimeline($id);
        $submissions = $this->formModel->getSubmissionsForLead($id);
        $documents = $this->db->fetchAll(
            "SELECT * FROM documents WHERE lead_id = ? ORDER BY created_at DESC",
            [$id]
        );
        $remarks = $this->db->fetchAll(
            "SELECT r.*, u.name as user_name FROM remarks r LEFT JOIN users u ON r.user_id = u.id WHERE r.lead_id = ? ORDER BY r.created_at DESC",
            [$id]
        );
        // Get underwriting agents only
        $underwritingAgents = $this->db->fetchAll(
            "SELECT u.id, u.name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'underwriting' AND u.status = 'active' ORDER BY u.name"
        );

        $this->view('admin/review3_detail', [
            'title'               => 'Review 3 - Lead #' . $id,
            'lead'                => $lead,
            'timeline'            => $timeline,
            'submissions'         => $submissions,
            'documents'           => $documents,
            'remarks'             => $remarks,
            'underwritingAgents'  => $underwritingAgents,
        ]);
    }

    public function processReview3(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $remark = trim($_POST['admin_approval3_remark'] ?? '');
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);
        $user = currentUser();

        if (!$leadId || !in_array($action, ['approve_to_underwriting', 'reject'])) {
            $this->json(['error' => 'Invalid data.'], 400);
            return;
        }

        $lead = $this->leadModel->findById($leadId);
        if (!$lead) {
            $this->json(['error' => 'Lead not found.'], 404);
            return;
        }

        $currentStage = $lead['workflow_stage'];
        $newStage = ($action === 'approve_to_underwriting') ? 'UNDERWRITING' : 'REJECTED';

        // Store remark
        $this->db->insert('remarks', [
            'lead_id'    => $leadId,
            'user_id'    => $user['id'],
            'stage'      => 'ADMIN_REVIEW_3',
            'remark'     => $remark,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Transition workflow
        $this->workflowModel->transition($leadId, $currentStage, $newStage, $user['id'], $user['role_name'], $remark, 'admin_review_3');

        // Update lead — assign agent and stage
        $updateData = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($action === 'approve_to_underwriting' && $assignedTo) {
            $this->leadModel->assign($leadId, $assignedTo, $user['id'], $remark, true);
            $updateData['workflow_stage'] = $newStage;
        }
        $this->db->update('leads', $updateData, 'id = ?', [$leadId]);

        logActivity($user['id'], 'review3_' . $action, 'lead', $leadId, $currentStage, $newStage, $remark);

        $this->json(['success' => true, 'message' => 'Decision saved. Status: ' . humanStatus($newStage)]);
    }

    // ========== ADMIN REVIEW 4 (Underwriting → Dispatch) ==========

    public function review4(): void
    {
        $search = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;

        $where = "l.workflow_stage IN ('UNDERWRITING_APPROVED', 'DISPATCH')";
        $params = [];

        if ($search) {
            $s = '%' . $search . '%';
            $where .= ' AND (l.id = ? OR l.customer_name LIKE ? OR l.mobile_number LIKE ?)';
            $params[] = $search;
            $params[] = $s;
            $params[] = $s;
        }

        $total = $this->db->count('leads l', $where, $params);
        $totalPages = max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $leads = $this->db->fetchAll(
            "SELECT l.id, l.customer_name, l.mobile_number, l.bank_name, l.workflow_stage, l.updated_at,
                    l.assigned_to, u.name as assigned_to_name
             FROM leads l
             LEFT JOIN users u ON l.assigned_to = u.id
             WHERE {$where}
             ORDER BY l.updated_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('admin/review4', [
            'title'       => 'Review 4 - Dispatch Decision',
            'leads'       => $leads,
            'total'       => $total,
            'totalPages'  => $totalPages,
            'page'        => $page,
            'search'      => $search,
        ]);
    }

    public function review4Detail(int $id): void
    {
        $lead = $this->leadModel->findById($id);
        if (!$lead) {
            $this->redirect('/admin/review4', 'error', 'Lead not found.');
            return;
        }
        $timeline = $this->leadModel->getTimeline($id);
        $submissions = $this->formModel->getSubmissionsForLead($id);
        $documents = $this->db->fetchAll(
            "SELECT * FROM documents WHERE lead_id = ? ORDER BY created_at DESC",
            [$id]
        );
        $remarks = $this->db->fetchAll(
            "SELECT r.*, u.name as user_name FROM remarks r LEFT JOIN users u ON r.user_id = u.id WHERE r.lead_id = ? ORDER BY r.created_at DESC",
            [$id]
        );
        // Get dispatch agents only
        $dispatchAgents = $this->db->fetchAll(
            "SELECT u.id, u.name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'dispatch' AND u.status = 'active' ORDER BY u.name"
        );

        $this->view('admin/review4_detail', [
            'title'            => 'Review 4 - Lead #' . $id,
            'lead'             => $lead,
            'timeline'         => $timeline,
            'submissions'      => $submissions,
            'documents'        => $documents,
            'remarks'          => $remarks,
            'dispatchAgents'   => $dispatchAgents,
        ]);
    }

    public function processReview4(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $remark = trim($_POST['admin_approval4_remark'] ?? '');
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);
        $user = currentUser();

        if (!$leadId || !in_array($action, ['approve_to_dispatch', 'reject'])) {
            $this->json(['error' => 'Invalid data.'], 400);
            return;
        }

        $lead = $this->leadModel->findById($leadId);
        if (!$lead) {
            $this->json(['error' => 'Lead not found.'], 404);
            return;
        }

        $currentStage = $lead['workflow_stage'];
        $newStage = ($action === 'approve_to_dispatch') ? 'DISPATCH' : 'REJECTED';

        // Store remark
        $this->db->insert('remarks', [
            'lead_id'    => $leadId,
            'user_id'    => $user['id'],
            'stage'      => 'ADMIN_REVIEW_4',
            'remark'     => $remark,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Transition workflow
        $this->workflowModel->transition($leadId, $currentStage, $newStage, $user['id'], $user['role_name'], $remark, 'admin_review_4');

        // Update lead — assign agent and stage
        $updateData = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($action === 'approve_to_dispatch' && $assignedTo) {
            $this->leadModel->assign($leadId, $assignedTo, $user['id'], $remark, true);
            $updateData['workflow_stage'] = $newStage;
        }
        $this->db->update('leads', $updateData, 'id = ?', [$leadId]);

        logActivity($user['id'], 'review4_' . $action, 'lead', $leadId, $currentStage, $newStage, $remark);

        $this->json(['success' => true, 'message' => 'Decision saved. Status: ' . humanStatus($newStage)]);
    }

    // ========== CASCADE FILTER DATA ==========

    public function cascadingFilters(): void
    {
        try {
            $baseWhere = 'l.assigned_to IS NULL';
            $params = [];

            // Apply filters OTHER than the one being queried
            if (!empty($_GET['location'])) {
                $baseWhere .= ' AND l.location = ?'; $params[] = $_GET['location'];
            }
            if (!empty($_GET['state'])) {
                $baseWhere .= ' AND l.state = ?'; $params[] = $_GET['state'];
            }
            if (!empty($_GET['response_date'])) {
                $baseWhere .= ' AND l.response_date = ?'; $params[] = $_GET['response_date'];
            }
            if (!empty($_GET['data_type'])) {
                $baseWhere .= ' AND l.data_type = ?'; $params[] = $_GET['data_type'];
            }
            if (!empty($_GET['bank_name'])) {
                $baseWhere .= ' AND l.bank_name = ?'; $params[] = $_GET['bank_name'];
            }
            if (!empty($_GET['search'])) {
                $s = '%' . $_GET['search'] . '%';
                $baseWhere .= ' AND (l.customer_name LIKE ? OR l.mobile_number LIKE ? OR l.id = ?)';
                $params[] = $s; $params[] = $s; $params[] = (int)$_GET['search'];
            }

            // For each filter, count values WITHOUT that filter applied
            $filters = [
                'location'      => 'location',
                'state'         => 'state',
                'response_date' => 'response_date',
                'data_type'     => 'data_type',
                'bank_name'     => 'bank_name',
            ];

            $result = [];
            foreach ($filters as $paramName => $col) {
                // Build WHERE excluding the current filter's param
                $where = $baseWhere;
                $p = $params;
                if (!empty($_GET[$paramName])) {
                    // Remove the last param for this filter
                    $whereParts = explode(" AND l.{$col} = ?", $where, 2);
                    if (count($whereParts) > 1) {
                        $where = $whereParts[0] . $whereParts[1];
                    }
                    // Remove the param value from array
                    $filterVal = $_GET[$paramName];
                    $p = array_values(array_filter($p, function($v) use ($filterVal) {
                        return $v !== $filterVal;
                    }));
                }

                $rows = $this->db->fetchAll(
                    "SELECT l.{$col} as val, COUNT(*) as cnt FROM leads l WHERE {$where} AND l.{$col} IS NOT NULL AND l.{$col} != '' GROUP BY l.{$col} ORDER BY cnt DESC",
                    $p
                );
                $result[$paramName] = array_map(function($r) {
                    return ['value' => $r['val'], 'count' => (int)$r['cnt']];
                }, $rows);
            }

            $this->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            error_log('cascadingFilters ERROR: ' . $e->getMessage());
            $this->json(['success' => true, 'location' => [], 'state' => [], 'response_date' => [], 'data_type' => [], 'bank_name' => []]);
        }
    }

    // ========== CHANGE PASSWORD ==========

    public function changePassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $user = currentUser();
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->json(['error' => 'All fields are required.'], 400);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->json(['error' => 'New password and confirmation do not match.'], 400);
            return;
        }

        if (strlen($newPassword) < 6) {
            $this->json(['error' => 'New password must be at least 6 characters.'], 400);
            return;
        }

        // Verify old password
        $dbUser = $this->db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
        if (!$dbUser || !password_verify($oldPassword, $dbUser['password_hash'])) {
            $this->json(['error' => 'Current password is incorrect.'], 400);
            return;
        }

        $this->db->update('users', [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id = ?', [$user['id']]);

        logActivity($user['id'], 'password_changed', 'user', $user['id']);

        $this->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    // ========== LEAD TEMPLATES ==========

    public function createTemplate(): void
    {
        $this->view('admin/create_template', [
            'title' => 'Create Upload Template',
        ]);
    }

    public function storeTemplate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $name = trim($_POST['template_name'] ?? '');
        $columns = $_POST['columns'] ?? [];

        if (empty($name) || empty($columns)) {
            $this->json(['error' => 'Template name and at least one column are required.'], 400);
            return;
        }

        try {
            // Ensure lead_templates table exists
            $this->db->query("CREATE TABLE IF NOT EXISTS `lead_templates` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `columns` TEXT,
                `created_by` INT UNSIGNED,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Create CSV header
            $header = array_map(function($col) { return trim($col); }, $columns);
            $csv = implode(',', array_map(function($col) { return '"' . str_replace('"', '""', $col) . '"'; }, $header));
            $csv .= "\n";

            // Save template record
            $templateId = $this->db->insert('lead_templates', [
                'name'       => $name,
                'columns'    => json_encode($header),
                'created_by' => currentUser()['id'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Save CSV file
            $templateDir = ROOT_PATH . '/public/uploads/templates';
            if (!is_dir($templateDir)) mkdir($templateDir, 0755, true);
            file_put_contents($templateDir . '/template_' . $templateId . '.csv', $csv);

            logActivity(currentUser()['id'], 'template_created', 'template', (int)$templateId);

            $this->json(['success' => true, 'message' => 'Template created.', 'id' => $templateId]);
        } catch (\Exception $e) {
            error_log('storeTemplate error: ' . $e->getMessage());
            $this->json(['error' => 'Failed to save template: ' . $e->getMessage()], 500);
        }
    }

    public function listTemplates(): void
    {
        try {
            // Ensure table exists
            $this->db->query("CREATE TABLE IF NOT EXISTS `lead_templates` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `columns` TEXT,
                `created_by` INT UNSIGNED,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $templates = $this->db->fetchAll(
                "SELECT t.*, u.name as created_by_name FROM lead_templates t LEFT JOIN users u ON t.created_by = u.id ORDER BY t.created_at DESC"
            );
            $this->json(['success' => true, 'templates' => $templates]);
        } catch (\Exception $e) {
            error_log('listTemplates error: ' . $e->getMessage());
            $this->json(['success' => true, 'templates' => []]);
        }
    }

    public function downloadTemplate(int $id): void
    {
        $template = $this->db->fetchOne("SELECT * FROM lead_templates WHERE id = ?", [$id]);
        if (!$template) {
            $this->redirect('/admin/leads/upload', 'error', 'Template not found.');
            return;
        }

        $filePath = ROOT_PATH . '/public/uploads/templates/template_' . $id . '.csv';
        if (!file_exists($filePath)) {
            $this->redirect('/admin/leads/upload', 'error', 'Template file not found.');
            return;
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $template['name']) . '.csv"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    // ========== LEAD DELETE ==========

    public function deleteLead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadIds = $_POST['lead_ids'] ?? [];
        if (empty($leadIds)) {
            $this->json(['error' => 'No leads selected.'], 400);
            return;
        }

        $count = 0;
        $errors = [];
        $this->db->beginTransaction();
        try {
            foreach ($leadIds as $leadId) {
                $leadId = (int)$leadId;
                if ($leadId <= 0) continue;
                try {
                    // Delete related data first
                    $this->db->delete('form_submission_values', 'submission_id IN (SELECT id FROM form_submissions WHERE lead_id = ?)', [$leadId]);
                    $this->db->delete('form_submissions', 'lead_id = ?', [$leadId]);
                    $this->db->delete('lead_assignments', 'lead_id = ?', [$leadId]);
                    $this->db->delete('workflow_history', 'lead_id = ?', [$leadId]);
                    $this->db->delete('remarks', 'lead_id = ?', [$leadId]);
                    $this->db->delete('documents', 'lead_id = ?', [$leadId]);
                    $this->db->delete('notifications', 'related_lead_id = ?', [$leadId]);
                    $this->db->delete('leads', 'id = ?', [$leadId]);
                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "Lead #{$leadId}: " . $e->getMessage();
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
            return;
        }

        logActivity(currentUser()['id'], 'leads_deleted', 'lead', null, null, json_encode(['count' => $count]));
        $msg = "{$count} leads deleted.";
        if (!empty($errors)) $msg .= ' ' . count($errors) . ' errors.';
        $this->json(['success' => true, 'message' => $msg, 'errors' => $errors]);
    }

    // ========== REASSIGN LEADS ==========

    public function reassignLeads(): void
    {
        $agents = $this->db->fetchAll(
            "SELECT u.id, u.name, r.display_name as role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.status = 'active' AND r.name IN ('agent', 'login_agent', 'team_leader') 
             ORDER BY r.display_name, u.name"
        );
        $this->view('admin/reassign_leads', [
            'title'  => 'Reassign Leads',
            'agents' => $agents,
        ]);
    }

    public function reassignData(): void
    {
        try {
            if (isset($_GET['get_filters'])) {
                $locations = $this->db->fetchAll("SELECT DISTINCT location FROM leads WHERE location IS NOT NULL AND location != '' ORDER BY location");
                $states = $this->db->fetchAll("SELECT DISTINCT state FROM leads WHERE state IS NOT NULL AND state != '' ORDER BY state");
                $dataTypes = $this->db->fetchAll("SELECT DISTINCT data_type FROM leads WHERE data_type IS NOT NULL AND data_type != '' ORDER BY data_type");
                $bankNames = $this->db->fetchAll("SELECT DISTINCT bank_name FROM leads WHERE bank_name IS NOT NULL AND bank_name != '' ORDER BY bank_name");
                $assignedUsers = $this->db->fetchAll(
                    "SELECT l.assigned_to, u.name, COUNT(*) as cnt FROM leads l JOIN users u ON l.assigned_to = u.id WHERE l.assigned_to IS NOT NULL GROUP BY l.assigned_to, u.name ORDER BY u.name"
                );

                $this->json([
                    'success' => true,
                    'locations' => array_column($locations, 'location'),
                    'states' => array_column($states, 'state'),
                    'data_types' => array_column($dataTypes, 'data_type'),
                    'bank_names' => array_column($bankNames, 'bank_name'),
                    'assigned_users' => $assignedUsers,
                ]);
                return;
            }

            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(500, max(1, (int)($_GET['per_page'] ?? 50)));
            $where = 'l.assigned_to IS NOT NULL';
            $params = [];

            if (!empty($_GET['assigned_to'])) {
                $where .= ' AND l.assigned_to = ?';
                $params[] = $_GET['assigned_to'];
            }
            if (!empty($_GET['location'])) {
                $where .= ' AND l.location = ?';
                $params[] = $_GET['location'];
            }
            if (!empty($_GET['state'])) {
                $where .= ' AND l.state = ?';
                $params[] = $_GET['state'];
            }
            if (!empty($_GET['data_type'])) {
                $where .= ' AND l.data_type = ?';
                $params[] = $_GET['data_type'];
            }
            if (!empty($_GET['bank_name'])) {
                $where .= ' AND l.bank_name = ?';
                $params[] = $_GET['bank_name'];
            }
            if (!empty($_GET['search'])) {
                $s = '%' . $_GET['search'] . '%';
                $where .= ' AND (l.customer_name LIKE ? OR l.mobile_number LIKE ? OR l.id = ?)';
                $params[] = $s;
                $params[] = $s;
                $params[] = (int)$_GET['search'];
            }

            $total = $this->db->count('leads l', $where, $params);
            $totalPages = max(1, ceil($total / $perPage));
            $page = min($page, $totalPages);
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT l.id, l.customer_name, l.mobile_number, l.location, l.state,
                           l.existing_la, l.salary, l.bank_name, l.workflow_stage, l.created_at,
                           l.assigned_to, u.name as assigned_to_name
                    FROM leads l
                    LEFT JOIN users u ON l.assigned_to = u.id
                    WHERE {$where}
                    ORDER BY l.created_at DESC
                    LIMIT {$perPage} OFFSET {$offset}";
            $data = $this->db->fetchAll($sql, $params);

            $this->json([
                'success' => true,
                'data' => $data,
                'total' => $total,
                'total_pages' => $totalPages,
                'page' => $page,
            ]);
        } catch (\Throwable $e) {
            error_log('reassignData ERROR: ' . $e->getMessage());
            $this->json(['success' => true, 'data' => [], 'total' => 0, 'total_pages' => 1, 'page' => 1]);
        }
    }

    public function processReassignment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadIds = $_POST['lead_ids'] ?? [];
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);

        if (empty($leadIds) || !$assignedTo) {
            $this->json(['error' => 'Please select leads and a user to reassign to.'], 400);
            return;
        }

        $targetUser = $this->db->fetchOne("SELECT id, name FROM users WHERE id = ? AND status = 'active'", [$assignedTo]);
        if (!$targetUser) {
            $this->json(['error' => 'Selected user not found or inactive.'], 400);
            return;
        }

        $count = 0;
        $errors = [];
        $this->db->beginTransaction();
        try {
            foreach ($leadIds as $leadId) {
                $leadId = (int)$leadId;
                if ($leadId <= 0) continue;
                try {
                    $this->leadModel->assign($leadId, $assignedTo);
                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "Lead #{$leadId}: " . $e->getMessage();
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->json(['error' => 'Reassignment failed: ' . $e->getMessage()], 500);
            return;
        }

        logActivity(currentUser()['id'], 'leads_reassigned', 'lead', null, null, 
            json_encode(['count' => $count, 'assigned_to' => $assignedTo, 'target' => $targetUser['name']]));

        $msg = "{$count} leads reassigned to {$targetUser['name']}.";
        if (!empty($errors)) $msg .= ' ' . count($errors) . ' errors.';
        $this->json(['success' => true, 'message' => $msg, 'errors' => $errors]);
    }
}
