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

/**
 * Test script for hook system migration.
 *
 * @package     local_tenant_restrictions
 * @copyright   2025 CinnaLab
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

// Only allow administrators to run this test
if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', 'Access denied');
}

$PAGE->set_url('/local/tenant_restrictions/test_hooks.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Hook System Test');
$PAGE->set_heading('Hook System Test');

echo $OUTPUT->header();

echo html_writer::tag('h2', 'Hook System Migration Test');

// Test 1: Check if hook classes exist
echo html_writer::tag('h3', 'Test 1: Hook Classes Check');
if (class_exists('\local_tenant_restrictions\hook\before_http_headers_hook')) {
    echo html_writer::tag('p', '✅ before_http_headers_hook class exists');
} else {
    echo html_writer::tag('p', '❌ before_http_headers_hook class not found');
}

if (class_exists('\local_tenant_restrictions\hook\before_footer_hook')) {
    echo html_writer::tag('p', '✅ before_footer_hook class exists');
} else {
    echo html_writer::tag('p', '❌ before_footer_hook class not found');
}

// Test 2: Check if hooks.php file exists
echo html_writer::tag('h3', 'Test 2: Hook Registration File Check');
$hooks_file = $CFG->dirroot . '/local/tenant_restrictions/db/hooks.php';
if (file_exists($hooks_file)) {
    echo html_writer::tag('p', '✅ hooks.php file exists');
    
    // Check if file contains hook registrations
    $content = file_get_contents($hooks_file);
    if (strpos($content, 'before_http_headers') !== false) {
        echo html_writer::tag('p', '✅ before_http_headers hook registered');
    } else {
        echo html_writer::tag('p', '❌ before_http_headers hook not registered');
    }
    
    if (strpos($content, 'before_footer_html_generation') !== false) {
        echo html_writer::tag('p', '✅ before_footer_html_generation hook registered');
    } else {
        echo html_writer::tag('p', '❌ before_footer_html_generation hook not registered');
    }
} else {
    echo html_writer::tag('p', '❌ hooks.php file not found');
}

// Test 3: Check if legacy functions are removed
echo html_writer::tag('h3', 'Test 3: Legacy Functions Check');
$lib_file = $CFG->dirroot . '/local/tenant_restrictions/lib.php';
$content = file_get_contents($lib_file);

if (strpos($content, 'local_tenant_restrictions_before_footer') === false) {
    echo html_writer::tag('p', '✅ Legacy before_footer function removed');
} else {
    echo html_writer::tag('p', '❌ Legacy before_footer function still exists');
}

if (strpos($content, 'local_tenant_restrictions_before_http_headers') === false) {
    echo html_writer::tag('p', '✅ Legacy before_http_headers function removed');
} else {
    echo html_writer::tag('p', '❌ Legacy before_http_headers function still exists');
}

// Test 4: Check Moodle version compatibility
echo html_writer::tag('h3', 'Test 4: Moodle Version Check');
$moodle_version = $CFG->version;
echo html_writer::tag('p', 'Moodle Version: ' . $moodle_version);

if ($moodle_version >= 2024042200) {
    echo html_writer::tag('p', '✅ Moodle version supports new hook system');
} else {
    echo html_writer::tag('p', '❌ Moodle version may not support new hook system');
}

echo html_writer::tag('hr', '');
echo html_writer::tag('p', html_writer::link(new moodle_url('/admin/settings.php', ['section' => 'local_tenant_restrictions']), 'Go to Plugin Settings', ['class' => 'btn btn-primary']));

echo $OUTPUT->footer();
