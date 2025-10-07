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
 * Navigation extension for tenant restrictions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation_extension {

    /**
     * Extend navigation to add tenant management menu.
     *
     * @param \navigation_node $navigation Navigation node
     */
    public static function extend_navigation($navigation) {
        global $USER;

        try {
            // Only add for Vendor Admin users
            if (!tenant_helper::is_vendor_admin()) {
                return;
            }

            $tenant = tenant_helper::get_user_tenant();
            if (!$tenant) {
                return;
            }

            // Check if the menu item already exists to avoid duplicates
            if ($navigation->find('tenant_managetenant', \navigation_node::TYPE_CUSTOM)) {
                return;
            }

            // Find the user menu
            $usermenu = $navigation->find('user', \navigation_node::TYPE_CONTAINER);
            if (!$usermenu) {
                return;
            }

            // Add "Manage Tenant" menu item
            $managetenant = $usermenu->add(
                get_string('managetenant_nav', 'local_tenant_restrictions'),
                new \moodle_url('/local/tenant_restrictions/manage_tenant.php'),
                \navigation_node::TYPE_CUSTOM,
                null,
                'tenant_managetenant'
            );
            $managetenant->set_icon(new \pix_icon('i/settings', ''));
        } catch (Exception $e) {
            // Log error but don't break the page
            debugging('Tenant restrictions navigation error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Filter course categories in navigation.
     *
     * @param array $categories Categories array
     * @return array Filtered categories
     */
    public static function filter_categories($categories) {
        if (!tenant_helper::has_restricted_access()) {
            return $categories;
        }

        $allowed_categories = tenant_helper::get_allowed_categories();
        
        $filtered = [];
        foreach ($categories as $category) {
            if (in_array($category->id, $allowed_categories)) {
                $filtered[] = $category;
            }
        }

        return $filtered;
    }

    /**
     * Filter courses in navigation.
     *
     * @param array $courses Courses array
     * @return array Filtered courses
     */
    public static function filter_courses($courses) {
        if (!tenant_helper::has_restricted_access()) {
            return $courses;
        }

        $allowed_categories = tenant_helper::get_allowed_categories();
        
        $filtered = [];
        foreach ($courses as $course) {
            if (in_array($course->category, $allowed_categories)) {
                $filtered[] = $course;
            }
        }

        return $filtered;
    }
}
