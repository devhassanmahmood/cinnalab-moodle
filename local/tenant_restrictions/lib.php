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
 * Library functions for local_tenant_restrictions.
 *
 * This file contains callback functions that hook into Moodle's core
 * to implement tenant-based category restrictions.
 *
 * FEATURES IMPLEMENTED:
 * 1. Course Management Page (/course/management.php) - Category tree filtering
 * 2. Course Creation (/course/edit.php) - Category dropdown filtering
 * 3. Course Editing (/course/edit.php) - Category dropdown filtering
 * 4. Server-side validation to prevent POST manipulation
 *
 * @package    local_tenant_restrictions
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Called before the page header is rendered.
 *
 * This is used to:
 * 1. Intercept course management page access and enforce tenant-based category restrictions
 * 2. Initialize JavaScript for category dropdown filtering on course edit pages
 */
function local_tenant_restrictions_before_standard_html_head() {
    global $PAGE, $USER;

    // Only process if restrictions should be applied.
    if (!class_exists('\local_tenant_restrictions\helper')) {
        return;
    }

    $helper = \local_tenant_restrictions\helper::class;
    if (!$helper::should_apply_restrictions()) {
        return;
    }

    // Get page info early.
    $pagetype = $PAGE->pagetype ?? '';
    $pageurl = $PAGE->url ?? null;
    $path = $pageurl ? $pageurl->get_path() : '';

    // Handle course management page category access.
    if ($pagetype === 'course-management' || strpos($path, '/course/management.php') !== false) {
        local_tenant_restrictions_check_management_access();
    }

    // Handle course edit page - inject JavaScript to filter category dropdown.
    if ($pagetype === 'course-edit' || strpos($path, '/course/edit.php') !== false) {
        local_tenant_restrictions_init_category_filter();
    }
}

/**
 * Initialize JavaScript for category dropdown filtering.
 *
 * Injects AMD module to filter the category autocomplete/select element
 * on course creation and editing pages.
 */
function local_tenant_restrictions_init_category_filter() {
    global $PAGE;

    $helper = \local_tenant_restrictions\helper::class;

    $tenantcategoryid = $helper::get_user_tenant_category_id();
    if ($tenantcategoryid === null) {
        return;
    }

    $allowedcategories = $helper::get_tenant_category_tree($tenantcategoryid);

    // Initialize the AMD module with allowed categories.
    $PAGE->requires->js_call_amd(
        'local_tenant_restrictions/category_filter',
        'init',
        [$allowedcategories, $tenantcategoryid]
    );
}

/**
 * Check and enforce category access on course management page.
 *
 * Validates that the requested category (if any) belongs to the user's tenant.
 * Redirects to user's tenant category if accessing unauthorized category.
 */
function local_tenant_restrictions_check_management_access() {
    $helper = \local_tenant_restrictions\helper::class;

    // Get requested category ID from URL.
    $categoryid = optional_param('categoryid', 0, PARAM_INT);

    if ($categoryid > 0) {
        // Validate category belongs to user's tenant.
        if (!$helper::is_category_in_user_tenant($categoryid)) {
            // Redirect to user's tenant root category.
            $tenantcategoryid = $helper::get_user_tenant_category_id();
            if ($tenantcategoryid) {
                $url = new moodle_url('/course/management.php', ['categoryid' => $tenantcategoryid]);
                redirect($url, get_string('accessdenied', 'local_tenant_restrictions'), null, \core\output\notification::NOTIFY_WARNING);
            } else {
                // No tenant assigned - redirect to site home.
                redirect(new moodle_url('/'), get_string('notenantassigned', 'local_tenant_restrictions'), null, \core\output\notification::NOTIFY_ERROR);
            }
        }
    } else {
        // No category specified - redirect to tenant's root category.
        $tenantcategoryid = $helper::get_user_tenant_category_id();
        if ($tenantcategoryid) {
            $url = new moodle_url('/course/management.php', ['categoryid' => $tenantcategoryid]);
            redirect($url);
        }
    }
}

/**
 * Extend the course navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param context $context The course context
 */
function local_tenant_restrictions_extend_navigation_course($navigation, $course, $context) {
    // Navigation extensions if needed.
}

