-- ============================================================
-- BestDeal CRM - Seed Data for Dynamic Forms
-- Run this AFTER the main migration.sql
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- FORM 1: AGENT LEAD FORM
-- ============================================================
INSERT INTO `forms` (`name`, `code`, `description`, `assigned_role`, `workflow_stage`, `status`, `created_at`) VALUES
('Agent Lead Form', 'AGENT_LEAD_FORM', 'Initial lead information form filled by Agent', 'agent', 'AGENT_DRAFT', 'active', NOW());

SET @agentFormId = LAST_INSERT_ID();

-- Sections
INSERT INTO `form_sections` (`form_id`, `name`, `description`, `display_order`, `created_at`) VALUES
(@agentFormId, 'Agent & Product Details', 'Agent and product identification', 1, NOW()),
(@agentFormId, 'Personal Information', 'Customer personal information', 2, NOW()),
(@agentFormId, 'Residence Information', 'Customer residence details', 3, NOW()),
(@agentFormId, 'Employment Details', 'Employment and income information', 4, NOW()),
(@agentFormId, 'Loan Requirement Details', 'Loan amount and purpose', 5, NOW()),
(@agentFormId, 'Salary Information', 'Salary and income details', 6, NOW()),
(@agentFormId, 'Credit Information', 'Credit score and existing obligations', 7, NOW()),
(@agentFormId, 'Documents', 'Document uploads', 8, NOW()),
(@agentFormId, 'Remarks', 'Additional notes', 9, NOW());

-- Agent & Product Details section
SET @sec1 = (SELECT id FROM form_sections WHERE form_id = @agentFormId AND name = 'Agent & Product Details' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `default_value`, `display_order`, `visible_roles`, `editable_roles`, `created_at`) VALUES
(@sec1, 'agent_name', 'Agent Name', 'readonly', 1, '', '', 1, 'agent', 'agent', NOW()),
(@sec1, 'agent_product_type', 'Product Type', 'dropdown', 1, 'Select product type', '', 2, 'agent', 'agent', NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`) 
SELECT id, 'Personal Loan', 'personal_loan', 1 FROM form_fields WHERE field_name = 'agent_product_type' AND section_id = @sec1 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`) 
SELECT id, 'Home Loan', 'home_loan', 2 FROM form_fields WHERE field_name = 'agent_product_type' AND section_id = @sec1 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`) 
SELECT id, 'Business Loan', 'business_loan', 3 FROM form_fields WHERE field_name = 'agent_product_type' AND section_id = @sec1 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`) 
SELECT id, 'Balance Transfer', 'balance_transfer', 4 FROM form_fields WHERE field_name = 'agent_product_type' AND section_id = @sec1 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`) 
SELECT id, 'Loan Against Property', 'lap', 5 FROM form_fields WHERE field_name = 'agent_product_type' AND section_id = @sec1 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`) 
SELECT id, 'Used Car Loan', 'used_car', 6 FROM form_fields WHERE field_name = 'agent_product_type' AND section_id = @sec1 LIMIT 1;

-- Personal Information section
SET @sec2 = (SELECT id FROM form_sections WHERE form_id = @agentFormId AND name = 'Personal Information' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `default_value`, `display_order`, `created_at`) VALUES
(@sec2, 'agent_customer_name', 'Customer Name', 'text', 1, 'Enter full name', '', 1, NOW()),
(@sec2, 'agent_father_name', 'Father/Husband Name', 'text', 0, 'Enter father or husband name', '', 2, NOW()),
(@sec2, 'agent_customer_dob', 'Date of Birth', 'date', 0, '', '', 3, NOW()),
(@sec2, 'agent_customer_age', 'Age', 'number', 0, 'Age in years', '', 4, NOW()),
(@sec2, 'agent_gender', 'Gender', 'dropdown', 0, '', '', 5, NOW()),
(@sec2, 'agent_marital_status', 'Marital Status', 'dropdown', 0, '', '', 6, NOW()),
(@sec2, 'agent_pan_number', 'PAN Number', 'text', 1, 'Enter PAN number', '', 7, NOW()),
(@sec2, 'agent_aadhar_number', 'Aadhar Number', 'text', 0, 'Enter 12-digit Aadhar', '', 8, NOW()),
(@sec2, 'agent_email', 'Email Address', 'email', 0, 'Enter email', '', 9, NOW()),
(@sec2, 'agent_mobile_number', 'Mobile Number', 'mobile', 1, 'Enter 10-digit mobile', '', 10, NOW()),
(@sec2, 'agent_alternate_mobile', 'Alternate Mobile', 'mobile', 0, '', '', 11, NOW());

