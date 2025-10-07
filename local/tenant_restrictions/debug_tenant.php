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

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Require login
require_login();

// Check if user is admin
if (!is_siteadmin()) {
    throw new moodle_exception('nopermission');
}

echo $OUTPUT->header();

echo "<h2>Multi Tenant Tool Debug Information</h2>";

// Check if Multi Tenant Tool is available
if (!class_exists('\tool_mutenancy\local\tenant')) {
    echo "<p style='color: red;'>Multi Tenant Tool is not available. Class '\\tool_mutenancy\\local\\tenant' not found.</p>";
} else {
    echo "<p style='color: green;'>Multi Tenant Tool is available.</p>";
}

// Debug tenant tables
$debug = \local_tenant_restrictions\tenant_helper::debug_tenant_tables();

echo "<h3>Database Tables Status:</h3>";
echo "<pre>";
print_r($debug);
echo "</pre>";

// Test current user's tenant
echo "<h3>Current User (" . $USER->username . ") Tenant Information:</h3>";

$tenant = \local_tenant_restrictions\tenant_helper::get_user_tenant();
if ($tenant) {
    echo "<p style='color: green;'>User has tenant: " . htmlspecialchars($tenant->name ?? 'Unknown') . "</p>";
    echo "<pre>";
    print_r($tenant);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>User has no tenant assignment.</p>";
}

// Test tenant role
$role = \local_tenant_restrictions\tenant_helper::get_user_tenant_role();
if ($role) {
    echo "<p style='color: green;'>User role: " . htmlspecialchars($role) . "</p>";
} else {
    echo "<p style='color: red;'>User has no tenant role.</p>";
}

// Test allowed categories
$categories = \local_tenant_restrictions\tenant_helper::get_allowed_categories();
if (!empty($categories)) {
    echo "<p style='color: green;'>Allowed categories: " . implode(', ', $categories) . "</p>";
} else {
    echo "<p style='color: red;'>No allowed categories found.</p>";
}

echo $OUTPUT->footer();
