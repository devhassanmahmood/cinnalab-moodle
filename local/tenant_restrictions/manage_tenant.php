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

// Check if user is Vendor Admin
if (!\local_tenant_restrictions\tenant_helper::is_vendor_admin()) {
    throw new \moodle_exception('nopermissions', 'error', '', 'Access denied');
}

$tenant = \local_tenant_restrictions\tenant_helper::get_user_tenant();
if (!$tenant) {
    throw new \moodle_exception('notenant', 'local_tenant_restrictions');
}

$PAGE->set_url('/local/tenant_restrictions/manage_tenant.php');
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('managetenant_nav', 'local_tenant_restrictions'));
$PAGE->set_heading(get_string('managetenant_nav', 'local_tenant_restrictions'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('managetenant_nav', 'local_tenant_restrictions'));

// Display tenant information
echo html_writer::start_tag('div', ['class' => 'tenant-info']);
echo html_writer::tag('h3', get_string('tenantinfo', 'local_tenant_restrictions'));
echo html_writer::tag('p', html_writer::tag('strong', get_string('tenantname', 'local_tenant_restrictions') . ': ') . $tenant->name);
echo html_writer::tag('p', html_writer::tag('strong', get_string('tenantidnumber', 'local_tenant_restrictions') . ': ') . $tenant->idnumber);
echo html_writer::tag('p', html_writer::tag('strong', get_string('tenantcategory', 'local_tenant_restrictions') . ': ') . $tenant->categoryid);
echo html_writer::end_tag('div');

// Add links to relevant management pages
echo html_writer::start_tag('div', ['class' => 'tenant-actions']);
echo html_writer::tag('h3', get_string('tenantactions', 'local_tenant_restrictions'));

$actions = [
    [
        'url' => new \moodle_url('/course/index.php', ['categoryid' => $tenant->categoryid]),
        'text' => get_string('viewcourses', 'local_tenant_restrictions'),
        'icon' => 'i/course'
    ],
    [
        'url' => new \moodle_url('/course/editcategory.php', ['id' => $tenant->categoryid]),
        'text' => get_string('editcategory', 'local_tenant_restrictions'),
        'icon' => 'i/settings'
    ],
    [
        'url' => new \moodle_url('/course/index.php', ['categoryid' => $tenant->categoryid, 'view' => 'management']),
        'text' => get_string('managecourses', 'local_tenant_restrictions'),
        'icon' => 'i/cog'
    ]
];

foreach ($actions as $action) {
    $icon = $OUTPUT->pix_icon($action['icon'], '');
    $link = html_writer::link($action['url'], $icon . ' ' . $action['text'], ['class' => 'btn btn-secondary']);
    echo html_writer::tag('div', $link, ['class' => 'tenant-action-item']);
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
