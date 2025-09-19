<?php
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

class local_customapi_external extends external_api {

    /**
     * 1. Create Tenant
     */
    public static function create_tenant_parameters() {
        return new external_function_parameters([
            'domain'       => new external_value(PARAM_TEXT, 'Tenant ID (idnumber)', VALUE_REQUIRED),
            'company_name' => new external_value(PARAM_TEXT, 'Tenant Name', VALUE_REQUIRED),
        ]);
    }

    public static function create_tenant($domain, $company_name) {
        global $DB, $CFG;

        // Validate parameters.
        $params = self::validate_parameters(self::create_tenant_parameters(), compact('domain', 'company_name'));

        // Security: system context, require tenancy admin.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('tool/mutenancy:admin', $context);

        // Load optional libs.
        // Cohort functions live in cohort/lib.php.
        require_once($CFG->dirroot . '/cohort/lib.php');
        // Course category API.
        require_once($CFG->dirroot . '/course/lib.php');

        // Try to include tool_mutenancy if available.
        $mutenancyavailable = false;
        $mutenancylib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (file_exists($mutenancylib)) {
            require_once($mutenancylib);
            $mutenancyavailable = class_exists('\\tool_mutenancy\\manager');
        }

        // Basic validation: ensure domain (idnumber) is unique in tool_mutenancy_tenant if table exists.
        if ($DB->get_manager()->table_exists('tool_mutenancy_tenant')) {
            $sql = 'SELECT id FROM {tool_mutenancy_tenant} WHERE ' . $DB->sql_compare_text('idnumber') . ' = :idnumber';
            if ($DB->record_exists_sql($sql, ['idnumber' => $domain])) {
                throw new moodle_exception('duplicateidnumber', 'local_customapi', '', $domain);
            }
        }

        // 1) Create a course category for the tenant.
        $categorydata = (object)[
            'name'     => $company_name,
            'idnumber' => $domain,
            'parent'   => 0,
        ];
        // core_course_category::create will throw if invalid.
        $category = core_course_category::create($categorydata);

        // 2) Create a cohort for tenant (system context by default).
        // cohort_add_cohort expects an object with: name, idnumber, contextid, description, descriptionformat, visible
        $cohortdata = (object)[
            'name'              => $company_name . ' Cohort',
            'idnumber'          => $domain . '_cohort',
            'contextid'         => context_system::instance()->id,
            'description'       => 'Cohort for tenant ' . $company_name,
            'descriptionformat' => FORMAT_HTML,
            'timecreated'       => time(),
        ];

        // Check if a cohort with same idnumber already exists in that context to avoid duplicates:
        if ($existing = $DB->get_record('cohort', ['idnumber' => $cohortdata->idnumber, 'contextid' => $cohortdata->contextid])) {
            $cohortid = $existing->id;
        } else {
            // cohort_add_cohort() is provided by cohort/lib.php; we required that above.
            $cohortid = cohort_add_cohort($cohortdata);
        }

        // 3) Insert tenant record: prefer tool_mutenancy API if present, otherwise insert directly (defensive).
        $tenantid = 0;
        if ($mutenancyavailable) {
            $manager = new \tool_mutenancy\manager();
            if (method_exists($manager, 'create_tenant')) {
                // If manager provides create_tenant, use it (API may differ per plugin version).
                $newtenant = $manager->create_tenant((object)[
                    'name' => $company_name,
                    'idnumber' => $domain,
                    'categoryid' => $category->id,
                    'cohortid' => $cohortid,
                ]);
                // manager::create_tenant may return object or id; handle both.
                if (is_object($newtenant) && isset($newtenant->id)) {
                    $tenantid = $newtenant->id;
                } else if (is_int($newtenant)) {
                    $tenantid = $newtenant;
                } else if (isset($newtenant->tenantid)) {
                    $tenantid = $newtenant->tenantid;
                }
            }
        }

        // Fallback: direct DB insert if plugin API not available or didn't return id.
        if (empty($tenantid)) {
            if (!$DB->get_manager()->table_exists('tool_mutenancy_tenant')) {
                throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
            }
            $tenantdata = (object)[
                'name' => $company_name,
                'idnumber' => $domain,
                'categoryid' => $category->id,
                'cohortid' => $cohortid,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $tenantid = $DB->insert_record('tool_mutenancy_tenant', $tenantdata);
        }

        return ['tenant_id' => (int)$tenantid];
    }

    public static function create_tenant_returns() {
        return new external_single_structure([
            'tenant_id' => new external_value(PARAM_INT, 'New tenant ID'),
        ]);
    }

    /**
     * 2. Create User Against Tenant
     */
    public static function create_user_in_tenant_parameters() {
        return new external_function_parameters([
            'email' => new external_value(PARAM_EMAIL, 'User email', VALUE_REQUIRED),
            'tenant' => new external_value(PARAM_INT, 'Tenant ID', VALUE_REQUIRED),
            'role_id' => new external_value(PARAM_INT, 'Role ID', VALUE_REQUIRED),
        ]);
    }

    public static function create_user_in_tenant($email, $tenant, $role_id) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::create_user_in_tenant_parameters(), compact('email', 'tenant', 'role_id'));

