<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch admin's name only if the session variable is set
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
    $stmt = $pdo->prepare("SELECT full_name FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    if ($admin) {
        $admin_name = htmlspecialchars($admin['full_name']);
    }
}

// Stats summary
$total = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$for_sale = $pdo->query("SELECT COUNT(*) FROM properties WHERE property_type = 'sale'")->fetchColumn();
$for_rent = $pdo->query("SELECT COUNT(*) FROM properties WHERE property_type = 'rent'")->fetchColumn();
$sold = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'sold'")->fetchColumn();
$published = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'published'")->fetchColumn();


/// Fetch properties with location + primary image
$stmt = $pdo->query("
    SELECT p.id, p.title, p.price, p.status, p.property_type, 
           p.bedrooms, p.bathrooms, p.area_sq_ft, 
           l.city, l.state, l.country,
           (SELECT filename FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM properties p
    JOIN locations l ON p.location_id = l.id
    ORDER BY p.created_by DESC
");
$properties = $stmt->fetchAll();

?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard | Property Gate</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
        <style>
            :root {
                --primary-color: #0d6efd;
                --secondary-color: #f39c12;
                --dark-color: #2c3e50;
                --light-color: #ecf0f1;
                --dashboard-bg: #f5f7fa;
            }

            body {
                font-family: 'Poppins', sans-serif;
                background-color: var(--dashboard-bg);
            }

            /* Sidebar Styling */
            .sidebar {
                width: 250px;
                background-color: var(--dark-color);
                color: white;
                padding: 20px;
                height: 100vh;
                position: fixed;
                top: 0;
                left: 0;
                overflow-y: auto;
                transition: transform 0.3s ease;
                z-index: 1000;
            }

            /* Offcanvas styling for mobile */
            @media (max-width: 768px) {
                .sidebar {
                    transform: translateX(-100%);
                }
                .sidebar.show {
                    transform: translateX(0);
                }
            }
            
            .sidebar-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .sidebar-header .logo {
                font-size: 1.5rem;
                font-weight: 600;
            }
            .sidebar-nav .nav-link {
                color: rgba(255, 255, 255, 0.8);
                font-size: 1rem;
                padding: 12px 15px;
                border-radius: 8px;
                margin-bottom: 5px;
                transition: background-color 0.3s, color 0.3s;
            }
            .sidebar-nav .nav-link:hover,
            .sidebar-nav .nav-link.active {
                background-color: rgba(255, 255, 255, 0.1);
                color: white;
            }
            .sidebar-nav .nav-link i {
                margin-right: 10px;
            }
            .sidebar-nav .nav-link.logout {
                color: #e74c3c;
            }
            .sidebar-nav .nav-link.logout:hover {
                background-color: #c0392b;
                color: white;
            }

            /* Top Header Bar */
            .top-header-bar {
                position: sticky;
                top: 0;
                left: 250px;
                z-index: 500;
                background-color: var(--dark-color);
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
                padding: 15px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                transition: left 0.3s ease;
            }

            @media (max-width: 768px) {
                .top-header-bar {
                    left: 0;
                }
            }
            
            /* Main Content */
            .main-content-wrapper {
                margin-left: 250px;
                transition: margin-left 0.3s ease;
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }
            
            .main-content {
                padding: 20px;
                flex-grow: 1;
            }
            
            @media (max-width: 768px) {
                .main-content-wrapper {
                    margin-left: 0;
                }
            }
            
            .page-title {
                font-size: 1.5rem;
                font-weight: 600;
                color: #fff;
                margin-bottom: 0;
            }
            
            .admin-name {
                font-size: 1rem;
                color: #fff;
                font-weight: 500;
            }

            /* Toggle Button */
            .toggle-btn {
                background: none;
                border: none;
                font-size: 1.5rem;
                color: white; /* Changed to white for visibility on dark background */
                display: none;
            }
            
            @media (max-width: 768px) {
                .toggle-btn {
                    display: block;
                }
            }

            /* Cards and Stats */
            .stat-card {
                background-color: white;
                border-radius: 10px;
                padding: 25px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                text-align: center;
            }
            .stat-card .icon {
                font-size: 2rem;
                color: var(--primary-color);
                margin-bottom: 10px;
            }
            .stat-card .value {
                font-size: 2.5rem;
                font-weight: 700;
                color: var(--dark-color);
            }
            .stat-card .label {
                font-size: 1rem;
                color: #777;
            }

            /* Table Styling */
            .table-container {
                background-color: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            }

            .property-table {
                width: 100%;
            }
            
            /* Ensure images have a fixed size to avoid layout shifts */
            .property-table .thumb {
                width: 80px;
                height: 60px;
                object-fit: cover;
                border-radius: 5px;
            }

            .property-table a {
                color: var(--primary-color);
                text-decoration: none;
            }
            .property-table a:hover {
                text-decoration: underline;
            }

            /* Filters and Search */
            .filter-section {
                margin-bottom: 20px;
            }
            .filter-section .form-control,
            .filter-section .form-select {
                border-radius: 8px;
            }
            
        </style>
    </head>
    <body>

    <div class="d-flex">
        <div class="sidebar d-flex flex-column" id="sidebar">
            <div class="sidebar-header">
                <h1 class="logo text-white">Property Gate</h1>
            </div>
            <nav class="sidebar-nav flex-grow-1">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_property.php">
                            <i class="bi bi-plus-square"></i> Add New Property
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-house"></i> View Properties
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-person"></i> Admins
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="main-content-wrapper" id="main-content-wrapper">

            <div class="top-header-bar">
                <h1 class="page-title">Dashboard</h1>
                <div class="d-flex align-items-center">
                    <span class="admin-name"><?= $admin_name ?></span>
                    <button class="toggle-btn ms-3" type="button">
                        <i id="toggleIcon" class="bi bi-list"></i>
                    </button>
                </div>
            </div>

            <div class="main-content">
                
                <div class="row g-4 mb-5">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-house-fill"></i></div>
                            <div class="value"><?= htmlspecialchars($total) ?></div>
                            <div class="label">Total Properties</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-tag-fill"></i></div>
                            <div class="value"><?= htmlspecialchars($for_sale) ?></div>
                            <div class="label">For Sale</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-cash-stack"></i></div>
                            <div class="value"><?= htmlspecialchars($for_rent) ?></div>
                            <div class="label">For Rent</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="value"><?= htmlspecialchars($published) ?></div>
                            <div class="label">Published</div>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold mb-3 mb-md-0">Property Listings</h3>
                        <div class="d-flex w-100 w-md-auto">
                            <input type="text" id="searchInput" class="form-control me-2" placeholder="Search by title, city...">
                            <select id="statusFilter" class="form-select me-2">
                                <option value="">All Status</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                                <option value="pending">Pending</option>
                                <option value="sold">Sold</option>
                            </select>
                            <select id="typeFilter" class="form-select me-2">
                                <option value="">All Types</option>
                                <option value="sale">For Sale</option>
                                <option value="rent">For Rent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover property-table">
                            <thead>
                                <tr>
                                    <th scope="col" class="d-none d-md-table-cell">ID</th>
                                    <th scope="col">Image</th>
                                    <th scope="col">Title</th>
                                    <th scope="col" class="d-none d-md-table-cell">City</th>
                                    <th scope="col" class="d-none d-md-table-cell">Price</th>
                                    <th scope="col" class="d-none d-md-table-cell">Type</th>
                                    <th scope="col" class="d-none d-md-table-cell">Beds</th>
                                    <th scope="col" class="d-none d-md-table-cell">Baths</th>
                                    <th scope="col" class="d-none d-md-table-cell">Area</th>
                                    <th scope="col" class="d-none d-md-table-cell">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="propertiesTableBody">
                                <?php if (empty($properties)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center">No properties found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($properties as $prop): ?>
                                        <tr>
                                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($prop['id']) ?></td>
                                            <td>
                                                <?php if ($prop['primary_image']): ?>
                                                    <img src="upload/<?= htmlspecialchars($prop['primary_image']) ?>" class="thumb">
                                                <?php else: ?>
                                                    <img src="../img/no-image.jpg" class="thumb" alt="No image available">
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($prop['title']) ?></td>
                                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($prop['city']) ?></td>
                                            <td class="d-none d-md-table-cell"><?= number_format($prop['price']) ?> PKR</td>
                                            <td class="d-none d-md-table-cell"><?= ucfirst($prop['property_type']) ?></td>
                                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($prop['bedrooms']) ?></td>
                                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($prop['bathrooms']) ?></td>
                                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($prop['area_sq_ft']) ? htmlspecialchars($prop['area_sq_ft']) . ' sqft' : '-' ?></td>
                                            <td class="d-none d-md-table-cell"><span class="badge <?= $prop['status'] == 'published' ? 'bg-success' : ($prop['status'] == 'sold' ? 'bg-danger' : 'bg-warning text-dark') ?>"><?= ucfirst($prop['status']) ?></span></td>
                                            <td>
                                                <a href="edit_property.php?id=<?= htmlspecialchars($prop['id']) ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil-square"></i></a>
                                                <a href="delete_property.php?id=<?= htmlspecialchars($prop['id']) ?>" onclick="return confirm('Are you sure you want to delete this property?')" class="btn btn-sm btn-outline-danger me-1"><i class="bi bi-trash"></i></a>
                                                <a href="../property-details.php?id=<?= htmlspecialchars($prop['id']) ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
            const propertiesTableBody = document.getElementById('propertiesTableBody');
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            const toggleIcon = document.getElementById('toggleIcon');
            
            const filterTable = () => {
                const search = searchInput.value.toLowerCase();
                const status = statusFilter.value;
                const type = typeFilter.value;
                const rows = propertiesTableBody.querySelectorAll('tr');

                rows.forEach(row => {
                    const title = row.cells[2].textContent.toLowerCase();
                    const city = row.cells[3].textContent.toLowerCase();
                    const rowStatus = row.cells[9].textContent.toLowerCase();
                    const rowType = row.cells[5].textContent.toLowerCase();

                    const matchesSearch = search === '' || title.includes(search) || city.includes(search);
                    const matchesStatus = status === '' || rowStatus === status;
                    const matchesType = type === '' || rowType === type;

                    if (matchesSearch && matchesStatus && matchesType) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            };

            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);
            typeFilter.addEventListener('change', filterTable);
            
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                if (sidebar.classList.contains('show')) {
                    toggleIcon.classList.remove('bi-list');
                    toggleIcon.classList.add('bi-x-lg');
                } else {
                    toggleIcon.classList.remove('bi-x-lg');
                    toggleIcon.classList.add('bi-list');
                }
            });

            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    if (sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                        toggleIcon.classList.remove('bi-x-lg');
                        toggleIcon.classList.add('bi-list');
                    }
                }
            });
        });
    </script>

    </body>
    </html>