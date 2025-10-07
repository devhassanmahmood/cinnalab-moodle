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
 * Settings for tenant restrictions plugin.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_tenant_restrictions', get_string('pluginname', 'local_tenant_restrictions'));
    
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configcheckbox(
            'local_tenant_restrictions/enabled',
            get_string('enabled', 'local_tenant_restrictions'),
            get_string('enabled_desc', 'local_tenant_restrictions'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_tenant_restrictions/restrict_course_creation',
            get_string('restrict_course_creation', 'local_tenant_restrictions'),
            get_string('restrict_course_creation_desc', 'local_tenant_restrictions'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_tenant_restrictions/restrict_category_access',
            get_string('restrict_category_access', 'local_tenant_restrictions'),
            get_string('restrict_category_access_desc', 'local_tenant_restrictions'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_tenant_restrictions/add_manage_tenant_menu',
            get_string('add_manage_tenant_menu', 'local_tenant_restrictions'),
            get_string('add_manage_tenant_menu_desc', 'local_tenant_restrictions'),
            1
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
