<?php
namespace Controllers;

class ServicesController extends BaseController
{
    private \Models\Lead $leadModel;

    public function __construct()
    {
        parent::__construct();
        $this->leadModel = new \Models\Lead();
    }

    // ============================================================
    // PF REQUESTS
    // ============================================================

    /** Agent: PF Request Form */
    public function pfRequestForm(): void
    {
        $user = currentUser();
        $recent = $this->db->fetchAll(
            "SELECT * FROM pf_requests WHERE agent_id = ? ORDER BY created_at DESC LIMIT 20",
            [$user['id']]
        );
        $this->view('services/pf_request_form', [
            'title'  => 'Raise PF Request',
            'recent' => $recent,
        ]);
    }

    /** Agent: Submit PF Request */
    public function pfRequestSubmit(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request.'], 405);
                return;
            }

            $user = currentUser();
            $data = [
                'agent_id'         => $user['id'],
                'customer_name'    => trim($_POST['customer_name'] ?? ''),
                'mobile'           => trim($_POST['mobile'] ?? ''),
                'monthly_salary'   => trim($_POST['monthly_salary'] ?? ''),
                'loan_requirement' => trim($_POST['loan_requirement'] ?? ''),
                'loan_type'        => trim($_POST['loan_type'] ?? ''),
                'processing_bank'  => trim($_POST['processing_bank'] ?? ''),
                'cibil_score'      => (int)($_POST['cibil_score'] ?? 0),
                'status'           => 'pending',
                'created_at'       => date('Y-m-d H:i:s'),
            ];

            if (empty($data['customer_name']) || empty($data['mobile'])) {
                $this->json(['error' => 'Customer name and mobile are required.'], 400);
                return;
            }

            $id = $this->db->insert('pf_requests', $data);
            logActivity($user['id'], 'pf_request_submitted', 'pf_request', $id);
            createNotification(0, 'New PF Request', "Agent {$user['name']} submitted a PF request for {$data['customer_name']}.", 'info', null);

