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

use core\hook\output\before_http_headers;

/**
 * Hook for before_http_headers to handle tenant restrictions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_http_headers_hook {

    /**
     * Handle the before_http_headers hook.
     *
     * @param before_http_headers $hook
     */
    public static function handle(before_http_headers $hook) {
        \local_tenant_restrictions\course_management_redirect::redirect_course_management();
        \local_tenant_restrictions\course_management_redirect::redirect_course_creation();
        \local_tenant_restrictions\course_management_redirect::redirect_course_creation_links();
        \local_tenant_restrictions\course_management_redirect::redirect_category_access();
    }
}
