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
 * Installation script for tenant restrictions plugin.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation hook.
 */
function xmldb_local_tenant_restrictions_install() {
    global $DB, $CFG;

    // Check if Multi Tenant Tool is installed
    if (!$DB->record_exists('config_plugins', ['plugin' => 'tool_mutenancy', 'name' => 'version'])) {
        throw new moodle_exception('mutenancy_required', 'local_tenant_restrictions');
    }

    // Set default settings
    set_config('enabled', 1, 'local_tenant_restrictions');
    set_config('restrict_course_creation', 1, 'local_tenant_restrictions');
    set_config('restrict_category_access', 1, 'local_tenant_restrictions');
    set_config('add_manage_tenant_menu', 1, 'local_tenant_restrictions');

    return true;
}

/**
 * Post uninstallation hook.
 */
function xmldb_local_tenant_restrictions_uninstall() {
    global $DB;

    // Remove plugin settings
    $DB->delete_records('config_plugins', ['plugin' => 'local_tenant_restrictions']);

    return true;
}
