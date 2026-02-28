# Offline License System - Installation Guide

## Overview

This system provides offline software licensing with RSA digital signatures:
- **PHP server** generates and signs license files
- **C# desktop app** verifies license files using the embedded public key
- No internet connection needed for verification (offline)
- Private key stored **outside** the public web root for security

## Architecture

```
[Your GoDaddy Server (PHP)]          [Customer's PC (C# App)]

  1. generate_keys.php
     -> Creates RSA key pair
     -> Private key stored OUTSIDE web root

  2. generate_license.php
     -> Signs license data    ------> license.lic file
        with PRIVATE key
                                       3. LicenseValidator.cs
                                          Verifies signature
                                          with PUBLIC key
                                          -> Valid / Invalid
```

---

## Part 1: PHP Server Setup (GoDaddy)

### Directory Structure

```
/home/username/                        <-- your home directory
  license_keys/                        <-- PRIVATE KEY here (outside web root!)
    private_key.pem                    (auto-created, KEEP SECRET)
    .htaccess                          (auto-created, blocks access)
  public_html/                         <-- web root
    licensing/                         <-- upload PHP files here
      config.php                       (configuration - set paths here)
      generate_keys.php                (run once to create keys)
      generate_license.php             (license generator with web UI)
      test_roundtrip.php               (test script)
      keys/                            (auto-created)
        public_key.pem                 (auto-created, safe to share)
        .htaccess                      (auto-created)
```

### Files to Upload

Upload all files from the `php/` folder to a folder on your server (e.g., `public_html/licensing/`):
- `config.php` - Configuration (set your private key path here)
- `generate_keys.php` - One-time key generation
- `generate_license.php` - License generator with web form
- `test_roundtrip.php` - Test script

### Step-by-Step Setup

1. **Upload files** to a folder on your server (e.g., `/licensing/`)

2. **Edit `config.php`** to set the correct private key directory path:
   ```php
   // Change 'username' to your GoDaddy hosting username:
   define('PRIVATE_KEY_DIR', '/home/username/license_keys');
   ```
   The auto-detect default usually works, but verify the path is correct.

3. **Generate RSA keys** - visit in your browser:
   ```
   https://your-site.com/licensing/generate_keys.php
   ```
   This creates:
   - Private key at `/home/username/license_keys/private_key.pem` (outside web root)
   - Public key at `/licensing/keys/public_key.pem` (local reference)

   **Copy the public key displayed** - you'll need it for C#.

4. **Generate licenses** - visit:
   ```
   https://your-site.com/licensing/generate_license.php
   ```
   Fill in the form and click "Generate License".
   Download the `.lic` file to send to your customer.

### Security Notes

- Private key is stored OUTSIDE the public web root - cannot be accessed via URL
- Both key directories are protected by `.htaccess` (extra safety layer)
- NEVER share or expose `private_key.pem`
- The `public_key.pem` is safe to embed in your C# app
- Consider adding HTTP Basic Auth or your own login to protect the generator page
- Optionally delete `generate_keys.php` from the server after initial setup

---

## Part 2: C# Desktop Application

### Solution Structure

```
csharp/
  LicenseSystem.sln                     (Visual Studio solution)
  LicenseVerifier/                      (Console demo)
    LicenseVerifier.cs                  (Core verification class)
    Program.cs                          (Console demo app)
    LicenseVerifier.csproj
  LicenseVerifierWinForms/              (WinForms demo)
    LicenseValidator.cs                 (Verification class)
    MainForm.cs                         (GUI form)
    Program.cs
    LicenseVerifierWinForms.csproj
```

### How to Build

1. Open `LicenseSystem.sln` in Visual Studio 2022
2. Build the solution (Ctrl+Shift+B)
3. Copy `test_license.lic` to `license.lic` in the output folder to test

### How to Integrate Into Your Own App

1. **Copy** `LicenseVerifier.cs` (or `LicenseValidator.cs`) into your project

2. **Replace the public key** in the class with YOUR key from `generate_keys.php`:
   ```csharp
   private const string PublicKeyPem = @"-----BEGIN PUBLIC KEY-----
   YOUR KEY HERE
   -----END PUBLIC KEY-----";
   ```

3. **Use it in your code:**
   ```csharp
   var validator = new LicenseValidator();
   var result = validator.ValidateLicenseFile("license.lic");

   if (result.IsValid && !result.License.IsExpired)
   {
       // Licensed - enable full app
       string licensee = result.License.LicenseeName;
       string type = result.License.LicenseType;
       List<string> features = result.License.FeatureList;
   }
   else
   {
       // Not licensed or expired
       MessageBox.Show(result.IsValid
           ? "Your license has expired."
           : result.ErrorMessage);
   }
   ```

### Requirements

- .NET 6.0 or later
- No external NuGet packages required (uses built-in System.Security.Cryptography)

---

## Part 3: License File Format

The `.lic` file is a simple text file:

```
----BEGIN LICENSE----
<base64-encoded JSON license data>
----BEGIN SIGNATURE----
<base64-encoded RSA-SHA256 signature>
----END LICENSE----
```

### License Data Fields

| Field | Description |
|-------|-------------|
| license_id | Unique ID (auto-generated) |
| licensee_name | Customer name |
| licensee_email | Customer email |
| company_name | Company name (optional) |
| product_name | Your software product name |
| product_version | Version string |
| license_type | trial / standard / professional / enterprise |
| issued_date | Date license was created |
| expiry_date | License expiration date |
| max_machines | Maximum allowed installs |
| features | Comma-separated feature flags |

---

## Testing

### Quick Test (PHP)

Run from command line on your server:
```bash
php test_roundtrip.php
```

This generates keys, creates a test license, and verifies it all in one step.

### Quick Test (C#)

1. Build the console project
2. Copy `test_license.lic` to the output folder as `license.lic`
3. Run the app - it should show "SIGNATURE VALID"

---

## Workflow Summary

1. **One-time setup:** Run `generate_keys.php` on your server (creates keys)
2. **For each customer:** Use `generate_license.php` to create a signed `.lic` file
3. **Customer receives:** The `.lic` file (via email, download, etc.)
4. **Customer's app:** Places `license.lic` alongside the .exe, app verifies on startup
5. **Security:** Private key is outside web root. Without it, nobody can forge a valid license.
