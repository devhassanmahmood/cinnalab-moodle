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
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');

// Check if user has restricted access
if (!\local_tenant_restrictions\tenant_helper::has_restricted_access()) {
    // If no restrictions, redirect to normal course management
    redirect(new moodle_url('/course/management.php'));
}

$tenant_category = \local_tenant_restrictions\tenant_helper::get_tenant_category();
if (!$tenant_category) {
    throw new moodle_exception('notenant', 'local_tenant_restrictions');
}

$PAGE->set_url('/local/tenant_restrictions/tenant_course_management.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('course_management', 'local_tenant_restrictions'));
$PAGE->set_heading(get_string('course_management', 'local_tenant_restrictions'));

echo $OUTPUT->header();

//echo $OUTPUT->heading(get_string('course_management', 'local_tenant_restrictions'));

// Get all allowed categories (tenant category + subcategories)
$allowed_categories = \local_tenant_restrictions\tenant_helper::get_allowed_categories();

if (!empty($allowed_categories)) {
    echo html_writer::tag('h3', get_string('tenant_categories', 'local_tenant_restrictions'));
    
    $all_courses = [];
    $category_info = [];
    
    // Get courses from all allowed categories
    foreach ($allowed_categories as $cat_id) {
        $category = \core_course_category::get($cat_id);
        if ($category) {
            $category_info[$cat_id] = [
                'name' => $category->name,
                'description' => $category->description,
                'path' => $category->path
            ];
            
            $courses = $category->get_courses();
            foreach ($courses as $course) {
                // Create a new object to store course info with category data
                $course_info = new stdClass();
                $course_info->id = $course->id;
                $course_info->fullname = $course->fullname;
                $course_info->shortname = $course->shortname;
                $course_info->category = $course->category;
                $course_info->category_id = $cat_id;
                $course_info->category_name = $category->name;
                $all_courses[] = $course_info;
            }
        }
    }
    
    // Display categories with their courses
    foreach ($allowed_categories as $cat_id) {
        $category = \core_course_category::get($cat_id);
        if ($category) {
            echo html_writer::tag('h4', $category->name);
            if (!empty($category->description)) {
                echo html_writer::tag('p', $category->description);
            }
            
            // Get courses for this specific category
            $category_courses = array_filter($all_courses, function($course_info) use ($cat_id) {
                return $course_info->category_id == $cat_id;
            });
            
            if (!empty($category_courses)) {
                $table = new html_table();
                $table->head = [
                    get_string('course'),
                    get_string('fullname'),
                    get_string('shortname'),
                    get_string('actions')
                ];
                
                foreach ($category_courses as $course_info) {
                    $actions = [];
                    
                    // Add edit action if user can manage course
                    if (has_capability('moodle/course:update', context_course::instance($course_info->id))) {
                        $actions[] = html_writer::link(
                            new moodle_url('/course/edit.php', ['id' => $course_info->id]),
                            get_string('edit'),
                            ['class' => 'btn btn-sm btn-secondary']
                        );
                    }
                    
                    // Add view action
                    $actions[] = html_writer::link(
                        new moodle_url('/course/view.php', ['id' => $course_info->id]),
                        get_string('view'),
                        ['class' => 'btn btn-sm btn-primary']
                    );
                    
                    $table->data[] = [
                        $course_info->id,
                        $course_info->fullname,
                        $course_info->shortname,
                        implode(' ', $actions)
                    ];
                }
                
                echo html_writer::table($table);
            } else {
                echo html_writer::tag('p', get_string('nocourses', 'local_tenant_restrictions'));
            }
            
            // Add create course button for this category
            if (has_capability('moodle/course:create', context_coursecat::instance($cat_id))) {
                echo html_writer::tag('div', 
                    html_writer::link(
                        new moodle_url('/course/edit.php', ['category' => $cat_id]),
                        get_string('createcourse', 'local_tenant_restrictions') . ' ' . $category->name,
                        ['class' => 'btn btn-primary btn-sm']
                    ),
                    ['class' => 'mt-2 mb-3']
                );
            }
        }
    }
} else {
    echo html_writer::tag('p', get_string('categorynotfound', 'local_tenant_restrictions'));
}

echo $OUTPUT->footer();
