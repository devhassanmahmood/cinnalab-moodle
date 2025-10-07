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
 * Page hook for tenant restrictions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_hook {

    /**
     * Hook to inject JavaScript and apply restrictions on page load.
     */
    public static function inject_restrictions() {
        global $PAGE;

        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        $allowed_categories = tenant_helper::get_allowed_categories();
        
        // Convert to JavaScript array
        $js_allowed_categories = json_encode($allowed_categories);

        // Inject JavaScript to filter categories using inline approach
        $js_code = '
        require(["jquery"], function(jQuery) {
            // Store configuration
            window.tenantRestrictions = {
                allowedCategories: ' . $js_allowed_categories . ',
                tenantCategory: ' . \local_tenant_restrictions\tenant_helper::get_tenant_category() . '
            };
            
            // Filter category dropdowns
            function filterCategoryDropdowns(allowedCategories) {
                if (!allowedCategories || allowedCategories.length === 0) {
                    return;
                }
                
                var selectors = [
                    "select[name=\\"category\\"]",
                    "select[name=\\"categoryid\\"]",
                    "select[id*=\\"id_category\\"]",
                    "select[id*=\\"category\\"]",
                    "#id_category",
                    "#id_categoryid"
                ];
                
                selectors.forEach(function(selector) {
                    jQuery(selector).each(function() {
                        var selectElement = jQuery(this);
                        var options = selectElement.find("option");
                        
                        options.each(function() {
                            var optionElement = jQuery(this);
                            var value = parseInt(optionElement.val());
                            
                            // Keep empty/default options
                            if (value === 0 || value === "" || optionElement.text().trim() === "" || 
                                optionElement.text().indexOf("Choose") !== -1 || 
                                optionElement.text().indexOf("Select") !== -1) {
                                return;
                            }
                            
                            // Keep tenant category, remove all others
                            if (allowedCategories.indexOf(value) === -1) {
                                optionElement.remove();
                            }
                        });
                    });
                });
                
                // Also filter custom dropdown elements that Moodle creates
                filterCustomDropdowns(allowedCategories);
                
                // Ensure tenant category is selected if only one category is allowed
                if (allowedCategories.length === 1) {
                    selectors.forEach(function(selector) {
                        jQuery(selector).each(function() {
                            var selectElement = jQuery(this);
                            var tenantCategoryValue = allowedCategories[0];
                            
                            // Check if tenant category option exists
                            var tenantOption = selectElement.find("option[value=\\"" + tenantCategoryValue + "\\"]");
                            
                            if (tenantOption.length === 0) {
                                // If tenant category option doesn\'t exist, we need to find it from the original data
                                // This shouldn\'t happen if filtering is working correctly
                                console.log("Tenant category option not found, this indicates a filtering issue");
                                return;
                            }
                            
                            // Set the tenant category as selected
                            selectElement.val(tenantCategoryValue);
                            
                            // Mark the option as selected
                            tenantOption.prop("selected", true);
                            
                            // Trigger change event to update any custom UI
                            selectElement.trigger("change");
                            
                            // Also trigger input event for custom dropdowns
                            selectElement.trigger("input");
                        });
                    });
                }
            }
            
            // Filter custom dropdown elements (autocomplete, select2, etc.)
            function filterCustomDropdowns(allowedCategories) {
                // Target various custom dropdown containers
                var customSelectors = [
                    ".form-autocomplete",
                    ".autocomplete-suggestions",
                    ".dropdown-menu",
                    ".select2-results",
                    ".ui-autocomplete",
                    "[data-field=\\"category\\"]"
                ];
                
                customSelectors.forEach(function(selector) {
                    jQuery(selector).each(function() {
                        var container = jQuery(this);
                        
                        // Find and remove unauthorized items
                        container.find("li, .option, .item, .suggestion").each(function() {
                            var item = jQuery(this);
                            var dataValue = item.data("value") || item.attr("data-value") || item.attr("value");
                            var value = parseInt(dataValue);
                            
                            if (value && allowedCategories.indexOf(value) === -1) {
                                item.remove();
                            }
                        });
                    });
                });
                
                // Ensure tenant category is selected in custom dropdowns
                if (allowedCategories.length === 1) {
                    var tenantCategoryValue = allowedCategories[0];
                    
                    // Find and select tenant category in custom dropdowns
                    customSelectors.forEach(function(selector) {
                        jQuery(selector).each(function() {
                            var container = jQuery(this);
                            var tenantItem = container.find("[data-value=\\"" + tenantCategoryValue + "\\"], [value=\\"" + tenantCategoryValue + "\\"]").first();
                            
                            if (tenantItem.length > 0) {
                                // Mark as selected
                                tenantItem.addClass("selected active");
                                tenantItem.attr("aria-selected", "true");
                                
                                // Update associated input field if exists
                                var input = container.find("input");
                                if (input.length > 0) {
                                    input.val(tenantItem.text());
                                    input.attr("data-value", tenantCategoryValue);
                                }
                            }
                        });
                    });
                }
            }
            
            // Modify course creation links
            function modifyCourseCreationLinks(tenantCategory) {
                if (!tenantCategory) {
                    return;
                }
                
                jQuery("a[href*=\\"course/edit.php\\"]").each(function() {
                    var linkElement = jQuery(this);
                    var href = linkElement.attr("href");
                    
                    // Replace course/edit.php with our restricted version
                    if (href.indexOf("course/edit.php") !== -1) {
                        var newHref = href.replace("course/edit.php", "local/tenant_restrictions/course_edit_restricted.php");
                        linkElement.attr("href", newHref);
                    }
                });
            }
            
            // Apply filtering immediately
            filterCategoryDropdowns(window.tenantRestrictions.allowedCategories);
            modifyCourseCreationLinks(window.tenantRestrictions.tenantCategory);
            
            // Set up interval for dynamic content
            setInterval(function() {
                filterCategoryDropdowns(window.tenantRestrictions.allowedCategories);
                modifyCourseCreationLinks(window.tenantRestrictions.tenantCategory);
            }, 1000);
            
            // Listen for form changes and dropdown events
            jQuery(document).on("focus click", "select[name=\\"category\\"], select[name=\\"categoryid\\"], .form-autocomplete input", function() {
                setTimeout(function() {
                    filterCategoryDropdowns(window.tenantRestrictions.allowedCategories);
                }, 200);
            });
            
            // Listen for dropdown opening events
            jQuery(document).on("click focus", ".form-autocomplete input, [data-field=\\"category\\"] input", function() {
                setTimeout(function() {
                    filterCategoryDropdowns(window.tenantRestrictions.allowedCategories);
                }, 100);
            });
            
            // Listen for AJAX completions that might reload dropdown content
            jQuery(document).ajaxComplete(function() {
                setTimeout(function() {
                    filterCategoryDropdowns(window.tenantRestrictions.allowedCategories);
                }, 300);
            });
        });
        ';

        $PAGE->requires->js_amd_inline($js_code);
    }

    /**
     * Hook to restrict course management page.
     */
    public static function restrict_course_management_page() {
        global $PAGE;

        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        // Check if we're on the course management page
        if ($PAGE->url->compare(new \moodle_url('/course/management.php'), URL_MATCH_BASE)) {
            $tenant_category = tenant_helper::get_tenant_category();
            if ($tenant_category) {
                // Redirect to tenant category management
                redirect(new \moodle_url('/course/index.php', ['categoryid' => $tenant_category]));
            }
        }
    }

    /**
     * Hook to restrict course creation page.
     */
    public static function restrict_course_creation_page() {
        global $PAGE;

        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        // Check if we're on the course creation page
        if ($PAGE->url->compare(new \moodle_url('/course/edit.php'), URL_MATCH_BASE)) {
            $categoryid = optional_param('category', 0, PARAM_INT);
            if ($categoryid && !course_filter::can_access_category($categoryid)) {
                $tenant_category = tenant_helper::get_tenant_category();
                if ($tenant_category) {
                    // Redirect to tenant category course creation
                    redirect(new \moodle_url('/course/edit.php', ['category' => $tenant_category]));
                }
            }
        }
    }

    /**
     * Hook to restrict category access.
     */
    public static function restrict_category_access_page() {
        global $PAGE;

        // Only apply restrictions to users with tenant restrictions
        if (!tenant_helper::has_restricted_access()) {
            return;
        }

        // Check if we're accessing a category
        if ($PAGE->url->compare(new \moodle_url('/course/index.php'), URL_MATCH_BASE)) {
            $categoryid = optional_param('categoryid', 0, PARAM_INT);
            if ($categoryid && !course_filter::can_access_category($categoryid)) {
                $tenant_category = tenant_helper::get_tenant_category();
                if ($tenant_category) {
                    // Redirect to tenant category
                    redirect(new \moodle_url('/course/index.php', ['categoryid' => $tenant_category]));
                }
            }
        }
    }
}
