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

echo $OUTPUT->heading(get_string('course_management', 'local_tenant_restrictions'));

// Get tenant category information
$category = \core_course_category::get($tenant_category);
if ($category) {
    echo html_writer::tag('h3', $category->name);
    echo html_writer::tag('p', $category->description);
    
    // Show courses in this category
    $courses = $category->get_courses();
    if (!empty($courses)) {
        echo html_writer::tag('h4', get_string('courses', 'local_tenant_restrictions'));
        
        $table = new html_table();
        $table->head = [
            get_string('course'),
            get_string('fullname'),
            get_string('shortname'),
            get_string('actions')
        ];
        
        foreach ($courses as $course) {
            $actions = [];
            
            // Add edit action if user can manage course
            if (has_capability('moodle/course:update', context_course::instance($course->id))) {
                $actions[] = html_writer::link(
                    new moodle_url('/course/edit.php', ['id' => $course->id]),
                    get_string('edit'),
                    ['class' => 'btn btn-sm btn-secondary']
                );
            }
            
            // Add view action
            $actions[] = html_writer::link(
                new moodle_url('/course/view.php', ['id' => $course->id]),
                get_string('view'),
                ['class' => 'btn btn-sm btn-primary']
            );
            
            $table->data[] = [
                $course->id,
                $course->fullname,
                $course->shortname,
                implode(' ', $actions)
            ];
        }
        
        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p', get_string('nocourses', 'local_tenant_restrictions'));
    }
    
    // Add create course button
    if (has_capability('moodle/course:create', context_coursecat::instance($tenant_category))) {
        echo html_writer::tag('div', 
            html_writer::link(
                new moodle_url('/course/edit.php', ['category' => $tenant_category]),
                get_string('createcourse', 'local_tenant_restrictions'),
                ['class' => 'btn btn-primary']
            ),
            ['class' => 'mt-3']
        );
    }
} else {
    echo html_writer::tag('p', get_string('categorynotfound', 'local_tenant_restrictions'));
}

echo $OUTPUT->footer();
