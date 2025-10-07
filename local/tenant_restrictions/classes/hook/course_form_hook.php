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

namespace local_tenant_restrictions\hook;

/**
 * Course form hook to restrict category selection for tenant users.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_form_hook {

    /**
     * Hook to modify course form for tenant users.
     * This removes the category dropdown for tenant users.
     */
    public static function handle() {
        global $PAGE, $USER;

        // Only apply to course edit pages
        if (!$PAGE->url->compare(new \moodle_url('/course/edit.php'), URL_MATCH_BASE)) {
            return;
        }

        // Check if user has tenant restrictions
        if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
            return;
        }

        // For tenant users, we'll use JavaScript to hide the category dropdown
        // and ensure the tenant category is pre-selected
        $tenant_category = \local_tenant_restrictions\tenant_helper::get_tenant_category();
        
        if ($tenant_category) {
            $js_code = '
            require(["jquery"], function(jQuery) {
                jQuery(document).ready(function() {
                    // Hide the category dropdown for tenant users
                    jQuery("#id_category").closest(".form-group").hide();
                    
                    // Ensure tenant category is selected
                    jQuery("#id_category").val(' . $tenant_category . ');
                    
                    // Also hide the label
                    jQuery("label[for=\\"id_category\\"]").closest(".form-group").hide();
                });
            });
            ';
            
            $PAGE->requires->js_amd_inline($js_code);
        }
    }
}
