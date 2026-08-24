<?php
/**
 * Web Routes - All application routes defined here
 */

$router = new Router();

// ============================================================
// PUBLIC ROUTES
// ============================================================
$router->get('/login', 'AuthController', 'showLogin');
$router->post('/login', 'AuthController', 'login');
$router->get('/logout', 'AuthController', 'logout');
$router->get('/', 'AuthController', 'showLogin');

// ============================================================
// AUTHENTICATED ROUTES
// ============================================================
$router->middleware(['AuthMiddleware'], function ($router) {

    // Dashboard redirect (role-based)
    $router->get('/dashboard', 'DashboardController', 'index');

    // ========================================================
    // ADMIN ROUTES
    // ========================================================
    $router->prefix('/admin', function ($router) {
        
        $router->middleware(['AuthMiddleware'], function ($router) {
            $router->get('/dashboard', 'AdminController', 'dashboard');
            
            // User Management
            $router->get('/users', 'AdminController', 'users');
            $router->post('/users/create', 'AdminController', 'createUser');
            $router->post('/users/update', 'AdminController', 'updateUser');
            $router->post('/users/toggle-status', 'AdminController', 'toggleUserStatus');
            $router->post('/users/reset-password', 'AdminController', 'resetUserPassword');
            $router->get('/users/{id}', 'AdminController', 'userProfile');
            
            // Lead Management
            $router->get('/leads', 'AdminController', 'leads');
            $router->get('/leads/data', 'AdminController', 'leadsAjax');
            
            // Lead Upload (must be before /leads/{id})
            $router->get('/leads/upload', 'AdminController', 'uploadLeads');
            $router->post('/leads/upload/process', 'AdminController', 'processUpload');
            $router->post('/leads/upload/mapping', 'AdminController', 'processMapping');
            $router->get('/leads/template/create', 'AdminController', 'createTemplate');
            $router->post('/leads/template/store', 'AdminController', 'storeTemplate');
            $router->get('/leads/template/{id}', 'AdminController', 'downloadTemplate');
            $router->get('/leads/templates', 'AdminController', 'listTemplates');
            
            // Lead Assignment (must be before /leads/{id})
            $router->get('/leads/assign', 'AdminController', 'assignLeads');
            $router->get('/leads/assign/data', 'AdminController', 'assignData');
            $router->post('/leads/assign', 'AdminController', 'processAssignment');
            
            // Lead Detail (wildcard last)
            $router->get('/leads/{id}', 'AdminController', 'leadDetail');
            
            // Admin Review 1
            $router->get('/review1', 'AdminController', 'review1');
            $router->get('/review1/{id}', 'AdminController', 'review1Detail');
            $router->post('/review1/process', 'AdminController', 'processReview1');
            
            // Admin Review 2
            $router->get('/review2', 'AdminController', 'review2');
            $router->get('/review2/{id}', 'AdminController', 'review2Detail');
            $router->post('/review2/process', 'AdminController', 'processReview2');
            
            // Admin Review 3 (Post-Login → Underwriting decision)
            $router->get('/review3', 'AdminController', 'review3');
            $router->get('/review3/{id}', 'AdminController', 'review3Detail');
            $router->post('/review3/process', 'AdminController', 'processReview3');
            
            // Admin Review 4 (Underwriting → Dispatch decision)
            $router->get('/review4', 'AdminController', 'review4');
            $router->get('/review4/{id}', 'AdminController', 'review4Detail');
            $router->post('/review4/process', 'AdminController', 'processReview4');
            
            // Roles & Permissions
            $router->get('/roles', 'AdminController', 'roles');
            $router->get('/roles/{id}/permissions', 'AdminController', 'rolePermissions');
            $router->post('/roles/permissions/save', 'AdminController', 'savePermissions');
            
            // Workflow
            $router->get('/workflow', 'AdminController', 'workflowStages');
            
            // Form Builder
            $router->get('/form-builder', 'FormBuilderController', 'index');
            $router->get('/form-builder/create', 'FormBuilderController', 'create');
            $router->post('/form-builder/store', 'FormBuilderController', 'store');
            $router->get('/form-builder/{id}/edit', 'FormBuilderController', 'edit');
            $router->post('/form-builder/{id}/update', 'FormBuilderController', 'update');
            $router->post('/form-builder/add-section', 'FormBuilderController', 'addSection');
            $router->post('/form-builder/add-field', 'FormBuilderController', 'addField');
            $router->post('/form-builder/field/{id}/delete', 'FormBuilderController', 'deleteField');
            
            // Form Builder - Field Options
            $router->post('/form-builder/field/{id}/options', 'FormBuilderController', 'getFieldOptions');
            $router->post('/form-builder/field/{id}/options/save', 'FormBuilderController', 'saveFieldOptions');
            $router->post('/form-builder/delete-with-password', 'FormBuilderController', 'deleteWithPassword');
            
            // Notifications
            $router->get('/notifications', 'AdminController', 'notifications');
            $router->post('/notifications/read', 'AdminController', 'readNotification');
            
            // Activity Logs
            $router->get('/activity-logs', 'AdminController', 'activityLogs');
            
            // Document Upload/Download
            $router->post('/documents/upload', 'AdminController', 'uploadDocument');
            $router->get('/documents/{id}/download', 'AdminController', 'downloadDocument');
        });
    });

    // ========================================================
    // AGENT ROUTES
    // ========================================================
    $router->prefix('/agent', function ($router) {
        $router->get('/dashboard', 'AgentController', 'dashboard');
        $router->get('/leads', 'AgentController', 'leads');
        $router->get('/leads/data', 'AgentController', 'leadsAjax');
        $router->get('/leads/{id}', 'AgentController', 'leadDetail');
        $router->get('/leads/{id}/fill-form', 'AgentController', 'fillForm');
        $router->post('/leads/save-draft', 'AgentController', 'saveDraft');
        $router->post('/leads/submit-form', 'AgentController', 'submitForm');
        
        // Notifications
        $router->get('/notifications', 'AgentController', 'notifications');
        $router->post('/notifications/read', 'AgentController', 'readNotification');
        
        // Documents
        $router->post('/documents/upload', 'AgentController', 'uploadDocument');
    });

    // ========================================================
    // LOGIN AGENT ROUTES
    // ========================================================
    $router->prefix('/login-agent', function ($router) {
        $router->get('/dashboard', 'LoginAgentController', 'dashboard');
        $router->get('/cases', 'LoginAgentController', 'cases');
        $router->get('/cases/data', 'LoginAgentController', 'casesAjax');
        $router->get('/cases/{id}/pre-login', 'LoginAgentController', 'preLoginChecklist');
        $router->post('/cases/save-draft', 'LoginAgentController', 'saveChecklistDraft');
        $router->post('/cases/submit-checklist', 'LoginAgentController', 'submitChecklist');
        $router->post('/cases/send-back', 'LoginAgentController', 'sendBackToAgent');
        $router->get('/cases/{id}/post-login', 'LoginAgentController', 'postLogin');
        $router->post('/cases/submit-post-login', 'LoginAgentController', 'submitPostLogin');
        
        // Notifications
        $router->get('/notifications', 'LoginAgentController', 'notifications');
        $router->post('/notifications/read', 'LoginAgentController', 'readNotification');
    });

    // ========================================================
    // TEAM LEADER ROUTES
    // ========================================================
    $router->prefix('/team-leader', function ($router) {
        $router->get('/dashboard', 'TeamLeaderController', 'dashboard');
        $router->get('/team', 'TeamLeaderController', 'team');
        $router->get('/team/leads', 'TeamLeaderController', 'teamLeads');
        
        // Notifications
        $router->get('/notifications', 'TeamLeaderController', 'notifications');
        $router->post('/notifications/read', 'TeamLeaderController', 'readNotification');
    });

    // ========================================================
    // UNDERWRITING ROUTES
    // ========================================================
    $router->prefix('/underwriting', function ($router) {
        $router->get('/dashboard', 'UnderwritingController', 'dashboard');
        $router->get('/cases', 'UnderwritingController', 'cases');
        $router->get('/cases/{id}', 'UnderwritingController', 'caseDetail');
        $router->post('/cases/process', 'UnderwritingController', 'processCase');
        
        // Notifications
        $router->get('/notifications', 'UnderwritingController', 'notifications');
        $router->post('/notifications/read', 'UnderwritingController', 'readNotification');
    });

    // ========================================================
    // DISPATCH ROUTES
    // ========================================================
    $router->prefix('/dispatch', function ($router) {
        $router->get('/dashboard', 'DispatchController', 'dashboard');
        $router->get('/cases', 'DispatchController', 'cases');
        $router->get('/cases/{id}', 'DispatchController', 'caseDetail');
        $router->post('/cases/process', 'DispatchController', 'processCase');
        
        // Notifications
        $router->get('/notifications', 'DispatchController', 'notifications');
        $router->post('/notifications/read', 'DispatchController', 'readNotification');
    });
});
