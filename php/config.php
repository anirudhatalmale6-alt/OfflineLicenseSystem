<?php
/**
 * License System Configuration
 *
 * IMPORTANT: Adjust PRIVATE_KEY_DIR to point OUTSIDE your public web root.
 *
 * Example GoDaddy directory structure:
 *   /home/username/              <-- home directory
 *   /home/username/license_keys/ <-- PRIVATE KEY goes here (outside web root)
 *   /home/username/public_html/  <-- web root (publicly accessible)
 *   /home/username/public_html/licensing/  <-- PHP scripts go here
 *
 * The private key must NEVER be inside public_html or any web-accessible folder.
 */

// =====================================================================
// CHANGE THIS to your actual path outside the public web root
// =====================================================================

// GoDaddy typical path (adjust 'username' to your hosting username):
// define('PRIVATE_KEY_DIR', '/home/username/license_keys');

// Auto-detect: goes one level above the document root
// This works on most GoDaddy shared hosting setups
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    define('PRIVATE_KEY_DIR', dirname($_SERVER['DOCUMENT_ROOT']) . '/license_keys');
} else {
    // CLI fallback: store in a 'private_keys' dir one level above the scripts
    define('PRIVATE_KEY_DIR', dirname(__DIR__) . '/license_keys');
}

// Key file paths (no need to change these)
define('PRIVATE_KEY_PATH', PRIVATE_KEY_DIR . '/private_key.pem');
define('PUBLIC_KEY_PATH', __DIR__ . '/keys/public_key.pem');

// RSA key size: 2048 or 4096
define('RSA_KEY_BITS', 2048);
