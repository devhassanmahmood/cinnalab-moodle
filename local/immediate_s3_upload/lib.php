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
 * Immediate S3 upload hook for Heroku.
 *
 * This plugin hooks into Moodle's file creation events to immediately
 * upload files to S3 via ObjectFS, preventing file loss on Heroku's
 * ephemeral filesystem.
 *
 * @package    local_immediate_s3_upload
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook into file creation to immediately upload to S3.
 *
 * @param \core\event\file_created $event
 */
function local_immediate_s3_upload_file_created(\core\event\file_created $event) {
    global $CFG;

    // Only process if ObjectFS is enabled.
    if (empty($CFG->alternative_file_system_class) ||
        $CFG->alternative_file_system_class !== '\tool_objectfs\s3_file_system') {
        return;
    }

    // Check if ObjectFS tasks are enabled.
    $enabletasks = get_config('tool_objectfs', 'enabletasks');
    if (empty($enabletasks)) {
        return;
    }

    // Get the file.
    $fileid = $event->objectid;
    $fs = get_file_storage();
    $file = $fs->get_file_by_id($fileid);

    if (!$file) {
        return;
    }

    // Get ObjectFS file system.
    $objectfs = get_file_storage()->get_file_system();
    if (!($objectfs instanceof \tool_objectfs\local\store\object_file_system)) {
        return;
    }

    // Check if file should be uploaded (respects size threshold and minimum age).
    $config = \tool_objectfs\local\manager::get_objectfs_config();
    $contenthash = $file->get_contenthash();

    // Check size threshold.
    if (!empty($config->sizethreshold) && $file->get_filesize() < $config->sizethreshold) {
        return;
    }

    // Check minimum age (if set to 0, upload immediately).
    if (!empty($config->minimumage)) {
        // File is too new, wait for scheduled task.
        return;
    }

    // Upload immediately using ObjectFS method that handles location updates.
    try {
        // This method uploads and automatically updates location to DUPLICATED.
        $objectfs->copy_object_from_local_to_external_by_hash($contenthash, $file->get_filesize());
    } catch (Exception $e) {
        // Log error but don't fail - scheduled task will retry.
        error_log("Immediate S3 upload failed for file {$fileid} (hash: {$contenthash}): " . $e->getMessage());
    }
}

