<?php
/**
 * Test script for local_customapi Moodle web service endpoints.
 *
 * @package   local_customapi
 * @copyright 2025 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 // Configuration
$token = 'f14f5c28b529c65551bd07f17c5bc552'; // Replace with your Moodle web service token
$moodle_url = 'https://cinnalab-moodle-d1962611ca61.herokuapp.com/webservice/rest/server.php'; // Replace with your Moodle URL
$format = 'json'; // Use JSON format for responses

// Test parameters (replace with valid values from your Moodle instance)
$test_domain = 'tenant1'; // Tenant ID (idnumber)
$test_company_name = 'Test Company'; // Tenant name
$test_email = 'testuser' . time() . '@example.com'; // Unique email for new user
$test_role_id = 1; // Replace with a valid role ID (e.g., tenant admin or manager)
$test_user_id = 2; // Replace with an existing user ID (e.g., from mdl_user table)
$test_tenant_id = 1; // Replace with a valid tenant ID after creating one
$test_course_id = 2; // Replace with a valid course ID for testing

/**
 * Make a Moodle web service API call.
 *
 * @param string $function Web service function name
 * @param array $params Parameters for the API call
 * @return array Decoded response
 */
function call_moodle_api($function, $params) {
    global $moodle_url, $token, $format;

    $params['wstoken'] = $token;
    $params['wsfunction'] = $function;
    $params['moodlewsrestformat'] = $format;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $moodle_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable for local testing; enable in production
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['error' => true, 'message' => 'cURL error: ' . curl_error($ch)];
    }

    if ($http_code != 200) {
        return ['error' => true, 'message' => 'HTTP error: ' . $http_code];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => true, 'message' => 'JSON decode error: ' . json_last_error_msg()];
    }

    return $data;
}

/**
 * Log test results.
 *
 * @param string $test_name Name of the test
 * @param array $response API response
 */
function log_result($test_name, $response) {
    echo "=== $test_name ===\n";
    if (isset($response['error'])) {
        echo "Error: {$response['message']}\n";
    } elseif (isset($response['exception'])) {
        echo "Moodle Exception: {$response['message']} (Error code: {$response['errorcode']})\n";
    } else {
        echo "Success: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

// Test 1: Create Tenant
echo "Starting Test 1: Create Tenant\n";
$response = call_moodle_api('local_customapi_create_tenant', [
    'domain' => $test_domain,
    'company_name' => $test_company_name,
]);
$tenant_id = isset($response['tenant_id']) ? $response['tenant_id'] : null;
log_result('Create Tenant', $response);

// Test 2: Create User Against Tenant
if ($tenant_id) {
    echo "Starting Test 2: Create User Against Tenant\n";
    $response = call_moodle_api('local_customapi_create_user_in_tenant', [
        'email' => $test_email,
        'tenant' => $tenant_id,
        'role_id' => $test_role_id,
    ]);
    $test_user_id = isset($response['user_id']) ? $response['user_id'] : null;
    log_result('Create User Against Tenant', $response);
} else {
    echo "Skipping Test 2: No valid tenant_id\n\n";
}

// Test 3: Add Existing User to Tenant
if ($tenant_id) {
    echo "Starting Test 3: Add Existing User to Tenant\n";
    $response = call_moodle_api('local_customapi_add_user_to_tenant', [
        'user_id' => $test_user_id,
        'tenant_id' => $tenant_id,
    ]);
    log_result('Add Existing User to Tenant', $response);
} else {
    echo "Skipping Test 3: No valid tenant_id\n\n";
}

// Test 4: Fetch All Roles
if ($tenant_id) {
    echo "Starting Test 4: Fetch All Roles\n";
    $response = call_moodle_api('local_customapi_get_tenant_roles', [
        'tenant_id' => $tenant_id,
    ]);
    log_result('Fetch All Roles', $response);
} else {
    echo "Skipping Test 4: No valid tenant_id\n\n";
}

// Test 5: Fetch Courses by Tenant
if ($tenant_id) {
    echo "Starting Test 5: Fetch Courses by Tenant\n";
    $response = call_moodle_api('local_customapi_get_tenant_courses', [
        'tenant_id' => $tenant_id,
    ]);
    log_result('Fetch Courses by Tenant', $response);
} else {
    echo "Skipping Test 5: No valid tenant_id\n\n";
}

// Test 6: Fetch Courses by Tenant and User
if ($tenant_id && $test_user_id) {
    echo "Starting Test 6: Fetch Courses by Tenant and User\n";
    $response = call_moodle_api('local_customapi_get_tenant_user_courses', [
        'tenant_id' => $tenant_id,
        'user_id' => $test_user_id,
    ]);
    log_result('Fetch Courses by Tenant and User', $response);
} else {
    echo "Skipping Test 6: No valid tenant_id or user_id\n\n";
}

echo "All tests completed.\n";
?>