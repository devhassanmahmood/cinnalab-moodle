<?php
/**
 * Diagnostic script to check if immediate S3 upload plugin is working
 * 
 * Usage: Access via browser (requires admin login)
 */

$is_cli = php_sapi_name() === 'cli';

if ($is_cli) {
    define('CLI_SCRIPT', true);
    define('NO_OUTPUT_BUFFERING', true);
} else {
    require_once(__DIR__ . '/config.php');
    require_login();
    require_capability('moodle/site:config', context_system::instance());
}

require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

function output($msg, $type = 'info') {
    global $is_cli;
    if ($is_cli) {
        $prefix = $type === 'error' ? 'ERROR: ' : ($type === 'success' ? 'SUCCESS: ' : ($type === 'warning' ? 'WARNING: ' : 'INFO: '));
        echo $prefix . strip_tags($msg) . "\n";
    } else {
        $class = $type;
        echo '<div class="' . $class . '">' . htmlspecialchars($msg) . '</div>';
    }
}

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Immediate S3 Upload Plugin Status</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    </style>
</head>
<body>
    <h1>Immediate S3 Upload Plugin - Diagnostic Status</h1>';
}

output("=== Plugin Installation Check ===", 'info');

// Check 1: Plugin directory exists
$plugin_dir = $CFG->dirroot . '/local/immediate_s3_upload';
if (is_dir($plugin_dir)) {
    output("✅ Plugin directory exists: {$plugin_dir}", 'success');
} else {
    output("❌ Plugin directory NOT found: {$plugin_dir}", 'error');
    if (!$is_cli) {
        echo '</body></html>';
    }
    exit(1);
}

// Check 2: Version file exists
$version_file = $plugin_dir . '/version.php';
if (file_exists($version_file)) {
    output("✅ Version file exists", 'success');
    // Read version file content without including it (to avoid $plugin initialization issues)
    $version_content = file_get_contents($version_file);
    if (preg_match('/\$plugin->version\s*=\s*(\d+);/', $version_content, $matches)) {
        output("Plugin version: {$matches[1]}", 'info');
    }
    if (preg_match('/\$plugin->release\s*=\s*[\'"]?([^\'";]+)[\'"]?;/', $version_content, $matches)) {
        output("Plugin release: {$matches[1]}", 'info');
    }
} else {
    output("❌ Version file NOT found", 'error');
}

// Check 3: Event observer file exists
$events_file = $plugin_dir . '/db/events.php';
if (file_exists($events_file)) {
    output("✅ Event observer file exists", 'success');
} else {
    output("❌ Event observer file NOT found", 'error');
}

// Check 4: ObjectFS is enabled
output("=== ObjectFS Configuration ===", 'info');
if (empty($CFG->alternative_file_system_class)) {
    output("❌ ObjectFS is NOT enabled in config.php", 'error');
} else {
    output("✅ ObjectFS filesystem class: {$CFG->alternative_file_system_class}", 'success');
    if ($CFG->alternative_file_system_class !== '\tool_objectfs\s3_file_system') {
        output("⚠️  Warning: Expected '\\tool_objectfs\\s3_file_system'", 'warning');
    }
}

// Check 5: ObjectFS tasks enabled
$enabletasks = get_config('tool_objectfs', 'enabletasks');
if ($enabletasks) {
    output("✅ ObjectFS tasks are enabled", 'success');
} else {
    output("❌ ObjectFS tasks are DISABLED - enable in ObjectFS settings", 'error');
}

// Check 6: Minimum age setting
$minimumage = get_config('tool_objectfs', 'minimumage');
if ($minimumage == 0) {
    output("✅ Minimum age is 0 (immediate upload)", 'success');
} else {
    output("⚠️  Minimum age is {$minimumage} seconds (files must wait before upload)", 'warning');
    output("   Set to 0 for immediate upload", 'info');
}

