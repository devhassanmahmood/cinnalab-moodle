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
 * Tenant helper class for managing tenant restrictions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenant_helper {

    /**
     * Get user's tenant information.
     *
     * @param int $userid User ID
     * @return object|null Tenant information or null if not found
     */
    public static function get_user_tenant($userid = null) {
        global $USER, $DB;

        if ($userid === null) {
            $userid = $USER->id;
        }

        // Check if user has tenantid set
        $user = $DB->get_record('user', ['id' => $userid], 'id, tenantid');
        if (!$user || empty($user->tenantid)) {
            return null;
        }

        // Get tenant information
        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $user->tenantid]);
        if (!$tenant) {
            return null;
        }

        return $tenant;
    }

    /**
     * Get user's role in tenant.
     *
     * @param int $userid User ID
     * @return string|null Role slug or null if not found
     */
    public static function get_user_tenant_role($userid = null) {
        global $USER, $DB;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $tenant = self::get_user_tenant($userid);
        if (!$tenant) {
            return null;
        }

        // Check if user is a tenant manager
        $is_manager = $DB->record_exists('tool_mutenancy_manager', [
            'tenantid' => $tenant->id,
            'userid' => $userid
        ]);

        if ($is_manager) {
            return 'vendor';
        }

        // Check user's role assignments in tenant context
        $tenantcontext = \context_tenant::instance($tenant->id);
        $roles = get_user_roles($tenantcontext, $userid, false);

        foreach ($roles as $role) {
            $role_shortname = $role->shortname;
            switch ($role_shortname) {
                case 'vendor':
                    return 'vendor';
                case 'partner_manager':
                    return 'partner_manager';
                case 'partner':
                    return 'partner';
                case 'partner_team_member':
                    return 'partner_team_member';
            }
        }

        return null;
    }

    /**
     * Check if user has restricted access (Vendor Admin or Partner Manager).
     *
     * @param int $userid User ID
     * @return bool True if user has restricted access
     */
    public static function has_restricted_access($userid = null) {
        $role = self::get_user_tenant_role($userid);
        return in_array($role, ['vendor', 'partner_manager']);
    }

    /**
     * Get allowed category IDs for user.
     *
     * @param int $userid User ID
     * @return array Array of allowed category IDs
     */
    public static function get_allowed_categories($userid = null) {
        $tenant = self::get_user_tenant($userid);
        if (!$tenant) {
            return [];
        }

        // Return only the tenant's category ID
        return [$tenant->categoryid];
    }

    /**
     * Check if user can access category.
     *
     * @param int $categoryid Category ID
     * @param int $userid User ID
     * @return bool True if user can access category
     */
    public static function can_access_category($categoryid, $userid = null) {
        $allowed_categories = self::get_allowed_categories($userid);
        return in_array($categoryid, $allowed_categories);
    }

    /**
     * Check if user is Vendor Admin.
     *
     * @param int $userid User ID
     * @return bool True if user is Vendor Admin
     */
    public static function is_vendor_admin($userid = null) {
        return self::get_user_tenant_role($userid) === 'vendor';
    }

    /**
     * Check if user is Partner Manager.
     *
     * @param int $userid User ID
     * @return bool True if user is Partner Manager
     */
    public static function is_partner_manager($userid = null) {
        return self::get_user_tenant_role($userid) === 'partner_manager';
    }

    /**
     * Get tenant category for user.
     *
     * @param int $userid User ID
     * @return int|null Tenant category ID or null if not found
     */
    public static function get_tenant_category($userid = null) {
        $tenant = self::get_user_tenant($userid);
        return $tenant ? $tenant->categoryid : null;
    }
}
