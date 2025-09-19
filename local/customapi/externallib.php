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
        global $DB;
        self::validate_parameters(self::create_tenant_parameters(), compact('domain', 'company_name'));

        // Require tenant management capability.
        self::validate_context(context_system::instance());
        require_capability('tool/mutenancy:manage', context_system::instance());

        require_once($CFG->dirroot . '/admin/tool/mutenancy/lib.php');

        // Create a new course category for the tenant.
        $categorydata = (object)[
            'name' => $company_name,
            'idnumber' => $domain,
            'parent' => 0, // Top-level category.
        ];
        $category = core_course_category::create($categorydata);

        // Create the tenant.
        $tenantdata = (object)[
            'name' => $company_name,
            'idnumber' => $domain,
            'categoryid' => $category->id,
            // Add other defaults as needed (e.g., description).
        ];
        $manager = new tool_mutenancy\manager();
        $tenant = $manager->create_tenant($tenantdata); // Adjust method name if different.

        return ['tenant_id' => $tenant->id];
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
        global $DB;
        self::validate_parameters(self::create_user_in_tenant_parameters(), compact('email', 'tenant', 'role_id'));

        // Require capabilities.
        self::validate_context(context_system::instance());
        require_capability('moodle/user:create', context_system::instance());
        require_capability('tool/mutenancy:manage', context_system::instance());

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/admin/tool/mutenancy/lib.php');

        // Validate tenant exists.
        $manager = new tool_mutenancy\manager();
        if (!$manager->get_tenant($tenant)) {
            throw new moodle_exception('Tenant not found');
        }

        // Create user.
        $userdata = (object)[
            'username' => $email,
            'email' => $email,
            'firstname' => 'Tenant',
            'lastname' => 'Admin',
            'password' => generate_custom_password(12), // Implement secure password generation.
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
        ];
        $userid = user_create_user($userdata);

        // Allocate to tenant.
        tool_mutenancy\tenancy::allocate_user($userid, $tenant); // Adjust method name if different.

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
        global $DB;
        self::validate_parameters(self::add_user_to_tenant_parameters(), compact('user_id', 'tenant_id'));

        self::validate_context(context_system::instance());
        require_capability('tool/mutenancy:manage', context_system::instance());

        require_once($CFG->dirroot . '/admin/tool/mutenancy/lib.php');

        // Validate tenant and user.
        $manager = new tool_mutenancy\manager();
        if (!$manager->get_tenant($tenant_id)) {
            throw new moodle_exception('Tenant not found');
        }
        if (!$DB->record_exists('user', ['id' => $user_id, 'deleted' => 0])) {
            throw new moodle_exception('User not found');
        }

        tool_mutenancy\tenancy::allocate_user($user_id, $tenant_id); // Adjust method name if different.

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
        self::validate_parameters(self::get_tenant_roles_parameters(), compact('tenant_id'));

        self::validate_context(context_system::instance());
        require_capability('moodle/role:manage', context_system::instance());

        require_once($CFG->dirroot . '/admin/tool/mutenancy/lib.php');

        $manager = new tool_mutenancy\manager();
        $tenant = $manager->get_tenant($tenant_id);
        if (!$tenant) {
            throw new moodle_exception('Tenant not found');
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
        self::validate_parameters(self::get_tenant_courses_parameters(), compact('tenant_id'));

        self::validate_context(context_system::instance());
        require_capability('moodle/course:view', context_system::instance());

        require_once($CFG->dirroot . '/admin/tool/mutenancy/lib.php');

        $manager = new tool_mutenancy\manager();
        $tenant = $manager->get_tenant($tenant_id);
        if (!$tenant) {
            throw new moodle_exception('Tenant not found');
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
        global $DB;
        self::validate_parameters(self::get_tenant_user_courses_parameters(), compact('tenant_id', 'user_id'));

        self::validate_context(context_system::instance());
        require_capability('moodle/course:view', context_system::instance());

        require_once($CFG->dirroot . '/admin/tool/mutenancy/lib.php');

        // Validate tenant and user.
        $manager = new tool_mutenancy\manager();
        if (!$manager->get_tenant($tenant_id)) {
            throw new moodle_exception('Tenant not found');
        }
        if (!$DB->record_exists('user', ['id' => $user_id, 'deleted' => 0])) {
            throw new moodle_exception('User not found');
        }

        // Validate user in tenant.
        if (tool_mutenancy\tenancy::get_user_tenant($user_id) != $tenant_id) {
            throw new moodle_exception('User not in tenant');
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