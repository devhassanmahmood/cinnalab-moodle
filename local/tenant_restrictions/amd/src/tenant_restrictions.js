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
            // Filter course category dropdowns
            this.filterCategoryDropdowns(allowedCategories);
            
            // Filter course management page
            this.filterCourseManagement(allowedCategories);
        },

        filterCategoryDropdowns: function(allowedCategories) {
            // Find all category dropdowns and filter them
            $('select[name="category"], select[name="categoryid"], select[id*="category"]').each(function() {
                var $select = $(this);
                var $options = $select.find('option');
                
                // If no allowed categories specified, don't filter
                if (!allowedCategories || allowedCategories.length === 0) {
                    return;
                }
                
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
        }
    };
});