-- Gender options
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Male', 'male', 1 FROM form_fields WHERE field_name = 'agent_gender' AND section_id = @sec2 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Female', 'female', 2 FROM form_fields WHERE field_name = 'agent_gender' AND section_id = @sec2 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Other', 'other', 3 FROM form_fields WHERE field_name = 'agent_gender' AND section_id = @sec2 LIMIT 1;

-- Marital Status options
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Single', 'single', 1 FROM form_fields WHERE field_name = 'agent_marital_status' AND section_id = @sec2 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Married', 'married', 2 FROM form_fields WHERE field_name = 'agent_marital_status' AND section_id = @sec2 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Divorced', 'divorced', 3 FROM form_fields WHERE field_name = 'agent_marital_status' AND section_id = @sec2 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Widowed', 'widowed', 4 FROM form_fields WHERE field_name = 'agent_marital_status' AND section_id = @sec2 LIMIT 1;

-- Residence Information section
SET @sec3 = (SELECT id FROM form_sections WHERE form_id = @agentFormId AND name = 'Residence Information' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `default_value`, `display_order`, `created_at`) VALUES
(@sec3, 'agent_residence_type', 'Residence Type', 'dropdown', 0, '', '', 1, NOW()),
(@sec3, 'agent_address_line1', 'Address Line 1', 'text', 0, 'Flat/House No, Building', '', 2, NOW()),
(@sec3, 'agent_address_line2', 'Address Line 2', 'text', 0, 'Street, Landmark', '', 3, NOW()),
(@sec3, 'agent_city', 'City', 'text', 0, 'Enter city', '', 4, NOW()),
(@sec3, 'agent_state', 'State', 'text', 0, 'Enter state', '', 5, NOW()),
(@sec3, 'agent_pincode', 'Pincode', 'text', 0, '6-digit pincode', '', 6, NOW()),
(@sec3, 'agent_residing_since', 'Residing Since (years)', 'number', 0, 'Years at current address', '', 7, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Owned', 'owned', 1 FROM form_fields WHERE field_name = 'agent_residence_type' AND section_id = @sec3 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Rented', 'rented', 2 FROM form_fields WHERE field_name = 'agent_residence_type' AND section_id = @sec3 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Parental', 'parental', 3 FROM form_fields WHERE field_name = 'agent_residence_type' AND section_id = @sec3 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Company Provided', 'company', 4 FROM form_fields WHERE field_name = 'agent_residence_type' AND section_id = @sec3 LIMIT 1;

-- Employment Details section
SET @sec4 = (SELECT id FROM form_sections WHERE form_id = @agentFormId AND name = 'Employment Details' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `default_value`, `display_order`, `created_at`) VALUES
(@sec4, 'agent_employment_type', 'Employment Type', 'dropdown', 0, '', '', 1, NOW()),
(@sec4, 'agent_company_name', 'Company/Organization Name', 'text', 0, 'Enter company name', '', 2, NOW()),
(@sec4, 'agent_designation', 'Designation', 'text', 0, 'Enter designation', '', 3, NOW()),
(@sec4, 'agent_industry', 'Industry', 'text', 0, 'Enter industry type', '', 4, NOW()),
(@sec4, 'agent_experience_years', 'Total Work Experience (years)', 'number', 0, 'Years of experience', '', 5, NOW()),
(@sec4, 'agent_current_experience', 'Current Company Experience (years)', 'number', 0, '', '', 6, NOW()),
(@sec4, 'agent_office_address', 'Office Address', 'textarea', 0, 'Full office address', '', 7, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Salaried', 'salaried', 1 FROM form_fields WHERE field_name = 'agent_employment_type' AND section_id = @sec4 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Self Employed', 'self_employed', 2 FROM form_fields WHERE field_name = 'agent_employment_type' AND section_id = @sec4 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Business Owner', 'business_owner', 3 FROM form_fields WHERE field_name = 'agent_employment_type' AND section_id = @sec4 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Professional', 'professional', 4 FROM form_fields WHERE field_name = 'agent_employment_type' AND section_id = @sec4 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Government', 'government', 5 FROM form_fields WHERE field_name = 'agent_employment_type' AND section_id = @sec4 LIMIT 1;

-- Loan Requirement Details section
SET @sec5 = (SELECT id FROM form_sections WHERE form_id = @agentFormId AND name = 'Loan Requirement Details' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `default_value`, `display_order`, `created_at`) VALUES
(@sec5, 'agent_loan_amount', 'Loan Amount Required', 'decimal', 1, 'Enter loan amount', '', 1, NOW()),
(@sec5, 'agent_tenure', 'Tenure (months)', 'number', 0, 'Loan tenure in months', '', 2, NOW()),
(@sec5, 'agent_existing_la', 'Existing Loan Amount', 'decimal', 0, 'Current outstanding loans', '', 3, NOW()),
(@sec5, 'agent_loan_purpose', 'Purpose of Loan', 'textarea', 0, 'Why do you need this loan?', '', 4, NOW()),
(@sec5, 'agent_preferred_bank', 'Preferred Bank', 'text', 0, 'Bank preference', '', 5, NOW()),
(@sec5, 'agent_previous_rejection', 'Any Previous Rejection?', 'radio', 0, '', '', 6, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'agent_previous_rejection' AND section_id = @sec5 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'agent_previous_rejection' AND section_id = @sec5 LIMIT 1;

-- Salary Information section
SET @sec6 = (SELECT id FROM form_sections WHERE form_id = @agentFormId AND name = 'Salary Information' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `default_value`, `display_order`, `created_at`) VALUES
(@sec6, 'agent_net_salary', 'Net Salary (Take Home)', 'decimal', 0, 'Monthly take-home salary', '', 1, NOW()),
(@sec6, 'agent_gross_salary', 'Gross Salary', 'decimal', 0, 'Monthly gross salary', '', 2, NOW()),
(@sec6, 'agent_salary_mode', 'Salary Mode', 'dropdown', 0, '', '', 3, NOW()),
(@sec6, 'agent_bank_name', 'Salary Account Bank', 'text', 0, 'Bank where salary is credited', '', 4, NOW()),
(@sec6, 'agent_account_number', 'Account Number', 'text', 0, 'Bank account number', '', 5, NOW()),
(@sec6, 'agent_itr_filed', 'ITR Filed?', 'radio', 0, '', '', 6, NOW()),
(@sec6, 'agent_itr_years', 'ITR Years Available', 'text', 0, 'e.g., 3 years', '', 7, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Bank Transfer', 'bank_transfer', 1 FROM form_fields WHERE field_name = 'agent_salary_mode' AND section_id = @sec6 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Cheque', 'cheque', 2 FROM form_fields WHERE field_name = 'agent_salary_mode' AND section_id = @sec6 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Cash', 'cash', 3 FROM form_fields WHERE field_name = 'agent_salary_mode' AND section_id = @sec6 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'agent_itr_filed' AND section_id = @sec6 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'agent_itr_filed' AND section_id = @sec6 LIMIT 1;

-- Credit Information section
SET @sec7 = (SELECT id FROM form_sections WHERE form_id = @agentFormId AND name = 'Credit Information' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `default_value`, `display_order`, `created_at`) VALUES
(@sec7, 'agent_cibil_score', 'CIBIL Score (if known)', 'number', 0, '300-900', '', 1, NOW()),
(@sec7, 'agent_existing_credit_cards', 'Number of Credit Cards', 'number', 0, '', '', 2, NOW()),
(@sec7, 'agent_total_credit_limit', 'Total Credit Card Limit', 'decimal', 0, 'Combined credit limit', '', 3, NOW()),
(@sec7, 'agent_utilized_amount', 'Utilized Credit Amount', 'decimal', 0, 'Amount currently utilized', '', 4, NOW()),
(@sec7, 'agent_other_loans', 'Other Active Loans', 'textarea', 0, 'List existing loans and EMIs', '', 5, NOW()),
(@sec7, 'agent_total_emi', 'Total Monthly EMI', 'decimal', 0, 'Sum of all EMIs', '', 6, NOW()),
(@sec7, 'agent_has_default', 'Any Loan Defaults?', 'radio', 0, '', '', 7, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'agent_has_default' AND section_id = @sec7 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'agent_has_default' AND section_id = @sec7 LIMIT 1;

-- Documents section
SET @sec8 = (SELECT id FROM form_sections WHERE form_id = @agentFormId AND name = 'Documents' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `default_value`, `display_order`, `created_at`) VALUES
(@sec8, 'agent_pan_card', 'PAN Card', 'file', 0, '', '', 1, NOW()),
(@sec8, 'agent_aadhar_card', 'Aadhar Card', 'file', 0, '', '', 2, NOW()),
(@sec8, 'agent_salary_slips', 'Salary Slips (3 months)', 'file', 0, '', '', 3, NOW()),
(@sec8, 'agent_bank_statements', 'Bank Statements (6 months)', 'file', 0, '', '', 4, NOW()),
(@sec8, 'agent_itr_documents', 'ITR Documents', 'file', 0, '', '', 5, NOW()),
(@sec8, 'agent_photo', 'Customer Photo', 'image', 0, '', '', 6, NOW());

-- Remarks section
SET @sec9 = (SELECT id FROM form_sections WHERE form_id = @agentFormId AND name = 'Remarks' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `default_value`, `display_order`, `created_at`) VALUES
(@sec9, 'agent_remark', 'Agent Remarks', 'textarea', 0, 'Any additional notes about this lead...', '', 1, NOW()),
(@sec9, 'agent_source_of_lead', 'Source of Lead', 'dropdown', 0, '', '', 2, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Walk-in', 'walkin', 1 FROM form_fields WHERE field_name = 'agent_source_of_lead' AND section_id = @sec9 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Referral', 'referral', 2 FROM form_fields WHERE field_name = 'agent_source_of_lead' AND section_id = @sec9 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Online', 'online', 3 FROM form_fields WHERE field_name = 'agent_source_of_lead' AND section_id = @sec9 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Telecall', 'telecall', 4 FROM form_fields WHERE field_name = 'agent_source_of_lead' AND section_id = @sec9 LIMIT 1;

-- Agent form role access
INSERT INTO `form_role_access` (`form_id`, `role_id`)
SELECT @agentFormId, id FROM roles WHERE name IN ('admin', 'agent', 'team_leader');


-- ============================================================
-- FORM 2: PRE-LOGIN CHECKLIST
-- ============================================================
INSERT INTO `forms` (`name`, `code`, `description`, `assigned_role`, `workflow_stage`, `status`, `created_at`) VALUES
('Pre-Login Checklist', 'PRE_LOGIN_CHECKLIST', 'Pre-Login verification checklist for Login Agent', 'login_agent', 'LOGIN_AGENT_DRAFT', 'active', NOW());

SET @preLoginFormId = LAST_INSERT_ID();

-- Sections
INSERT INTO `form_sections` (`form_id`, `name`, `description`, `display_order`, `created_at`) VALUES
(@preLoginFormId, 'Customer Details Verification', 'Verify customer identity and contact details', 1, NOW()),
(@preLoginFormId, 'KYC Documents', 'KYC document verification', 2, NOW()),
(@preLoginFormId, 'Income Documents Verification', 'Income and salary document checks', 3, NOW()),
(@preLoginFormId, 'Banking & Financial Checks', 'Bank account and CIBIL verification', 4, NOW()),
(@preLoginFormId, 'Internal Verification', 'Internal eligibility and policy checks', 5, NOW()),
(@preLoginFormId, 'Final Readiness', 'Final checks before login', 6, NOW());

-- Section 1: Customer Details
SET @plSec1 = (SELECT id FROM form_sections WHERE form_id = @preLoginFormId AND name = 'Customer Details Verification' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `display_order`, `created_at`) VALUES
(@plSec1, 'login_customer_name_match', 'Customer Name Matches ID Proof', 'radio', 1, '', 1, NOW()),
(@plSec1, 'login_mobile_verified', 'Mobile Number Verified', 'radio', 1, '', 2, NOW()),
(@plSec1, 'login_address_verified', 'Address Verified', 'radio', 0, '', 3, NOW()),
(@plSec1, 'login_customer_contact_remark', 'Customer Contact Remark', 'textarea', 0, '', 4, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_customer_name_match' AND section_id = @plSec1 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_customer_name_match' AND section_id = @plSec1 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_mobile_verified' AND section_id = @plSec1 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_mobile_verified' AND section_id = @plSec1 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_address_verified' AND section_id = @plSec1 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_address_verified' AND section_id = @plSec1 LIMIT 1;

-- Section 2: KYC Documents
SET @plSec2 = (SELECT id FROM form_sections WHERE form_id = @preLoginFormId AND name = 'KYC Documents' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `display_order`, `created_at`) VALUES
(@plSec2, 'login_pan_verified', 'PAN Card Verified', 'radio', 1, '', 1, NOW()),
(@plSec2, 'login_aadhar_verified', 'Aadhar Card Verified', 'radio', 1, '', 2, NOW()),
(@plSec2, 'login_photo_verified', 'Customer Photo Matches', 'radio', 0, '', 3, NOW()),
(@plSec2, 'login_signature_match', 'Signature Matches', 'radio', 0, '', 4, NOW()),
(@plSec2, 'login_kyc_remark', 'KYC Verification Remark', 'textarea', 0, '', 5, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_pan_verified' AND section_id = @plSec2 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_pan_verified' AND section_id = @plSec2 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_aadhar_verified' AND section_id = @plSec2 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_aadhar_verified' AND section_id = @plSec2 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_photo_verified' AND section_id = @plSec2 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_photo_verified' AND section_id = @plSec2 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_signature_match' AND section_id = @plSec2 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_signature_match' AND section_id = @plSec2 LIMIT 1;

-- Section 3: Income Documents
SET @plSec3 = (SELECT id FROM form_sections WHERE form_id = @preLoginFormId AND name = 'Income Documents Verification' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `display_order`, `created_at`) VALUES
(@plSec3, 'login_salary_slips_verified', 'Salary Slips Verified (3 months)', 'radio', 1, '', 1, NOW()),
(@plSec3, 'login_bank_statements_verified', 'Bank Statements Verified (6 months)', 'radio', 1, '', 2, NOW()),
(@plSec3, 'login_itr_verified', 'ITR Documents Verified', 'radio', 0, '', 3, NOW()),
(@plSec3, 'login_income_consistent', 'Income is Consistent', 'radio', 0, '', 4, NOW()),
(@plSec3, 'login_income_remark', 'Income Verification Remark', 'textarea', 0, '', 5, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_salary_slips_verified' AND section_id = @plSec3 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_salary_slips_verified' AND section_id = @plSec3 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_bank_statements_verified' AND section_id = @plSec3 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_bank_statements_verified' AND section_id = @plSec3 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_itr_verified' AND section_id = @plSec3 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_itr_verified' AND section_id = @plSec3 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_income_consistent' AND section_id = @plSec3 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_income_consistent' AND section_id = @plSec3 LIMIT 1;

-- Section 4: Banking & Financial Checks
SET @plSec4 = (SELECT id FROM form_sections WHERE form_id = @preLoginFormId AND name = 'Banking & Financial Checks' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `display_order`, `created_at`) VALUES
(@plSec4, 'login_cibil_score', 'CIBIL Score', 'number', 1, '', 1, NOW()),
(@plSec4, 'login_cibil_report_verified', 'CIBIL Report Verified', 'radio', 1, '', 2, NOW()),
(@plSec4, 'login_no_dishonor', 'No Cheque Bounce / Dishonor', 'radio', 1, '', 3, NOW()),
(@plSec4, 'login_existing_loans_checked', 'Existing Loans Checked', 'radio', 0, '', 4, NOW()),
(@plSec4, 'login_fraud_check', 'No Fraud Alert', 'radio', 0, '', 5, NOW()),
(@plSec4, 'login_banking_remark', 'Banking Check Remark', 'textarea', 0, '', 6, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_cibil_report_verified' AND section_id = @plSec4 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_cibil_report_verified' AND section_id = @plSec4 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_no_dishonor' AND section_id = @plSec4 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_no_dishonor' AND section_id = @plSec4 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_existing_loans_checked' AND section_id = @plSec4 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_existing_loans_checked' AND section_id = @plSec4 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_fraud_check' AND section_id = @plSec4 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_fraud_check' AND section_id = @plSec4 LIMIT 1;

-- Section 5: Internal Verification
SET @plSec5 = (SELECT id FROM form_sections WHERE form_id = @preLoginFormId AND name = 'Internal Verification' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `display_order`, `created_at`) VALUES
(@plSec5, 'login_eligibility_check', 'Eligibility Check Completed', 'radio', 1, '', 1, NOW()),
(@plSec5, 'login_eligible_amount', 'Eligible Amount', 'decimal', 0, '', 2, NOW()),
(@plSec5, 'login_eligible_tenure', 'Eligible Tenure (months)', 'number', 0, '', 3, NOW()),
(@plSec5, 'login_bank_selection', 'Selected Bank for Login', 'dropdown', 0, '', 4, NOW()),
(@plSec5, 'login_internal_remark', 'Internal Verification Remark', 'textarea', 0, '', 5, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_eligibility_check' AND section_id = @plSec5 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_eligibility_check' AND section_id = @plSec5 LIMIT 1;

-- Section 6: Final Readiness
SET @plSec6 = (SELECT id FROM form_sections WHERE form_id = @preLoginFormId AND name = 'Final Readiness' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `display_order`, `created_at`) VALUES
(@plSec6, 'login_all_documents_collected', 'All Documents Collected', 'radio', 1, '', 1, NOW()),
(@plSec6, 'login_form_ready_for_login', 'Form Ready for Bank Login', 'radio', 1, '', 2, NOW()),
(@plSec6, 'login_customer_aware', 'Customer Aware of Terms', 'radio', 0, '', 3, NOW()),
(@plSec6, 'login_final_remark', 'Final Remark', 'textarea', 0, '', 4, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_all_documents_collected' AND section_id = @plSec6 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_all_documents_collected' AND section_id = @plSec6 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_form_ready_for_login' AND section_id = @plSec6 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_form_ready_for_login' AND section_id = @plSec6 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Yes', 'yes', 1 FROM form_fields WHERE field_name = 'login_customer_aware' AND section_id = @plSec6 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'No', 'no', 2 FROM form_fields WHERE field_name = 'login_customer_aware' AND section_id = @plSec6 LIMIT 1;

-- Pre-login form role access
INSERT INTO `form_role_access` (`form_id`, `role_id`)
SELECT @preLoginFormId, id FROM roles WHERE name IN ('admin', 'login_agent');


-- ============================================================
-- FORM 3: POST-LOGIN FORM
-- ============================================================
INSERT INTO `forms` (`name`, `code`, `description`, `assigned_role`, `workflow_stage`, `status`, `created_at`) VALUES
('Post-Login Form', 'POST_LOGIN_FORM', 'Post-login banking and loan details', 'login_agent', 'POST_LOGIN', 'active', NOW());

SET @postLoginFormId = LAST_INSERT_ID();

-- Sections
INSERT INTO `form_sections` (`form_id`, `name`, `description`, `display_order`, `created_at`) VALUES
(@postLoginFormId, 'Loan & Banking Details', 'Loan disbursement and banking details', 1, NOW()),
(@postLoginFormId, 'Login Processing Details', 'Bank login processing information', 2, NOW()),
(@postLoginFormId, 'Post-Login Remarks', 'Additional notes', 3, NOW());

-- Loan & Banking Details
SET @plSec7 = (SELECT id FROM form_sections WHERE form_id = @postLoginFormId AND name = 'Loan & Banking Details' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `display_order`, `created_at`) VALUES
(@plSec7, 'login_approved_amount', 'Approved Loan Amount', 'decimal', 1, '', 1, NOW()),
(@plSec7, 'login_approved_tenure', 'Approved Tenure (months)', 'number', 1, '', 2, NOW()),
(@plSec7, 'login_roi', 'Rate of Interest (%)', 'decimal', 0, '', 3, NOW()),
(@plSec7, 'login_emi_amount', 'Monthly EMI', 'decimal', 0, '', 4, NOW()),
(@plSec7, 'login_external_pos_amount', 'External POS Amount', 'decimal', 0, '', 5, NOW()),
(@plSec7, 'login_internal_pos_amount', 'Internal POS Amount', 'decimal', 0, '', 6, NOW()),
(@plSec7, 'login_net_amount', 'Net Disbursement Amount', 'decimal', 0, '', 7, NOW()),
(@plSec7, 'login_loan_type', 'Loan Type', 'dropdown', 0, '', 8, NOW()),
(@plSec7, 'login_bt_bank', 'BT Bank (Balance Transfer)', 'text', 0, '', 9, NOW()),
(@plSec7, 'login_login_bank', 'Login Bank', 'text', 1, '', 10, NOW()),
(@plSec7, 'login_disbursement_mode', 'Disbursement Mode', 'dropdown', 0, '', 11, NOW());

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'New Loan', 'new_loan', 1 FROM form_fields WHERE field_name = 'login_loan_type' AND section_id = @plSec7 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Balance Transfer', 'balance_transfer', 2 FROM form_fields WHERE field_name = 'login_loan_type' AND section_id = @plSec7 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Top-up', 'topup', 3 FROM form_fields WHERE field_name = 'login_loan_type' AND section_id = @plSec7 LIMIT 1;

INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'NEFT/RTGS', 'neft_rtgs', 1 FROM form_fields WHERE field_name = 'login_disbursement_mode' AND section_id = @plSec7 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Cheque', 'cheque', 2 FROM form_fields WHERE field_name = 'login_disbursement_mode' AND section_id = @plSec7 LIMIT 1;
INSERT INTO `form_field_options` (`field_id`, `label`, `value`, `display_order`)
SELECT id, 'Direct Credit', 'direct_credit', 3 FROM form_fields WHERE field_name = 'login_disbursement_mode' AND section_id = @plSec7 LIMIT 1;

-- Login Processing Details
SET @plSec8 = (SELECT id FROM form_sections WHERE form_id = @postLoginFormId AND name = 'Login Processing Details' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `display_order`, `created_at`) VALUES
(@plSec8, 'login_reference_number', 'Bank Reference Number', 'text', 0, '', 1, NOW()),
(@plSec8, 'login_processing_date', 'Login Processing Date', 'date', 0, '', 2, NOW()),
(@plSec8, 'login_expected_disbursement', 'Expected Disbursement Date', 'date', 0, '', 3, NOW()),
(@plSec8, 'login_bank_contact_person', 'Bank Contact Person', 'text', 0, '', 4, NOW()),
(@plSec8, 'login_bank_contact_number', 'Bank Contact Number', 'mobile', 0, '', 5, NOW());

-- Post-Login Remarks
SET @plSec9 = (SELECT id FROM form_sections WHERE form_id = @postLoginFormId AND name = 'Post-Login Remarks' LIMIT 1);

INSERT INTO `form_fields` (`section_id`, `field_name`, `label`, `type`, `required`, `placeholder`, `display_order`, `created_at`) VALUES
(@plSec9, 'login_post_remark', 'Login Agent Remarks', 'textarea', 0, 'Any additional notes...', 1, NOW());

-- Post-login form role access
INSERT INTO `form_role_access` (`form_id`, `role_id`)
SELECT @postLoginFormId, id FROM roles WHERE name IN ('admin', 'login_agent');


-- ============================================================
-- SEED WORKFLOW TRANSITIONS
-- ============================================================
INSERT INTO `workflow_transitions` (`from_stage`, `to_stage`, `action`, `allowed_roles`, `requires_remark`, `display_order`) VALUES
('LEAD_UPLOADED', 'LEAD_ASSIGNED', 'assign', 'admin', 0, 1),
('LEAD_ASSIGNED', 'AGENT_DRAFT', 'save_draft', 'agent', 0, 2),
('AGENT_DRAFT', 'AGENT_SUBMITTED', 'form_submitted', 'agent', 0, 3),
('AGENT_SUBMITTED', 'ADMIN_REVIEW_1', 'form_submitted', 'system', 0, 4),
('ADMIN_REVIEW_1', 'LOGIN_AGENT_ASSIGNED', 'approve', 'admin', 0, 5),
('ADMIN_REVIEW_1', 'LEAD_ASSIGNED', 'reassign', 'admin', 0, 6),
('ADMIN_REVIEW_1', 'REJECTED', 'reject', 'admin', 1, 7),
('LOGIN_AGENT_ASSIGNED', 'LOGIN_AGENT_DRAFT', 'save_draft', 'login_agent', 0, 8),
('LOGIN_AGENT_DRAFT', 'ADMIN_REVIEW_2', 'checklist_submitted', 'login_agent', 0, 9),
('ADMIN_REVIEW_2', 'LOGIN_APPROVED', 'approve', 'admin', 0, 10),
('ADMIN_REVIEW_2', 'RETURNED_TO_AGENT', 'send_back', 'admin', 1, 11),
('ADMIN_REVIEW_2', 'REJECTED', 'reject', 'admin', 1, 12),
('LOGIN_APPROVED', 'POST_LOGIN', 'post_login', 'login_agent', 0, 13),
('POST_LOGIN', 'UNDERWRITING', 'send_to_underwriting', 'admin', 0, 14),
('UNDERWRITING', 'UNDERWRITING_APPROVED', 'approve', 'underwriting', 0, 15),
('UNDERWRITING', 'UNDERWRITING_REJECTED', 'reject', 'underwriting', 1, 16),
('UNDERWRITING_APPROVED', 'DISPATCH', 'assign_dispatch', 'system', 0, 17),
('DISPATCH', 'COMPLETED', 'complete', 'dispatch', 0, 18);
