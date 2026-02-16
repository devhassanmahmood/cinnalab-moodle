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

    $fileid = $event->objectid;
    $logprefix = "[ImmediateS3Upload] File {$fileid}: ";

    // Only process if ObjectFS is enabled.
    if (empty($CFG->alternative_file_system_class) ||
        $CFG->alternative_file_system_class !== '\tool_objectfs\s3_file_system') {
        error_log($logprefix . "ObjectFS not enabled, skipping");
        return;
    }

    // Check if ObjectFS tasks are enabled.
    $enabletasks = get_config('tool_objectfs', 'enabletasks');
    if (empty($enabletasks)) {
        error_log($logprefix . "ObjectFS tasks disabled, skipping");
        return;
    }

    // Get the file.
    $fs = get_file_storage();
    $file = $fs->get_file_by_id($fileid);

    if (!$file) {
        error_log($logprefix . "File not found in storage");
        return;
    }

    $contenthash = $file->get_contenthash();
    $filesize = $file->get_filesize();
    $filename = $file->get_filename();

    // Skip directory entries.
    if ($filename === '.') {
        return;
    }

    error_log($logprefix . "Processing file: {$filename} ({$filesize} bytes, hash: {$contenthash})");

    // Get ObjectFS file system.
    $objectfs = get_file_storage()->get_file_system();
    if (!($objectfs instanceof \tool_objectfs\local\store\object_file_system)) {
        error_log($logprefix . "File system is not ObjectFS instance");
        return;
    }

    // Check if file should be uploaded (respects size threshold and minimum age).
    $config = \tool_objectfs\local\manager::get_objectfs_config();

    // Check size threshold.
    if (!empty($config->sizethreshold) && $filesize < $config->sizethreshold) {
        error_log($logprefix . "File size ({$filesize}) below threshold ({$config->sizethreshold}), skipping");
        return;
    }

    // Check minimum age (if set to 0, upload immediately).
    if (!empty($config->minimumage)) {
        error_log($logprefix . "Minimum age is {$config->minimumage}, waiting for scheduled task");
        return;
    }

    // Check current location - if already uploaded, skip.
    $current_location = $objectfs->get_object_location_from_hash($contenthash);
    if ($current_location == OBJECT_LOCATION_DUPLICATED || $current_location == OBJECT_LOCATION_EXTERNAL) {
        error_log($logprefix . "File already uploaded (location: {$current_location}), skipping");
        return;
    }
    
    // If in ERROR state, reset to LOCAL first.
    if ($current_location == OBJECT_LOCATION_ERROR) {
        error_log($logprefix . "File is in ERROR state, resetting to LOCAL for retry");
        \tool_objectfs\local\manager::update_object_by_hash($contenthash, OBJECT_LOCATION_LOCAL, $filesize);
    }

    // Check if local file exists before uploading.
    $localpath = $CFG->dataroot . '/filedir/' . substr($contenthash, 0, 2) . '/' . substr($contenthash, 2, 2) . '/' . $contenthash;
    if (!file_exists($localpath)) {
        error_log($logprefix . "WARNING: Local file does not exist at {$localpath} - may have been deleted by Heroku");
        error_log($logprefix . "This is expected on Heroku's ephemeral filesystem. Scheduled task will handle this.");
        return;
    }

    error_log($logprefix . "Local file exists, attempting upload to S3...");

    // Upload immediately using ObjectFS method that handles location updates.
    try {
        // This method uploads and automatically updates location to DUPLICATED.
        $result = $objectfs->copy_object_from_local_to_external_by_hash($contenthash, $filesize);
        
        // Check the result - copy_object_from_local_to_external_by_hash returns the new location.
        $new_location = $objectfs->get_object_location_from_hash($contenthash);
        
        if ($new_location == OBJECT_LOCATION_DUPLICATED || $new_location == OBJECT_LOCATION_EXTERNAL) {
            error_log($logprefix . "SUCCESS: File uploaded to S3 (location: {$new_location})");
        } else {
            error_log($logprefix . "WARNING: Upload may have failed (location: {$new_location})");
        }
    } catch (Exception $e) {
        // Log detailed error.
        error_log($logprefix . "ERROR: Upload failed - " . $e->getMessage());
        error_log($logprefix . "Stack trace: " . $e->getTraceAsString());
    }
}

