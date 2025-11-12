<?php
/**
 * Test script for update_user_info endpoint
 * 
 * Usage: Access this file via browser as an admin user
 * URL: https://your-moodle-site.com/local/customapi/test_update_user.php
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/customapi/externallib.php');

// Require login and admin capabilities
require_login();
require_capability('moodle/site:config', context_system::instance());

// Set page context
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/customapi/test_update_user.php');
$PAGE->set_title('Test Update User Info API');
$PAGE->set_heading('Test Update User Info API');

echo $OUTPUT->header();

echo '<h2>Test Update User Info Endpoint</h2>';

// Get user ID from URL parameter, default to user ID 2
$userid = optional_param('userid', 2, PARAM_INT);

// Get action (if form submitted)
$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'update') {
    // Get form data
    $firstname = optional_param('firstname', null, PARAM_TEXT);
    $lastname = optional_param('lastname', null, PARAM_TEXT);
    $email = optional_param('email', null, PARAM_EMAIL);
    $phone1 = optional_param('phone1', null, PARAM_TEXT);
    $idnumber = optional_param('idnumber', null, PARAM_RAW);
    $department = optional_param('department', null, PARAM_TEXT);
    $institution = optional_param('institution', null, PARAM_TEXT);
    
    try {
        // Call the API function
        $result = local_customapi_external::update_user_info(
            $userid,
            $firstname ?: null,
            $lastname ?: null,
            $email ?: null,
            $idnumber ?: null,
            $phone1 ?: null,
            null,  // phone2
            $institution ?: null,
            $department ?: null
        );
        
        echo '<div style="padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;">';
        echo '<h3 style="color: #155724; margin-top: 0;">✓ Success!</h3>';
        echo '<p><strong>User ID:</strong> ' . $result['user_id'] . '</p>';
        echo '<p><strong>Updated Fields:</strong> ' . implode(', ', $result['updated_fields']) . '</p>';
        echo '<p><strong>Message:</strong> ' . $result['message'] . '</p>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div style="padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;">';
        echo '<h3 style="color: #721c24; margin-top: 0;">✗ Error</h3>';
        echo '<p><strong>Message:</strong> ' . $e->getMessage() . '</p>';
        echo '<p><strong>Error Code:</strong> ' . (method_exists($e, 'errorcode') ? $e->errorcode : 'N/A') . '</p>';
        echo '</div>';
    }
}

// Fetch current user data to pre-fill form
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);

if (!$user) {
    echo '<div style="padding: 15px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 5px;">';
    echo '<p><strong>User not found!</strong> Please provide a valid user ID.</p>';
    echo '</div>';
} else {
    echo '<div style="padding: 15px; background-color: #e7f3ff; border: 1px solid #b3d7ff; border-radius: 5px; margin-bottom: 20px;">';
    echo '<h3 style="margin-top: 0;">Current User Information</h3>';
    echo '<p><strong>ID:</strong> ' . $user->id . '</p>';
    echo '<p><strong>Username:</strong> ' . $user->username . '</p>';
    echo '<p><strong>Full Name:</strong> ' . fullname($user) . '</p>';
    echo '<p><strong>Email:</strong> ' . $user->email . '</p>';
    echo '<p><strong>ID Number:</strong> ' . ($user->idnumber ?: '(not set)') . '</p>';
    echo '<p><strong>Phone1:</strong> ' . ($user->phone1 ?: '(not set)') . '</p>';
    echo '<p><strong>Department:</strong> ' . ($user->department ?: '(not set)') . '</p>';
    echo '<p><strong>Institution:</strong> ' . ($user->institution ?: '(not set)') . '</p>';
    echo '</div>';
}

// Display form
?>
<form method="post" action="<?php echo $PAGE->url; ?>" style="max-width: 600px;">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
    
    <div style="margin-bottom: 20px;">
        <label for="userid" style="display: block; font-weight: bold; margin-bottom: 5px;">User ID:</label>
        <input type="number" id="userid" name="userid" value="<?php echo $userid; ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
        <small style="color: #666;">The Moodle user ID to update</small>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="firstname" style="display: block; font-weight: bold; margin-bottom: 5px;">First Name:</label>
        <input type="text" id="firstname" name="firstname" value="<?php echo $user ? s($user->firstname) : ''; ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="lastname" style="display: block; font-weight: bold; margin-bottom: 5px;">Last Name:</label>
        <input type="text" id="lastname" name="lastname" value="<?php echo $user ? s($user->lastname) : ''; ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="email" style="display: block; font-weight: bold; margin-bottom: 5px;">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo $user ? s($user->email) : ''; ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        <small style="color: #666;">Must be unique</small>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="idnumber" style="display: block; font-weight: bold; margin-bottom: 5px;">ID Number:</label>
        <input type="text" id="idnumber" name="idnumber" value="<?php echo $user ? s($user->idnumber) : ''; ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        <small style="color: #666;">Must be unique (optional)</small>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="phone1" style="display: block; font-weight: bold; margin-bottom: 5px;">Phone Number:</label>
        <input type="text" id="phone1" name="phone1" value="<?php echo $user ? s($user->phone1) : ''; ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="institution" style="display: block; font-weight: bold; margin-bottom: 5px;">Institution:</label>
        <input type="text" id="institution" name="institution" value="<?php echo $user ? s($user->institution) : ''; ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="department" style="display: block; font-weight: bold; margin-bottom: 5px;">Department:</label>
        <input type="text" id="department" name="department" value="<?php echo $user ? s($user->department) : ''; ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 20px;">
        <button type="submit" 
                style="background-color: #0066cc; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            Update User Information
        </button>
    </div>
    
    <div style="padding: 15px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 5px;">
        <strong>Note:</strong> Only fill in the fields you want to update. Empty fields will not be updated.
    </div>
</form>

<div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
    <h3>REST API Example</h3>
    <p>To call this via REST API, use:</p>
    <pre style="background-color: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;">
curl -X POST "<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_customapi_update_user_info" \
  -d "moodlewsrestformat=json" \
  -d "user_id=<?php echo $userid; ?>" \
  -d "firstname=John" \
  -d "lastname=Doe" \
  -d "email=john.doe@example.com"
    </pre>
</div>

<?php
echo $OUTPUT->footer();


