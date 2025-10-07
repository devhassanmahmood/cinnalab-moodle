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

namespace local_tenant_restrictions\observers;

/**
 * Course form observer for tenant restrictions.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_form_observer {

    /**
     * Filter category dropdown for tenant users.
     * This observer hooks into the course form display event and filters the category dropdown.
     *
     * @param \core\event\course_edit_form_displayed $event
     */
    public static function filter_category_dropdown($event) {
        global $USER, $PAGE;

        // Check if user has tenant restrictions
        if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
            return;
        }

        // Get tenant information
        $tenant = \local_tenant_restrictions\tenant_helper::get_user_tenant();
        if (!$tenant) {
            return;
        }

        // Inject JavaScript to filter the category dropdown
        $tenant_category = $tenant->categoryid;
        $allowed_categories = \local_tenant_restrictions\tenant_helper::get_allowed_categories();

        $js_code = '
        require(["jquery"], function(jQuery) {
            jQuery(document).ready(function() {
                // Wait for the form to be fully loaded
                setTimeout(function() {
                    filterCategoryDropdown();
                }, 500);
                
                // Also filter when the dropdown is opened
                jQuery(document).on("focus click", "#id_category", function() {
                    setTimeout(function() {
                        filterCategoryDropdown();
                    }, 100);
                });
            });
            
            function filterCategoryDropdown() {
                var allowedCategories = ' . json_encode($allowed_categories) . ';
                var tenantCategory = ' . $tenant_category . ';
                
                // Find the category autocomplete element
                var categoryElement = jQuery("#id_category");
                if (categoryElement.length === 0) {
                    return;
                }
                
                // Get the autocomplete dropdown container
                var autocompleteContainer = categoryElement.closest(".form-autocomplete");
                if (autocompleteContainer.length === 0) {
                    return;
                }
                
                // Filter the dropdown options
                var dropdown = autocompleteContainer.find(".autocomplete-dropdown, .dropdown-menu");
                if (dropdown.length > 0) {
                    dropdown.find("li, .option, .item").each(function() {
                        var item = jQuery(this);
                        var dataValue = item.data("value") || item.attr("data-value") || item.attr("value");
                        var value = parseInt(dataValue);
                        
                        // Hide items not in allowed categories
                        if (value && allowedCategories.indexOf(value) === -1) {
                            item.hide();
                        }
                    });
                }
                
                // Also filter the select element if it exists
                var selectElement = autocompleteContainer.find("select");
                if (selectElement.length > 0) {
                    selectElement.find("option").each(function() {
                        var option = jQuery(this);
                        var value = parseInt(option.val());
                        
                        // Keep empty/default options
                        if (value === 0 || value === "" || option.text().trim() === "") {
                            return;
                        }
                        
                        // Hide options not in allowed categories
                        if (allowedCategories.indexOf(value) === -1) {
                            option.hide();
                        }
                    });
                }
                
                // Ensure tenant category is selected if no category is currently selected
                var currentValue = categoryElement.val();
                if (!currentValue || currentValue === "" || currentValue === "0") {
                    categoryElement.val(tenantCategory);
                }
            }
        });
        ';

        $PAGE->requires->js_amd_inline($js_code);
    }
}
