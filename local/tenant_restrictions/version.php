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
 * Version information for local_tenant_restrictions.
 *
 * This plugin implements strict tenant-based course category visibility
 * and selection rules for Vendor Admin and Partner Manager roles.
 *
 * @package    local_tenant_restrictions
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_tenant_restrictions';
$plugin->version   = 2025010725;  // Upgraded from 2025010724
$plugin->requires  = 2024042200;  // Moodle 4.4+
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';

// Dependency on mutenancy plugin.
$plugin->dependencies = [
    'tool_mutenancy' => ANY_VERSION,
];

