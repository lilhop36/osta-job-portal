<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_dashboard = str_starts_with($current_path, '/osta%20job%20portal/admin/')
              || str_starts_with($current_path, '/osta%20job%20portal/employer/')
              || str_starts_with($current_path, '/osta%20job%20portal/applicant/');
if ($is_dashboard) {
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
<?php
    return;
}
?>
    <footer style="background: var(--osta-dark); color: white; margin-top: 3rem;">
        <div class="container">
            <div class="row py-5">
                <div class="col-md-5 mb-4 mb-md-0">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-briefcase me-2" style="color: #90EE90;"></i>
                        OSTA<span style="color: #90EE90;">Jobs</span>
                    </h5>
                    <p style="color: rgba(255,255,255,0.65); line-height: 1.7;">
                        Connecting talented individuals with opportunities in science and technology across Oromia.
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="d-flex align-items-center justify-content-center" style="width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.7);transition:all 0.3s;"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="d-flex align-items-center justify-content-center" style="width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.7);transition:all 0.3s;"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="d-flex align-items-center justify-content-center" style="width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.7);transition:all 0.3s;"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 mb-md-0">
                    <h6 class="fw-bold text-uppercase mb-3" style="font-size:0.8rem; letter-spacing:1px; color: rgba(255,255,255,0.5);">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/index.php" style="color: rgba(255,255,255,0.65);">Home</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/jobs.php" style="color: rgba(255,255,255,0.65);">Find Jobs</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/about.php" style="color: rgba(255,255,255,0.65);">About Us</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/contact.php" style="color: rgba(255,255,255,0.65);">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 col-6">
                    <h6 class="fw-bold text-uppercase mb-3" style="font-size:0.8rem; letter-spacing:1px; color: rgba(255,255,255,0.5);">Contact Info</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2" style="color: rgba(255,255,255,0.65);"><i class="fas fa-envelope me-2" style="color: #90EE90;"></i>info@osta.gov.et</li>
                        <li class="mb-2" style="color: rgba(255,255,255,0.65);"><i class="fas fa-phone me-2" style="color: #90EE90;"></i>+251-11-1234567</li>
                        <li class="mb-2" style="color: rgba(255,255,255,0.65);"><i class="fas fa-map-marker-alt me-2" style="color: #90EE90;"></i>Addis Ababa, Ethiopia</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="text-center py-3" style="background: rgba(0,0,0,0.2);">
            <p class="mb-0" style="color: rgba(255,255,255,0.45); font-size: 0.85rem;">
                &copy; <?php echo date('Y'); ?> Oromia Science and Technology Authority. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Page-specific scripts -->
    <?php if (function_exists('page_specific_scripts')) { 
        page_specific_scripts(); 
    } ?>
</body>
</html>
