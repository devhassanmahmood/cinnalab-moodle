<?php
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

class local_customapi_external extends external_api {

    /**
     * Try to load the tool_mutenancy manager class.
     */
    protected static function load_mutenancy_manager() {
        global $CFG;
        $managerclass = '\\tool_mutenancy\\local\\manager';

        if (class_exists($managerclass)) {
            return new $managerclass();
        }

        $mutenancylib = $CFG->dirroot . '/admin/tool/mutenancy/lib.php';
        if (file_exists($mutenancylib)) {
            require_once($mutenancylib);
            if (class_exists($managerclass)) {
                return new $managerclass();
            }
        }

        $managerfile = $CFG->dirroot . '/admin/tool/mutenancy/classes/manager.php';
        if (file_exists($managerfile)) {
            require_once($managerfile);
            if (class_exists($managerclass)) {
                return new $managerclass();
            }
        }

        return null;
    }

    /**
     * Detect tenant table.
     */
    protected static function detect_mutenancy_table() {
        global $DB;
        $candidates = ['tool_mutenancy_tenant', 'tool_mutenancy_tenants'];
        foreach ($candidates as $t) {
            if ($DB->get_manager()->table_exists($t)) {
                return $t;
            }
        }
        return false;
    }

    /* -------------------- 1. Create Tenant -------------------- */

    public static function create_tenant_parameters() {
        return new external_function_parameters([
            'domain'       => new external_value(PARAM_TEXT, 'Tenant ID (idnumber)', VALUE_REQUIRED),
            'company_name' => new external_value(PARAM_TEXT, 'Tenant Name', VALUE_REQUIRED),
        ]);
    }

