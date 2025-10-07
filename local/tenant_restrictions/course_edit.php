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
if (\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
    $categoryid = optional_param('category', 0, PARAM_INT);
    
    // If no category specified or category not allowed, redirect to tenant category
    if (!$categoryid || !\local_tenant_restrictions\tenant_helper::can_access_category($categoryid)) {
        $tenant_category = \local_tenant_restrictions\tenant_helper::get_tenant_category();
        if ($tenant_category) {
            // Redirect to tenant category course creation
            redirect(new moodle_url('/course/edit.php', ['category' => $tenant_category]));
        }
    }
}

// If no restrictions or category is allowed, redirect to normal course edit
redirect(new moodle_url('/course/edit.php', $_GET));
