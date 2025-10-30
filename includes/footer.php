    <footer class="bg-light mt-5">
        <div class="container">
            <div class="row py-4">
                <div class="col-md-6">
                    <h5>Oromia Science and Technology Authority</h5>
                    <p>Connecting talented individuals with opportunities in science and technology.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php">About Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <p>Email: info@osta.gov.et<br>
                    Phone: +251-11-1234567</p>
                </div>
            </div>
        </div>
        <div class="text-center py-3 bg-dark text-white">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> OSTA Job Portal. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Page-specific scripts -->
    <?php if (function_exists('page_specific_scripts')) { 
        page_specific_scripts(); 
    } ?>
</body>
</html>
