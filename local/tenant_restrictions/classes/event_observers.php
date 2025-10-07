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
 * Event observers for tenant restrictions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_observers {

    /**
     * Observer for course creation events.
     *
     * @param \core\event\course_created $event
     */
    public static function course_created(\core\event\course_created $event) {
        global $USER;

        // Check if user has restricted access
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        $course = $event->get_record_snapshot('course', $event->objectid);
        if (!$course) {
            return;
        }

        // Check if course is in allowed category
        if (!tenant_helper::can_access_category($course->category)) {
            // This shouldn't happen if restrictions are properly applied
            // but we can log it for debugging
            debugging('Course created in restricted category by tenant user', DEBUG_DEVELOPER);
        }
    }

    /**
     * Observer for category access events.
     *
     * @param \core\event\course_category_viewed $event
     */
    public static function category_viewed(\core\event\course_category_viewed $event) {
        global $USER;

        // Check if user has restricted access
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        $categoryid = $event->objectid;
        if (!tenant_helper::can_access_category($categoryid)) {
            // Redirect to tenant category
            $tenant_category = tenant_helper::get_tenant_category();
            if ($tenant_category) {
                redirect(new \moodle_url('/course/index.php', ['categoryid' => $tenant_category]));
            } else {
                redirect(new \moodle_url('/'));
            }
        }
    }

    /**
     * Observer for course access events.
     *
     * @param \core\event\course_viewed $event
     */
    public static function course_viewed(\core\event\course_viewed $event) {
        global $USER;

        // Check if user has restricted access
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        $course = $event->get_record_snapshot('course', $event->objectid);
        if (!$course) {
            return;
        }

        if (!tenant_helper::can_access_category($course->category)) {
            // Redirect to tenant category
            $tenant_category = tenant_helper::get_tenant_category();
            if ($tenant_category) {
                redirect(new \moodle_url('/course/index.php', ['categoryid' => $tenant_category]));
            } else {
                redirect(new \moodle_url('/'));
            }
        }
    }
}
