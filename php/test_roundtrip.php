<?php
/**
 * Test Round-Trip Script
 *
 * Generates keys, creates a test license, and verifies it - all in one go.
 * This proves the signing and verification process works end-to-end.
 *
 * Usage: php test_roundtrip.php
 */

require_once __DIR__ . '/generate_license.php';

echo "=== License System Round-Trip Test ===\n\n";

// Step 1: Generate keys if they don't exist
if (!file_exists(PRIVATE_KEY_PATH)) {
    echo "Generating RSA key pair...\n";
    include __DIR__ . '/generate_keys.php';
    echo "\n";
}

// Step 2: Generate a test license
echo "Generating test license...\n";

$testLicenseData = [
    'licensee_name'   => 'Test User',
    'licensee_email'  => 'test@example.com',
    'company_name'    => 'Test Company',
    'product_name'    => 'MyDesktopApp',
    'product_version' => '1.0',
    'license_type'    => 'standard',
    'expiry_date'     => '2027-12-31',
    'max_machines'    => 2,
    'features'        => 'feature_a,feature_b,feature_c',
];

$outputPath = __DIR__ . '/test_license.lic';
$licenseContent = generate_license($testLicenseData, PRIVATE_KEY_PATH, $outputPath);

echo "License file saved to: $outputPath\n\n";
echo "--- License File Contents ---\n";
echo $licenseContent;
echo "--- End License File ---\n\n";

// Step 3: Verify the license (simulating what C# will do)
echo "Verifying license...\n";

$lines = explode("\n", trim($licenseContent));
$dataBase64 = $lines[1];
$signatureBase64 = $lines[3];

$dataString = base64_decode($dataBase64);
$signature = base64_decode($signatureBase64);

echo "Decoded license data: $dataString\n\n";

// Load public key
$publicKey = file_get_contents(PUBLIC_KEY_PATH);
$pubKeyResource = openssl_pkey_get_public($publicKey);

// Verify signature
$verifyResult = openssl_verify($dataString, $signature, $pubKeyResource, OPENSSL_ALGO_SHA256);

if ($verifyResult === 1) {
    echo "VERIFICATION: SUCCESS - Signature is valid!\n";
} elseif ($verifyResult === 0) {
    echo "VERIFICATION: FAILED - Signature is invalid!\n";
} else {
    echo "VERIFICATION: ERROR - " . openssl_error_string() . "\n";
}

// Parse and display license info
$licenseInfo = json_decode($dataString, true);
echo "\n--- Parsed License Info ---\n";
foreach ($licenseInfo as $key => $value) {
    echo sprintf("  %-18s: %s\n", $key, $value);
}

// Check expiry
$expiryDate = new DateTime($licenseInfo['expiry_date']);
$now = new DateTime();
if ($expiryDate > $now) {
    echo "\n  Status: ACTIVE (expires " . $expiryDate->format('M d, Y') . ")\n";
} else {
    echo "\n  Status: EXPIRED\n";
}

echo "\n=== Round-trip test complete ===\n";
