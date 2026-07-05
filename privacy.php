<?php require_once 'config/database.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="mb-4"><i class="fas fa-shield-alt me-2"></i>Privacy Policy</h1>
            <p class="text-muted">Last updated: July 2026</p>

            <div class="card">
                <div class="card-body">
                    <h5>1. Information We Collect</h5>
                    <p>When you use the OSTA Job Portal, we collect the following information:</p>
                    <ul>
                        <li><strong>Personal Information:</strong> Full name, email address, phone number, address, date of birth</li>
                        <li><strong>Professional Information:</strong> Education, work experience, skills, certifications, resume/CV uploads</li>
                        <li><strong>Account Information:</strong> Username, password (encrypted), role (applicant, employer, admin)</li>
                        <li><strong>Usage Data:</strong> Pages visited, jobs viewed, applications submitted, login timestamps</li>
                        <li><strong>Device Information:</strong> Browser type, IP address, operating system</li>
                    </ul>

                    <h5>2. How We Use Your Information</h5>
                    <ul>
                        <li>To create and manage your account</li>
                        <li>To process job applications and match you with relevant opportunities</li>
                        <li>To notify you about application status updates, interviews, and new job postings</li>
                        <li>To communicate with you regarding your account and inquiries</li>
                        <li>To improve the portal and user experience</li>
                        <li>To ensure platform security and prevent fraud</li>
                        <li>To comply with legal obligations</li>
                    </ul>

                    <h5>3. Information Sharing</h5>
                    <p>We share your information as follows:</p>
                    <ul>
                        <li><strong>With Employers:</strong> When you apply for a job, the employer for that position can see your application profile, resume, and submitted documents</li>
                        <li><strong>With Admin:</strong> Platform administrators have access to all data for management purposes</li>
                        <li><strong>Third Parties:</strong> We do not sell or share your personal information with third parties for marketing purposes</li>
                    </ul>

                    <h5>4. Data Security</h5>
                    <p>We implement industry-standard security measures including:</p>
                    <ul>
                        <li>Encrypted password storage (Argon2id)</li>
                        <li>HTTPS/TLS encryption for data in transit</li>
                        <li>CSRF protection on all forms</li>
                        <li>SQL injection prevention via prepared statements</li>
                        <li>Regular security audits and logging</li>
                        <li>Session timeout and secure cookie settings</li>
                    </ul>

                    <h5>5. Data Retention</h5>
                    <p>Your account data is retained as long as your account is active. When you delete your account, your personal data is permanently removed within 30 days. Application records may be retained for audit purposes.</p>

                    <h5>6. Your Rights</h5>
                    <ul>
                        <li>Access your personal data at any time</li>
                        <li>Update or correct your information through your profile</li>
                        <li>Export your data in JSON format</li>
                        <li>Delete your account and associated data</li>
                        <li>Opt out of non-essential notifications</li>
                    </ul>

                    <h5>7. Cookies</h5>
                    <p>We use session cookies to maintain your login state and security tokens. These cookies are essential for the portal to function and are automatically deleted when you log out or close your browser.</p>

                    <h5>8. Children's Privacy</h5>
                    <p>This portal is intended for users aged 18 and above. We do not knowingly collect information from minors.</p>

                    <h5>9. Changes to This Policy</h5>
                    <p>We may update this privacy policy from time to time. Changes will be posted on this page with an updated revision date.</p>

                    <h5>10. Contact Us</h5>
                    <p>For questions about this privacy policy, please contact us at <a href="mailto:info@osta.gov.et">info@osta.gov.et</a> or visit our <a href="contact.php">Contact page</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