            $this->json(['success' => true, 'message' => 'PF request submitted!', 'id' => $id]);
        } catch (\Throwable $e) {
            error_log('PF submit error: ' . $e->getMessage());
            $this->json(['error' => 'Submit failed: ' . $e->getMessage()], 500);
        }
    }

    /** Admin: PF Requests List */
    public function pfRequests(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;
        $search = trim($_GET['search'] ?? '');
        $statusFilter = $_GET['status'] ?? '';

        $where = '1=1';
        $params = [];
        if ($search) {
            $where .= ' AND (p.customer_name LIKE ? OR p.mobile LIKE ? OR u.name LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($statusFilter) {
            $where .= ' AND p.status = ?';
            $params[] = $statusFilter;
        }

        $total = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM pf_requests p LEFT JOIN users u ON p.agent_id = u.id WHERE {$where}", $params)['cnt'];
        $rows = $this->db->fetchAll(
            "SELECT p.*, u.name as agent_name FROM pf_requests p LEFT JOIN users u ON p.agent_id = u.id WHERE {$where} ORDER BY p.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('services/pf_requests_admin', [
            'title'     => 'PF Requests',
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'search'    => $search,
            'status'    => $statusFilter,
        ]);
    }

    /** Admin: Verify PF Request */
    public function pfVerify(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $row = $this->db->fetchOne(
            "SELECT p.*, u.name as agent_name FROM pf_requests p LEFT JOIN users u ON p.agent_id = u.id WHERE p.id = ?",
            [$id]
        );
        if (!$row) {
            $this->json(['error' => 'Not found.'], 404);
            return;
        }
        $this->view('services/pf_verify', [
            'title' => 'Verify PF Request - ' . $row['customer_name'],
            'row'   => $row,
        ]);
    }

    /** Admin: Process PF Verification */
    public function pfProcess(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request.'], 405);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            $approved = $_POST['admin_approved'] ?? 'pending';
            $remarks = trim($_POST['admin_remarks'] ?? '');

            // Handle file uploads
            $files = [];
            if (!empty($_FILES['admin_files']['name'][0])) {
                $uploadDir = ROOT_PATH . '/public/uploads/pf/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                foreach ($_FILES['admin_files']['name'] as $i => $name) {
                    if ($_FILES['admin_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $safeName = 'pf_' . $id . '_' . time() . '_' . $i . '.' . $ext;
                        move_uploaded_file($_FILES['admin_files']['tmp_name'][$i], $uploadDir . $safeName);
                        $files[] = $safeName;
                    }
                }
            }

            $updateData = [
                'admin_approved' => $approved,
                'admin_remarks'  => $remarks,
                'status'         => 'replied',
                'updated_at'     => date('Y-m-d H:i:s'),
            ];
            if (!empty($files)) {
                $existing = $this->db->fetchOne("SELECT admin_files FROM pf_requests WHERE id = ?", [$id]);
                $existingFiles = json_decode($existing['admin_files'] ?? '[]', true) ?? [];
                $updateData['admin_files'] = json_encode(array_merge($existingFiles, $files));
            }

            $this->db->update('pf_requests', $updateData, 'id = ?', [$id]);

            // Notify agent
            $row = $this->db->fetchOne("SELECT agent_id, customer_name FROM pf_requests WHERE id = ?", [$id]);
            if ($row) {
                createNotification($row['agent_id'], 'PF Request Updated', "Your PF request for {$row['customer_name']} has been {$approved}.", 'info', null);
            }

            logActivity(currentUser()['id'], 'pf_request_processed', 'pf_request', $id);
            $this->json(['success' => true, 'message' => 'PF request updated!']);
        } catch (\Throwable $e) {
            error_log('PF process error: ' . $e->getMessage());
            $this->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // CIBIL REQUESTS
    // ============================================================

    /** Agent: CIBIL Request Form */
    public function cibilRequestForm(): void
    {
        $user = currentUser();
        $recent = $this->db->fetchAll(
            "SELECT * FROM cibil_requests WHERE agent_id = ? ORDER BY created_at DESC LIMIT 20",
            [$user['id']]
        );
        $this->view('services/cibil_request_form', [
            'title'  => 'Add New CIBIL Request',
            'recent' => $recent,
        ]);
    }

    /** Agent: Submit CIBIL Request */
    public function cibilRequestSubmit(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request.'], 405);
                return;
            }

            $user = currentUser();
            $data = [
                'agent_id'          => $user['id'],
                'name_as_pan'       => trim($_POST['name_as_pan'] ?? ''),
                'pan_no'            => strtoupper(trim($_POST['pan_no'] ?? '')),
                'mobile'            => trim($_POST['mobile'] ?? ''),
                'cibil_score'       => (int)($_POST['cibil_score'] ?? 0),
                'monthly_salary'    => trim($_POST['monthly_salary'] ?? ''),
                'loan_requirement'  => trim($_POST['loan_requirement'] ?? ''),
                'loan_type'         => trim($_POST['loan_type'] ?? ''),
                'loan_eligible_calc'=> trim($_POST['loan_eligible_calc'] ?? ''),
                'calculator_id'     => trim($_POST['calculator_id'] ?? ''),
                'requirement'       => trim($_POST['requirement'] ?? ''),
                'status'            => 'pending',
                'created_at'        => date('Y-m-d H:i:s'),
            ];

            if (empty($data['name_as_pan']) || empty($data['pan_no']) || empty($data['mobile'])) {
                $this->json(['error' => 'Name, PAN, and Mobile are required.'], 400);
                return;
            }

            $id = $this->db->insert('cibil_requests', $data);
            logActivity($user['id'], 'cibil_request_submitted', 'cibil_request', $id);
            createNotification(0, 'New CIBIL Request', "Agent {$user['name']} submitted a CIBIL request for {$data['name_as_pan']}.", 'info', null);

            $this->json(['success' => true, 'message' => 'CIBIL request submitted!', 'id' => $id]);
        } catch (\Throwable $e) {
            error_log('CIBIL submit error: ' . $e->getMessage());
            $this->json(['error' => 'Submit failed: ' . $e->getMessage()], 500);
        }
    }

    /** Admin: CIBIL Requests List */
    public function cibilRequests(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;
        $search = trim($_GET['search'] ?? '');
        $statusFilter = $_GET['status'] ?? '';

        $where = '1=1';
        $params = [];
        if ($search) {
            $where .= ' AND (c.name_as_pan LIKE ? OR c.pan_no LIKE ? OR c.mobile LIKE ? OR u.name LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($statusFilter) {
            $where .= ' AND c.status = ?';
            $params[] = $statusFilter;
        }

        $total = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM cibil_requests c LEFT JOIN users u ON c.agent_id = u.id WHERE {$where}", $params)['cnt'];
        $rows = $this->db->fetchAll(
            "SELECT c.*, u.name as agent_name FROM cibil_requests c LEFT JOIN users u ON c.agent_id = u.id WHERE {$where} ORDER BY c.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('services/cibil_requests_admin', [
            'title'     => 'CIBIL Requests',
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'search'    => $search,
            'status'    => $statusFilter,
        ]);
    }

    /** Admin: Verify CIBIL Request */
    public function cibilVerify(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $row = $this->db->fetchOne(
            "SELECT c.*, u.name as agent_name FROM cibil_requests c LEFT JOIN users u ON c.agent_id = u.id WHERE c.id = ?",
            [$id]
        );
        if (!$row) {
            $this->json(['error' => 'Not found.'], 404);
            return;
        }
        $this->view('services/cibil_verify', [
            'title' => 'Verify CIBIL Request - ' . $row['name_as_pan'],
            'row'   => $row,
        ]);
    }

    /** Admin: Process CIBIL Verification */
    public function cibilProcess(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request.'], 405);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            $cibilChecked = $_POST['cibil_checked'] ?? 'no';
            $cibilCompany = trim($_POST['cibil_company'] ?? '');
            $cibilScoreActual = (int)($_POST['cibil_score_actual'] ?? 0);
            $mainStatus = trim($_POST['main_status'] ?? 'N/A');
            $subStatus = trim($_POST['sub_status'] ?? 'N/A');
            $agentCibilRemarks = trim($_POST['agent_cibil_remarks'] ?? '');
            $adminRemarks = trim($_POST['admin_remarks'] ?? '');

            // Handle PDF uploads
            $pdf1 = null;
            $pdf2 = null;
            $uploadDir = ROOT_PATH . '/public/uploads/cibil/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (!empty($_FILES['cibil_pdf1']['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES['cibil_pdf1']['name'], PATHINFO_EXTENSION));
                $pdf1 = 'cibil_' . $id . '_1_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['cibil_pdf1']['tmp_name'], $uploadDir . $pdf1);
            }
            if (!empty($_FILES['cibil_pdf2']['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES['cibil_pdf2']['name'], PATHINFO_EXTENSION));
                $pdf2 = 'cibil_' . $id . '_2_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['cibil_pdf2']['tmp_name'], $uploadDir . $pdf2);
            }

            $updateData = [
                'cibil_checked'       => $cibilChecked,
                'cibil_company'       => $cibilCompany,
                'cibil_score_actual'  => $cibilScoreActual ?: null,
                'main_status'         => $mainStatus,
                'sub_status'          => $subStatus,
                'agent_cibil_remarks' => $agentCibilRemarks,
                'admin_remarks'       => $adminRemarks,
                'status'              => 'replied',
                'updated_at'          => date('Y-m-d H:i:s'),
            ];
            if ($pdf1) $updateData['cibil_pdf1'] = $pdf1;
            if ($pdf2) $updateData['cibil_pdf2'] = $pdf2;

            $this->db->update('cibil_requests', $updateData, 'id = ?', [$id]);

            $row = $this->db->fetchOne("SELECT agent_id, name_as_pan FROM cibil_requests WHERE id = ?", [$id]);
            if ($row) {
                createNotification($row['agent_id'], 'CIBIL Request Updated', "Your CIBIL request for {$row['name_as_pan']} has been replied.", 'info', null);
            }

            logActivity(currentUser()['id'], 'cibil_request_processed', 'cibil_request', $id);
            $this->json(['success' => true, 'message' => 'CIBIL request updated!']);
        } catch (\Throwable $e) {
            error_log('CIBIL process error: ' . $e->getMessage());
            $this->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // CRM DATA ENTRY
    // ============================================================

    /** Agent: Data Entry Form + My Entries */
    public function dataEntry(): void
    {
        $user = currentUser();
        $dispositionFilter = $_GET['disposition'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = 'user_id = ?';
        $params = [$user['id']];
        if ($dispositionFilter) {
            $where .= ' AND disposition = ?';
            $params[] = $dispositionFilter;
        }

        $total = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM crm_entries WHERE {$where}", $params)['cnt'];
        $entries = $this->db->fetchAll(
            "SELECT * FROM crm_entries WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('services/data_entry', [
            'title'              => 'CRM Data Entry',
            'entries'            => $entries,
            'total'              => $total,
            'page'               => $page,
            'perPage'            => $perPage,
            'dispositionFilter'  => $dispositionFilter,
            'user'               => $user,
        ]);
    }

    /** Agent: Submit Data Entry */
    public function dataEntrySubmit(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request.'], 405);
                return;
            }

            $user = currentUser();
            $data = [
                'user_id'       => $user['id'],
                'mobile_no'     => trim($_POST['mobile_no'] ?? ''),
                'customer_name' => trim($_POST['customer_name'] ?? ''),
                'city'          => trim($_POST['city'] ?? ''),
                'salary'        => trim($_POST['salary'] ?? ''),
                'loan_amount'   => trim($_POST['loan_amount'] ?? ''),
                'disposition'   => trim($_POST['disposition'] ?? ''),
                'remarks'       => trim($_POST['remarks'] ?? ''),
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            if (empty($data['mobile_no']) || empty($data['customer_name']) || empty($data['disposition'])) {
                $this->json(['error' => 'Mobile, Customer Name, and Disposition are required.'], 400);
                return;
            }

            $id = $this->db->insert('crm_entries', $data);
            logActivity($user['id'], 'crm_entry_created', 'crm_entry', $id);

            $this->json(['success' => true, 'message' => 'Entry saved!', 'id' => $id]);
        } catch (\Throwable $e) {
            error_log('Data entry error: ' . $e->getMessage());
            $this->json(['error' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    /** Admin: Data Entry Dashboard */
    public function dataDashboard(): void
    {
        // Overall stats
        $totalRecords = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM crm_entries")['cnt'];
        $todayRecords = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM crm_entries WHERE DATE(created_at) = CURDATE()")['cnt'];

        // User performance
        $userPerformance = $this->db->fetchAll(
            "SELECT u.name as user_name,
                    COUNT(e.id) as total_records,
                    SUM(CASE WHEN e.disposition = 'RNR' THEN 1 ELSE 0 END) as rnr,
                    SUM(CASE WHEN e.disposition = 'Disconnected' THEN 1 ELSE 0 END) as disconnected,
                    SUM(CASE WHEN e.disposition = 'Not Interested' THEN 1 ELSE 0 END) as not_interested,
                    SUM(CASE WHEN e.disposition = 'Call Back' THEN 1 ELSE 0 END) as call_back,
                    SUM(CASE WHEN e.disposition = 'Follow Up' THEN 1 ELSE 0 END) as follow_up,
                    SUM(CASE WHEN e.disposition = 'Not Eligible' THEN 1 ELSE 0 END) as not_eligible,
                    SUM(CASE WHEN e.disposition = 'Self Employed' THEN 1 ELSE 0 END) as self_employed,
                    SUM(CASE WHEN e.disposition = 'Lead' THEN 1 ELSE 0 END) as lead,
                    SUM(CASE WHEN e.disposition = 'DNC' THEN 1 ELSE 0 END) as dnc
             FROM crm_entries e
             LEFT JOIN users u ON e.user_id = u.id
             WHERE DATE(e.created_at) = CURDATE()
             GROUP BY e.user_id
             ORDER BY total_records DESC"
        );

        $this->view('services/data_dashboard', [
            'title'           => 'RM Admin Dashboard',
            'totalRecords'    => $totalRecords,
            'todayRecords'    => $todayRecords,
            'userPerformance' => $userPerformance,
        ]);
    }

    /** Admin: View All Data Entries */
    public function dataViewAll(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        $search = trim($_GET['search'] ?? '');
        $dispositionFilter = $_GET['disposition'] ?? '';
        $userFilter = $_GET['user_id'] ?? '';

        $where = '1=1';
        $params = [];
        if ($search) {
            $where .= ' AND (e.mobile_no LIKE ? OR e.customer_name LIKE ? OR e.city LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($dispositionFilter) {
            $where .= ' AND e.disposition = ?';
            $params[] = $dispositionFilter;
        }
        if ($userFilter) {
            $where .= ' AND e.user_id = ?';
            $params[] = (int)$userFilter;
        }

        $total = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM crm_entries e WHERE {$where}", $params)['cnt'];
        $entries = $this->db->fetchAll(
            "SELECT e.*, u.name as user_name FROM crm_entries e LEFT JOIN users u ON e.user_id = u.id WHERE {$where} ORDER BY e.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $users = $this->db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

        $this->view('services/data_view_all', [
            'title'              => 'All Data Entries',
            'entries'            => $entries,
            'total'              => $total,
            'page'               => $page,
            'perPage'            => $perPage,
            'search'             => $search,
            'dispositionFilter'  => $dispositionFilter,
            'userFilter'         => $userFilter,
            'users'              => $users,
        ]);
    }

    /** Admin: Add Data Entry (manual) */
    public function dataAddForm(): void
    {
        $users = $this->db->fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");
        $this->view('services/data_add', [
            'title' => 'Add Data Entry',
            'users' => $users,
        ]);
    }

    /** Admin: Submit Manual Data Entry */
    public function dataAddSubmit(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request.'], 405);
                return;
            }

            $data = [
                'user_id'       => (int)($_POST['user_id'] ?? currentUser()['id']),
                'mobile_no'     => trim($_POST['mobile_no'] ?? ''),
                'customer_name' => trim($_POST['customer_name'] ?? ''),
                'city'          => trim($_POST['city'] ?? ''),
                'salary'        => trim($_POST['salary'] ?? ''),
                'loan_amount'   => trim($_POST['loan_amount'] ?? ''),
                'disposition'   => trim($_POST['disposition'] ?? ''),
                'remarks'       => trim($_POST['remarks'] ?? ''),
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            if (empty($data['mobile_no']) || empty($data['customer_name']) || empty($data['disposition'])) {
                $this->json(['error' => 'Mobile, Customer Name, and Disposition are required.'], 400);
                return;
            }

            $id = $this->db->insert('crm_entries', $data);
            logActivity(currentUser()['id'], 'crm_entry_created_admin', 'crm_entry', $id);

            $this->json(['success' => true, 'message' => 'Entry saved!', 'id' => $id]);
        } catch (\Throwable $e) {
            error_log('Admin data entry error: ' . $e->getMessage());
            $this->json(['error' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }
}
