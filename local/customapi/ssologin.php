<?php
require('../../config.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot . '/local/customapi/classes/JWT.php');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 1. Get token from URL
$token = required_param('token', PARAM_RAW);
$token = trim(urldecode($token)); // remove spaces and URL-encode artifacts

// 2. Your secret key
$secretkey = '7c3f2a1bb4d0b8b2f6e6a8fcb5a937ec0f85e4a52a1b2d76a16e3a92c37d4f7d';

try {
    // 3. Decode token
    $decoded = JWT::decode($token, new Key($secretkey, 'HS256'));

} catch (\Firebase\JWT\SignatureInvalidException $e) {
    // Extract header, payload, signature
    list($headb64, $bodyb64, $sigb64) = explode('.', $token);

    $headerJson = JWT::urlsafeB64Decode($headb64);
    $payloadJson = JWT::urlsafeB64Decode($bodyb64);
    $signature = JWT::urlsafeB64Decode($sigb64);

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);

    // Compute expected signature
    $signedData = $headb64 . '.' . $bodyb64;
    $expectedSig = hash_hmac('sha256', $signedData, $secretkey, true);

    $debuginfo = [
        'error' => 'Signature verification failed',
        /*'header' => $header,
        'payload' => $payload,
        'provided_signature_base64' => $sigb64,
        'provided_signature_hex' => bin2hex($signature),
        'expected_signature_hex' => bin2hex($expectedSig),*/
    ];
    //$decoded = JWT::decode($token, new Key(bin2hex($expectedSig), 'HS256'));
    throw new moodle_exception('invalidtoken', 'local_customapi', '', null, json_encode($debuginfo));

} catch (\Firebase\JWT\BeforeValidException $e) {
    throw new moodle_exception('invalidtoken', 'local_customapi', '', null, 'Token used before valid time: ' . $e->getMessage());

} catch (\Firebase\JWT\ExpiredException $e) {
    throw new moodle_exception('invalidtoken', 'local_customapi', '', null, 'Token expired: ' . $e->getMessage());

} catch (Exception $e) {
    throw new moodle_exception('invalidtoken', 'local_customapi', '', null, 'Other error: ' . $e->getMessage());
}

// 4. Get user ID from payload
if (empty($decoded->userid)) {
    throw new moodle_exception('nouserid', 'local_customapi');
}
$userid = (int)$decoded->userid;

// 5. Verify user exists
$user = $DB->get_record('user', [
    'id'       => $userid,
    'deleted'  => 0,
    'suspended'=> 0
], '*', MUST_EXIST);

// 6. Log the user in
complete_user_login($user);

// 7. Redirect to dashboard (or custom page)
redirect(new moodle_url('/my'));
