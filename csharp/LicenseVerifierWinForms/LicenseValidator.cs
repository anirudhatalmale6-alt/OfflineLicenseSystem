using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;

namespace LicenseVerifierWinForms
{
    public class LicenseInfo
    {
        public string LicenseId { get; set; } = "";
        public string LicenseeName { get; set; } = "";
        public string LicenseeEmail { get; set; } = "";
        public string CompanyName { get; set; } = "";
        public string ProductName { get; set; } = "";
        public string ProductVersion { get; set; } = "";
        public string LicenseType { get; set; } = "";
        public DateTime IssuedDate { get; set; }
        public DateTime ExpiryDate { get; set; }
        public int MaxMachines { get; set; }
        public string Features { get; set; } = "";

        public bool IsExpired => DateTime.Now.Date > ExpiryDate;

        public List<string> FeatureList =>
            string.IsNullOrEmpty(Features)
                ? new List<string>()
                : Features.Split(',').Select(f => f.Trim()).ToList();
    }

    public class LicenseValidationResult
    {
        public bool IsValid { get; set; }
        public string ErrorMessage { get; set; } = "";
        public LicenseInfo? License { get; set; }
    }

    public class LicenseValidator
    {
        // Replace this with YOUR public key from generate_keys.php
        private const string PublicKeyPem = @"-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz9PgWfxP80u59mcbIWc6
1XycQGxpj9K7BbNncD0jvyXQ9HzGzZrs9hMAUKfMt5KoMTd1ExB7MmWjrdRp5+60
9xlSL65YYpY8c/RzgGJUxpnFP3FAmMui9Y9zB3PLaHFlOQBzm7z5QpzvweMM8Rt2
0iMrXm6xv3EgVPSQoMq/KpSiJ/FHGrbY5aPhvQfUzZJw3A02B3Jti3HkRbDmMjPT
XNRXY+3vHMyCHj/l2u63oJSOiMW04UUTfzZ1RDopL5Ie9+5xWXLpysUwgQkqZ4nr
tKq1AIcIFIwRJpHJzwqwJZXopUubX4NHt15lKH28o3BtjcTwGlhQoWtyP8g47HtE
jwIDAQAB
-----END PUBLIC KEY-----";

        public LicenseValidationResult ValidateLicenseFile(string filePath)
        {
            try
            {
                if (!File.Exists(filePath))
                    return new LicenseValidationResult { IsValid = false, ErrorMessage = $"License file not found: {filePath}" };

                return ValidateLicenseContent(File.ReadAllText(filePath));
            }
            catch (Exception ex)
            {
                return new LicenseValidationResult { IsValid = false, ErrorMessage = $"Error reading license file: {ex.Message}" };
            }
        }

        public LicenseValidationResult ValidateLicenseContent(string licenseContent)
        {
            try
            {
                var lines = licenseContent.Trim().Split('\n').Select(l => l.Trim()).Where(l => !string.IsNullOrEmpty(l)).ToArray();

                if (lines.Length < 4 || lines[0] != "----BEGIN LICENSE----" || lines[2] != "----BEGIN SIGNATURE----" || lines[4] != "----END LICENSE----")
                    return new LicenseValidationResult { IsValid = false, ErrorMessage = "Invalid license file format." };

                byte[] dataBytes = Encoding.UTF8.GetBytes(Encoding.UTF8.GetString(Convert.FromBase64String(lines[1])));
                byte[] signatureBytes = Convert.FromBase64String(lines[3]);

                if (!VerifySignature(dataBytes, signatureBytes))
                    return new LicenseValidationResult { IsValid = false, ErrorMessage = "License signature verification failed." };

                string jsonString = Encoding.UTF8.GetString(Convert.FromBase64String(lines[1]));
                return new LicenseValidationResult { IsValid = true, License = ParseLicenseJson(jsonString) };
            }
            catch (Exception ex)
            {
                return new LicenseValidationResult { IsValid = false, ErrorMessage = $"Validation error: {ex.Message}" };
            }
        }

        private bool VerifySignature(byte[] data, byte[] signature)
        {
            using var rsa = RSA.Create();
            rsa.ImportFromPem(PublicKeyPem.ToCharArray());
            return rsa.VerifyData(data, signature, HashAlgorithmName.SHA256, RSASignaturePadding.Pkcs1);
        }

        private LicenseInfo ParseLicenseJson(string json)
        {
            using var doc = JsonDocument.Parse(json);
            var root = doc.RootElement;

            return new LicenseInfo
            {
                LicenseId = GetString(root, "license_id"),
                LicenseeName = GetString(root, "licensee_name"),
                LicenseeEmail = GetString(root, "licensee_email"),
                CompanyName = GetString(root, "company_name"),
                ProductName = GetString(root, "product_name"),
                ProductVersion = GetString(root, "product_version"),
                LicenseType = GetString(root, "license_type"),
                IssuedDate = DateTime.Parse(GetString(root, "issued_date", "2000-01-01")),
                ExpiryDate = DateTime.Parse(GetString(root, "expiry_date", "2000-01-01")),
                MaxMachines = root.TryGetProperty("max_machines", out var m) && m.ValueKind == JsonValueKind.Number ? m.GetInt32() : 1,
                Features = GetString(root, "features"),
            };
        }

        private static string GetString(JsonElement root, string prop, string def = "") =>
            root.TryGetProperty(prop, out var el) ? el.GetString() ?? def : def;
    }
}
