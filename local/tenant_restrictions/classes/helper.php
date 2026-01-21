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
 * Helper class for tenant-based category restrictions.
 *
 * Provides utility functions to:
 * - Get user's tenant category ID
 * - Get all categories within a tenant hierarchy
 * - Validate category access for tenant users
 *
 * @package    local_tenant_restrictions
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** @var array Cache for tenant category IDs */
    private static $tenantcategorycache = [];

    /** @var array Cache for tenant category trees */
    private static $categorytreecache = [];

    /**
     * Check if mutenancy plugin is active and available.
     *
     * @return bool True if mutenancy is active
     */
    public static function is_mutenancy_active(): bool {
        // Check if the mutenancy tenancy class exists and is active.
        if (!class_exists('\tool_mutenancy\local\tenancy')) {
            return false;
        }
        return \tool_mutenancy\local\tenancy::is_active();
    }

    /**
     * Get the tenant's root category ID for a given user.
     *
     * This function retrieves the course category that is assigned to the user's tenant.
     * Each tenant maps to ONE root course category.
     *
     * @param int|null $userid User ID, null for current user
     * @return int|null Category ID or null if user has no tenant
     */
    public static function get_user_tenant_category_id(?int $userid = null): ?int {
        global $USER, $DB;

        // Use current user if not specified.
        if ($userid === null) {
            $userid = $USER->id;
        }

        // Check cache first.
        if (isset(self::$tenantcategorycache[$userid])) {
            return self::$tenantcategorycache[$userid];
        }

        // Ensure mutenancy is active.
        if (!self::is_mutenancy_active()) {
            self::$tenantcategorycache[$userid] = null;
            return null;
        }

        // Get user's tenant ID from mutenancy.
        $tenantid = null;
        if (class_exists('\tool_mutenancy\local\tenancy')) {
            $tenantid = \tool_mutenancy\local\tenancy::get_user_tenantid($userid);
        }

        // If no tenant ID found via mutenancy, check role assignments.
        // Users might be tenant managers without being tenant members.
        if (!$tenantid) {
            $tenantid = self::get_tenant_from_role_assignment($userid);
        }

        if (!$tenantid) {
            self::$tenantcategorycache[$userid] = null;
            return null;
        }

        // Get the tenant's category ID.
        $categoryid = self::get_tenant_category_from_id($tenantid);
        self::$tenantcategorycache[$userid] = $categoryid;

        return $categoryid;
    }

    /**
     * Get tenant ID from role assignment context.
     *
     * This handles cases where users are assigned roles within a tenant context
     * but are not direct tenant members (e.g., Partner Managers).
     *
     * @param int $userid User ID
     * @return int|null Tenant ID or null
     */
    private static function get_tenant_from_role_assignment(int $userid): ?int {
        global $DB;

        // Check if user has role assignments with tool_mutenancy component.
        // These assignments store the tenant ID in the itemid field.
        $sql = "SELECT DISTINCT ra.itemid
                  FROM {role_assignments} ra
                 WHERE ra.userid = :userid
                   AND ra.component = 'tool_mutenancy'
                   AND ra.itemid > 0
              ORDER BY ra.timemodified DESC";

        $assignments = $DB->get_records_sql($sql, ['userid' => $userid], 0, 1);
        if ($assignments) {
            $assignment = reset($assignments);
            return (int)$assignment->itemid;
        }

        return null;
    }

    /**
     * Get category ID from tenant ID.
     *
     * @param int $tenantid Tenant ID
     * @return int|null Category ID or null
     */
    private static function get_tenant_category_from_id(int $tenantid): ?int {
        global $DB;

        // Try using the tenant class if available.
        if (class_exists('\tool_mutenancy\local\tenant')) {
            $tenant = \tool_mutenancy\local\tenant::fetch($tenantid);
            if ($tenant && !empty($tenant->categoryid)) {
                return (int)$tenant->categoryid;
            }
        }

        // Fallback: direct database query.
        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], 'categoryid');
        if ($tenant && !empty($tenant->categoryid)) {
            return (int)$tenant->categoryid;
        }

        return null;
    }

    /**
     * Get all category IDs within a tenant's hierarchy.
     *
     * Returns the root category and all its descendants (subcategories).
     *
     * @param int $rootcategoryid The tenant's root category ID
     * @return array Array of category IDs
     */
    public static function get_tenant_category_tree(int $rootcategoryid): array {
        global $DB;

        // Check cache first.
        if (isset(self::$categorytreecache[$rootcategoryid])) {
            return self::$categorytreecache[$rootcategoryid];
        }

        $categoryids = [$rootcategoryid];

        // Get the root category to find its path.
        $rootcat = $DB->get_record('course_categories', ['id' => $rootcategoryid], 'id, path');
        if (!$rootcat) {
            self::$categorytreecache[$rootcategoryid] = $categoryids;
            return $categoryids;
        }

        // Find all subcategories using the path.
        // Categories have paths like /1/2/3 where each number is a category ID.
        $pathlike = $DB->sql_like('path', ':pathpattern');
        $sql = "SELECT id FROM {course_categories} WHERE $pathlike";
        $params = ['pathpattern' => $rootcat->path . '/%'];

        $subcats = $DB->get_records_sql($sql, $params);
        foreach ($subcats as $subcat) {
            $categoryids[] = (int)$subcat->id;
        }

        self::$categorytreecache[$rootcategoryid] = $categoryids;
        return $categoryids;
    }

    /**
     * Check if a category belongs to the user's tenant.
     *
     * @param int $categoryid Category ID to check
     * @param int|null $userid User ID, null for current user
     * @return bool True if category is within user's tenant hierarchy
     */
    public static function is_category_in_user_tenant(int $categoryid, ?int $userid = null): bool {
        $tenantcategoryid = self::get_user_tenant_category_id($userid);

        // If user has no tenant, they cannot access any category through this check.
        if ($tenantcategoryid === null) {
            return false;
        }

        $allowedcategories = self::get_tenant_category_tree($tenantcategoryid);
        return in_array($categoryid, $allowedcategories);
    }

    /**
     * Check if the current user should have tenant restrictions applied.
     *
     * Site admins and users with bypass capability are excluded from restrictions.
     *
     * @param int|null $userid User ID, null for current user
     * @return bool True if restrictions should be applied
     */
    public static function should_apply_restrictions(?int $userid = null): bool {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        // Never restrict site admins.
        if (is_siteadmin($userid)) {
            return false;
        }

        // Check for bypass capability.
        $context = \context_system::instance();
        if (has_capability('local/tenant_restrictions:bypassrestrictions', $context, $userid)) {
            return false;
        }

        // Check if mutenancy is active.
        if (!self::is_mutenancy_active()) {
            return false;
        }

        // Only apply restrictions if user has a tenant.
        $tenantcategoryid = self::get_user_tenant_category_id($userid);
        return ($tenantcategoryid !== null);
    }

    /**
     * Filter an array of categories to only include those in the user's tenant.
     *
     * @param array $categories Array of category objects or IDs
     * @param int|null $userid User ID, null for current user
     * @return array Filtered array of categories
     */
    public static function filter_categories_for_tenant(array $categories, ?int $userid = null): array {
        if (!self::should_apply_restrictions($userid)) {
            return $categories;
        }

        $tenantcategoryid = self::get_user_tenant_category_id($userid);
        if ($tenantcategoryid === null) {
            return []; // No tenant = no categories visible.
        }

        $allowedcategories = self::get_tenant_category_tree($tenantcategoryid);

        $filtered = [];
        foreach ($categories as $key => $category) {
            $catid = is_object($category) ? $category->id : $category;
            if (in_array((int)$catid, $allowedcategories)) {
                $filtered[$key] = $category;
            }
        }

        return $filtered;
    }

    /**
     * Get category select options filtered for the user's tenant.
     *
     * Used for course creation/editing category dropdown.
     *
     * @param int|null $userid User ID, null for current user
     * @return array Array of category ID => name pairs
     */
    public static function get_tenant_category_options(?int $userid = null): array {
        global $DB;

        $tenantcategoryid = self::get_user_tenant_category_id($userid);
        if ($tenantcategoryid === null) {
            return [];
        }

        $allowedcategories = self::get_tenant_category_tree($tenantcategoryid);
        if (empty($allowedcategories)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($allowedcategories, SQL_PARAMS_NAMED);
        $sql = "SELECT id, name, depth, path
                  FROM {course_categories}
                 WHERE id $insql
                   AND visible = 1
              ORDER BY sortorder";

        $categories = $DB->get_records_sql($sql, $params);

        $options = [];
        foreach ($categories as $cat) {
            // Add indentation based on depth relative to tenant root.
            $rootcat = $DB->get_record('course_categories', ['id' => $tenantcategoryid], 'depth');
            $relativedepth = $cat->depth - $rootcat->depth;
            $indent = str_repeat('  ', $relativedepth);
            $options[$cat->id] = $indent . $cat->name;
        }

        return $options;
    }

    /**
     * Validate that a category ID is valid for the current user's tenant.
     *
     * @param int $categoryid Category ID to validate
     * @param int|null $userid User ID, null for current user
     * @return bool True if valid, false otherwise
     * @throws \moodle_exception if validation fails and strict mode is enabled
     */
    public static function validate_category_for_tenant(int $categoryid, ?int $userid = null, bool $strict = false): bool {
        if (!self::should_apply_restrictions($userid)) {
            return true;
        }

        $valid = self::is_category_in_user_tenant($categoryid, $userid);

        if (!$valid && $strict) {
            throw new \moodle_exception('categorynotintenant', 'local_tenant_restrictions');
        }

        return $valid;
    }

    /**
     * Clear all caches.
     *
     * Call this when tenant assignments change.
     */
    public static function clear_cache(): void {
        self::$tenantcategorycache = [];
        self::$categorytreecache = [];
    }

    /**
     * Get SQL WHERE clause to filter categories for tenant.
     *
     * @param string $tablealias Table alias for course_categories table
     * @param int|null $userid User ID, null for current user
     * @return array Array with 'sql' and 'params' keys
     */
    public static function get_tenant_category_sql(string $tablealias = 'cc', ?int $userid = null): array {
        global $DB;

        $result = ['sql' => '', 'params' => []];

        if (!self::should_apply_restrictions($userid)) {
            return $result;
        }

        $tenantcategoryid = self::get_user_tenant_category_id($userid);
        if ($tenantcategoryid === null) {
            // No tenant = restrict all categories.
            return ['sql' => ' AND 1=0', 'params' => []];
        }

        $allowedcategories = self::get_tenant_category_tree($tenantcategoryid);
        if (empty($allowedcategories)) {
            return ['sql' => ' AND 1=0', 'params' => []];
        }

        $paramprefix = 'tenantcat_' . random_string(4) . '_';
        list($insql, $params) = $DB->get_in_or_equal($allowedcategories, SQL_PARAMS_NAMED, $paramprefix);
        $result['sql'] = " AND {$tablealias}.id {$insql}";
        $result['params'] = $params;

        return $result;
    }
}

