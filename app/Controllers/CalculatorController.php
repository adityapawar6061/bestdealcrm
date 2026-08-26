<?php
namespace Controllers;

class CalculatorController extends BaseController
{
    /**
     * Generate a unique 6-char code for save/load
     */
    private function generateCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $existing = $this->db->fetchOne(
                "SELECT id FROM calculator_saves WHERE code = ?",
                [$code]
            );
            if (!$existing) return $code;
        }
        return strtoupper(substr(uniqid(), -6));
    }

    /**
     * Calculator page
     */
    public function calculator(): void
    {
        $bankPolicies = require ROOT_PATH . '/config/bank_policies.php';
        $this->view('tools/calculator', [
            'title'         => 'EMI Comparison Calculator',
            'bankPolicies'  => $bankPolicies,
        ]);
    }

    /**
     * Eligibility Checker page
     */
    public function eligibility(): void
    {
        $bankPolicies = require ROOT_PATH . '/config/bank_policies.php';
        $this->view('tools/eligibility', [
            'title'       => 'Bank Eligibility Checker',
            'bankPolicies'=> $bankPolicies,
        ]);
    }

    /**
     * API: Save calculator data
     */
    public function apiSave(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['success' => false, 'message' => 'Invalid request.'], 405);
                return;
            }

            $data = $_POST['data'] ?? null;
            $saveType = $_POST['save_type'] ?? 'calculator';
            $user = currentUser();

            if (!$data) {
                $this->json(['success' => false, 'message' => 'No data provided.'], 400);
                return;
            }

            // Decode if it's JSON string
            $decoded = is_string($data) ? json_decode($data, true) : $data;
            if (!$decoded) {
                $this->json(['success' => false, 'message' => 'Invalid data format.'], 400);
                return;
            }

            $customerName = $decoded['customerName'] ?? $decoded['customer_name'] ?? null;

            $code = $this->generateCode();

            $this->db->insert('calculator_saves', [
                'code'           => $code,
                'customer_name'  => $customerName,
                'save_type'      => $saveType,
                'data'           => json_encode($decoded),
                'created_by'     => $user['id'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);

            logActivity($user['id'] ?? null, 'calculator_saved', 'calculator', null, null,
                json_encode(['code' => $code, 'customer_name' => $customerName, 'type' => $saveType]));

            $this->json(['success' => true, 'code' => $code, 'message' => 'Saved successfully!']);
        } catch (\Throwable $e) {
            error_log('Calculator save error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Load calculator data by code
     */
    public function apiLoad(): void
    {
        try {
            $code = strtoupper(trim($_GET['code'] ?? ''));

            if (!$code) {
                $this->json(['success' => false, 'message' => 'No code provided.'], 400);
                return;
            }

            $row = $this->db->fetchOne(
                "SELECT * FROM calculator_saves WHERE code = ?",
                [$code]
            );

            if (!$row) {
                $this->json(['success' => false, 'message' => 'Code not found.'], 404);
                return;
            }

            $data = json_decode($row['data'], true);
            if (!$data) {
                $this->json(['success' => false, 'message' => 'Invalid stored data.'], 500);
                return;
            }

            $this->json([
                'success'      => true,
                'data'         => json_encode($data),
                'customer_name'=> $row['customer_name'],
                'save_type'    => $row['save_type'],
                'created_at'   => $row['created_at'],
            ]);
        } catch (\Throwable $e) {
            error_log('Calculator load error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Load failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Update existing calculator by code
     */
    public function apiUpdate(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['success' => false, 'message' => 'Invalid request.'], 405);
                return;
            }

            $code = strtoupper(trim($_POST['code'] ?? ''));
            $data = $_POST['data'] ?? null;

            if (!$code || !$data) {
                $this->json(['success' => false, 'message' => 'Code and data required.'], 400);
                return;
            }

            $decoded = is_string($data) ? json_decode($data, true) : $data;

            $existing = $this->db->fetchOne(
                "SELECT id FROM calculator_saves WHERE code = ?",
                [$code]
            );

            if (!$existing) {
                $this->json(['success' => false, 'message' => 'Code not found.'], 404);
                return;
            }

            $customerName = $decoded['customerName'] ?? $decoded['customer_name'] ?? null;

            $this->db->update('calculator_saves', [
                'data'          => json_encode($decoded),
                'customer_name' => $customerName,
                'updated_at'    => date('Y-m-d H:i:s'),
            ], 'code = ?', [$code]);

            $this->json(['success' => true, 'message' => 'Updated successfully!']);
        } catch (\Throwable $e) {
            error_log('Calculator update error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: List recent saves
     */
    public function apiList(): void
    {
        try {
            $user = currentUser();
            $rows = $this->db->fetchAll(
                "SELECT code, customer_name, save_type, created_at, updated_at 
                 FROM calculator_saves 
                 WHERE created_by = ? 
                 ORDER BY updated_at DESC LIMIT 50",
                [$user['id']]
            );

            $this->json(['success' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
