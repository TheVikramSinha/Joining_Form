-- ============================================================
-- setup_v3_patch.sql — Run this IN ADDITION to setup.sql
-- Adds field configuration and extended colour settings
-- ============================================================

-- Field configuration table
CREATE TABLE IF NOT EXISTS field_config (
    field_key       VARCHAR(100) PRIMARY KEY,
    section         VARCHAR(100)  NOT NULL,
    original_label  VARCHAR(200)  NOT NULL,
    custom_label    VARCHAR(200)  DEFAULT NULL,
    status          ENUM('required','optional','hidden') DEFAULT 'optional',
    display_order   INT           DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Populate all form fields with defaults
INSERT IGNORE INTO field_config (field_key, section, original_label, status, display_order) VALUES
-- Section 1: Personal
('first_name',        'Personal', 'First Name',                    'required', 1),
('middle_name',       'Personal', 'Middle Name',                   'optional', 2),
('last_name',         'Personal', 'Last Name',                     'required', 3),
('date_of_birth',     'Personal', 'Date of Birth',                 'optional', 4),
('parent_name',       'Personal', 'Father / Mother / Husband Name','optional', 5),
('gender',            'Personal', 'Gender',                        'optional', 6),
('marital_status',    'Personal', 'Marital Status',                'optional', 7),
('photo',             'Personal', 'Passport Photo',                'optional', 8),

-- Section 2: Address
('present_address',   'Address', 'Present Address',                'optional', 1),
('present_state',     'Address', 'Present State',                  'optional', 2),
('present_district',  'Address', 'Present District',               'optional', 3),
('present_phone',     'Address', 'Present Phone',                  'optional', 4),
('email',             'Address', 'Email Address',                  'optional', 5),
('permanent_address', 'Address', 'Permanent Address',              'optional', 6),
('permanent_state',   'Address', 'Permanent State',                'optional', 7),
('permanent_district','Address', 'Permanent District',             'optional', 8),
('permanent_phone',   'Address', 'Permanent Phone',                'optional', 9),

-- Section 3: Joining
('date_of_appointment',      'Joining', 'Date of Appointment',       'optional',  1),
('date_of_joining',          'Joining', 'Date of Joining',           'required',  2),
('office_at_initial_joining','Joining', 'Office at Initial Joining', 'optional',  3),
('initial_designation',      'Joining', 'Initial Designation',       'required',  4),
('mode_of_recruitment',      'Joining', 'Mode of Recruitment',       'optional',  5),

-- Section 4: Salary
('basic_pay',         'Salary', 'Basic Pay',              'optional', 1),
('bank_name',         'Salary', 'Bank Name',              'optional', 2),
('ifsc_code',         'Salary', 'IFSC Code',              'optional', 3),
('account_no',        'Salary', 'Account Number',         'optional', 4),
('pan_card',          'Salary', 'PAN Card Number',        'optional', 5),
('commitment_person', 'Salary', 'Commitment Made By',     'optional', 6),
('commitment_text',   'Salary', 'Commitment Details',     'optional', 7),

-- Section 5-7: Dynamic table sections (section-level toggle)
('section_education', 'Education', 'Education Section',   'optional', 1),
('section_training',  'Training',  'Training Section',    'optional', 1),
('section_family',    'Family',    'Family Section',       'optional', 1),
('section_service',   'Service',   'Service History Section','optional',1),

-- Section 8: Nomination
('nominee_name',              'Nomination', 'Nominee Name',          'optional', 1),
('nominee_relation',          'Nomination', 'Nominee Relation',      'optional', 2),
('nominee_age',               'Nomination', 'Nominee Age',           'optional', 3),
('nominee_address',           'Nomination', 'Nominee Address',       'optional', 4),
('nominee_state',             'Nomination', 'Nominee State',         'optional', 5),
('nominee_block',             'Nomination', 'Nominee Block',         'optional', 6),
('nominee_district',          'Nomination', 'Nominee District',      'optional', 7),
('reference_1',               'Nomination', 'Reference 1',           'optional', 8),
('reference_2',               'Nomination', 'Reference 2',           'optional', 9),
('emergency_contact_name',    'Nomination', 'Emergency Contact Name','optional', 10),
('emergency_address',         'Nomination', 'Emergency Address',     'optional', 11),
('emergency_phone',           'Nomination', 'Emergency Phone',       'optional', 12),

-- Section 9: Documents
('docs_certificates', 'Documents', 'Certificates / Mark Sheets', 'optional', 1),
('docs_dob',          'Documents', 'Proof of Date of Birth',      'optional', 2),
('docs_experience',   'Documents', 'Experience Certificate',      'optional', 3),
('docs_relieving',    'Documents', 'Relieving Letter',            'optional', 4);

-- Add secondary and accent colour settings
INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
('brand_secondary', '#0f172a'),
('brand_accent',    '#f59e0b');

-- Rename existing primary key for clarity
INSERT IGNORE INTO app_settings (setting_key, setting_value)
SELECT 'brand_primary', setting_value FROM app_settings WHERE setting_key = 'brand_color'
ON DUPLICATE KEY UPDATE setting_value = setting_value;
