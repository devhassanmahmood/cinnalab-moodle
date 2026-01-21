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

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_http_headers;

/**
 * Hook callbacks for local_tenant_restrictions.
 *
 * These callbacks intercept various Moodle processes to enforce
 * tenant-based category restrictions.
 *
 * @package    local_tenant_restrictions
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Callback for before_http_headers hook.
     *
     * Intercepts page requests to enforce tenant category restrictions
     * before the page is rendered.
     *
     * @param before_http_headers $hook The hook instance
     * @return void
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $PAGE;

        // Only process if helper class is available.
        if (!class_exists('\local_tenant_restrictions\helper')) {
            return;
        }

        // Check if restrictions should be applied.
        if (!helper::should_apply_restrictions()) {
            return;
        }

        // Get the current page type.
        $pagetype = $PAGE->pagetype ?? '';
        $pageurl = $PAGE->url ?? null;

        // Handle different page types.
        switch ($pagetype) {
            case 'course-management':
                self::handle_course_management_page();
                break;

            case 'course-edit':
                self::handle_course_edit_page();
                break;
        }

        // Also check URL patterns for pages that might not have proper pagetype set yet.
        if ($pageurl) {
            $path = $pageurl->get_path();
            if (strpos($path, '/course/management.php') !== false) {
                self::handle_course_management_page();
            } else if (strpos($path, '/course/edit.php') !== false) {
                self::handle_course_edit_page();
            }
        }
    }

    /**
     * Handle course management page restrictions.
     *
     * Validates category access and redirects if necessary.
     *
     * @return void
     */
    private static function handle_course_management_page(): void {
        // Get requested category ID from URL.
        $categoryid = optional_param('categoryid', 0, PARAM_INT);

        $tenantcategoryid = helper::get_user_tenant_category_id();

        if ($categoryid > 0) {
            // Validate category belongs to user's tenant.
            if (!helper::is_category_in_user_tenant($categoryid)) {
                // Redirect to user's tenant root category.
                if ($tenantcategoryid) {
                    $url = new \moodle_url('/course/management.php', ['categoryid' => $tenantcategoryid]);
                    redirect($url, get_string('accessdenied', 'local_tenant_restrictions'), null, \core\output\notification::NOTIFY_WARNING);
                } else {
                    // No tenant assigned - redirect to site home.
                    redirect(new \moodle_url('/'), get_string('notenantassigned', 'local_tenant_restrictions'), null, \core\output\notification::NOTIFY_ERROR);
                }
            }
        } else {
            // No category specified - redirect to tenant's root category.
            if ($tenantcategoryid) {
                $url = new \moodle_url('/course/management.php', ['categoryid' => $tenantcategoryid]);
                redirect($url);
            }
        }
    }

    /**
     * Handle course edit page restrictions.
     *
     * Validates category access for course creation/editing.
     * Also validates POST data to prevent form manipulation.
     *
     * @return void
     */
    private static function handle_course_edit_page(): void {
        global $DB;

        // Get course ID (for editing existing course).
        $courseid = optional_param('id', 0, PARAM_INT);

        // Get category from URL (for new course) or POST data.
        $categoryid = optional_param('category', 0, PARAM_INT);

        // If editing existing course, get its current category.
        if ($courseid > 0 && $categoryid === 0) {
            $course = $DB->get_record('course', ['id' => $courseid], 'id, category');
            if ($course) {
                $categoryid = (int)$course->category;
            }
        }

        // Validate category for new course creation.
        if ($categoryid > 0) {
            if (!helper::is_category_in_user_tenant($categoryid)) {
                $tenantcategoryid = helper::get_user_tenant_category_id();
                if ($tenantcategoryid) {
                    // For new course, redirect with correct category.
                    if ($courseid === 0) {
                        $url = new \moodle_url('/course/edit.php', ['category' => $tenantcategoryid]);
                        redirect($url, get_string('accessdenied', 'local_tenant_restrictions'), null, \core\output\notification::NOTIFY_WARNING);
                    }
                    // For existing course in wrong category, show error.
                    // This shouldn't happen normally, but handles edge cases.
                }
            }
        }

        // SERVER-SIDE VALIDATION: Check POST data for category manipulation.
        // This prevents users from submitting the form with a category outside their tenant.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::validate_course_form_submission();
        }
    }

    /**
     * Validate course form submission.
     *
     * Checks that the submitted category is within the user's tenant.
     * This is the server-side protection against POST manipulation.
     *
     * @return void
     */
    private static function validate_course_form_submission(): void {
        // Get submitted category from form data.
        $submittedcategory = optional_param('category', 0, PARAM_INT);

        if ($submittedcategory > 0) {
            if (!helper::is_category_in_user_tenant($submittedcategory)) {
                // Invalid category submission - this is likely a POST manipulation attempt.
                throw new \moodle_exception('invalidcategoryselection', 'local_tenant_restrictions');
            }
        }
    }
}

