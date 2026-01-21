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

/**
 * AMD module to filter category dropdowns based on tenant restrictions.
 *
 * This module runs on course edit pages and filters the category
 * autocomplete/select options to only show categories within the
 * user's tenant hierarchy.
 *
 * @module     local_tenant_restrictions/category_filter
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    return {
        /**
         * Initialize the category filter.
         *
         * @param {Array} allowedCategoryIds Array of allowed category IDs
         * @param {Number} defaultCategoryId Default category ID to select
         */
        init: function(allowedCategoryIds, defaultCategoryId) {
            // Wait for DOM ready
            $(document).ready(function() {
                // Find category select/autocomplete element
                var $categoryElement = $('#id_category');
                
                if ($categoryElement.length === 0) {
                    return;
                }

                // Get element type
                var tagName = $categoryElement.prop('tagName').toLowerCase();
                
                if (tagName === 'select') {
                    // Standard select element
                    filterSelectElement($categoryElement, allowedCategoryIds, defaultCategoryId);
                } else if (tagName === 'input') {
                    // Autocomplete input - need to filter suggestions
                    filterAutocompleteElement($categoryElement, allowedCategoryIds, defaultCategoryId);
                }
            });

            /**
             * Filter a standard select element.
             *
             * @param {jQuery} $select The select element
             * @param {Array} allowedIds Allowed category IDs
             * @param {Number} defaultId Default category ID
             */
            function filterSelectElement($select, allowedIds, defaultId) {
                $select.find('option').each(function() {
                    var optionValue = parseInt($(this).val(), 10);
                    if (optionValue > 0 && allowedIds.indexOf(optionValue) === -1) {
                        $(this).remove();
                    }
                });

                // Set default value if not already selected
                if (defaultId && $select.val() === '') {
                    $select.val(defaultId);
                }
            }

            /**
             * Filter autocomplete element suggestions.
             *
             * @param {jQuery} $input The autocomplete input
             * @param {Array} allowedIds Allowed category IDs
             * @param {Number} defaultId Default category ID
             */
            function filterAutocompleteElement($input, allowedIds, defaultId) {
                // For Moodle's autocomplete, we need to filter the hidden select
                var $hiddenSelect = $input.siblings('select[name="category"]');
                
                if ($hiddenSelect.length) {
                    filterSelectElement($hiddenSelect, allowedIds, defaultId);
                }

                // Also intercept the autocomplete AJAX if possible
                // This is a fallback for dynamically loaded options
                var originalVal = $input.val();
                
                // Observe for DOM changes in the autocomplete suggestions
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        $(mutation.addedNodes).find('[data-value]').each(function() {
                            var itemValue = parseInt($(this).data('value'), 10);
                            if (itemValue > 0 && allowedIds.indexOf(itemValue) === -1) {
                                $(this).hide();
                            }
                        });
                    });
                });

                // Start observing the autocomplete container
                var $container = $input.closest('.form-autocomplete-downdrop, .form-autocomplete-suggestions');
                if ($container.length) {
                    observer.observe($container[0], { childList: true, subtree: true });
                }
            }
        }
    };
});

