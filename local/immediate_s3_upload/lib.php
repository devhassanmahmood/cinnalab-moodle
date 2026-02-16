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
 * Hook into file creation (via hook) to immediately upload to S3.
 * This hook fires AFTER the file is written to disk, which is better for Heroku.
 * 
 * This function handles both:
 * - Modern hook format: receives \core_files\hook\after_file_created object
 * - Legacy callback format: receives stdClass filerecord
 *
 * @param \core_files\hook\after_file_created|\stdClass $hook_or_record Hook object or filerecord
 */
function local_immediate_s3_upload_after_file_created($hook_or_record) {
    // Handle modern hook format
    if ($hook_or_record instanceof \core_files\hook\after_file_created) {
        $file = $hook_or_record->storedfile;
        $fileid = $file->get_id();
        local_immediate_s3_upload_process_file($fileid, $file);
        return;
    }
    
    // Handle legacy callback format (stdClass filerecord)
    if ($hook_or_record instanceof \stdClass && isset($hook_or_record->id)) {
        $fileid = $hook_or_record->id;
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($fileid);
        if ($file) {
            local_immediate_s3_upload_process_file($fileid, $file);
        }
        return;
    }
    
    // Unknown format - log and skip
    error_log("[ImmediateS3Upload] Unknown hook format received: " . gettype($hook_or_record));
}

/**
 * Hook into file creation (via event) to immediately upload to S3.
 * This is a fallback in case the hook doesn't fire.
 *
 * @param \core\event\file_created $event
 */
function local_immediate_s3_upload_file_created(\core\event\file_created $event) {
    $fileid = $event->objectid;
    $fs = get_file_storage();
    $file = $fs->get_file_by_id($fileid);
    
    if ($file) {
        local_immediate_s3_upload_process_file($fileid, $file);
    }
}

/**
 * Process file upload to S3.
 *
 * @param int $fileid File ID
 * @param \stored_file $file File object
 */
function local_immediate_s3_upload_process_file($fileid, $file) {
    global $CFG;

    $logprefix = "[ImmediateS3Upload] File {$fileid}: ";

    // Only process if ObjectFS is enabled.
    if (empty($CFG->alternative_file_system_class) ||
        $CFG->alternative_file_system_class !== '\tool_objectfs\s3_file_system') {
        return; // Silent return - ObjectFS not configured
    }

    // Check if ObjectFS tasks are enabled.
    $enabletasks = get_config('tool_objectfs', 'enabletasks');
    if (empty($enabletasks)) {
        return; // Silent return - tasks disabled
    }

    if (!$file) {
        error_log($logprefix . "File object is null");
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
    // Try multiple possible locations (Heroku might store files temporarily in different places)
    $l1 = substr($contenthash, 0, 2);
    $l2 = substr($contenthash, 2, 2);
    $localpath = $CFG->dataroot . '/filedir/' . $l1 . '/' . $l2 . '/' . $contenthash;
    
    // Also check PHP's temp directory (files might be there temporarily)
    $tmppath = sys_get_temp_dir() . '/' . $contenthash;
    
    $fileexists = false;
    $actualpath = null;
    
    if (file_exists($localpath)) {
        $fileexists = true;
        $actualpath = $localpath;
    } elseif (file_exists($tmppath)) {
        $fileexists = true;
        $actualpath = $tmppath;
        // Try to move it to the correct location
        $dir = dirname($localpath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (@copy($tmppath, $localpath)) {
            $actualpath = $localpath;
            error_log($logprefix . "Moved file from temp to {$localpath}");
        }
    }
    
    if (!$fileexists) {
        error_log($logprefix . "WARNING: Local file does not exist at {$localpath} or {$tmppath}");
        error_log($logprefix . "File may have been deleted by Heroku. Attempting upload anyway - ObjectFS may handle it.");
        // Continue - ObjectFS might be able to upload from contenthash alone or from another location
    } else {
        error_log($logprefix . "Found file at: {$actualpath}, attempting upload to S3...");
    }

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

