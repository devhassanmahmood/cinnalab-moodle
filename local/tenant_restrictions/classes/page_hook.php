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
        $js_code = "
        require(['jquery'], function($) {
            // Store configuration
            window.tenantRestrictions = {
                allowedCategories: {$js_allowed_categories},
                tenantCategory: " . \local_tenant_restrictions\tenant_helper::get_tenant_category() . "
            };
            
            // Filter category dropdowns
            function filterCategoryDropdowns(allowedCategories) {
                if (!allowedCategories || allowedCategories.length === 0) {
                    return;
                }
                
                var selectors = [
                    'select[name=\"category\"]',
                    'select[name=\"categoryid\"]',
                    'select[id*=\"id_category\"]',
                    'select[id*=\"category\"]',
                    '#id_category',
                    '#id_categoryid'
                ];
                
                selectors.forEach(function(selector) {
                    $(selector).each(function() {
                        var $select = $(this);
                        var $options = $select.find('option');
                        
                        $options.each(function() {
                            var $option = $(this);
                            var value = parseInt($option.val());
                            
                            // Keep empty/default options
                            if (value === 0 || value === '' || $option.text().trim() === '' || 
                                $option.text().indexOf('Choose') !== -1 || 
                                $option.text().indexOf('Select') !== -1) {
                                return;
                            }
                            
                            // Hide options not in allowed categories
                            if (allowedCategories.indexOf(value) === -1) {
                                $option.hide();
                            }
                        });
                    });
                });
            }
            
            // Modify course creation links
            function modifyCourseCreationLinks(tenantCategory) {
                if (!tenantCategory) {
                    return;
                }
                
                $('a[href*=\"course/edit.php\"]').each(function() {
                    var $link = $(this);
                    var href = $link.attr('href');
                    
                    if (href.indexOf('category=') === -1) {
                        var separator = href.indexOf('?') !== -1 ? '&' : '?';
                        var newHref = href + separator + 'category=' + tenantCategory;
                        $link.attr('href', newHref);
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
            
            // Listen for form changes
            $(document).on('focus click', 'select[name=\"category\"], select[name=\"categoryid\"], .form-autocomplete input', function() {
                setTimeout(function() {
                    filterCategoryDropdowns(window.tenantRestrictions.allowedCategories);
                }, 200);
            });
        });
        ";

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
