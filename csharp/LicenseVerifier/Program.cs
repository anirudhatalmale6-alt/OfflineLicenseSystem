using System;
using System.IO;
using LicenseVerifier;

namespace LicenseDemo
{
    /// <summary>
    /// Demo console application showing how to verify a license file.
    /// </summary>
    class Program
    {
        static void Main(string[] args)
        {
            Console.WriteLine("=== License Verification Demo ===\n");

            // Determine license file path
            string licenseFilePath;

            if (args.Length > 0)
            {
                licenseFilePath = args[0];
            }
            else
            {
                // Default: look for license.lic in the current directory
                licenseFilePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "license.lic");

                if (!File.Exists(licenseFilePath))
                {
                    // Also check current working directory
                    licenseFilePath = "license.lic";
                }
            }

            Console.WriteLine($"License file: {Path.GetFullPath(licenseFilePath)}\n");

            // Create the validator
            var validator = new LicenseValidator();

            // Validate the license
            var result = validator.ValidateLicenseFile(licenseFilePath);

            if (result.IsValid)
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.WriteLine("SIGNATURE VALID - License is authentic!\n");
                Console.ResetColor();

                Console.WriteLine("--- License Details ---");
                Console.WriteLine(result.License);
                Console.WriteLine();

                // Check expiry
                if (result.License!.IsExpired)
                {
                    Console.ForegroundColor = ConsoleColor.Yellow;
                    Console.WriteLine("WARNING: This license has expired.");
                    Console.ResetColor();
                }
                else
                {
                    var daysLeft = (result.License.ExpiryDate - DateTime.Now.Date).Days;
                    Console.ForegroundColor = ConsoleColor.Green;
                    Console.WriteLine($"License is active. {daysLeft} days remaining.");
                    Console.ResetColor();
                }

                // Show features
                if (result.License.FeatureList.Count > 0)
                {
                    Console.WriteLine("\nEnabled Features:");
                    foreach (var feature in result.License.FeatureList)
                    {
                        Console.WriteLine($"  - {feature}");
                    }
                }

                // --- Example: How to use in your own application ---
                Console.WriteLine("\n--- Integration Example ---");
                Console.WriteLine("In your app, you would do something like:\n");
                Console.WriteLine("  var validator = new LicenseValidator();");
                Console.WriteLine("  var result = validator.ValidateLicenseFile(\"license.lic\");");
                Console.WriteLine("  if (result.IsValid && !result.License.IsExpired)");
                Console.WriteLine("  {");
                Console.WriteLine("      // App is licensed - enable full functionality");
                Console.WriteLine("  }");
                Console.WriteLine("  else");
                Console.WriteLine("  {");
                Console.WriteLine("      // Not licensed - show trial/purchase dialog");
                Console.WriteLine("  }");
            }
            else
            {
                Console.ForegroundColor = ConsoleColor.Red;
                Console.WriteLine("LICENSE VERIFICATION FAILED!");
                Console.WriteLine($"Error: {result.ErrorMessage}");
                Console.ResetColor();
            }

            Console.WriteLine("\nPress any key to exit...");
            Console.ReadKey();
        }
    }
}
