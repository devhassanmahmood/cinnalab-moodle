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
     * Note: This function is now empty as we use original Moodle pages.
     */
    public static function redirect_course_management() {
        // Using original Moodle course management pages
        // Category filtering is handled by observer instead
    }

    /**
     * Hook to redirect course creation page for tenant users.
     * Note: This function is now empty as we use original Moodle pages.
     */
    public static function redirect_course_creation() {
        // Using original Moodle course creation pages
        // Category filtering is handled by observer instead
    }

    /**
     * Hook to redirect course creation from "Add new course" links.
     * Note: This function is now empty as we use the original Moodle pages.
     */
    public static function redirect_course_creation_links() {
        // Using original Moodle course creation pages
        // Category dropdown is hidden with JavaScript instead of redirecting
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
