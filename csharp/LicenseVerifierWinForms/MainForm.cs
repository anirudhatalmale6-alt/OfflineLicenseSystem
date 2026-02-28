using System;
using System.Drawing;
using System.IO;
using System.Windows.Forms;

namespace LicenseVerifierWinForms
{
    public class MainForm : Form
    {
        private TextBox txtLicenseFile;
        private Button btnBrowse;
        private Button btnVerify;
        private Label lblStatus;
        private TextBox txtDetails;
        private Panel statusPanel;

        public MainForm()
        {
            InitializeComponents();
            this.Text = "License Verifier Demo";
            this.Size = new Size(620, 520);
            this.StartPosition = FormStartPosition.CenterScreen;
            this.FormBorderStyle = FormBorderStyle.FixedSingle;
            this.MaximizeBox = false;
        }

        private void InitializeComponents()
        {
            // Title
            var lblTitle = new Label
            {
                Text = "License Verification",
                Font = new Font("Segoe UI", 16, FontStyle.Bold),
                Location = new Point(20, 15),
                AutoSize = true
            };

            // File path label
            var lblFile = new Label
            {
                Text = "License File:",
                Location = new Point(20, 60),
                AutoSize = true,
                Font = new Font("Segoe UI", 10)
            };

            // File path textbox
            txtLicenseFile = new TextBox
            {
                Location = new Point(20, 85),
                Size = new Size(450, 25),
                Font = new Font("Segoe UI", 10)
            };

            // Browse button
            btnBrowse = new Button
            {
                Text = "Browse...",
                Location = new Point(480, 83),
                Size = new Size(100, 28),
                Font = new Font("Segoe UI", 9)
            };
            btnBrowse.Click += BtnBrowse_Click;

            // Verify button
            btnVerify = new Button
            {
                Text = "Verify License",
                Location = new Point(20, 125),
                Size = new Size(560, 40),
                Font = new Font("Segoe UI", 11, FontStyle.Bold),
                BackColor = Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnVerify.FlatAppearance.BorderSize = 0;
            btnVerify.Click += BtnVerify_Click;

            // Status panel
            statusPanel = new Panel
            {
                Location = new Point(20, 180),
                Size = new Size(560, 40),
                Visible = false
            };

            lblStatus = new Label
            {
                Dock = DockStyle.Fill,
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
                TextAlign = ContentAlignment.MiddleCenter,
                ForeColor = Color.White
            };
            statusPanel.Controls.Add(lblStatus);

            // Details textbox
            txtDetails = new TextBox
            {
                Location = new Point(20, 235),
                Size = new Size(560, 230),
                Multiline = true,
                ReadOnly = true,
                ScrollBars = ScrollBars.Vertical,
                Font = new Font("Consolas", 10),
                BackColor = Color.FromArgb(245, 245, 245),
                Visible = false
            };

            // Add controls
            this.Controls.AddRange(new Control[]
            {
                lblTitle, lblFile, txtLicenseFile, btnBrowse, btnVerify,
                statusPanel, txtDetails
            });
        }

        private void BtnBrowse_Click(object? sender, EventArgs e)
        {
            using var dialog = new OpenFileDialog
            {
                Filter = "License Files (*.lic)|*.lic|All Files (*.*)|*.*",
                Title = "Select License File"
            };

            if (dialog.ShowDialog() == DialogResult.OK)
            {
                txtLicenseFile.Text = dialog.FileName;
            }
        }

        private void BtnVerify_Click(object? sender, EventArgs e)
        {
            string filePath = txtLicenseFile.Text.Trim();

            if (string.IsNullOrEmpty(filePath))
            {
                MessageBox.Show("Please select a license file first.", "No File Selected",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            var validator = new LicenseValidator();
            var result = validator.ValidateLicenseFile(filePath);

            statusPanel.Visible = true;
            txtDetails.Visible = true;

            if (result.IsValid)
            {
                var lic = result.License!;

                if (lic.IsExpired)
                {
                    statusPanel.BackColor = Color.FromArgb(234, 179, 8);
                    lblStatus.Text = "LICENSE VALID BUT EXPIRED";
                }
                else
                {
                    statusPanel.BackColor = Color.FromArgb(34, 197, 94);
                    lblStatus.Text = "LICENSE VALID AND ACTIVE";
                }

                var daysLeft = (lic.ExpiryDate - DateTime.Now.Date).Days;

                txtDetails.Text =
                    $"License ID:     {lic.LicenseId}\r\n" +
                    $"Licensee:       {lic.LicenseeName}\r\n" +
                    $"Email:          {lic.LicenseeEmail}\r\n" +
                    $"Company:        {lic.CompanyName}\r\n" +
                    $"Product:        {lic.ProductName} v{lic.ProductVersion}\r\n" +
                    $"License Type:   {lic.LicenseType}\r\n" +
                    $"Issued:         {lic.IssuedDate:yyyy-MM-dd}\r\n" +
                    $"Expires:        {lic.ExpiryDate:yyyy-MM-dd}\r\n" +
                    $"Max Machines:   {lic.MaxMachines}\r\n" +
                    $"Features:       {lic.Features}\r\n" +
                    $"Days Left:      {(lic.IsExpired ? "EXPIRED" : $"{daysLeft} days")}\r\n" +
                    $"\r\nSignature:      VERIFIED (RSA-SHA256)";
            }
            else
            {
                statusPanel.BackColor = Color.FromArgb(239, 68, 68);
                lblStatus.Text = "LICENSE VERIFICATION FAILED";
                txtDetails.Text = $"Error: {result.ErrorMessage}";
            }
        }
    }
}
