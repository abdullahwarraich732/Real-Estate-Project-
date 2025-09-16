<?php
session_start();
require_once "../config/db.php";

// Fetch admin's name, or default to 'Admin'
$admin_name = 'Admin';
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
    $stmt = $pdo->prepare("SELECT full_name FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    if ($admin) {
        $admin_name = htmlspecialchars($admin['full_name']);
    }
} else {
    header("Location: login.php");
    exit;
}

// Handle image deletion via AJAX call within the same file
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_image') {
    $image_id = intval($_POST['image_id']);
    try {
        $stmt = $pdo->prepare("SELECT filename FROM property_images WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            $file_path = "upload/" . $image['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $stmt = $pdo->prepare("DELETE FROM property_images WHERE id = ?");
            $stmt->execute([$image_id]);
            echo json_encode(['success' => 'Image deleted successfully.']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Image not found.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if (!isset($_GET['id'])) {
    die("Property ID missing.");
}

$property_id = intval($_GET['id']);
$success = "";
$error = "";

// Fetch property details, including new address fields
$stmt = $pdo->prepare("
    SELECT p.*, l.city, l.state, l.country, l.address_line1, l.address_line2 
    FROM properties p
    JOIN locations l ON p.location_id = l.id
    WHERE p.id = ?
");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    die("Property not found.");
}

// Handle property update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $title = $_POST['title'];
        $short_description = $_POST['short_description'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $status = $_POST['status'];
        $property_type = $_POST['property_type'];
        $bedrooms = $_POST['bedrooms'] ?: null;
        $bathrooms = $_POST['bathrooms'] ?: null;
        $area = $_POST['area'] ?: null;
        
        // New address fields
        $city = $_POST['city'];
        $state = $_POST['state'];
        $country = $_POST['country'];
        $address_line1 = $_POST['address_line1'];
        $address_line2 = $_POST['address_line2'];

        // Update location with new address fields
        $stmt = $pdo->prepare("
            UPDATE locations
            SET city = ?, state = ?, country = ?, address_line1 = ?, address_line2 = ?
            WHERE id = ?
        ");
        $stmt->execute([$city, $state, $country, $address_line1, $address_line2, $property['location_id']]);

        // Update property
        $stmt = $pdo->prepare("
            UPDATE properties 
            SET title=?, short_description=?, description=?, price=?, status=?, property_type=?, 
                bedrooms=?, bathrooms=?, area_sq_ft=? 
            WHERE id=?
        ");
        $stmt->execute([
            $title, $short_description, $description, $price, $status, $property_type,
            $bedrooms, $bathrooms, $area, $property_id
        ]);

        // Handle new images
        if (!empty($_FILES['new_images']['name'][0])) {
            $upload_dir = "upload/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $max_size = 5 * 1024 * 1024; // 5 MB

            foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = basename($_FILES['new_images']['name'][$key]);
                $file_size = $_FILES['new_images']['size'][$key];
                
                if ($file_size > $max_size) {
                    $error = "Error: File " . $file_name . " is larger than 5MB.";
                    continue;
                }

                $target_file = $upload_dir . uniqid() . "_" . $file_name;
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = "Error: Only JPG, JPEG, PNG, & GIF files are allowed.";
                    continue;
                }

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $is_primary = ($key === 0) ? 1 : 0;
                    $stmt = $pdo->prepare("INSERT INTO property_images (property_id, filename, is_primary) VALUES (?, ?, ?)");
                    $stmt->execute([$property_id, basename($target_file), $is_primary]);
                }
            }
        }

        $success = "Property updated successfully!";
        header("Location: dashboard.php?id=$property_id&success=1");
        exit;

    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = "Property updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property | Property Gate</title>
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
            margin: 0;
            padding: 0;
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
            width: 100%;
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
            width: 100%;
        }
        
        .main-content {
            padding: 20px;
            flex-grow: 1;
            max-width: 1200px; 
            margin: 0 auto;
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
            color: white; 
            display: none;
        }
        
        @media (max-width: 768px) {
            .toggle-btn {
                display: block;
            }
        }

        /* Form Styling */
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .form-control, .form-select {
            border-radius: 8px;
        }
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
        }

        /* Image Gallery Styling */
        .add-image-card {
            border: 2px dashed #ccc;
            border-radius: 10px;
            width: 100%;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        .add-image-card:hover {
            border-color: var(--primary-color);
        }
        .image-preview-container {
            transition: transform 0.2s;
            cursor: pointer;
            overflow: hidden;
            border-radius: 8px;
            height: 150px; /* Fixed height for consistent grid */
            position: relative;
        }
        .image-preview-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .image-preview-container:hover {
            transform: translateY(-5px);
        }
        .btn-delete-image {
            display: none;
            opacity: 0.85;
            background-color: rgba(220, 53, 69, 0.8);
            border: none;
            color: white;
            font-size: 1rem;
            width: 32px;
            height: 32px;
            line-height: 1;
            padding: 0;
            z-index: 10;
        }
        .image-preview-container:hover .btn-delete-image {
            display: flex;
            justify-content: center;
            align-items: center;
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_property.php">
                        <i class="bi bi-plus-square"></i> Add New Property
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="bi bi-house"></i> View Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-person"></i> Admins
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link logout" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="main-content-wrapper" id="main-content-wrapper">
        <div class="top-header-bar">
            <h1 class="page-title">Edit Property</h1>
            <div class="d-flex align-items-center">
                <span class="admin-name"><?= $admin_name ?></span>
                <button class="toggle-btn ms-3" type="button">
                    <i id="toggleIcon" class="bi bi-list"></i>
                </button>
            </div>
        </div>

        <div class="container-fluid">
            <div class="main-content">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Property Details</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($property['title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="short_description" class="form-label">Short Description</label>
                                <textarea class="form-control" id="short_description" name="short_description" rows="3" required><?= htmlspecialchars($property['short_description']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($property['description']) ?></textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price (PKR)</label>
                                    <input type="number" class="form-control" id="price" name="price" value="<?= $property['price'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="property_type" class="form-label">Property Type</label>
                                    <select class="form-select" id="property_type" name="property_type">
                                        <option value="sale" <?= $property['property_type'] === 'sale' ? 'selected' : '' ?>>For Sale</option>
                                        <option value="rent" <?= $property['property_type'] === 'rent' ? 'selected' : '' ?>>For Rent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4 mb-3">
                                    <label for="bedrooms" class="form-label">Bedrooms</label>
                                    <input type="number" class="form-control" id="bedrooms" name="bedrooms" value="<?= $property['bedrooms'] ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="bathrooms" class="form-label">Bathrooms</label>
                                    <input type="number" class="form-control" id="bathrooms" name="bathrooms" value="<?= $property['bathrooms'] ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="area" class="form-label">Area (sqft)</label>
                                    <input type="number" class="form-control" id="area" name="area" value="<?= $property['area_sq_ft'] ?>">
                                </div>
                            </div>

                            <hr>
                            <h5 class="card-title fw-bold">Location Details</h5>
                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label for="address_line1" class="form-label">Address Line 1</label>
                                    <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?= htmlspecialchars($property['address_line1']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="address_line2" class="form-label">Address Line 2</label>
                                    <input type="text" class="form-control" id="address_line2" name="address_line2" value="<?= htmlspecialchars($property['address_line2']) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($property['city']) ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state" name="state" value="<?= htmlspecialchars($property['state']) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($property['country']) ?>" required>
                                </div>
                            </div>

                            <hr>
                            <h5 class="card-title fw-bold">Images and Status</h5>
                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="published" <?= $property['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                        <option value="draft" <?= $property['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="sold" <?= $property['status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
                                        <option value="archived" <?= $property['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Images</label>
                                    <div class="row g-2" id="image-gallery">
                                        <?php
                                        // Fetch existing images for the property
                                        $stmt_images = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
                                        $stmt_images->execute([$property_id]);
                                        $images = $stmt_images->fetchAll();

                                        if ($images) {
                                            foreach ($images as $image) {
                                                $image_path = 'upload/' . htmlspecialchars($image['filename']);
                                                echo '
                                                <div class="col-6 col-md-4 col-lg-3 position-relative image-preview-container" data-image-id="' . $image['id'] . '">
                                                    <img src="' . $image_path . '" class="img-fluid rounded-3" alt="Property Image">
                                                    <button type="button" class="btn-delete-image position-absolute top-0 end-0 m-1 btn rounded-circle p-0">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </div>';
                                            }
                                        }
                                        ?>
                                        <div class="col-6 col-md-4 col-lg-3 d-flex justify-content-center align-items-center">
                                            <label for="new_images" class="add-image-card">
                                                <i class="bi bi-plus-lg fs-1 text-muted"></i>
                                                <p class="text-muted mb-0">Add New</p>
                                            </label>
                                            <input class="d-none" type="file" id="new_images" name="new_images[]" multiple accept="image/*">
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Max file size: 5MB. Uploading new images will be added to the property's gallery.</small>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="submit" class="btn btn-primary">Update Property</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.querySelector('.toggle-btn');
        const toggleIcon = document.getElementById('toggleIcon');
        
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

        // Image Gallery Functionality
        const imageGallery = document.getElementById('image-gallery');
        const newImagesInput = document.getElementById('new_images');

        imageGallery.addEventListener('click', (e) => {
            const deleteButton = e.target.closest('.btn-delete-image');
            if (deleteButton) {
                const container = deleteButton.closest('.image-preview-container');
                const imageId = container.dataset.imageId;

                if (confirm('Are you sure you want to delete this image?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete_image');
                    formData.append('image_id', imageId);

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            container.remove();
                            console.log('Image deleted successfully.');
                        } else {
                            alert(data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the image.');
                    });
                }
            }
        });

        newImagesInput.addEventListener('change', () => {
            const files = newImagesInput.files;
            if (files) {
                for (const file of files) {
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File ' + file.name + ' is larger than 5MB. It will not be uploaded.');
                        continue;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const newImageHtml = `
                            <div class="col-6 col-md-4 col-lg-3 position-relative image-preview-container new-image-preview">
                                <img src="${e.target.result}" class="img-fluid rounded-3" alt="New Image Preview">
                                <button type="button" class="btn-delete-image position-absolute top-0 end-0 m-1 btn rounded-circle p-0">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        `;
                        const addImageCard = imageGallery.querySelector('.add-image-card').parentNode;
                        addImageCard.insertAdjacentHTML('beforebegin', newImageHtml);
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        // Handle deletion of new image previews
        imageGallery.addEventListener('click', (e) => {
            if (e.target.closest('.new-image-preview .btn-delete-image')) {
                const container = e.target.closest('.new-image-preview');
                container.remove();
            }
        });
    });
</script>

</body>
</html>