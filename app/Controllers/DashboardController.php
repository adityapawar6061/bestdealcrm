<?php
namespace Controllers;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $user = currentUser();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $roleDashboards = [
            'admin'          => '/admin/dashboard',
            'team_leader'    => '/team-leader/dashboard',
            'agent'          => '/agent/dashboard',
            'login_agent'    => '/login-agent/dashboard',
            'underwriting'   => '/underwriting/dashboard',
            'dispatch'       => '/dispatch/dashboard',
        ];

        $dashboard = $roleDashboards[$user['role_name']] ?? '/admin/dashboard';
        $this->redirect($dashboard);
    }
}
