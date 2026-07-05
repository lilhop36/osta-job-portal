<?php require_once 'config/database.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="mb-4"><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h1>

            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I create an account?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Click "Register" on the top navigation bar. Fill in your details, verify your email with the 6-digit OTP code, and your account will be ready. You can then complete your profile and start applying for jobs.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            How do I apply for a job?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Browse the Jobs page, click on a job that interests you, and click the "Apply" button. You will need to have a completed centralized application profile before applying. You can track all your applications from your dashboard.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            What is a centralized application?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            A centralized application is your master profile that contains your personal information, education, work experience, and skills. Instead of filling out a separate form for each job, you create one profile and apply to multiple jobs with a single click.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            How do I track my application status?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Log in to your account and go to "Applications" in your dashboard. You will see the current status of each application (Pending, Shortlisted, Interview Scheduled, Accepted, or Rejected). You will also receive in-app notifications when your status changes.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            Can I save jobs to apply later?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes. Click the save/bookmark icon on any job listing to save it. You can view all your saved jobs from the "Saved Jobs" section in your dashboard and apply when you are ready.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                            How do I upload my resume or documents?
                        </button>
                    </h2>
                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Go to "Documents" in your dashboard. You can upload your resume, cover letter, certificates, and other supporting documents. Accepted formats include PDF, DOC, and DOCX (max 5MB each).
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                            I forgot my password. What should I do?
                        </button>
                    </h2>
                    <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Click "Forgot Password?" on the login page. Enter your email address and you will receive a one-time password (OTP). Use that code to set a new password.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                            How do employers contact me?
                        </button>
                    </h2>
                    <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            When an employer shortlists or accepts your application, you will receive an in-app notification. Employers may also send you messages through the portal messaging system. Keep your profile updated to increase your chances.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                            Can I set up job alerts?
                        </button>
                    </h2>
                    <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes. Go to "Job Alerts" in your dashboard and create an alert with your preferred keywords, location, and job type. You will be notified when matching jobs are posted. You can choose daily, weekly, or monthly alert frequency.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                            Who can I contact for support?
                        </button>
                    </h2>
                    <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            For technical issues or questions about the portal, please use the <a href="contact.php">Contact Us</a> page or email us at <a href="mailto:info@osta.gov.et">info@osta.gov.et</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