// Check 7: Event observer registration
output("=== Event Observer Registration ===", 'info');
$observers = core_component::get_observers('\core\event\file_created');
$found = false;
foreach ($observers as $observer) {
    if (isset($observer['callback']) && strpos($observer['callback'], 'local_immediate_s3_upload') !== false) {
        $found = true;
        output("✅ Event observer registered: {$observer['callback']}", 'success');
        output("   Priority: " . ($observer['priority'] ?? 'default'), 'info');
        break;
    }
}

if (!$found) {
    output("❌ Event observer NOT registered - run: php admin/cli/purge_caches.php", 'error');
    output("   Then run: php admin/cli/upgrade.php --non-interactive", 'info');
}

// Check 8: Recent file uploads status
output("=== Recent File Uploads Status ===", 'info');
global $DB;
try {
    $sql = "SELECT 
        f.id,
        f.filename,
        f.contenthash,
        f.filesize,
        FROM_UNIXTIME(f.timecreated) as uploaded_at,
        CASE oo.location 
            WHEN 0 THEN 'LOCAL' 
            WHEN 1 THEN 'DUPLICATED ✅' 
            WHEN 2 THEN 'EXTERNAL (S3) ✅' 
            WHEN -1 THEN 'ERROR ❌' 
            WHEN -2 THEN 'ORPHANED' 
            ELSE 'NOT TRACKED'
        END as location
    FROM {files} f
    LEFT JOIN {tool_objectfs_objects} oo ON oo.contenthash = f.contenthash
    WHERE f.filename != '.'
    ORDER BY f.timecreated DESC
    LIMIT 10";
    
    $recent_files = $DB->get_records_sql($sql);
    
    if (empty($recent_files)) {
        output("No recent files found", 'warning');
    } else {
        output("Found " . count($recent_files) . " recent files:", 'info');
        if ($is_cli) {
            foreach ($recent_files as $file) {
                output("  - {$file->filename} ({$file->filesize} bytes) - {$file->location} - {$file->uploaded_at}", 'info');
            }
        } else {
            echo '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
            echo '<tr><th>Filename</th><th>Size</th><th>Location</th><th>Uploaded</th></tr>';
            foreach ($recent_files as $file) {
                $location_class = (strpos($file->location, '✅') !== false) ? 'success' : 
                                 ((strpos($file->location, '❌') !== false) ? 'error' : 'warning');
                echo '<tr>';
                echo '<td>' . htmlspecialchars($file->filename) . '</td>';
                echo '<td>' . number_format($file->filesize) . ' bytes</td>';
                echo '<td class="' . $location_class . '">' . htmlspecialchars($file->location) . '</td>';
                echo '<td>' . htmlspecialchars($file->uploaded_at) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
} catch (Exception $e) {
    output("Error checking recent files: " . $e->getMessage(), 'error');
}

// Summary
output("=== Summary ===", 'info');
if ($found && $enabletasks && $minimumage == 0 && !empty($CFG->alternative_file_system_class)) {
    output("✅ All checks passed! Plugin should be working.", 'success');
    output("   Try uploading a file through Moodle and check if it appears in S3.", 'info');
} else {
    output("⚠️  Some checks failed. Please fix the issues above.", 'warning');
}

if (!$is_cli) {
    echo '
    <h2>Next Steps</h2>
    <ol>
        <li>If event observer is not registered, run:
            <pre>php admin/cli/purge_caches.php
php admin/cli/upgrade.php --non-interactive</pre>
        </li>
        <li>If ObjectFS tasks are disabled, enable them in:
            <strong>Site administration → Plugins → Object storage file system → Plugin Settings</strong>
        </li>
        <li>If minimum age is not 0, set it to 0 in ObjectFS settings</li>
        <li>Upload a test file through Moodle and check this page again</li>
    </ol>
    </body>
</html>';
} else {
    echo "\n=== Diagnostic Complete ===\n";
}