    public static function create_tenant($domain, $company_name) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::create_tenant_parameters(), compact('domain', 'company_name'));

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('tool/mutenancy:admin', $context);

        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/course/lib.php');

        $manager = self::load_mutenancy_manager();
        $mutenancytable = self::detect_mutenancy_table();

        if (empty($manager) && !$mutenancytable) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }

        if ($mutenancytable) {
            $sql = 'SELECT id FROM {' . $mutenancytable . '} WHERE ' . $DB->sql_compare_text('idnumber') . ' = :idnumber';
            if ($DB->record_exists_sql($sql, ['idnumber' => $domain])) {
                throw new moodle_exception('duplicateidnumber', 'local_customapi', '', $domain);
            }
        }

        $existingcategory = $DB->get_record('course_categories', ['idnumber' => $domain]);
        if ($existingcategory) {
            $category = core_course_category::get($existingcategory->id);
        } else {
            $categorydata = (object)[
                'name' => $company_name,
                'idnumber' => $domain . '_cat',
                'parent' => 0,
            ];
            $category = core_course_category::create($categorydata);
        }

        $cohortidnumber = $domain . '_cohort';
        $cohortcontext = context_system::instance()->id;
        if ($existing = $DB->get_record('cohort', ['idnumber' => $cohortidnumber, 'contextid' => $cohortcontext])) {
            $cohortid = $existing->id;
        } else {
            $cohortdata = (object)[
                'name' => $company_name . ' Cohort',
                'idnumber' => $cohortidnumber,
                'contextid' => $cohortcontext,
                'description' => 'Cohort for tenant ' . $company_name,
                'descriptionformat' => FORMAT_HTML,
                'timecreated' => time(),
            ];
            $cohortid = cohort_add_cohort($cohortdata);
        }

        $tenantid = 0;
        if (!empty($manager) && method_exists($manager, 'create_tenant')) {
            $maybe = $manager->create_tenant((object)[
                'name' => $company_name,
                'idnumber' => $domain,
                'categoryid' => $category->id,
                'cohortid' => $cohortid,
            ]);
            if (is_object($maybe) && isset($maybe->id)) {
                $tenantid = (int)$maybe->id;
            } else if (is_int($maybe)) {
                $tenantid = $maybe;
            } else if (is_array($maybe) && isset($maybe['id'])) {
                $tenantid = (int)$maybe['id'];
            }
        }

        if (empty($tenantid)) {
            if (!$mutenancytable) {
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
            $tenantid = $DB->insert_record($mutenancytable, $tenantdata);
        }

        return ['tenant_id' => (int)$tenantid];
    }

    public static function create_tenant_returns() {
        return new external_single_structure([
            'tenant_id' => new external_value(PARAM_INT, 'New tenant ID'),
        ]);
    }

    /* -------------------- 2. Create User in Tenant -------------------- */

    public static function create_user_in_tenant_parameters() {
        return new external_function_parameters([
            'email' => new external_value(PARAM_EMAIL, 'User email', VALUE_REQUIRED),
            'tenant' => new external_value(PARAM_INT, 'Tenant ID', VALUE_REQUIRED),
            'role_id' => new external_value(PARAM_INT, 'Role ID', VALUE_REQUIRED),
            'firstname' => new external_value(PARAM_TEXT, 'First name', VALUE_DEFAULT, 'Tenant'),
            'lastname' => new external_value(PARAM_TEXT, 'Last name', VALUE_DEFAULT, 'Admin'),
            'password' => new external_value(PARAM_RAW, 'Password (optional)', VALUE_DEFAULT, ''),
        ]);
    }

    public static function create_user_in_tenant($email, $tenant, $role_id, $firstname = 'Tenant', $lastname = 'Admin', $password = '') {
        global $DB, $CFG;

        $params = self::validate_parameters(self::create_user_in_tenant_parameters(), compact('email', 'tenant', 'role_id', 'firstname', 'lastname', 'password'));

        $context = context_system::instance();
        self::validate_context($context);

        require_capability('moodle/user:create', $context);
        require_capability('tool/mutenancy:admin', $context);

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/accesslib.php');

        $manager = self::load_mutenancy_manager();
        $mutenancytable = self::detect_mutenancy_table();
        if (empty($manager) && !$mutenancytable) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }

        $tenantobj = null;
        if (!empty($manager) && method_exists($manager, 'get_tenant')) {
            $tenantobj = $manager->get_tenant($tenant);
        } else if ($mutenancytable) {
            $tenantobj = $DB->get_record($mutenancytable, ['id' => $tenant]);
        }
        if (!$tenantobj) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        $userpassword = $password ?: generate_custom_password(12);

        $userdata = (object)[
            'username' => $email,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'password' => $userpassword,
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id ?? 1,
        ];

        $userid = user_create_user($userdata);

        // ✅ Use correct allocation method.
        if (class_exists('\\tool_mutenancy\\local\\user') && method_exists('\\tool_mutenancy\\local\\user', 'allocate')) {
            \tool_mutenancy\local\user::allocate($userid, $tenant);
        } else {
            throw new moodle_exception('mutenancymethodmissing', 'local_customapi', '', 'allocate');
        }

        if (empty($tenantobj->categoryid)) {
            throw new moodle_exception('tenantnoconfiguredcategory', 'local_customapi');
        }
        $contextcat = context_coursecat::instance($tenantobj->categoryid);
        role_assign($role_id, $userid, $contextcat->id);

        return ['user_id' => (int)$userid, 'password' => $userpassword];
    }

    public static function create_user_in_tenant_returns() {
        return new external_single_structure([
            'user_id' => new external_value(PARAM_INT, 'New user ID'),
            'password' => new external_value(PARAM_TEXT, 'Generated or provided password')
        ]);
    }

    /* -------------------- 3. Add Existing User to Tenant -------------------- */

    public static function add_user_to_tenant_parameters() {
        return new external_function_parameters([
            'user_id' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
            'tenant_id' => new external_value(PARAM_INT, 'Tenant ID', VALUE_REQUIRED),
        ]);
    }

    public static function add_user_to_tenant($user_id, $tenant_id) {
        global $DB;

        self::validate_parameters(self::add_user_to_tenant_parameters(), compact('user_id', 'tenant_id'));

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('tool/mutenancy:admin', $context);

        if (!$DB->record_exists('user', ['id' => $user_id, 'deleted' => 0])) {
            throw new moodle_exception('usernotfound', 'local_customapi');
        }

        $tenantobj = $DB->get_record(self::detect_mutenancy_table(), ['id' => $tenant_id]);
        if (!$tenantobj) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        // ✅ Correct static allocation call.
        if (class_exists('\\tool_mutenancy\\local\\user') && method_exists('\\tool_mutenancy\\local\\user', 'allocate')) {
            \tool_mutenancy\local\user::allocate($user_id, $tenant_id);
        } else {
            throw new moodle_exception('mutenancymethodmissing', 'local_customapi', '', 'allocate');
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

        $manager = self::load_mutenancy_manager();
        $mutenancytable = self::detect_mutenancy_table();
        if (empty($manager) && !$mutenancytable) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }

        // Get tenant object
        $tenantobj = null;
        if (!empty($manager) && method_exists($manager, 'get_tenant')) {
            $tenantobj = $manager->get_tenant($tenant_id);
        } else if ($mutenancytable) {
            global $DB;
            $tenantobj = $DB->get_record($mutenancytable, ['id' => $tenant_id]);
        }
        if (!$tenantobj) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        $contextcat = context_coursecat::instance($tenantobj->categoryid);
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

        $manager = self::load_mutenancy_manager();
        $mutenancytable = self::detect_mutenancy_table();
        if (empty($manager) && !$mutenancytable) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }

        // Get tenant object
        if (!empty($manager) && method_exists($manager, 'get_tenant')) {
            $tenantobj = $manager->get_tenant($tenant_id);
        } else {
            $tenantobj = $DB->get_record($mutenancytable, ['id' => $tenant_id]);
        }
        if (!$tenantobj) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }

        $coursesdata = core_course_get_courses_by_field('category', $tenantobj->categoryid);
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

        $manager = self::load_mutenancy_manager();
        $mutenancytable = self::detect_mutenancy_table();
        if (empty($manager) && !$mutenancytable) {
            throw new moodle_exception('mutenancynotinstalled', 'local_customapi');
        }

        // Validate tenant and user.
        if (!empty($manager) && method_exists($manager, 'get_tenant')) {
            $tenantobj = $manager->get_tenant($tenant_id);
        } else {
            $tenantobj = $DB->get_record($mutenancytable, ['id' => $tenant_id]);
        }
        if (!$tenantobj) {
            throw new moodle_exception('tenantnotfound', 'local_customapi');
        }
        if (!$DB->record_exists('user', ['id' => $user_id, 'deleted' => 0])) {
            throw new moodle_exception('usernotfound', 'local_customapi');
        }

        // Check membership if API provides method.
        if (class_exists('\\tool_mutenancy\\tenancy') && method_exists('\\tool_mutenancy\\tenancy', 'get_user_tenant')) {
            if (\tool_mutenancy\tenancy::get_user_tenant($user_id) != $tenant_id) {
                throw new moodle_exception('usernotintenant', 'local_customapi');
            }
        } else if (!empty($manager) && method_exists($manager, 'get_user_tenant')) {
            if ($manager->get_user_tenant($user_id) != $tenant_id) {
                throw new moodle_exception('usernotintenant', 'local_customapi');
            }
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
