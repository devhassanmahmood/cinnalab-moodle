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

// Check if user has restricted access
if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
    // If no restrictions, redirect to normal course edit
    redirect(new moodle_url('/course/edit.php'));
}

$tenant_category = \local_tenant_restrictions\tenant_helper::get_tenant_category();
if (!$tenant_category) {
    throw new moodle_exception('notenant', 'local_tenant_restrictions');
}

// Get course ID if editing
$courseid = optional_param('id', 0, PARAM_INT);
$category = optional_param('category', $tenant_category, PARAM_INT);

// Always force the category to be the tenant category
$category = $tenant_category;

// Build redirect URL with proper parameters
$params = ['category' => $category];
if ($courseid) {
    $params['id'] = $courseid;
}

// Redirect to normal course edit with tenant category
redirect(new moodle_url('/course/edit.php', $params));
