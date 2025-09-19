<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_customapi_create_tenant' => [
        'classname' => 'local_customapi_external',
        'methodname' => 'create_tenant',
        'classpath' => 'local/customapi/externallib.php',
        'description' => 'Create a new tenant',
        'type' => 'write',
        'capabilities' => 'tool/mutenancy:admin',
        'ajax' => true,
    ],
    'local_customapi_create_user_in_tenant' => [
        'classname' => 'local_customapi_external',
        'methodname' => 'create_user_in_tenant',
        'classpath' => 'local/customapi/externallib.php',
        'description' => 'Create a user and assign to tenant with role',
        'type' => 'write',
        'capabilities' => 'moodle/user:create,tool/mutenancy:admin',
        'ajax' => true,
    ],
    'local_customapi_add_user_to_tenant' => [
        'classname' => 'local_customapi_external',
        'methodname' => 'add_user_to_tenant',
        'classpath' => 'local/customapi/externallib.php',
        'description' => 'Add an existing user to a tenant',
        'type' => 'write',
        'capabilities' => 'tool/mutenancy:admin',
        'ajax' => true,
    ],
    'local_customapi_get_tenant_roles' => [
        'classname' => 'local_customapi_external',
        'methodname' => 'get_tenant_roles',
        'classpath' => 'local/customapi/externallib.php',
        'description' => 'Fetch assignable roles for a tenant',
        'type' => 'read',
        'capabilities' => 'moodle/role:manage',
        'ajax' => true,
    ],
    'local_customapi_get_tenant_courses' => [
        'classname' => 'local_customapi_external',
        'methodname' => 'get_tenant_courses',
        'classpath' => 'local/customapi/externallib.php',
        'description' => 'Fetch courses for a tenant',
        'type' => 'read',
        'capabilities' => 'moodle/course:view',
        'ajax' => true,
    ],
    'local_customapi_get_tenant_user_courses' => [
        'classname' => 'local_customapi_external',
        'methodname' => 'get_tenant_user_courses',
        'classpath' => 'local/customapi/externallib.php',
        'description' => 'Fetch courses for a user in a tenant',
        'type' => 'read',
        'capabilities' => 'moodle/course:view',
        'ajax' => true,
    ],
];

$services = [
    'Custom API for React Integration' => [
        'functions' => array_keys($functions),
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
