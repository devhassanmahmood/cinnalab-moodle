<?php
/**
 * S3 Upload Test using ObjectFS Client
 * 
 * This test uses ObjectFS's own S3 client, so it uses the EXACT same
 * configuration and code path as ObjectFS in Moodle.
 * 
 * Usage: 
 * - Browser: https://your-site.com/test_s3_objectfs.php
 * - CLI: heroku run "php test_s3_objectfs.php" --app cinnalab-moodle-production
 */

// Bootstrap Moodle
$is_cli = php_sapi_name() === 'cli';

if ($is_cli) {
    // CLI mode
    define('CLI_SCRIPT', true);
    define('NO_OUTPUT_BUFFERING', true);
} else {
    // Web mode - don't set CLI_SCRIPT
    // Require login for security
    require_once(__DIR__ . '/config.php');
    require_login();
    require_capability('moodle/site:config', context_system::instance());
}

// Load Moodle config
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

// Check if ObjectFS is available
if (empty($CFG->alternative_file_system_class) || 
    $CFG->alternative_file_system_class !== '\tool_objectfs\s3_file_system') {
    die("Error: ObjectFS is not enabled. Set \$CFG->alternative_file_system_class in config.php\n");
}

// Load ObjectFS
require_once($CFG->dirroot . '/admin/tool/objectfs/lib.php');
require_once($CFG->dirroot . '/admin/tool/objectfs/classes/local/manager.php');
require_once($CFG->dirroot . '/admin/tool/objectfs/classes/local/store/object_file_system.php');

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>S3 Upload Test (ObjectFS)</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        form { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    </style>
</head>
<body>
    <h1>S3 Upload Test (Using ObjectFS Client)</h1>
    <p>This test uses ObjectFS\'s own S3 client, so it uses the <strong>exact same configuration</strong> as Moodle.</p>';
}

function output($msg, $type = 'info') {
    global $is_cli;
    if ($is_cli) {
        $prefix = $type === 'error' ? 'ERROR: ' : ($type === 'success' ? 'SUCCESS: ' : ($type === 'warning' ? 'WARNING: ' : 'INFO: '));
        echo $prefix . strip_tags($msg) . "\n";
    } else {
        echo '<div class="' . $type . '">' . htmlspecialchars($msg) . '</div>';
    }
}

// Get ObjectFS configuration
$objectfs_config = \tool_objectfs\local\manager::get_objectfs_config();

output("=== ObjectFS Configuration ===", 'info');
output("Filesystem: " . ($objectfs_config->filesystem ?: 'Not set'), 'info');
output("S3 Bucket: " . ($objectfs_config->s3_bucket ?: 'Not set'), 'info');
output("S3 Region: " . ($objectfs_config->s3_region ?: 'Not set'), 'info');
output("Use SDK Creds: " . ($objectfs_config->s3_usesdkcreds ? 'Yes' : 'No'), 'info');
if (!$objectfs_config->s3_usesdkcreds) {
    output("S3 Key: " . (substr($objectfs_config->s3_key, 0, 10) . '...' ?: 'Not set'), 'info');
}

// Initialize ObjectFS file system
try {
    $fs = get_file_storage()->get_file_system();
    if (!($fs instanceof \tool_objectfs\local\store\object_file_system)) {
        throw new Exception("File system is not ObjectFS instance");
    }
    output("ObjectFS file system initialized", 'success');
} catch (Exception $e) {
    output("Failed to initialize ObjectFS: " . $e->getMessage(), 'error');
    if (!$is_cli) {
        echo '</body></html>';
    }
    exit(1);
}