/**
 * Hook into form definition.
 *
 * This callback is called when forms are being defined.
 * We use it to filter the category select element in course forms.
 *
 * @param MoodleQuickForm $mform The form being processed
 * @param string $formtype The type of form
 */
function local_tenant_restrictions_form_definition_after_data($mform, $formtype = '') {
    // Form modifications are handled via coursecat_options callback.
}

/**
 * Filter categories for course creation/editing.
 *
 * This is called via the course category options to filter available categories.
 *
 * @return array|null Array of allowed category IDs or null for no restriction
 */
function local_tenant_restrictions_get_allowed_categories() {
    if (!class_exists('\local_tenant_restrictions\helper')) {
        return null;
    }

    $helper = \local_tenant_restrictions\helper::class;

    if (!$helper::should_apply_restrictions()) {
        return null;
    }

    $tenantcategoryid = $helper::get_user_tenant_category_id();
    if ($tenantcategoryid === null) {
        return []; // No categories allowed.
    }

    return $helper::get_tenant_category_tree($tenantcategoryid);
}

/**
 * Get the default category for course creation.
 *
 * @return int|null Default category ID or null
 */
function local_tenant_restrictions_get_default_category() {
    if (!class_exists('\local_tenant_restrictions\helper')) {
        return null;
    }

    $helper = \local_tenant_restrictions\helper::class;

    if (!$helper::should_apply_restrictions()) {
        return null;
    }

    return $helper::get_user_tenant_category_id();
}

/**
 * Validate course data before saving.
 *
 * This provides server-side validation to prevent POST manipulation.
 * Called when a course is being created or updated.
 *
 * @param stdClass $data The course data being saved
 * @param array $files Files associated with the form
 * @return array Array of errors (empty if validation passed)
 */
function local_tenant_restrictions_course_validation($data, $files = []) {
    $errors = [];

    if (!class_exists('\local_tenant_restrictions\helper')) {
        return $errors;
    }

    $helper = \local_tenant_restrictions\helper::class;

    if (!$helper::should_apply_restrictions()) {
        return $errors;
    }

    // Validate category selection.
    if (!empty($data['category'])) {
        if (!$helper::is_category_in_user_tenant((int)$data['category'])) {
            $errors['category'] = get_string('invalidcategoryselection', 'local_tenant_restrictions');
        }
    }

    return $errors;
}

/**
 * Pre-process course data.
 *
 * Called before a course is created/updated.
 * Validates and potentially corrects category assignment.
 *
 * @param stdClass $course The course object
 */
function local_tenant_restrictions_pre_course_create($course) {
    local_tenant_restrictions_validate_course_category($course);
}

/**
 * Pre-process course data for updates.
 *
 * @param stdClass $course The course object
 */
function local_tenant_restrictions_pre_course_update($course) {
    local_tenant_restrictions_validate_course_category($course);
}

/**
 * Validate and enforce category restrictions for course creation/update.
 *
 * @param stdClass $course The course object (passed by reference)
 * @throws moodle_exception if category is invalid and cannot be corrected
 */
function local_tenant_restrictions_validate_course_category($course) {
    if (!class_exists('\local_tenant_restrictions\helper')) {
        return;
    }

    $helper = \local_tenant_restrictions\helper::class;

    if (!$helper::should_apply_restrictions()) {
        return;
    }

    if (empty($course->category)) {
        // Set default category to tenant root.
        $tenantcategoryid = $helper::get_user_tenant_category_id();
        if ($tenantcategoryid) {
            $course->category = $tenantcategoryid;
        }
        return;
    }

    // Validate the category.
    if (!$helper::is_category_in_user_tenant((int)$course->category)) {
        throw new moodle_exception('categorynotintenant', 'local_tenant_restrictions');
    }
}

/**
 * Page init callback.
 *
 * Called early in page initialization. Used to set up category filtering.
 */
function local_tenant_restrictions_after_config() {
    // Early initialization if needed.
}

/**
 * Callback when user logs in.
 *
 * Clear caches to ensure fresh tenant data.
 *
 * @param \core\event\user_loggedin $event The login event
 */
function local_tenant_restrictions_user_loggedin($event) {
    if (class_exists('\local_tenant_restrictions\helper')) {
        \local_tenant_restrictions\helper::clear_cache();
    }
}

