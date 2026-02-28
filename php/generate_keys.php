<?php
/**
 * RSA Key Pair Generator
 *
 * Run this ONCE on your server to generate the RSA key pair.
 * - Private key: stored OUTSIDE the public web root (secure)
 * - Public key: stored locally for reference + embedded in C# app
 *
 * Usage: Access via browser or CLI: php generate_keys.php
 */

require_once __DIR__ . '/config.php';

// Create private key directory OUTSIDE web root
if (!is_dir(PRIVATE_KEY_DIR)) {
    if (!mkdir(PRIVATE_KEY_DIR, 0700, true)) {
        die("ERROR: Cannot create private key directory: " . PRIVATE_KEY_DIR . "\n" .
            "Please create it manually and ensure PHP has write permission.\n");
    }
}

// Create local keys directory for public key
$publicKeyDir = __DIR__ . '/keys';
if (!is_dir($publicKeyDir)) {
    mkdir($publicKeyDir, 0755, true);
}

// Generate RSA key pair
$config = [
    'private_key_bits' => RSA_KEY_BITS,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$keyPair = openssl_pkey_new($config);

if ($keyPair === false) {
    die("ERROR: Failed to generate key pair. OpenSSL error: " . openssl_error_string() . "\n");
}

// Extract private key
openssl_pkey_export($keyPair, $privateKey);

// Extract public key
$publicKeyDetails = openssl_pkey_get_details($keyPair);
$publicKey = $publicKeyDetails['key'];

// Save private key OUTSIDE the web root
file_put_contents(PRIVATE_KEY_PATH, $privateKey);
chmod(PRIVATE_KEY_PATH, 0600);

// Save public key locally (safe to be public)
file_put_contents(PUBLIC_KEY_PATH, $publicKey);

// Protect directories with .htaccess
file_put_contents($publicKeyDir . '/.htaccess', "Deny from all\n");
file_put_contents(PRIVATE_KEY_DIR . '/.htaccess', "Deny from all\n");

$isWeb = php_sapi_name() !== 'cli';

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<pre style='font-family:monospace; padding:20px; background:#f9f9f9;'>\n";
}

echo "=== RSA " . RSA_KEY_BITS . "-bit Key Pair Generated Successfully ===\n\n";
echo "Private key saved to: " . PRIVATE_KEY_PATH . "\n";
echo "  (OUTSIDE web root - secure)\n\n";
echo "Public key saved to:  " . PUBLIC_KEY_PATH . "\n";
echo "  (Local reference copy)\n\n";
echo "=== PUBLIC KEY (embed this in your C# application) ===\n\n";
echo $publicKey;
echo "\n=== SECURITY NOTES ===\n";
echo "1. Private key is stored OUTSIDE the public web root.\n";
echo "2. NEVER share or expose the private key.\n";
echo "3. The public key above is safe to embed in your C# application.\n";
echo "4. Delete this script from the server after running it (optional but recommended).\n";

if ($isWeb) {
    echo "</pre>\n";
}
