<?php
/**
 * RSA Key Pair Generator
 *
 * Run this ONCE on your server to generate the RSA key pair.
 * The private key stays on the server (never distribute it).
 * The public key gets embedded in your C# application.
 *
 * Usage: Access via browser or CLI: php generate_keys.php
 */

// Configuration
$keyDir = __DIR__ . '/keys';

// Create keys directory if it doesn't exist
if (!is_dir($keyDir)) {
    mkdir($keyDir, 0700, true);
}

// Generate RSA key pair (2048-bit)
$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$keyPair = openssl_pkey_new($config);

if ($keyPair === false) {
    die("ERROR: Failed to generate key pair. OpenSSL error: " . openssl_error_string());
}

// Extract private key
openssl_pkey_export($keyPair, $privateKey);

// Extract public key
$publicKeyDetails = openssl_pkey_get_details($keyPair);
$publicKey = $publicKeyDetails['key'];

// Save private key (server only - KEEP SECRET)
$privateKeyPath = $keyDir . '/private_key.pem';
file_put_contents($privateKeyPath, $privateKey);
chmod($privateKeyPath, 0600);

// Save public key (this gets embedded in C# app)
$publicKeyPath = $keyDir . '/public_key.pem';
file_put_contents($publicKeyPath, $publicKey);

// Protect keys directory with .htaccess
$htaccess = $keyDir . '/.htaccess';
file_put_contents($htaccess, "Deny from all\n");

echo "=== RSA Key Pair Generated Successfully ===\n\n";
echo "Private key saved to: $privateKeyPath\n";
echo "Public key saved to:  $publicKeyPath\n\n";
echo "=== PUBLIC KEY (embed this in your C# application) ===\n\n";
echo $publicKey;
echo "\n=== IMPORTANT ===\n";
echo "1. NEVER share or expose the private key.\n";
echo "2. The keys/ directory is protected by .htaccess.\n";
echo "3. Copy the public key above into your C# application.\n";
