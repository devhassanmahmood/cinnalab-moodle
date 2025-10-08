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

use core\hook\output\before_footer_html_generation;

/**
 * Hook for before_footer_html_generation to handle tenant restrictions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_footer_hook {

    /**
     * Handle the before_footer_html_generation hook.
     *
     * @param before_footer_html_generation $hook
     */
    public static function handle(before_footer_html_generation $hook) {
        // Apply category filtering on course management page
        \local_tenant_restrictions\page_restrictions::filter_course_management_page();
        
        // Apply category filtering on course edit page
        \local_tenant_restrictions\page_restrictions::filter_course_edit_page();
    }
}
