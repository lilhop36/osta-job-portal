<?php
declare(strict_types=1);

namespace App\Controllers;

class AboutController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'About Us';
        include dirname(__DIR__, 2) . '/includes/header.php';
        ?>
        <div class="container mt-5">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="fw-bold mb-4" style="color: var(--osta-dark);">About OSTA Job Portal</h1>
                    <p class="lead text-muted mb-5">
                        Connecting talented individuals with opportunities in science and technology across Oromia.
                    </p>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="mb-3" style="font-size: 2.5rem; color: var(--osta-green);">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h5 class="fw-bold">Our Mission</h5>
                            <p class="text-muted">To bridge the gap between skilled professionals and employers in the science and technology sector across Oromia.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="mb-3" style="font-size: 2.5rem; color: var(--osta-green);">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h5 class="fw-bold">Our Vision</h5>
                            <p class="text-muted">To become the leading platform for talent acquisition and career development in the region's technology sector.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="mb-3" style="font-size: 2.5rem; color: var(--osta-green);">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h5 class="fw-bold">Our Values</h5>
                            <p class="text-muted">Integrity, innovation, inclusivity, and excellence in everything we do to serve our community.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5">
                            <h3 class="fw-bold mb-3" style="color: var(--osta-dark);">About Oromia Science and Technology Authority</h3>
                            <p class="text-muted">
                                The Oromia Science and Technology Authority (OSTA) is dedicated to advancing science, technology, and innovation across the Oromia region. The OSTA Job Portal is one of our key initiatives to connect skilled professionals with employers in the technology sector.
                            </p>
                            <p class="text-muted">
                                Our platform serves as a centralized hub for job seekers and employers, streamlining the recruitment process and ensuring that the best talent finds the right opportunities.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        include dirname(__DIR__, 2) . '/includes/footer.php';
    }
}
