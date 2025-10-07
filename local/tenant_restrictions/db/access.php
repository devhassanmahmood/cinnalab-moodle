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
 * Tenant restrictions capabilities.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Manage tenant - Vendor Admin capability.
    'local/tenant_restrictions:managetenant' => [
        'captype' => 'write',
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Create courses in tenant category - Partner Manager capability.
    'local/tenant_restrictions:createcourse' => [
        'captype' => 'write',
        'riskbitmask' => RISK_DATALOSS,
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Manage courses in tenant category - Partner Manager capability.
    'local/tenant_restrictions:managecourse' => [
        'captype' => 'write',
        'riskbitmask' => RISK_DATALOSS,
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Access tenant category only - Vendor Admin and Partner Manager capability.
    'local/tenant_restrictions:accesstenantcategory' => [
        'captype' => 'read',
        'riskbitmask' => RISK_PERSONAL,
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
