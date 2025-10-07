<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test script for tenant restrictions plugin.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

// Only allow administrators to run this test
if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', 'Access denied');
}

$PAGE->set_url('/local/tenant_restrictions/test_plugin.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Tenant Restrictions Plugin Test');
$PAGE->set_heading('Tenant Restrictions Plugin Test');

echo $OUTPUT->header();

echo html_writer::tag('h2', 'Tenant Restrictions Plugin Test');

// Test 1: Check if Multi Tenant Tool is installed
echo html_writer::tag('h3', 'Test 1: Multi Tenant Tool Check');
if (class_exists('\tool_mutenancy\local\tenant')) {
    echo html_writer::tag('p', '✅ Multi Tenant Tool is installed and available');
} else {
    echo html_writer::tag('p', '❌ Multi Tenant Tool is not installed or not available');
}

// Test 2: Check if plugin classes are loaded
echo html_writer::tag('h3', 'Test 2: Plugin Classes Check');
if (class_exists('\local_tenant_restrictions\tenant_helper')) {
    echo html_writer::tag('p', '✅ Tenant Helper class is loaded');
} else {
    echo html_writer::tag('p', '❌ Tenant Helper class is not loaded');
}

if (class_exists('\local_tenant_restrictions\navigation_extension')) {
    echo html_writer::tag('p', '✅ Navigation Extension class is loaded');
} else {
    echo html_writer::tag('p', '❌ Navigation Extension class is not loaded');
}

// Test 3: Check plugin settings
echo html_writer::tag('h3', 'Test 3: Plugin Settings Check');
$settings = [
    'enabled' => get_config('local_tenant_restrictions', 'enabled'),
    'restrict_course_creation' => get_config('local_tenant_restrictions', 'restrict_course_creation'),
    'restrict_category_access' => get_config('local_tenant_restrictions', 'restrict_category_access'),
    'add_manage_tenant_menu' => get_config('local_tenant_restrictions', 'add_manage_tenant_menu'),
];

foreach ($settings as $setting => $value) {
    $status = $value ? '✅ Enabled' : '❌ Disabled';
    echo html_writer::tag('p', html_writer::tag('strong', $setting . ': ') . $status);
}

// Test 4: Check current user tenant info
echo html_writer::tag('h3', 'Test 4: Current User Tenant Info');
try {
    $tenant = \local_tenant_restrictions\tenant_helper::get_user_tenant();
    if ($tenant) {
        echo html_writer::tag('p', '✅ User has tenant: ' . $tenant->name . ' (ID: ' . $tenant->id . ')');
        echo html_writer::tag('p', 'Tenant Category ID: ' . $tenant->categoryid);
        
        $role = \local_tenant_restrictions\tenant_helper::get_user_tenant_role();
        if ($role) {
            echo html_writer::tag('p', 'User Role: ' . $role);
        } else {
            echo html_writer::tag('p', '❌ No tenant role assigned');
        }
    } else {
        echo html_writer::tag('p', 'ℹ️ Current user has no tenant assignment');
    }
} catch (Exception $e) {
    echo html_writer::tag('p', '❌ Error getting tenant info: ' . $e->getMessage());
}

// Test 5: Check capabilities
echo html_writer::tag('h3', 'Test 5: Capabilities Check');
$capabilities = [
    'local/tenant_restrictions:managetenant',
    'local/tenant_restrictions:createcourse',
    'local/tenant_restrictions:managecourse',
    'local/tenant_restrictions:accesstenantcategory',
];

foreach ($capabilities as $capability) {
    if (has_capability($capability, context_system::instance())) {
        echo html_writer::tag('p', '✅ ' . $capability);
    } else {
        echo html_writer::tag('p', '❌ ' . $capability);
    }
}

echo html_writer::tag('hr', '');
echo html_writer::tag('p', html_writer::link(new moodle_url('/admin/settings.php', ['section' => 'local_tenant_restrictions']), 'Go to Plugin Settings', ['class' => 'btn btn-primary']));

echo $OUTPUT->footer();
