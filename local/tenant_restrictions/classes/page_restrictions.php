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
 * Page restrictions for tenant users.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_restrictions {

    /**
     * Apply category filtering on course management page.
     */
    public static function filter_course_management_page() {
        global $PAGE;

        // Check if user has tenant restrictions (vendor or partner_manager)
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        // Only apply on course management page
        if (!$PAGE->url || !$PAGE->url->compare(new \moodle_url('/course/management.php'), URL_MATCH_BASE)) {
            return;
        }

        // Get allowed categories
        $allowed_categories = tenant_helper::get_allowed_categories();
        if (empty($allowed_categories)) {
            return;
        }

        $tenant_category = tenant_helper::get_tenant_category();

        // Inject JavaScript to filter categories on the course management page
        $js_code = '
        require(["jquery"], function($) {
            $(document).ready(function() {
                var allowedCategories = ' . json_encode($allowed_categories) . ';
                var tenantCategory = ' . $tenant_category . ';
                
                console.log("Tenant restrictions active - filtering categories");
                console.log("Allowed categories:", allowedCategories);
                
                // Function to filter categories in the category list
                function filterCategories() {
                    // Filter category listings in the management page
                    $(".categorypicker, .category-listing, .category-list").each(function() {
                        $(this).find("li, .category-item, .listitem").each(function() {
                            var $item = $(this);
                            var categoryId = parseInt($item.data("categoryid") || $item.attr("data-categoryid") || $item.attr("data-id"));
                            
                            if (categoryId && allowedCategories.indexOf(categoryId) === -1) {
                                $item.hide().remove();
                            }
                        });
                    });
                    
                    // Filter category dropdowns
                    $("#menucategoryid, select[name=\"categoryid\"], #id_category").each(function() {
                        var $select = $(this);
                        $select.find("option").each(function() {
                            var $option = $(this);
                            var value = parseInt($option.val());
                            
                            if (value && allowedCategories.indexOf(value) === -1) {
                                $option.remove();
                            }
                        });
                        
                        // Auto-select tenant category if nothing selected
                        if (!$select.val() || $select.val() === "0") {
                            $select.val(tenantCategory);
                        }
                    });
                    
                    // Filter category tree/hierarchy
                    $(".category-tree, .categorytree").find("li, .category-item").each(function() {
                        var $item = $(this);
                        var categoryId = parseInt($item.data("categoryid") || $item.attr("data-categoryid") || $item.data("id"));
                        
                        if (categoryId && allowedCategories.indexOf(categoryId) === -1) {
                            $item.hide().remove();
                        }
                    });
                    
                    // Filter table rows with category data
                    $("table.categorytable, table.categorylist, table.course-list").find("tr").each(function() {
                        var $row = $(this);
                        var categoryId = parseInt($row.data("categoryid") || $row.attr("data-categoryid"));
                        
                        if (categoryId && allowedCategories.indexOf(categoryId) === -1) {
                            $row.hide().remove();
                        }
                    });
                }
                
                // Apply filtering immediately
                filterCategories();
                
                // Re-apply filtering after AJAX requests (Moodle uses AJAX for some updates)
                setTimeout(filterCategories, 500);
                setTimeout(filterCategories, 1000);
                setTimeout(filterCategories, 2000);
                
                // Watch for DOM changes and re-filter
                if (typeof MutationObserver !== "undefined") {
                    var observer = new MutationObserver(function(mutations) {
                        filterCategories();
                    });
                    
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        });
        ';

        $PAGE->requires->js_amd_inline($js_code);
    }

    /**
     * Apply category filtering on course edit page (creation/editing).
     */
    public static function filter_course_edit_page() {
        global $PAGE;

        // Check if user has tenant restrictions (vendor or partner_manager)
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        // Only apply on course edit page
        if (!$PAGE->url || !$PAGE->url->compare(new \moodle_url('/course/edit.php'), URL_MATCH_BASE)) {
            return;
        }

        // Get allowed categories
        $allowed_categories = tenant_helper::get_allowed_categories();
        if (empty($allowed_categories)) {
            return;
        }

        $tenant_category = tenant_helper::get_tenant_category();

        // Inject JavaScript to filter category dropdown
        $js_code = '
        require(["jquery"], function($) {
            $(document).ready(function() {
                var allowedCategories = ' . json_encode($allowed_categories) . ';
                var tenantCategory = ' . $tenant_category . ';
                
                console.log("Tenant restrictions active - filtering course category dropdown");
                console.log("Allowed categories:", allowedCategories);
                
                function filterCategoryDropdown() {
                    // Find the category select element
                    var $categorySelect = $("#id_category");
                    
                    if ($categorySelect.length === 0) {
                        return;
                    }
                    
                    // Filter options in the select element
                    $categorySelect.find("option").each(function() {
                        var $option = $(this);
                        var value = parseInt($option.val());
                        
                        // Skip empty/default options
                        if (!value || value === 0) {
                            return;
                        }
                        
                        // Remove options not in allowed categories
                        if (allowedCategories.indexOf(value) === -1) {
                            $option.remove();
                        }
                    });
                    
                    // Auto-select tenant category if nothing selected or invalid selection
                    var currentValue = parseInt($categorySelect.val());
                    if (!currentValue || currentValue === 0 || allowedCategories.indexOf(currentValue) === -1) {
                        $categorySelect.val(tenantCategory);
                        $categorySelect.trigger("change");
                    }
                }
                
                // Apply filtering immediately
                filterCategoryDropdown();
                
                // Re-apply when the form is loaded or updated
                setTimeout(filterCategoryDropdown, 500);
                setTimeout(filterCategoryDropdown, 1000);
                
                // Apply when category dropdown is focused
                $(document).on("focus click", "#id_category", function() {
                    setTimeout(filterCategoryDropdown, 100);
                });
            });
        });
        ';

        $PAGE->requires->js_amd_inline($js_code);
    }
}

