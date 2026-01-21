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

namespace local_tenant_restrictions\form;

defined('MOODLE_INTERNAL') || die();

/**
 * Course category filter for forms.
 *
 * This class provides methods to filter the category dropdown
 * in course creation and editing forms.
 *
 * @package    local_tenant_restrictions
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_category_filter {

    /**
     * Filter the category select element options.
     *
     * @param \MoodleQuickForm $mform The form object
     * @return void
     */
    public static function filter_category_element($mform): void {
        if (!class_exists('\local_tenant_restrictions\helper')) {
            return;
        }

        $helper = \local_tenant_restrictions\helper::class;

        if (!$helper::should_apply_restrictions()) {
            return;
        }

        // Check if the form has a category element.
        if (!$mform->elementExists('category')) {
            return;
        }

        $element = $mform->getElement('category');
        if (!$element) {
            return;
        }

        // Get allowed categories for the current user.
        $allowedoptions = $helper::get_tenant_category_options();
        if (empty($allowedoptions)) {
            return;
        }

        // Replace the options in the select element.
        // Note: This requires the element to be a select type.
        if (method_exists($element, 'removeOptions') && method_exists($element, 'loadArray')) {
            // Clear existing options and load filtered ones.
            $element->removeOptions();
            $element->loadArray($allowedoptions);
        }

        // Set default value to tenant's root category.
        $tenantcategoryid = $helper::get_user_tenant_category_id();
        if ($tenantcategoryid && !$mform->isSubmitted()) {
            $mform->setDefault('category', $tenantcategoryid);
        }
    }

    /**
     * Validate category selection in form submission.
     *
     * @param array $data Form data
     * @return array Validation errors
     */
    public static function validate_category(array $data): array {
        $errors = [];

        if (!class_exists('\local_tenant_restrictions\helper')) {
            return $errors;
        }

        $helper = \local_tenant_restrictions\helper::class;

        if (!$helper::should_apply_restrictions()) {
            return $errors;
        }

        if (!empty($data['category'])) {
            if (!$helper::is_category_in_user_tenant((int)$data['category'])) {
                $errors['category'] = get_string('invalidcategoryselection', 'local_tenant_restrictions');
            }
        }

        return $errors;
    }
}

