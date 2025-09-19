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
 * Language strings for local_customapi plugin.
 *
 * @package   local_customapi
 * @copyright 2025 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Custom API for React Integration';

// Function descriptions for web services.
$string['local_customapi_create_tenant:desc'] = 'Creates a new tenant with the specified ID and name.';
$string['local_customapi_create_user_in_tenant:desc'] = 'Creates a new user, assigns them to a tenant, and assigns a role within the tenant.';
$string['local_customapi_add_user_to_tenant:desc'] = 'Adds an existing user to a specified tenant.';
$string['local_customapi_get_tenant_roles:desc'] = 'Fetches all assignable roles for a specified tenant.';
$string['local_customapi_get_tenant_courses:desc'] = 'Fetches all courses associated with a specified tenant.';
$string['local_customapi_get_tenant_user_courses:desc'] = 'Fetches all courses for a specific user within a specified tenant.';

// Error messages.
$string['tenantnotfound'] = 'The specified tenant was not found.';
$string['usernotfound'] = 'The specified user was not found.';
$string['usernotintenant'] = 'The user is not allocated to the specified tenant.';