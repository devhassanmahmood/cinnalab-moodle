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

admin_externalpage_setup('local_tenant_restrictions');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_tenant_restrictions'));

echo html_writer::tag('p', get_string('settings_description', 'local_tenant_restrictions'));

echo html_writer::start_tag('div', ['class' => 'tenant-restrictions-info']);
echo html_writer::tag('h3', get_string('current_settings', 'local_tenant_restrictions'));

$settings = [
    'enabled' => get_config('local_tenant_restrictions', 'enabled'),
    'restrict_course_creation' => get_config('local_tenant_restrictions', 'restrict_course_creation'),
    'restrict_category_access' => get_config('local_tenant_restrictions', 'restrict_category_access'),
    'add_manage_tenant_menu' => get_config('local_tenant_restrictions', 'add_manage_tenant_menu'),
];

foreach ($settings as $setting => $value) {
    $status = $value ? get_string('enabled', 'local_tenant_restrictions') : get_string('disabled', 'local_tenant_restrictions');
    $class = $value ? 'success' : 'warning';
    echo html_writer::tag('p', 
        html_writer::tag('strong', get_string($setting, 'local_tenant_restrictions') . ': ') . 
        html_writer::tag('span', $status, ['class' => 'badge badge-' . $class])
    );
}

echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'tenant-restrictions-actions']);
echo html_writer::tag('h3', get_string('quick_actions', 'local_tenant_restrictions'));

$actions = [
    [
        'url' => new moodle_url('/admin/settings.php', ['section' => 'local_tenant_restrictions']),
        'text' => get_string('edit_settings', 'local_tenant_restrictions'),
        'icon' => 'i/settings'
    ],
    [
        'url' => new moodle_url('/admin/roles/manage.php'),
        'text' => get_string('manage_roles', 'local_tenant_restrictions'),
        'icon' => 'i/roles'
    ],
    [
        'url' => new moodle_url('/admin/user.php'),
        'text' => get_string('assign_roles', 'local_tenant_restrictions'),
        'icon' => 'i/user'
    ]
];

foreach ($actions as $action) {
    $icon = $OUTPUT->pix_icon($action['icon'], '');
    $link = html_writer::link($action['url'], $icon . ' ' . $action['text'], ['class' => 'btn btn-secondary']);
    echo html_writer::tag('div', $link, ['class' => 'tenant-action-item']);
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
