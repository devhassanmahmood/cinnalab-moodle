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

define(['jquery'], function($) {
    return {
        init: function(allowedCategories) {
            // Store allowed categories for later use
            this.allowedCategories = allowedCategories;
            
            // Filter course category dropdowns
            this.filterCategoryDropdowns(allowedCategories);
            
            // Filter course management page
            this.filterCourseManagement(allowedCategories);
            
            // Filter breadcrumbs
            this.filterBreadcrumbs(allowedCategories);
            
            // Set up mutation observer for dynamically loaded content
            this.setupMutationObserver();
            
            // Also filter on document ready and window load
            $(document).ready(function() {
                this.filterCategoryDropdowns(allowedCategories);
            }.bind(this));
            
            $(window).on('load', function() {
                this.filterCategoryDropdowns(allowedCategories);
            }.bind(this));
        },

        setupMutationObserver: function() {
            // Create a mutation observer to watch for DOM changes
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    var shouldRefilter = false;
                    
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            // Check if any of the added nodes contain category-related elements
                            for (var i = 0; i < mutation.addedNodes.length; i++) {
                                var node = mutation.addedNodes[i];
                                if (node.nodeType === 1) { // Element node
                                    var $node = $(node);
                                    if ($node.find('select[name="category"], select[name="categoryid"], .form-autocomplete').length > 0 ||
                                        $node.is('select[name="category"], select[name="categoryid"], .form-autocomplete')) {
                                        shouldRefilter = true;
                                        break;
                                    }
                                }
                            }
                        }
                    });
                    
                    if (shouldRefilter) {
                        setTimeout(function() {
                            this.filterCategoryDropdowns(this.allowedCategories);
                        }.bind(this), 100);
                    }
                }.bind(this));
                
                // Start observing
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        },

        filterCategoryDropdowns: function(allowedCategories) {
            // If no allowed categories specified, don't filter
            if (!allowedCategories || allowedCategories.length === 0) {
                return;
            }

            // Target various category selection elements
            var selectors = [
                'select[name="category"]',
                'select[name="categoryid"]', 
                'select[id*="category"]',
                'select[id*="id_category"]',
                '.form-group select',
                '.form-item select',
                'select[data-field="category"]',
                'select[data-field="categoryid"]'
            ];

            selectors.forEach(function(selector) {
                $(selector).each(function() {
                    var $select = $(this);
                    var $options = $select.find('option');
                    
                    // Hide options that are not in allowed categories
                    $options.each(function() {
                        var $option = $(this);
                        var value = parseInt($option.val());
                        
                        // Keep the "Select a category" or empty option
                        if (value === 0 || value === '' || $option.text().trim() === '') {
                            return;
                        }
                        
                        // Hide options not in allowed categories
                        if (allowedCategories.indexOf(value) === -1) {
                            $option.hide();
                        }
                    });
                });
            });

            // Also filter autocomplete/select2 elements
            this.filterAutocompleteElements(allowedCategories);
        },

        filterAutocompleteElements: function(allowedCategories) {
            // Target autocomplete/select2 elements
            $('.form-autocomplete, .select2-container, [data-field="category"]').each(function() {
                var $element = $(this);
                
                // Find associated input or select
                var $input = $element.find('input, select');
                if ($input.length === 0) {
                    $input = $element;
                }
                
                // Filter based on data attributes or text content
                $element.find('li, .option, .item').each(function() {
                    var $item = $(this);
                    var text = $item.text().trim();
                    var dataValue = $item.data('value') || $item.attr('data-value');
                    var value = parseInt(dataValue);
                    
                    // Check if this item should be hidden
                    if (value && allowedCategories.indexOf(value) === -1) {
                        $item.hide();
                    }
                });
            });
        },

        filterCourseManagement: function(allowedCategories) {
            // Filter course management page categories
            if (allowedCategories && allowedCategories.length > 0) {
                $('.course_category_tree .category').each(function() {
                    var $category = $(this);
                    var categoryId = $category.data('categoryid') || $category.attr('data-categoryid');
                    
                    if (categoryId && allowedCategories.indexOf(parseInt(categoryId)) === -1) {
                        $category.hide();
                    }
                });
            }
        },

        filterBreadcrumbs: function(allowedCategories) {
            // Filter breadcrumb links
            if (allowedCategories && allowedCategories.length > 0) {
                $('.breadcrumb a').each(function() {
                    var $link = $(this);
                    var href = $link.attr('href');
                    
                    // Check if link contains categoryid parameter
                    if (href && href.indexOf('categoryid=') !== -1) {
                        var categoryId = parseInt(href.match(/categoryid=(\d+)/)[1]);
                        if (allowedCategories.indexOf(categoryId) === -1) {
                            $link.hide();
                        }
                    }
                });
            }
        }
    };
});
