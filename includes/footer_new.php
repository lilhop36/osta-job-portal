        </div><!-- End of container -->
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="text-uppercase mb-4">About OSTA Job Portal</h5>
                    <p>Connecting talented individuals with opportunities in science and technology across Oromia region.</p>
                    <div class="social-links mt-3">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg-2">
                    <h5 class="text-uppercase mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/jobs.php" class="text-white-50">Browse Jobs</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/about.php" class="text-white-50">About Us</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/contact.php" class="text-white-50">Contact</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/faq.php" class="text-white-50">FAQs</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/privacy.php" class="text-white-50">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 col-lg-3">
                    <h5 class="text-uppercase mb-4">For Job Seekers</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/register.php" class="text-white-50">Create Account</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/applicant/job_alerts.php" class="text-white-50">Job Alerts</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/career-advice.php" class="text-white-50">Career Advice</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/resume-tips.php" class="text-white-50">Resume Tips</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 col-lg-3">
                    <h5 class="text-uppercase mb-4">For Employers</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/employer/register.php" class="text-white-50">Post a Job</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/employer/pricing.php" class="text-white-50">Pricing</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/employer/resources.php" class="text-white-50">Resources</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php" class="text-white-50">Contact Sales</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4 bg-secondary">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> OSTA Job Portal. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">
                        <a href="<?php echo SITE_URL; ?>/terms.php" class="text-white-50 me-3">Terms of Service</a>
                        <a href="<?php echo SITE_URL; ?>/privacy.php" class="text-white-50">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button onclick="topFunction()" id="backToTop" class="btn btn-primary btn-lg rounded-circle shadow" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Page-specific scripts -->
    <?php if (function_exists('page_specific_scripts')) { 
        page_specific_scripts(); 
    } ?>
    
    <script>
        // Back to top button
        let mybutton = document.getElementById("backToTop");
        
        // When the user scrolls down 20px from the top, show the button
        window.onscroll = function() {scrollFunction()};
        
        function scrollFunction() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                mybutton.style.display = "block";
            } else {
                mybutton.style.display = "none";
            }
        }
        
        // When the user clicks on the button, scroll to the top
        function topFunction() {
            document.body.scrollTop = 0; // For Safari
            document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
        }
    </script>
</body>
</html>
