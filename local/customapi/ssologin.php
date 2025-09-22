<?php
require('../../config.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot . '/local/customapi/classes/JWT.php');



use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 1. Get token from URL
$token = required_param('token', PARAM_RAW);

// 2. Decode token
//$secretkey = $CFG->jwt_secret ?? 'change_this_secret_key';
$secretkey = '7c3f2a1bb4d0b8b2f6e6a8fcb5a937ec0f85e4a52a1b2d76a16e3a92c37d4f7d';

try {
    //$decoded = JWT::decode($token, new Key($secretkey, 'HS256'));
    $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secretkey, 'HS256'));
} catch (Exception $e) {
    print_error('invalidtoken', 'local_customapi', '', null, $e->getMessage());
}

// 3. Get user ID from payload
if (empty($decoded->userid)) {
    print_error('nouserid', 'local_customapi');
}
$userid = (int)$decoded->userid;

// 4. Verify user exists
$user = $DB->get_record('user', [
    'id'       => $userid,
    'deleted'  => 0,
    'suspended'=> 0
], '*', MUST_EXIST);

// 5. Log the user in
complete_user_login($user);

// 6. Redirect to dashboard (or custom page)
redirect(new moodle_url('/my'));
