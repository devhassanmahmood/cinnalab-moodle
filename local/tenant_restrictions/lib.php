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
 * Tenant restrictions library functions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook to filter course categories for course creation.
 *
 * @param array $categories Array of course categories
 * @return array Filtered categories
 */
function local_tenant_restrictions_filter_course_categories($categories) {
    global $USER;

    // Only apply restrictions to users with tenant restrictions
    if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
        return $categories;
    }

    $allowed_categories = \local_tenant_restrictions\tenant_helper::get_allowed_categories();
    
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
 * Hook to check if user can access course category.
 *
 * @param int $categoryid Category ID
 * @return bool True if user can access category
 */
function local_tenant_restrictions_can_access_category($categoryid) {
    return \local_tenant_restrictions\tenant_helper::can_access_category($categoryid);
}

/**
 * Hook to check if user can create course in category.
 *
 * @param int $categoryid Category ID
 * @return bool True if user can create course in category
 */
function local_tenant_restrictions_can_create_course($categoryid) {
    // Only apply restrictions to users with tenant restrictions
    if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
        return true;
    }

    return \local_tenant_restrictions\tenant_helper::can_access_category($categoryid);
}

/**
 * Hook to check if user can manage course.
 *
 * @param int $courseid Course ID
 * @return bool True if user can manage course
 */
function local_tenant_restrictions_can_manage_course($courseid) {
    global $DB;

    // Only apply restrictions to users with tenant restrictions
    if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
        return true;
    }

    // Get course category
    $course = $DB->get_record('course', ['id' => $courseid], 'category');
    if (!$course) {
        return false;
    }

    return \local_tenant_restrictions\tenant_helper::can_access_category($course->category);
}

/**
 * Hook to add navigation items for tenant management.
 *
 * @param navigation_node $navigation Navigation node
 */
function local_tenant_restrictions_extend_navigation($navigation) {
    \local_tenant_restrictions\navigation_extension::extend_navigation($navigation);
}

/**
 * Hook to restrict access to course category management.
 *
 * @param int $categoryid Category ID
 * @return bool True if user can manage category
 */
function local_tenant_restrictions_can_manage_category($categoryid) {
    // Only apply restrictions to users with tenant restrictions
    if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
        return true;
    }

    // Tenant users cannot manage categories outside their tenant
    return \local_tenant_restrictions\tenant_helper::can_access_category($categoryid);
}

/**
 * Hook to filter course list for users.
 *
 * @param array $courses Array of courses
 * @return array Filtered courses
 */
function local_tenant_restrictions_filter_courses($courses) {
    // Only apply restrictions to users with tenant restrictions
    if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
        return $courses;
    }

    $allowed_categories = \local_tenant_restrictions\tenant_helper::get_allowed_categories();
    
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
 * Hook to restrict breadcrumb navigation.
 *
 * @param array $breadcrumbs Array of breadcrumb items
 * @return array Filtered breadcrumbs
 */
function local_tenant_restrictions_filter_breadcrumbs($breadcrumbs) {
    // Only apply restrictions to users with tenant restrictions
    if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
        return $breadcrumbs;
    }

    $allowed_categories = \local_tenant_restrictions\tenant_helper::get_allowed_categories();
    
    // Remove breadcrumbs that link to categories outside tenant
    $filtered_breadcrumbs = [];
    foreach ($breadcrumbs as $breadcrumb) {
        // Check if breadcrumb is a category link
        if (isset($breadcrumb['categoryid']) && !in_array($breadcrumb['categoryid'], $allowed_categories)) {
            continue;
        }
        $filtered_breadcrumbs[] = $breadcrumb;
    }

    return $filtered_breadcrumbs;
}

/**
 * Hook to override capability check for course category changes.
 * This prevents tenant users from seeing the category dropdown.
 *
 * @param string $capability The capability being checked
 * @param context $context The context where capability is checked
 * @param int $userid User ID
 * @return bool|null True if allowed, false if denied, null if no override
 */
function local_tenant_restrictions_override_capability($capability, $context, $userid) {
    // Only override the specific capability we want to restrict
    if ($capability !== 'moodle/course:changecategory') {
        return null; // No override for other capabilities
    }

    // Only apply to course category contexts
    if ($context->contextlevel !== CONTEXT_COURSECAT) {
        return null;
    }

    // Check if user has tenant restrictions
    if (!\local_tenant_restrictions\tenant_helper::has_restricted_access($userid)) {
        return null; // No override for non-tenant users
    }

    // For tenant users, deny the capability to change categories
    return false;
}

// Legacy callback functions removed - now using new hook system
// See db/hooks.php for new hook registrations
