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

require_once('../../config.php');
require_once($CFG->dirroot.'/course/edit_form.php');
// coursecatlib.php doesn't exist in newer Moodle versions - removed

// Check if user has restricted access
if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
    // If no restrictions, redirect to normal course edit
    $courseid = optional_param('id', 0, PARAM_INT);
    $categoryid = optional_param('category', 0, PARAM_INT);
    
    if ($courseid) {
        redirect(new moodle_url('/course/edit.php', ['id' => $courseid]));
    } else {
        redirect(new moodle_url('/course/edit.php', ['category' => $categoryid]));
    }
}

$courseid = optional_param('id', 0, PARAM_INT);
$categoryid = optional_param('category', 0, PARAM_INT);

// Get tenant information
$tenant_category = \local_tenant_restrictions\tenant_helper::get_tenant_category();
if (!$tenant_category) {
    throw new moodle_exception('notenant', 'local_tenant_restrictions');
}

// Get allowed categories (tenant + subcategories)
$allowed_categories = \local_tenant_restrictions\tenant_helper::get_allowed_categories();

// Validate category access
if ($categoryid && !in_array($categoryid, $allowed_categories)) {
    // Redirect to tenant category if trying to access unauthorized category
    redirect(new moodle_url('/local/tenant_restrictions/tenant_course_edit.php', ['category' => $tenant_category]));
}

// If no category specified, use tenant category
if (!$categoryid) {
    $categoryid = $tenant_category;
}

// Set up the page
$PAGE->set_url('/local/tenant_restrictions/tenant_course_edit.php');
$PAGE->set_context(context_system::instance());

if ($courseid) {
    // Editing existing course
    $course = get_course($courseid);
    $PAGE->set_title(get_string('editcourse') . ': ' . $course->fullname);
    $PAGE->set_heading(get_string('editcourse'));
} else {
    // Creating new course
    $PAGE->set_title(get_string('addnewcourse'));
    $PAGE->set_heading(get_string('addnewcourse'));
}

echo $OUTPUT->header();

// Override the course category list to show only tenant categories
class tenant_course_edit_form extends course_edit_form {
    
    protected function definition() {
        parent::definition();
        
        // Override the category field to show only tenant categories
        if ($mform = $this->_form) {
            $category_element = $mform->getElement('category');
            if ($category_element) {
                // Get tenant categories
                $allowed_categories = \local_tenant_restrictions\tenant_helper::get_allowed_categories();
                
                if (!empty($allowed_categories)) {
                    // Get category options for allowed categories only
                    $category_options = [];
                    foreach ($allowed_categories as $cat_id) {
                        $category = \core_course_category::get($cat_id);
                        if ($category) {
                            $category_options[$cat_id] = $category->get_formatted_name();
                        }
                    }
                    
                    // Update the category element with filtered options
                    $category_element->loadArray($category_options);
                }
            }
        }
    }
}

// Initialize the form
if ($courseid) {
    // Editing existing course
    $course = get_course($courseid);
    $mform = new tenant_course_edit_form(null, ['course' => $course, 'category' => $course->category]);
} else {
    // Creating new course
    $course = new stdClass();
    $course->id = 0;
    $course->category = $categoryid;
    $mform = new tenant_course_edit_form(null, ['course' => $course, 'category' => $categoryid]);
}

// Handle form submission
if ($mform->is_cancelled()) {
    // Redirect back to tenant course management
    redirect(new moodle_url('/local/tenant_restrictions/tenant_course_management.php'));
} else if ($data = $mform->get_data()) {
    // Process form data
    if ($courseid) {
        // Update existing course
        $data->id = $courseid;
        $course = update_course($data);
        $redirect_url = new moodle_url('/local/tenant_restrictions/tenant_course_management.php');
    } else {
        // Create new course
        $course = create_course($data);
        $redirect_url = new moodle_url('/course/view.php', ['id' => $course->id]);
    }
    
    redirect($redirect_url);
}

// Display the form
$mform->display();

echo $OUTPUT->footer();