        $context = context_system::instance();
        self::validate_context($context);

        // Capabilities: creating users and tenancy management.
        require_capability('moodle/user:create', $context);
        require_capability('tool/mutenancy:admin', $context);

        // Ensure user lib is available.
        require_once($CFG->dirroot . '/user/lib.php');

        // Check mutenancy plugin availability.
        $mutenancylib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancylib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancylib);

        $manager = new \tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$manager->get_tenant($tenant)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        // Create user data (you should ideally expand firstname/lastname etc. as params)
        $userdata = (object)[
            'username' => $email,
            'email' => $email,
            'firstname' => 'Tenant',
            'lastname' => 'Admin',
            'password' => generate_custom_password(12),
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id ?? 1,
        ];

        // user_create_user returns the new user id.
        $userid = user_create_user($userdata);

        // allocate to tenant: tenancy class might provide allocate_user() static method.
        if (!class_exists('\\tool_mutenancy\\tenancy') ||
            !method_exists('\\tool_mutenancy\\tenancy', 'allocate_user')) {
            // Try manager-based allocation if available
            if (method_exists($manager, 'allocate_user')) {
                $manager->allocate_user($userid, $tenant);
            } else {
                throw new moodle_exception('mutenancymethodmissing', 'local_customapi', '', 'allocate_user');
            }
        } else {
            \tool_mutenancy\tenancy::allocate_user($userid, $tenant);
        }

        // Assign role to the tenant's category context (tenant manager probably expects this).
        $tenantobj = $manager->get_tenant($tenant);
        if (empty($tenantobj->categoryid)) {
            throw new moodle_exception('tenantnoconfiguredcategory', 'local_customapi');
        }
        $contextcat = context_coursecat::instance($tenantobj->categoryid);
        role_assign($role_id, $userid, $contextcat->id);

        return ['user_id' => (int)$userid];
    }

    public static function create_user_in_tenant_returns() {
        return new external_single_structure([
            'user_id' => new external_value(PARAM_INT, 'New user ID'),
        ]);
    }

    /**
     * 3. Add Existing User to Tenant
     */
    public static function add_user_to_tenant_parameters() {
        return new external_function_parameters([
            'user_id' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
            'tenant_id' => new external_value(PARAM_INT, 'Tenant ID', VALUE_REQUIRED),
        ]);
    }

    public static function add_user_to_tenant($user_id, $tenant_id) {
        global $DB, $CFG;

        self::validate_parameters(self::add_user_to_tenant_parameters(), compact('user_id', 'tenant_id'));

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('tool/mutenancy:admin', $context);

        $mutenancylib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancylib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancylib);

        $manager = new \tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$manager->get_tenant($tenant_id)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }
        if (!$DB->record_exists('user', ['id' => $user_id, 'deleted' => 0])) {
            throw new moodle_exception('usernotfound', 'local_customapi');
        }

        if (class_exists('\\tool_mutenancy\\tenancy') && method_exists('\\tool_mutenancy\\tenancy', 'allocate_user')) {
            \tool_mutenancy\tenancy::allocate_user($user_id, $tenant_id);
        } else if (method_exists($manager, 'allocate_user')) {
            $manager->allocate_user($user_id, $tenant_id);
        } else {
            throw new moodle_exception('mutenancymethodmissing', 'local_customapi', '', 'allocate_user');
        }

        return ['success' => true];
    }

    public static function add_user_to_tenant_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
        ]);
    }

    /**
     * 4. Fetch All Roles in tenant context
     */
    public static function get_tenant_roles_parameters() {
        return new external_function_parameters([
            'tenant_id' => new external_value(PARAM_INT, 'Tenant ID', VALUE_REQUIRED),
        ]);
    }

    public static function get_tenant_roles($tenant_id) {
        global $CFG;

        self::validate_parameters(self::get_tenant_roles_parameters(), compact('tenant_id'));

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/role:manage', $context);

        $mutenancylib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancylib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancylib);

        $manager = new \tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$tenant = $manager->get_tenant($tenant_id)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        $contextcat = context_coursecat::instance($tenant->categoryid);
        $roles = role_get_assignable_roles($contextcat);

        $result = [];
        foreach ($roles as $id => $name) {
            $result[] = ['id' => (int)$id, 'name' => $name];
        }
        return $result;
    }

    public static function get_tenant_roles_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Role ID'),
                'name' => new external_value(PARAM_TEXT, 'Role name'),
            ])
        );
    }

    /**
     * 5. Fetch Courses by Tenant
     */
    public static function get_tenant_courses_parameters() {
        return new external_function_parameters([
            'tenant_id' => new external_value(PARAM_INT, 'Tenant ID', VALUE_REQUIRED),
        ]);
    }

    public static function get_tenant_courses($tenant_id) {
        global $CFG, $DB;

        self::validate_parameters(self::get_tenant_courses_parameters(), compact('tenant_id'));

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/course:view', $context);

        $mutenancylib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancylib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancylib);

        $manager = new \tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$tenant = $manager->get_tenant($tenant_id)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        $coursesdata = core_course_get_courses_by_field('category', $tenant->categoryid);
        $result = [];
        if (!empty($coursesdata->courses)) {
            foreach ($coursesdata->courses as $course) {
                $result[] = [
                    'id' => (int)$course->id,
                    'fullname' => $course->fullname,
                    'shortname' => $course->shortname,
                ];
            }
        }
        return $result;
    }

    public static function get_tenant_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
            ])
        );
    }

    /**
     * 6. Fetch Courses by Tenant and User
     */
    public static function get_tenant_user_courses_parameters() {
        return new external_function_parameters([
            'tenant_id' => new external_value(PARAM_INT, 'Tenant ID', VALUE_REQUIRED),
            'user_id' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
        ]);
    }

    public static function get_tenant_user_courses($tenant_id, $user_id) {
        global $DB, $CFG;

        self::validate_parameters(self::get_tenant_user_courses_parameters(), compact('tenant_id', 'user_id'));

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/course:view', $context);

        $mutenancylib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancylib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancylib);

        $manager = new \tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$manager->get_tenant($tenant_id)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }
        if (!$DB->record_exists('user', ['id' => $user_id, 'deleted' => 0])) {
            throw new moodle_exception('usernotfound', 'local_customapi');
        }

        // Check membership
        if (class_exists('\\tool_mutenancy\\tenancy') && method_exists('\\tool_mutenancy\\tenancy', 'get_user_tenant')) {
            if (\tool_mutenancy\tenancy::get_user_tenant($user_id) != $tenant_id) {
                throw new moodle_exception('usernotintenant', 'local_customapi');
            }
        } else if (method_exists($manager, 'get_user_tenant')) {
            if ($manager->get_user_tenant($user_id) != $tenant_id) {
                throw new moodle_exception('usernotintenant', 'local_customapi');
            }
        } else {
            // As a last resort, skip strict check - but better to require tenancy APIs.
        }

        $courses = enrol_get_users_courses($user_id);
        $result = [];
        foreach ($courses as $course) {
            $result[] = [
                'id' => (int)$course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
            ];
        }
        return $result;
    }

    public static function get_tenant_user_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
            ])
        );
    }
}

/**
 * Helper function to generate a secure password.
 */
function generate_custom_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}
