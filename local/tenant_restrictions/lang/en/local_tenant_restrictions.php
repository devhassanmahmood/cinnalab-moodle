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
 * Language strings for local_tenant_restrictions.
 *
 * @package    local_tenant_restrictions
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Tenant Category Restrictions';
$string['privacy:metadata'] = 'The tenant restrictions plugin does not store any personal data.';

// Error messages.
$string['accessdenied'] = 'Access denied: You do not have permission to access this category.';
$string['categorynotintenant'] = 'The selected category does not belong to your tenant.';
$string['notenantassigned'] = 'You are not assigned to any tenant.';
$string['invalidcategoryselection'] = 'Invalid category selection. You can only select categories within your tenant.';

// Capability strings.
$string['tenant_restrictions:bypassrestrictions'] = 'Bypass tenant category restrictions';

