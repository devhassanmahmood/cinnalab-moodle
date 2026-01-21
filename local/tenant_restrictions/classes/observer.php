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

/**
 * Event observer for local_tenant_restrictions.
 *
 * Handles events to enforce tenant restrictions and clear caches.
 *
 * @package    local_tenant_restrictions
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Handle course_created event.
     *
     * Validates that the course was created in a valid tenant category.
     *
     * @param \core\event\course_created $event
     * @return void
     */
    public static function course_created(\core\event\course_created $event): void {
        // Course already created at this point, but we can log violations.
        self::validate_course_category($event->objectid, $event->userid);
    }

    /**
     * Handle course_updated event.
     *
     * Validates that the course category change is valid for the tenant.
     *
     * @param \core\event\course_updated $event
     * @return void
     */
    public static function course_updated(\core\event\course_updated $event): void {
        // Course already updated, validate for logging purposes.
        self::validate_course_category($event->objectid, $event->userid);
    }

    /**
     * Handle user_loggedin event.
     *
     * Clears caches to ensure fresh tenant data for the session.
     *
     * @param \core\event\user_loggedin $event
     * @return void
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        helper::clear_cache();
    }

    /**
     * Handle user_loggedout event.
     *
     * Clears caches when user logs out.
     *
     * @param \core\event\user_loggedout $event
     * @return void
     */
    public static function user_loggedout(\core\event\user_loggedout $event): void {
        helper::clear_cache();
    }

    /**
     * Validate course category assignment.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID who made the change
     * @return void
     */
    private static function validate_course_category(int $courseid, int $userid): void {
        global $DB;

        if (!helper::should_apply_restrictions($userid)) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $courseid], 'id, category, fullname');
        if (!$course) {
            return;
        }

        if (!helper::is_category_in_user_tenant((int)$course->category, $userid)) {
            // Log the violation but don't block (course is already saved).
            debugging(
                "Tenant restriction violation: User $userid modified course '{$course->fullname}' " .
                "(ID: $courseid) in category {$course->category} outside their tenant.",
                DEBUG_DEVELOPER
            );
        }
    }
}

