<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/customapi/classes/JWT.php');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Get token from URL or manually
$token = required_param('token', PARAM_RAW);
$token = urldecode($token); // important if passed via URL

$secretkey = '7c3f2a1bb4d0b8b2f6e6a8fcb5a937ec0f85e4a52a1b2d76a16e3a92c37d4f7d';

try {
    // Split token
    list($headb64, $bodyb64, $sigb64) = explode('.', $token);

    $headerJson = JWT::urlsafeB64Decode($headb64);
    $payloadJson = JWT::urlsafeB64Decode($bodyb64);
    $signature = JWT::urlsafeB64Decode($sigb64);

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);

    echo "===== JWT DEBUG =====\n";
    echo "Header:\n"; print_r($header);
    echo "Payload:\n"; print_r($payload);
    echo "Signature (base64): $sigb64\n";
    echo "Decoded Signature (hex): " . bin2hex($signature) . "\n";
    echo "Algorithm: " . $header['alg'] . "\n";

    // Verify signature manually
    $signedData = $headb64 . '.' . $bodyb64;

    $hash = hash_hmac('sha256', $signedData, $secretkey, true);
    if (hash_equals($hash, $signature)) {
        echo "✅ Signature is valid!\n";
    } else {
        echo "❌ Signature verification failed!\n";
        echo "Expected (hex): " . bin2hex($hash) . "\n";
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