// Test 1: Test S3 connection
output("=== Test 1: S3 Connection Test ===", 'info');
try {
    $external_client = $fs->get_external_client();
    if (method_exists($external_client, 'test_connection')) {
        $result = $external_client->test_connection();
        if ($result) {
            output("S3 connection test passed", 'success');
        } else {
            output("S3 connection test failed - check credentials and bucket permissions", 'error');
        }
    } else {
        output("Connection test method not available", 'warning');
    }
} catch (Exception $e) {
    output("Connection test error: " . $e->getMessage(), 'error');
    if (!$is_cli) {
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['testfile'])) {
    $file = $_FILES['testfile'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        output("File upload error: " . $file['error'], 'error');
    } else {
        $filename = $file['name'];
        $tmpfile = $file['tmp_name'];
        $filesize = filesize($tmpfile);
        
        output("=== Test 3: File Upload Test ===", 'info');
        output("File: {$filename} ({$filesize} bytes)", 'info');
        
        // Calculate content hash (same as Moodle)
        $contenthash = sha1_file($tmpfile);
        output("Content hash: {$contenthash}", 'info');
        
        // Check if file already exists
        $current_location = $fs->get_object_location_from_hash($contenthash);
        $location_names = [
            OBJECT_LOCATION_LOCAL => 'LOCAL',
            OBJECT_LOCATION_DUPLICATED => 'DUPLICATED',
            OBJECT_LOCATION_EXTERNAL => 'EXTERNAL (S3)',
            OBJECT_LOCATION_ERROR => 'ERROR',
            OBJECT_LOCATION_ORPHANED => 'ORPHANED',
        ];
        output("Current location: " . ($location_names[$current_location] ?? 'UNKNOWN'), 'info');
        
        // Try to upload
        try {
            $start_time = microtime(true);
            $success = $fs->copy_from_local_to_external($contenthash);
            $upload_time = round((microtime(true) - $start_time) * 1000, 2);
            
            if ($success) {
                output("Upload successful! Took {$upload_time}ms", 'success');
                
                // Update location
                \tool_objectfs\local\manager::update_object_by_hash($contenthash, OBJECT_LOCATION_DUPLICATED);
                
                // Verify new location
                $new_location = $fs->get_object_location_from_hash($contenthash);
                output("New location: " . ($location_names[$new_location] ?? 'UNKNOWN'), 'success');
                
                // Try to generate pre-signed URL
                output("=== Test 3: Pre-signed URL Generation ===", 'info');
                try {
                    if (method_exists($external_client, 'generate_presigned_url')) {
                        $presigned_url = $external_client->generate_presigned_url($contenthash);
                        output("Pre-signed URL generated:", 'success');
                        if ($is_cli) {
                            output($presigned_url, 'info');
                        } else {
                            echo '<pre>' . htmlspecialchars($presigned_url) . '</pre>';
                            echo '<p><a href="' . htmlspecialchars($presigned_url) . '" target="_blank">Test download</a></p>';
                        }
                    } else {
                        output("Pre-signed URL generation not available", 'warning');
                    }
                } catch (Exception $e) {
                    output("Pre-signed URL error: " . $e->getMessage(), 'error');
                }
                
            } else {
                output("Upload failed (returned false)", 'error');
            }
        } catch (Exception $e) {
            output("Upload exception: " . $e->getMessage(), 'error');
            if (!$is_cli) {
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }
        }
    }
}

if (!$is_cli) {
    echo '
    <h2>Upload Test File</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Select a file to upload:</label><br>
        <input type="file" name="testfile" required><br><br>
        <button type="submit">Upload to S3 via ObjectFS</button>
    </form>
    
    <h2>What This Test Does</h2>
    <ul>
        <li>Uses ObjectFS\'s own S3 client (same as Moodle)</li>
        <li>Uses the same configuration from ObjectFS settings</li>
        <li>Tests upload, location tracking, and pre-signed URLs</li>
        <li>Shows exactly what ObjectFS does when uploading files</li>
    </ul>
    
    <h2>Troubleshooting</h2>
    <p>If upload fails, check:</p>
    <ul>
        <li>ObjectFS settings in Moodle admin</li>
        <li>AWS credentials are correct</li>
        <li>S3 bucket permissions</li>
        <li>Check Heroku logs for detailed errors</li>
    </ul>
    </body>
</html>';
} else {
    echo "\n=== ObjectFS S3 Test Complete ===\n";
    echo "For file upload test, access this script via browser.\n";
}

