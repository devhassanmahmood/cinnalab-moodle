<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/externallib.php');

class local_customapi_external extends external_api {

    /**
     * 1. Create Tenant
     */
    public static function create_tenant_parameters() {
        return new external_function_parameters([
            'domain' => new external_value(PARAM_TEXT, 'Tenant ID (idnumber)', VALUE_REQUIRED),
            'company_name' => new external_value(PARAM_TEXT, 'Tenant Name', VALUE_REQUIRED),
        ]);
    }

    public static function create_tenant($domain, $company_name) {
        global $DB, $CFG;
        self::validate_parameters(self::create_tenant_parameters(), compact('domain', 'company_name'));

        // Require tenant management capability.
        self::validate_context(context_system::instance());
        require_capability('tool/mutenancy:admin', context_system::instance());

        // Check for tool_mutenancy dependency.
        $mutenancy_lib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        //echo $mutenancy_lib; exit;
        /*if (!file_exists($mutenancy_lib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }*/
        require_once($mutenancy_lib);

        // Validate multi-tenancy is active.
        /*if (!$DB->record_exists('config_plugins', ['plugin' => 'tool_mutenancy', 'name' => 'active', 'value' => '1'])) {
            throw new moodle_exception('multitenancynotactive', 'local_customapi');
        }

        // Validate unique idnumber.
        $sql = 'SELECT id FROM {tool_mutenancy_tenant} WHERE ' . $DB->sql_compare_text('idnumber') . ' = :idnumber';
        if ($DB->record_exists_sql($sql, ['idnumber' => $domain])) {
            throw new moodle_exception('duplicateidnumber', 'local_customapi', '', $domain);
        }*/

        // Create a new course category for the tenant.
        $categorydata = (object)[
            'name' => $company_name,
            'idnumber' => $domain,
            'parent' => 0, // Top-level category.
        ];
        $category = core_course_category::create($categorydata);

        $cohortdata = (object)[
            'name' => $company_name . ' Cohort',
            'idnumber' => $domain . '_cohort',
            'contextid' => context_system::instance()->id,
            'description' => 'Cohort for tenant ' . $company_name,
            'timecreated' => time(),
        ];
        $cohortid = cohort_add_cohort($cohortdata);

        // Create the tenant.
        $tenantdata = (object)[
            'name' => $company_name,
            'idnumber' => $domain,
            'categoryid' => $category->id,
            'cohortid' => $cohortid,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
       $tenant_id = $DB->insert_record('tool_mutenancy_tenant', $tenantdata);

        return ['tenant_id' => $tenant_id];
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
        self::validate_parameters(self::create_user_in_tenant_parameters(), compact('email', 'tenant', 'role_id'));

        // Require capabilities.
        self::validate_context(context_system::instance());
        require_capability('moodle/user:create', context_system::instance());
        require_capability('tool/mutenancy:admin', context_system::instance());

        // Check for tool_mutenancy dependency.
        $mutenancy_lib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancy_lib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancy_lib);
        require_once($CFG->dirroot . '/user/lib.php');

        // Validate tenant exists.
        $manager = new tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$manager->get_tenant($tenant)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        // Create user.
        $userdata = (object)[
            'username' => $email,
            'email' => $email,
            'firstname' => 'Tenant',
            'lastname' => 'Admin',
            'password' => generate_custom_password(12),
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
        ];
        $userid = user_create_user($userdata);

        // Allocate to tenant.
        if (!class_exists('tool_mutenancy\tenancy') || !method_exists('tool_mutenancy\tenancy', 'allocate_user')) {
            throw new moodle_exception('mutenancymethodmissing', 'local_customapi', '', 'allocate_user');
        }
        tool_mutenancy\tenancy::allocate_user($userid, $tenant);

        // Assign role in tenant’s course category context.
        $tenant = $manager->get_tenant($tenant);
        $context = context_coursecat::instance($tenant->categoryid);
        role_assign($role_id, $userid, $context->id);

        return ['user_id' => $userid];
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

        self::validate_context(context_system::instance());
        require_capability('tool/mutenancy:admin', context_system::instance());

        // Check for tool_mutenancy dependency.
        $mutenancy_lib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancy_lib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancy_lib);

        // Validate tenant and user.
        $manager = new tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$manager->get_tenant($tenant_id)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }
        if (!$DB->record_exists('user', ['id' => $user_id, 'deleted' => 0])) {
            throw new moodle_exception('usernotfound', 'local_customapi');
        }

        if (!class_exists('tool_mutenancy\tenancy') || !method_exists('tool_mutenancy\tenancy', 'allocate_user')) {
            throw new moodle_exception('mutenancymethodmissing', 'local_customapi', '', 'allocate_user');
        }
        tool_mutenancy\tenancy::allocate_user($user_id, $tenant_id);

        return ['success' => true];
    }

    public static function add_user_to_tenant_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation success'),
        ]);
    }

    /**
     * 4. Fetch All Roles
     */
    public static function get_tenant_roles_parameters() {
        return new external_function_parameters([
            'tenant_id' => new external_value(PARAM_INT, 'Tenant ID', VALUE_REQUIRED),
        ]);
    }

    public static function get_tenant_roles($tenant_id) {
        global $CFG;
        self::validate_parameters(self::get_tenant_roles_parameters(), compact('tenant_id'));

        self::validate_context(context_system::instance());
        require_capability('moodle/role:manage', context_system::instance());

        $mutenancy_lib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancy_lib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancy_lib);

        $manager = new tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$tenant = $manager->get_tenant($tenant_id)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        $context = context_coursecat::instance($tenant->categoryid);
        $roles = role_get_assignable_roles($context);

        $result = [];
        foreach ($roles as $id => $name) {
            $result[] = ['id' => $id, 'name' => $name];
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
        global $CFG;
        self::validate_parameters(self::get_tenant_courses_parameters(), compact('tenant_id'));

        self::validate_context(context_system::instance());
        require_capability('moodle/course:view', context_system::instance());

        $mutenancy_lib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancy_lib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancy_lib);

        $manager = new tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$tenant = $manager->get_tenant($tenant_id)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        $courses = core_course_get_courses_by_field('category', $tenant->categoryid);
        $result = [];
        foreach ($courses->courses as $course) {
            $result[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
            ];
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

        self::validate_context(context_system::instance());
        require_capability('moodle/course:view', context_system::instance());

        $mutenancy_lib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (!file_exists($mutenancy_lib)) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }
        require_once($mutenancy_lib);

        // Validate tenant and user.
        $manager = new tool_mutenancy\manager();
        if (!method_exists($manager, 'get_tenant') || !$manager->get_tenant($tenant_id)) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }
        if (!$DB->record_exists('user', ['id' => $user_id, 'deleted' => 0])) {
            throw new moodle_exception('usernotfound', 'local_customapi');
        }

        if (!class_exists('tool_mutenancy\tenancy') || !method_exists('tool_mutenancy\tenancy', 'get_user_tenant') || tool_mutenancy\tenancy::get_user_tenant($user_id) != $tenant_id) {
            throw new moodle_exception('usernotintenant', 'local_customapi');
        }

        $courses = enrol_get_users_courses($user_id);
        $result = [];
        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
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
function generate_custom_password($length) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}