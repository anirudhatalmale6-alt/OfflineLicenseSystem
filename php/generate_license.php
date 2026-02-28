<?php
/**
 * License File Generator
 *
 * This script generates a digitally signed license file.
 * It reads user/license info, signs it with the private key,
 * and outputs a .lic file that the C# app can verify.
 *
 * Usage:
 *   POST to this script with license parameters, or
 *   call generate_license() directly from your own code.
 */

// Configuration
$privateKeyPath = __DIR__ . '/keys/private_key.pem';

/**
 * Generate a signed license file.
 *
 * @param array $licenseData Associative array with license fields
 * @param string $privateKeyPath Path to the private key PEM file
 * @param string|null $outputPath Where to save the .lic file (null = return content)
 * @return string The license file content
 */
function generate_license(array $licenseData, string $privateKeyPath, ?string $outputPath = null): string
{
    // Validate required fields
    $required = ['licensee_name', 'licensee_email', 'product_name', 'license_type', 'expiry_date'];
    foreach ($required as $field) {
        if (empty($licenseData[$field])) {
            throw new InvalidArgumentException("Missing required field: $field");
        }
    }

    // Add metadata
    $licenseData['issued_date'] = date('Y-m-d');
    $licenseData['license_id'] = strtoupper(bin2hex(random_bytes(8)));

    // Load private key
    $privateKeyPem = file_get_contents($privateKeyPath);
    if ($privateKeyPem === false) {
        throw new RuntimeException("Cannot read private key file: $privateKeyPath");
    }

    $privateKey = openssl_pkey_get_private($privateKeyPem);
    if ($privateKey === false) {
        throw new RuntimeException("Invalid private key. OpenSSL error: " . openssl_error_string());
    }

    // Create the license data string (sorted keys for consistency)
    ksort($licenseData);
    $dataString = json_encode($licenseData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Sign the data
    $signature = '';
    $signResult = openssl_sign($dataString, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    if (!$signResult) {
        throw new RuntimeException("Failed to sign license data. OpenSSL error: " . openssl_error_string());
    }

    // Encode signature as base64
    $signatureBase64 = base64_encode($signature);

    // Build the license file content
    $licenseContent = "----BEGIN LICENSE----\n";
    $licenseContent .= base64_encode($dataString) . "\n";
    $licenseContent .= "----BEGIN SIGNATURE----\n";
    $licenseContent .= $signatureBase64 . "\n";
    $licenseContent .= "----END LICENSE----\n";

    // Save to file if output path specified
    if ($outputPath !== null) {
        file_put_contents($outputPath, $licenseContent);
    }

    return $licenseContent;
}

// =============================================================
// Handle HTTP POST request (web interface)
// =============================================================
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $licenseData = [
            'licensee_name'  => trim($_POST['licensee_name'] ?? ''),
            'licensee_email' => trim($_POST['licensee_email'] ?? ''),
            'company_name'   => trim($_POST['company_name'] ?? ''),
            'product_name'   => trim($_POST['product_name'] ?? ''),
            'product_version'=> trim($_POST['product_version'] ?? '1.0'),
            'license_type'   => trim($_POST['license_type'] ?? 'standard'),
            'expiry_date'    => trim($_POST['expiry_date'] ?? ''),
            'max_machines'   => intval($_POST['max_machines'] ?? 1),
            'features'       => trim($_POST['features'] ?? ''),
        ];

        // Remove empty optional fields
        $licenseData = array_filter($licenseData, function ($v) {
            return $v !== '' && $v !== 0;
        });

        // Generate license
        $licenseContent = generate_license($licenseData, $privateKeyPath);

        // Option 1: Return as download
        if (isset($_POST['download']) && $_POST['download'] === '1') {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="license.lic"');
            echo $licenseContent;
            exit;
        }

        // Option 2: Return as JSON
        echo json_encode([
            'success' => true,
            'license_id' => $licenseData['license_id'] ?? 'N/A',
            'license_content' => $licenseContent,
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ]);
    }
    exit;
}

// If running from CLI (e.g. required by another script), stop here
if (php_sapi_name() === 'cli') {
    return;
}

// =============================================================
// Simple HTML form for manual license generation (web only)
// =============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Generator</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 0 20px; }
        h1 { color: #333; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #555; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { margin-top: 20px; padding: 12px 30px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #1d4ed8; }
        .note { color: #888; font-size: 0.85em; margin-top: 3px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>License Generator</h1>

    <?php if (!file_exists($privateKeyPath)): ?>
        <p style="color:red;"><strong>Warning:</strong> Private key not found. Run <code>generate_keys.php</code> first.</p>
    <?php endif; ?>

    <form method="POST" id="licenseForm">
        <label>Licensee Name *</label>
        <input type="text" name="licensee_name" required placeholder="John Doe">

        <label>Licensee Email *</label>
        <input type="email" name="licensee_email" required placeholder="john@example.com">

        <label>Company Name</label>
        <input type="text" name="company_name" placeholder="Acme Corp">

        <label>Product Name *</label>
        <input type="text" name="product_name" required placeholder="MyDesktopApp">

        <label>Product Version</label>
        <input type="text" name="product_version" value="1.0">

        <label>License Type *</label>
        <select name="license_type">
            <option value="trial">Trial</option>
            <option value="standard" selected>Standard</option>
            <option value="professional">Professional</option>
            <option value="enterprise">Enterprise</option>
        </select>

        <label>Expiry Date *</label>
        <input type="date" name="expiry_date" required>
        <div class="note">Set far in the future for lifetime licenses</div>

        <label>Max Machines</label>
        <input type="number" name="max_machines" value="1" min="1">

        <label>Features</label>
        <input type="text" name="features" placeholder="feature1,feature2,feature3">
        <div class="note">Comma-separated list of enabled features</div>

        <button type="submit">Generate License</button>
    </form>

    <div id="result" style="display:none; margin-top:20px;">
        <h2>Generated License</h2>
        <pre id="licenseOutput"></pre>
        <button onclick="downloadLicense()">Download .lic File</button>
    </div>

    <script>
        document.getElementById('licenseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('licenseOutput').textContent = data.license_content;
                        document.getElementById('result').style.display = 'block';
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => alert('Request failed: ' + err));
        });

        function downloadLicense() {
            const formData = new FormData(document.getElementById('licenseForm'));
            formData.append('download', '1');

            fetch('', { method: 'POST', body: formData })
                .then(r => r.blob())
                .then(blob => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'license.lic';
                    a.click();
                    URL.revokeObjectURL(url);
                });
        }
    </script>
</body>
</html>
