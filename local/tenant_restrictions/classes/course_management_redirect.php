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

namespace local_tenant_restrictions;

/**
 * Course management redirect class for tenant restrictions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_management_redirect {

    /**
     * Hook to redirect course management page for tenant users.
     */
    public static function redirect_course_management() {
        global $PAGE;

        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        // Check if we're on the course management page
        if ($PAGE->url->compare(new \moodle_url('/course/management.php'), URL_MATCH_BASE)) {
            // Redirect to our custom tenant course management page
            redirect(new \moodle_url('/local/tenant_restrictions/tenant_course_management.php'));
        }
    }

    /**
     * Hook to redirect course creation page for tenant users.
     */
    public static function redirect_course_creation() {
        global $PAGE;

        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        // Check if we're on the course creation page
        if ($PAGE->url->compare(new \moodle_url('/course/edit.php'), URL_MATCH_BASE)) {
            $categoryid = optional_param('category', 0, PARAM_INT);
            
            // If no category specified or category not allowed, redirect to tenant category
            if (!$categoryid || !tenant_helper::can_access_category($categoryid)) {
                $tenant_category = tenant_helper::get_tenant_category();
                if ($tenant_category) {
                    // Redirect to tenant category course creation
                    redirect(new \moodle_url('/course/edit.php', ['category' => $tenant_category]));
                }
            }
        }
    }

    /**
     * Hook to redirect course creation from "Add new course" links.
     */
    public static function redirect_course_creation_links() {
        global $PAGE;

        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        // Check if we're on pages that might have "Add new course" links
        if ($PAGE->url->compare(new \moodle_url('/course/index.php'), URL_MATCH_BASE) ||
            $PAGE->url->compare(new \moodle_url('/my/'), URL_MATCH_BASE)) {
            
            $tenant_category = tenant_helper::get_tenant_category();
            if ($tenant_category) {
                // Inject JavaScript to modify "Add new course" links
                $js_code = '
                require(["jquery"], function(jQuery) {
                    jQuery(document).ready(function() {
                        // Modify all "Add new course" links to include tenant category
                jQuery("a[href*=\\"course/edit.php\\"]").each(function() {
                    var linkElement = jQuery(this);
                    var href = linkElement.attr("href");
                    
                    // Replace course/edit.php with our restricted version
                    if (href.indexOf("course/edit.php") !== -1) {
                        var newHref = href.replace("course/edit.php", "local/tenant_restrictions/course_edit_restricted.php");
                        linkElement.attr("href", newHref);
                    }
                });
                    });
                });
                ';
                
                $PAGE->requires->js_amd_inline($js_code);
            }
        }
    }

    /**
     * Hook to redirect category access for tenant users.
     */
    public static function redirect_category_access() {
        global $PAGE;

        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        // Check if we're accessing a category
        if ($PAGE->url->compare(new \moodle_url('/course/index.php'), URL_MATCH_BASE)) {
            $categoryid = optional_param('categoryid', 0, PARAM_INT);
            if ($categoryid && !tenant_helper::can_access_category($categoryid)) {
                $tenant_category = tenant_helper::get_tenant_category();
                if ($tenant_category) {
                    // Redirect to tenant category
                    redirect(new \moodle_url('/course/index.php', ['categoryid' => $tenant_category]));
                }
            }
        }
    }
}
