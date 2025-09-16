<?php
require_once "config/db.php";

// Get published properties
$stmt = $pdo->query("SELECT p.id, p.title, p.short_description, p.price, p.bedrooms, p.bathrooms, p.area_sq_ft, l.city,
    (SELECT filename FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) AS main_image
    FROM properties p
    JOIN locations l ON p.location_id = l.id
    WHERE p.status = 'published'
    ORDER BY p.id DESC");
$properties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Gate - Your Dream Home Awaits</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="css/styles.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light" id="navbar">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="img/ho.jpg" alt="Property Gate Logo">
                Property Gate
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon-line"></span>
                <span class="navbar-toggler-icon-line"></span>
                <span class="navbar-toggler-icon-line"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#featured-properties">Featured Properties</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">Testimonials</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link" href="#footer">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>


    <header class="header">
        <div id="header-bg"></div>
        <div class="container" data-aos="fade-up" data-aos-duration="1500">
            <h1>Your Dream Home Awaits</h1>
            <p>Browse, Compare, and Find Your Perfect Property</p>
            <a href="#featured-properties" class="btn btn-light">Explore Now</a>
        </div>
        <div class="wave-container">
            <svg class="wave-svg" viewBox="0 0 1440 320" preserveAspectRatio="none" style="width: 100%; height: 100%;">
                <path fill="#F5F7FA" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,138.7C672,149,768,203,864,202.7C960,203,1056,149,1152,117.3C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
    </header>

    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <h2 data-aos="fade-in" data-aos-duration="1000">How It Works</h2>
            <div class="row">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="icon-box">
                        <i class="bi bi-map"></i>
                        <h4>01. Search for Location</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis mollis et sem sed
                            sollicitudin.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="icon-box">
                        <i class="bi bi-house-door"></i>
                        <h4>02. Select Property Type</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis mollis et sem sed
                            sollicitudin.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="icon-box">
                        <i class="bi bi-check-circle"></i>
                        <h4>03. Book Your Property</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis mollis et sem sed
                            sollicitudin.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="featured-properties" class="container py-5">
        <h2 class="text-center mb-5 fw-bold text-primary" data-aos="fade-in">Featured Properties</h2>
        <div class="row g-4 justify-content-center">
            <?php if (empty($properties)): ?>
                <p class="text-center col-12">No properties found at the moment. Please check back later!</p>
            <?php else: ?>
                <?php foreach ($properties as $prop): ?>
                    <div class="col-sm-12 col-md-6 col-lg-4" data-aos="fade-up">
                        <div class="card shadow-lg h-100 property-card">
                            <div class="property-image-container">
                                <img src="admin/upload/<?= htmlspecialchars($prop['main_image'] ?: 'no-image.jpg') ?>"
                                    class="card-img-top property-image" alt="Image of <?= htmlspecialchars($prop['title']) ?>">
                                <div class="badge-container">
                                    <span class="badge-featured">Featured</span>
                                    <span class="badge-new">New</span>
                                </div>
                                <div class="price-badge">PKR <?= number_format($prop['price']) ?></div>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold text-truncate"><?= htmlspecialchars($prop['title']) ?></h5>
                                <p class="card-text text-muted mb-2"><i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($prop['city']) ?></p>
                                <div class="card-text short-description">
                                    <?= htmlspecialchars($prop['short_description']) ?>
                                </div>

                                <hr class="my-3">

                                <div class="property-features d-flex justify-content-between text-center flex-wrap">
                                    <div class="feature-item">
                                        <i class="bi bi-door-open fs-5 text-primary"></i>
                                        <div class="feature-text"><?= htmlspecialchars($prop['bedrooms']) ?> Beds</div>
                                    </div>
                                    <div class="feature-item">
                                        <i class="bi bi-badge-wc fs-5 text-primary"></i>
                                        <div class="feature-text"><?= htmlspecialchars($prop['bathrooms']) ?> Baths</div>
                                    </div>
                                    <div class="feature-item">
                                        <i class="bi bi-arrows-fullscreen fs-5 text-primary"></i>
                                        <div class="feature-text"><?= htmlspecialchars($prop['area_sq_ft']) ?> sq.ft</div>
                                    </div>
                                </div>
                                <div class="mt-auto pt-3 text-center">
                                    <a href="property.php?id=<?= $prop['id'] ?>" class="btn btn-primary w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="testimonial-bg-section">
        <div class="faq-wave-top">
            <svg viewBox="0 0 1440 100" preserveAspectRatio="none" style="width: 100%; height: 100%;">
                <path fill="#F5F7FA" d="M0,0L1440,0L1440,50L720,100L0,50L0,0Z"></path>
            </svg>
        </div>
        <section id="faq" class="faq-section">
            <div class="container-xl" data-aos="fade-in">
                <div class="faq_1 row">
                    <div class="col-md-4" data-aos="fade-right">
                        <div class="faq_1l">
                            <img src="img/bu.jpg" class="w-100 rounded_10" alt="abc">
                        </div>
                    </div>
                    <div class="col-md-8" data-aos="fade-left">
                        <div class="faq_1r">
                            <h2>Frequently Asked Questions</h2>
                            <hr class="line">
                            <div class="accordion" id="accordionExample">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                            <i class="fa fa-check-circle me-2"></i> 1. What are the costs to buy a house?
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show"
                                        aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem
                                            Ipsum has been the industry's standard dummy text ever since the 1500s.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            <i class="fa fa-check-circle me-2"></i> 2. What are the steps to sell a house?
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                                        data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem
                                            Ipsum has been the industry's standard dummy text ever since the 1500s.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseThree" aria-expanded="false"
                                            aria-controls="collapseThree">
                                            <i class="fa fa-check-circle me-2"></i> 3. Do you have loan consultants?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse"
                                        aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Yes, we offer loan consultancy services to guide you through financing options. Our
                                            experts are here to help!
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingFour">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseFour" aria-expanded="false"
                                            aria-controls="collapseFour">
                                            <i class="fa fa-check-circle me-2"></i> 4. When will the project be completed?
                                        </button>
                                    </h2>
                                    <div id="collapseFour" class="accordion-collapse collapse"
                                        aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem
                                            Ipsum has been the industry's standard dummy text ever since the 1500s.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingFive">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseFive" aria-expanded="false"
                                            aria-controls="collapseFive">
                                            <i class="fa fa-check-circle me-2"></i> 5. How can I list my property?
                                        </button>
                                    </h2>
                                    <div id="collapseFive" class="accordion-collapse collapse"
                                        aria-labelledby="headingFive" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            You can easily list your property on our platform by signing up and following the simple steps.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <section id="testimonials">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold text-primary" data-aos="fade-in">What Our Clients Say</h2>
            <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="testimonial-content">
                                    <p class="testimonial-text">"Property Gate made finding my new home an absolute breeze. The website is intuitive, and the listings are high-quality. I found exactly what I was looking for!"</p>
                                    <div class="testimonial-footer">
                                        <h4 class="testimonial-name">Jane Doe</h4>
                                        <p class="testimonial-location">Lahore, Pakistan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="testimonial-content">
                                    <p class="testimonial-text">"I sold my property in record time with Property Gate. The process was seamless, and their team was incredibly helpful every step of the way. Highly recommended!"</p>
                                    <div class="testimonial-footer">
                                        <h4 class="testimonial-name">John Smith</h4>
                                        <p class="testimonial-location">Islamabad, Pakistan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="testimonial-content">
                                    <p class="testimonial-text">"The search filters are powerful and helped me narrow down my options quickly. I appreciated the detailed descriptions and high-resolution images. Great experience overall."</p>
                                    <div class="testimonial-footer">
                                        <h4 class="testimonial-name">Sarah Khan</h4>
                                        <p class="testimonial-location">Karachi, Pakistan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </section>

        <div class="contact-footer-container">
    <div class="footer-wave-top">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path fill="#2C3E50" fill-opacity="1" d="M0,256L60,245.3C120,235,240,213,360,208C480,203,600,213,720,224C840,235,960,245,1080,245.3C1200,245,1320,235,1380,229.3L1440,224L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path>
        </svg>
    </div>
    <div class="contact-footer-container">
    <div class="footer-wave-top">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path fill="#2C3E50" fill-opacity="1" d="M0,256L60,245.3C120,235,240,213,360,208C480,203,600,213,720,224C840,235,960,245,1080,245.3C1200,245,1320,235,1380,229.3L1440,224L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path>
        </svg>
    </div>
    <div class="contact-footer-container">
    <div class="footer-wave-top">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path fill="#2C3E50" fill-opacity="1" d="M0,256L60,245.3C120,235,240,213,360,208C480,203,600,213,720,224C840,235,960,245,1080,245.3C1200,245,1320,235,1380,229.3L1440,224L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path>
        </svg>
    </div>
    <div class="contact-footer-container">
    <div class="footer-wave-top">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path fill="#2C3E50" fill-opacity="1" d="M0,256L60,245.3C120,235,240,213,360,208C480,203,600,213,720,224C840,235,960,245,1080,245.3C1200,245,1320,235,1380,229.3L1440,224L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path>
        </svg>
    </div>
    <footer class="footer-custom bg-dark-green text-white py-5" id="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 col-md-12 text-center text-lg-start mb-4 mb-lg-0">
                    <div class="footer-logo mb-3">
                        <img src="img/ho.jpg" alt="Property Gate Logo" class="logo-image me-2">
                        <span class="logo-text fw-bold">Property Gate</span>
                    </div>
                    <p class="footer-mission">
                        Property Gate is a leading non-partisan organization dedicated to helping you find your dream home. We are not part of any group or organization. We welcome all clients interested in quality property listings.
                    </p>
                    <div class="social-links d-flex justify-content-center justify-content-lg-start mt-4">
                        <a href="#" class="social-icon-link me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-icon-link me-2"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="social-icon-link me-2"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-icon-link"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <div class="col-lg-6 col-md-12 mt-4 mt-lg-0 text-center">
                    <h4 class="text-white fw-bold mb-4">Contact us on WhatsApp</h4>
                    <a href="https://wa.me/923006005714" class="whatsapp-btn d-inline-flex align-items-center justify-content-center fw-bold text-decoration-none">
                        <i class="bi bi-whatsapp me-2 fs-4"></i> WhatsApp
                    </a>
                </div>
            </div>

            <hr class="my-5 border-white-50">

            <div class="row text-center text-md-start">
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="fw-bold text-light-green">FEEL FREE TO CONTACT US</h5>
                    <ul class="list-unstyled contact-info mt-3">
                        <li><i class="bi bi-phone me-2"></i>PK: +92 300 600 5714</li>
                        <li><i class="bi bi-envelope me-2"></i>Anwar5714@gmail.com</li>
                    </ul>
                </div>

                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="fw-bold text-light-green">QUICK LINKS</h5>
                    <ul class="list-unstyled quick-links mt-3">
                        <li><a href="#" class="text-white-50">About Us</a></li>
                        <li><a href="#featured-properties" class="text-white-50">Properties</a></li>
                        <li><a href="#" class="text-white-50">Packages</a></li>
                        <li><a href="#" class="text-white-50">Blog</a></li>
                        <li><a href="#" class="text-white-50">Privacy Policy</a></li>
                    </ul>
                </div>

                <div class="col-md-4">
                    <h5 class="fw-bold text-light-green">YOUR DREAM HOME AWAITS</h5>
                    <p class="text-white-50 mt-3">
                        Discover the finest properties for sale and rent. Our expert agents are here to guide you through every step of your real estate journey. Whether you are buying, selling, or renting, we have the perfect solution for you.
                    </p>
                </div>
            </div>
            
            <hr class="my-4 border-white-50">

            <div class="text-center text-white-50">
                <p class="mb-0">© 2025 Property Gate. All Rights Reserved.</p>
                <p>Designed and Developed by MTA OPTIVIST.</p>
            </div>

        </div>
    </footer>
</div>
</div>
</div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        AOS.init({
            once: true
        });

        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const navCollapse = document.getElementById('navbarNav');
                const bsCollapse = new bootstrap.Collapse(navCollapse, {
                    toggle: false
                });
                if (navCollapse.classList.contains('show')) {
                    bsCollapse.hide();
                }
            });
        });

        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>

</html>