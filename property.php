<?php
// PHP code to fetch property and images goes here
require_once "config/db.php";

$id = $_GET['id'] ?? 0;

// Fetch property
$stmt = $pdo->prepare("SELECT p.*, l.address_line1, l.address_line2, l.city, l.state, l.postal_code, l.country
    FROM properties p
    JOIN locations l ON p.location_id = l.id
    WHERE p.id = ? AND p.status = 'published'");
$stmt->execute([$id]);
$property = $stmt->fetch();

if (!$property) {
    die("Property not found.");
}

// Fetch images
$stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY sort_order ASC, is_primary DESC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// Fetch suggested properties (up to 3, excluding the current one)
$suggested_properties_stmt = $pdo->prepare("SELECT p.id, p.title, p.price, p.currency, p.area_sq_ft, i.filename
    FROM properties p
    LEFT JOIN property_images i ON p.id = i.property_id AND i.is_primary = 1
    WHERE p.id != ? AND p.status = 'published'
    ORDER BY RAND() LIMIT 3");
$suggested_properties_stmt->execute([$id]);
$suggested_properties = $suggested_properties_stmt->fetchAll();

// Prepare structured data for SEO (Schema.org)
$structured_data = [
    "@context" => "https://schema.org",
    "@type" => "RealEstateAgent",
    "name" => "Property Gate",
    "description" => "Real estate company listing properties for sale and rent.",
    "url" => "http://yourdomain.com", // Replace with your domain
    "address" => [
        "@type" => "PostalAddress",
        "streetAddress" => "123 Main Street",
        "addressLocality" => "Islamabad",
        "addressRegion" => "Punjab",
        "addressCountry" => "PK"
    ],
    "image" => "http://yourdomain.com/logo.png", // Replace with your logo URL
    "sameAs" => [
        "https://www.facebook.com/yourpage",
        "https://twitter.com/yourpage"
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Gate - <?= htmlspecialchars($property['title']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.js" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <style>
        /* General Body and Font Styles - ALL FONT SIZES REDUCED */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            font-size: 15px; /* Reduced base font size */
        }

        /* Navbar */
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand img {
            height: 35px; /* Slightly smaller logo */
        }
        
        /* Main Content Section */
        .property-container {
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .property-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 15px;
            transition: transform 0.5s ease;
        }
        
        .property-image:hover {
            transform: scale(1.05);
        }

        .details-section {
            padding: 25px; /* Reduced padding */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .property-title {
            font-size: 2rem; /* Reduced from 2.5rem */
            font-weight: 700;
            color: #333;
        }

        .property-location {
            color: #777;
            font-size: 1rem; /* Reduced from 1.1rem */
        }
        
        .price-section {
            border-radius: 10px;
            padding: 15px; /* Reduced padding */
            background-color: #fff;
        }

        .property-price {
            font-size: 1.8rem; /* Reduced from 2.2rem */
            font-weight: 700;
            color: #f39c12;
        }
        
        .whatsapp-btn {
            background-color: #25D366;
            color: white;
            padding: 12px 25px; /* Reduced padding */
            border-radius: 50px;
            font-size: 1rem; /* Reduced from 1.25rem */
            transition: background-color 0.3s ease, transform 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .whatsapp-btn:hover {
            background-color: #128C7E;
            transform: translateY(-3px);
        }

        .features-section {
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
        }
        /* New styles for the feature icons before the button */
        .details-features-container {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-bottom: 20px;
        }

        .details-feature-item {
            font-size: 1rem;
            color: #555;
        }

        /* Style for the PNG icon */
        .details-feature-item .icon-png {
            height: 24px; /* Adjust size as needed */
            margin-bottom: 5px;
        }

        .details-feature-item i {
            font-size: 1.5rem; /* Icon size */
            color: #0d6efd;
        }

        .description-section {
            margin-top: 20px; /* Reduced margin */
        }

        .description-section h4 {
            font-size: 1.2rem;
        }

        .description-section p {
            font-size: 0.9rem;
        }
        
        /* New Section Styles */
        .property-info-section {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 25px; /* Reduced padding */
            margin-bottom: 25px; /* Space between sections */
        }

        .property-info-section h3 {
            font-size: 1.5rem; /* Reduced from 1.8rem */
            font-weight: 700;
            color: #F39C12;
            margin-bottom: 20px; /* Reduced margin */
            border-bottom: 2px solid rgba(243, 156, 18, 0.2);
            padding-bottom: 8px; /* Reduced padding */
        }

        .property-info-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px; /* Reduced space between rows */
        }

        .property-info-item {
            flex: 1 1 30%;
            min-width: 200px; /* Reduced min-width */
            margin-bottom: 10px;
        }

        .property-info-item strong, .property-info-item span {
            font-size: 0.9rem; /* Reduced font size */
        }

        .property-info-item strong {
            color: #333;
            margin-right: 5px;
        }

        .property-info-item span {
            color: #666;
        }

        /* Suggested Properties Section Styles */
        .suggested-section {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 25px;
        }

        .suggested-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .suggested-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .suggested-card-img {
            height: 200px;
            object-fit: cover;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .property-info-item {
                flex: 1 1 45%;
                min-width: unset;
            }
        }

        @media (max-width: 576px) {
            .property-info-item {
                flex: 1 1 100%;
            }
            .property-title {
                font-size: 1.75rem; /* Further reduction for mobile */
            }
            .overview-grid {
                grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            }
        }

    </style>
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
                    <li class="nav-item"><a class="nav-link" aria-current="page" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#featured-properties">Featured Properties</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#testimonials">Testimonials</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#faq">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#footer">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <div class="container py-5" data-aos="fade-up">
            <div class="row property-container g-0 mb-5">
                <div class="col-md-7">
                    <?php if ($images): ?>
                        <div id="propertyCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images as $index => $img): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="admin/upload/<?= htmlspecialchars($img['filename']) ?>" class="d-block w-100 property-image" alt="Image of <?= htmlspecialchars($property['title']) ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <img src="admin/upload/no-image.jpg" class="img-fluid property-image" alt="No image available">
                    <?php endif; ?>
                </div>

                <div class="col-md-5 details-section">
                    <div>
                        <h1 class="property-title"><?= htmlspecialchars($property['title']) ?></h1>
                        <p class="property-location"><i class="bi bi-geo-alt-fill me-2"></i> <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state']) ?>, <?= htmlspecialchars($property['country']) ?></p>
                        
                        <div class="price-section my-4 shadow-sm">
                               <h3 class="property-price"><?= number_format($property['price']) ?> <?= htmlspecialchars($property['currency']) ?></h3>
                        </div>
                        
                        <div class="description-section">
                            <h4 class="fw-bold">Description</h4>
                            <p class="text-secondary"><?= nl2br(htmlspecialchars($property['description'])) ?></p>
                        </div>
                    </div>
                    
                    <div class="details-features-container">
                        <div class="details-feature-item">
                            <img src="img/bed.png" alt="Bed Icon" class="icon-png">
                            <p><?= htmlspecialchars($property['bedrooms'] ?? 'N/A') ?> Beds</p>
                        </div>
                        <div class="details-feature-item">
                            <i class="bi bi-droplet"></i>
                            <p><?= htmlspecialchars($property['bathrooms'] ?? 'N/A') ?> Baths</p>
                        </div>
                        <div class="details-feature-item">
                            <i class="bi bi-house-door"></i>
                            <p><?= htmlspecialchars($property['area_sq_ft'] ?? 'N/A') ?> Sqft</p>
                        </div>
                    </div>

                    <a href="https://wa.me/923006005714?text=Hello, I am interested in the property listed as: <?= urlencode($property['title']) ?>. Can you please provide more details?"
 target="_blank"
 class="whatsapp-btn text-center mt-4 text-decoration-none">
 <i class="bi bi-whatsapp me-2"></i> Contact on WhatsApp
</a>
                </div>
            </div>
            
            <div class="property-info-section" data-aos="fade-up" data-aos-delay="200">
                <h3>Property Address</h3>
                <div class="row">
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Address Line 1:</strong> <span><?= htmlspecialchars($property['address_line1'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>City:</strong> <span><?= htmlspecialchars($property['city'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>State/County:</strong> <span><?= htmlspecialchars($property['state'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Country:</strong> <span><?= htmlspecialchars($property['country'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Postal Code:</strong> <span><?= htmlspecialchars($property['postal_code'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>

            <div class="property-info-section" data-aos="fade-up" data-aos-delay="300">
                <h3>Property Details</h3>
                <div class="row">
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Property ID:</strong> <span><?= htmlspecialchars($property['id'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Property Type:</strong> <span><?= htmlspecialchars($property['property_type'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Price:</strong> <span><?= number_format($property['price']) ?> <?= htmlspecialchars($property['currency']) ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Bedrooms:</strong> <span><?= htmlspecialchars($property['bedrooms'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Bathrooms:</strong> <span><?= htmlspecialchars($property['bathrooms'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Area:</strong> <span><?= htmlspecialchars($property['area_sq_ft'] ?? 'N/A') ?> ft²</span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Status:</strong> <span><?= htmlspecialchars(ucwords($property['status'] ?? 'N/A')) ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Price/sqft:</strong> <span><?= number_format($property['price'] / ($property['area_sq_ft'] > 0 ? $property['area_sq_ft'] : 1), 2) ?> <?= htmlspecialchars($property['currency']) ?></span>
                    </div>
                    <div class="col-md-6 col-lg-4 property-info-item">
                        <strong>Last Updated:</strong> <span><?= htmlspecialchars(date('F j, Y', strtotime($property['updated_at']))) ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($suggested_properties)): ?>
            <div class="suggested-section mt-5" data-aos="fade-up" data-aos-delay="400">
                <h3 class="mb-4">You might also like...</h3>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($suggested_properties as $s_property): ?>
                    <div class="col">
                        <div class="card suggested-card h-100">
                            <img src="admin/upload/<?= htmlspecialchars($s_property['filename'] ?? 'no-image.jpg') ?>" class="card-img-top suggested-card-img" alt="<?= htmlspecialchars($s_property['title']) ?>">
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?= htmlspecialchars($s_property['title']) ?></h5>
                                <p class="card-text text-secondary mb-2"><?= number_format($s_property['price']) ?> <?= htmlspecialchars($s_property['currency']) ?></p>
                                <p class="card-text text-muted small"><?= htmlspecialchars($s_property['area_sq_ft'] ?? 'N/A') ?> ft²</p>
                                <a href="property-details.php?id=<?= htmlspecialchars($s_property['id']) ?>" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
    
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
      AOS.init();
    </script>
</body>
</html>