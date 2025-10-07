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
 * Course filter class for tenant restrictions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_filter {

    /**
     * Filter course categories for tenant restrictions.
     *
     * @param array $categories Array of course categories
     * @return array Filtered categories
     */
    public static function filter_course_categories($categories) {
        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return $categories;
        }

        $allowed_categories = tenant_helper::get_allowed_categories();
        
        // Filter categories to only include allowed ones
        $filtered_categories = [];
        foreach ($categories as $category) {
            if (in_array($category->id, $allowed_categories)) {
                $filtered_categories[] = $category;
            }
        }

        return $filtered_categories;
    }

    /**
     * Filter courses for tenant restrictions.
     *
     * @param array $courses Array of courses
     * @return array Filtered courses
     */
    public static function filter_courses($courses) {
        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return $courses;
        }

        $allowed_categories = tenant_helper::get_allowed_categories();
        
        // Filter courses to only include those in allowed categories
        $filtered_courses = [];
        foreach ($courses as $course) {
            if (in_array($course->category, $allowed_categories)) {
                $filtered_courses[] = $course;
            }
        }

        return $filtered_courses;
    }

    /**
     * Check if user can access course category.
     *
     * @param int $categoryid Category ID
     * @return bool True if user can access category
     */
    public static function can_access_category($categoryid) {
        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return true;
        }

        return tenant_helper::can_access_category($categoryid);
    }

    /**
     * Redirect unauthorized access to tenant category.
     *
     * @param int $categoryid Category ID being accessed
     */
    public static function redirect_unauthorized_access($categoryid) {
        if (!self::can_access_category($categoryid)) {
            $tenant_category = tenant_helper::get_tenant_category();
            if ($tenant_category) {
                redirect(new \moodle_url('/course/index.php', ['categoryid' => $tenant_category]));
            } else {
                redirect(new \moodle_url('/'));
            }
        }
    }

    /**
     * Get allowed category IDs for current user.
     *
     * @return array Array of allowed category IDs
     */
    public static function get_allowed_categories() {
        if (!tenant_helper::has_restricted_access()) {
            return []; // Empty array means no restrictions
        }

        return tenant_helper::get_allowed_categories();
    }
}
