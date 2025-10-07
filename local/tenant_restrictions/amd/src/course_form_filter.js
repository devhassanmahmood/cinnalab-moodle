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
            // Store allowed categories
            this.allowedCategories = allowedCategories;
            
            // Apply filtering immediately
            this.filterCourseForm();
            
            // Set up interval to keep filtering
            this.setupIntervalFiltering();
            
            // Set up event listeners
            this.setupEventListeners();
        },

        filterCourseForm: function() {
            if (!this.allowedCategories || this.allowedCategories.length === 0) {
                return;
            }

            // Target the specific course form elements
            this.filterCategorySelects();
            this.filterAutocompleteFields();
            this.filterFormAutocomplete();
        },

        filterCategorySelects: function() {
            // Find all category-related select elements
            var selectors = [
                'select[name="category"]',
                'select[name="categoryid"]',
                'select[id*="id_category"]',
                'select[id*="category"]',
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
                        if (this.allowedCategories.indexOf(value) === -1) {
                            $option.hide();
                        }
                    }.bind(this));
                }.bind(this));
            });
        },

        filterAutocompleteFields: function() {
            // Target autocomplete fields
            $('.form-autocomplete, .autocomplete, [data-field="category"]').each(function() {
                var $element = $(this);
                
                // Find the dropdown/options container
                var $dropdown = $element.find('.dropdown-menu, .autocomplete-suggestions, .options');
                if ($dropdown.length === 0) {
                    $dropdown = $element;
                }
                
                // Filter options in dropdown
                $dropdown.find('li, .option, .item, .suggestion').each(function() {
                    var $item = $(this);
                    var dataValue = $item.data('value') || $item.attr('data-value') || $item.attr('value');
                    var value = parseInt(dataValue);
                    
                    if (value && this.allowedCategories.indexOf(value) === -1) {
                        $item.hide();
                    }
                }.bind(this));
            });
        },

        filterFormAutocomplete: function() {
            // Target Moodle's form autocomplete specifically
            $('.form-autocomplete').each(function() {
                var $autocomplete = $(this);
                var $input = $autocomplete.find('input');
                
                if ($input.length > 0) {
                    // Override the autocomplete behavior
                    $input.on('focus click', function() {
                        setTimeout(function() {
                            this.filterAutocompleteOptions($autocomplete);
                        }.bind(this), 100);
                    }.bind(this));
                }
            });
        },

        filterAutocompleteOptions: function($autocomplete) {
            // Find the dropdown that appears
            var $dropdown = $('.dropdown-menu, .autocomplete-suggestions');
            if ($dropdown.length === 0) {
                $dropdown = $autocomplete.find('.dropdown-menu, .autocomplete-suggestions');
            }
            
            if ($dropdown.length > 0) {
                $dropdown.find('li, .option, .item').each(function() {
                    var $item = $(this);
                    var dataValue = $item.data('value') || $item.attr('data-value');
                    var value = parseInt(dataValue);
                    
                    if (value && this.allowedCategories.indexOf(value) === -1) {
                        $item.hide();
                    }
                }.bind(this));
            }
        },

        setupIntervalFiltering: function() {
            // Apply filtering every 500ms to catch dynamically loaded content
            setInterval(function() {
                this.filterCourseForm();
            }.bind(this), 500);
        },

        setupEventListeners: function() {
            // Listen for form changes and AJAX completions
            $(document).on('DOMNodeInserted', function(e) {
                if ($(e.target).find('select[name="category"], .form-autocomplete').length > 0) {
                    setTimeout(function() {
                        this.filterCourseForm();
                    }.bind(this), 100);
                }
            }.bind(this));
            
            // Listen for AJAX completions
            $(document).ajaxComplete(function() {
                setTimeout(function() {
                    this.filterCourseForm();
                }.bind(this), 100);
            }.bind(this));
        }
    };
});
