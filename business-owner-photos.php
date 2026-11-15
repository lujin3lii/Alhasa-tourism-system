<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['business_owner_id'])) {
    header('Location: business-owner-login.php');
    exit;
}

$ownerId = (int) $_SESSION['business_owner_id'];
$ownerName = $_SESSION['business_owner_name'] ?? 'My Account';

$uploadMessage = '';
$messageType = 'success';

/* CSRF protection */
if (empty($_SESSION['photo_upload_token'])) {
    $_SESSION['photo_upload_token'] = bin2hex(random_bytes(32));
}

/* Create the photo table if it does not exist */
$conn->query("
    CREATE TABLE IF NOT EXISTS business_owner_photos (
        photo_id INT AUTO_INCREMENT PRIMARY KEY,
        business_owner_id INT NOT NULL,
        photo_path VARCHAR(500) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

/* Handle photo uploads */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photos'])) {

    if (
        empty($_POST['photo_upload_token']) ||
        !hash_equals($_SESSION['photo_upload_token'], $_POST['photo_upload_token'])
    ) {
        $uploadMessage = 'Invalid request. Please refresh the page and try again.';
        $messageType = 'error';

    } elseif (
        !isset($_FILES['business_photos']) ||
        empty($_FILES['business_photos']['name'][0])
    ) {
        $uploadMessage = 'Please select at least one photo.';
        $messageType = 'error';

    } else {

        $uploadDirectory = __DIR__ . '/uploads/business-photos/';

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        $maxFileSize = 5 * 1024 * 1024;
        $maxFiles = 10;

        $files = $_FILES['business_photos'];
        $totalFiles = count($files['name']);
        $uploadedCount = 0;
        $errors = [];

        if ($totalFiles > $maxFiles) {
            $errors[] = 'You can upload a maximum of 10 photos at a time.';
        } else {

            $checkImage = finfo_open(FILEINFO_MIME_TYPE);

            for ($i = 0; $i < $totalFiles; $i++) {

                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = 'One of the photos could not be uploaded.';
                    continue;
                }

                if ($files['size'][$i] > $maxFileSize) {
                    $errors[] = 'Each photo must be 5 MB or smaller.';
                    continue;
                }

                $mimeType = finfo_file($checkImage, $files['tmp_name'][$i]);

                if (!isset($allowedTypes[$mimeType])) {
                    $errors[] = 'Only JPG, PNG, and WEBP images are allowed.';
                    continue;
                }

                if (getimagesize($files['tmp_name'][$i]) === false) {
                    $errors[] = 'An invalid image file was selected.';
                    continue;
                }

                $extension = $allowedTypes[$mimeType];
                $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

                $destination = $uploadDirectory . $fileName;
                $relativePath = 'uploads/business-photos/' . $fileName;

                if (move_uploaded_file($files['tmp_name'][$i], $destination)) {

                    $stmt = $conn->prepare("
                        INSERT INTO business_owner_photos
                        (business_owner_id, photo_path)
                        VALUES (?, ?)
                    ");

                    if ($stmt) {
                        $stmt->bind_param('is', $ownerId, $relativePath);

                        if ($stmt->execute()) {
                            $uploadedCount++;
                        } else {
                            unlink($destination);
                            $errors[] = 'A photo could not be saved to the database.';
                        }

                        $stmt->close();

                    } else {
                        unlink($destination);
                        $errors[] = 'Could not save photo information.';
                    }

                } else {
                    $errors[] = 'Could not save an uploaded photo.';
                }
            }

            finfo_close($checkImage);
        }

        if ($uploadedCount > 0) {
            $uploadMessage = $uploadedCount . ' photo(s) uploaded successfully.';
            $messageType = 'success';
        }

        if (!empty($errors)) {
            $uploadMessage .= ($uploadMessage ? ' ' : '') . implode(' ', array_unique($errors));
            $messageType = $uploadedCount > 0 ? 'success' : 'error';
        }
    }
}

/* Fetch this owner's saved photos */
$photos = [];

$stmt = $conn->prepare("
    SELECT photo_id, photo_path, created_at
    FROM business_owner_photos
    WHERE business_owner_id = ?
    ORDER BY created_at DESC
");

if ($stmt) {
    $stmt->bind_param('i', $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $photos[] = $row;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Photos | Al-Ahsa Vision Hub</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .business-page {
            min-height: 100vh;
            padding: 120px 24px 50px;
            color: #263b32;
            background: linear-gradient(135deg, #ffffff, #f5f8f4, #eaf2eb);
            font-family: inherit;
        }
        .business-page * { box-sizing: border-box; }
        .business-container { width: 100%; max-width: 1100px; margin: 0 auto; }
        .business-heading { margin-bottom: 28px; }
        .business-label {
            display: inline-block; margin-bottom: 12px; padding: 7px 13px;
            border-radius: 20px; background: #e3f0e6; color: #075b45;
            font-size: 12px; font-weight: 700; letter-spacing: .5px;
        }
        .business-heading h1 { margin: 0 0 10px; color: #075b45; font-size: 30px; line-height: 1.4; }
        .business-heading p { margin: 0; color: #68796e; font-size: 15px; line-height: 1.8; }
        .business-back-link { display: inline-block; margin-bottom: 20px; color: #075b45; font-size: 14px; font-weight: 600; text-decoration: none; }
        .business-back-link:hover { color: #08775a; text-decoration: underline; }
        .business-card { overflow: hidden; margin-bottom: 25px; border: 1px solid #dfe8e1; border-radius: 20px; background: #fff; box-shadow: 0 16px 42px rgba(32,72,51,.08); }
        .business-card-header { display: flex; align-items: center; gap: 18px; padding: 28px; background: linear-gradient(135deg,#eaf3ec,#f7faf7); border-bottom: 1px solid #dfe8e1; }
        .business-icon { display: flex; flex-shrink: 0; align-items: center; justify-content: center; width: 70px; height: 70px; border-radius: 18px; background: #fff; color: #075b45; font-size: 30px; }
        .business-title h2 { margin: 0 0 7px; color: #075b45; font-size: 23px; }
        .business-title p { margin: 0; color: #68796e; font-size: 14px; line-height: 1.7; }
        .business-card-body { padding: 28px; }
        .details-heading { margin: 0 0 20px; color: #263b32; font-size: 19px; }
        .business-form-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 18px; }
        .business-field { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
        .business-field.full-width { grid-column: 1 / -1; }
        .business-field label { color: #68796e; font-size: 13px; font-weight: 600; }
        .business-field input,.business-field select,.business-field textarea {
            width: 100%; min-height: 46px; padding: 12px 14px; border: 1px solid #dfe8e1;
            border-radius: 10px; background: #fbfdfb; color: #263b32; font: inherit; font-size: 14px;
        }
        .business-field textarea { min-height: 120px; resize: vertical; }
        .business-field input:focus,.business-field select:focus,.business-field textarea:focus { outline: 2px solid #b9d9c2; border-color: #08775a; }
        .business-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 26px; }
        .business-button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 47px; padding: 12px 20px; border: 1px solid #075b45; border-radius: 10px; background: linear-gradient(135deg,#075b45,#08775a); color: #fff; font-family: inherit; font-size: 14px; font-weight: 700; text-decoration: none; transition: .2s; cursor: pointer; }
        .business-button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(7,91,69,.17); }
        .business-button.secondary { border-color: #d7e1d9; background: #fff; color: #075b45; }
        .business-button.secondary:hover { background: #f1f7f2; }
        .business-note { display: flex; align-items: flex-start; gap: 13px; margin-top: 25px; padding: 20px; border: 1px solid #dfe8e1; border-radius: 14px; background: rgba(255,255,255,.85); }
        .business-note-icon { color: #08775a; font-size: 21px; }
        .business-note h3 { margin: 0 0 7px; color: #075b45; font-size: 15px; }
        .business-note p { margin: 0; color: #68796e; font-size: 13px; line-height: 1.8; }
        .photo-upload-box { padding: 25px; border: 1px dashed #b9cdbd; border-radius: 14px; background: #fbfdfb; }
        .photo-upload-box p { color: #68796e; font-size: 14px; line-height: 1.8; }
        .photo-preview-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 15px; }
        .photo-preview { min-height: 130px; display: flex; align-items: center; justify-content: center; padding: 15px; border: 1px solid #e1e9e2; border-radius: 13px; background: #fbfdfb; color: #68796e; text-align: center; overflow-wrap: anywhere; }
        .photo-preview img { display: block; max-width: 100%; max-height: 200px; margin: 0 auto 8px; border-radius: 9px; object-fit: contain; }
        .upload-message { margin-bottom: 20px; padding: 14px 16px; border-radius: 10px; font-size: 14px; line-height: 1.7; }
        .upload-message.success { background: #eaf5ec; color: #075b45; border: 1px solid #c9e2ce; }
        .upload-message.error { background: #fff0f0; color: #a12828; border: 1px solid #efcccc; }
        @media (max-width:600px) {
            .business-page { padding: 120px 15px 35px; }
            .business-heading h1 { font-size: 26px; }
            .business-card-header { padding: 22px 18px; }
            .business-icon { width: 56px; height: 56px; font-size: 25px; }
            .business-title h2 { font-size: 19px; }
            .business-card-body { padding: 20px 16px; }
            .business-form-grid,.photo-preview-grid { grid-template-columns: 1fr; gap: 13px; }
            .business-field.full-width { grid-column: auto; }
            .business-actions { flex-direction: column; }
            .business-button { width: 100%; }
        }
    </style>
</head>
<body>

    <!-- Menu Button -->
    <button class="menu-button"
            onclick="toggleMenu()"
            aria-label="Open menu">
        ☰
    </button>

    <!-- Sidebar Background -->
    <div class="sidebar-backdrop" onclick="toggleMenu()"></div>

    <!-- Business Owner Sidebar -->
    <nav class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <a href="business-owner-index.php">
                <img src="images/logo.png"
                     alt="Al-Ahsa Vision Hub"
                     class="sidebar-logo">
            </a>

            <h2>Al-Ahsa <span>Vision Hub</span></h2>
            <p>Business Owner Portal</p>
        </div>

        <div class="sidebar-links">
            <a href="business-owner-index.php"><span>⌂</span> Home</a>
            <a href="business-owner-dashboard.php"><span>⌂</span> Dashboard</a>
            <a href="business-owner-my-business.php"><span>▣</span> My Business</a>
            <a href="business-owner-edit.php"><span>✎</span> Edit Business Information</a>
            <a href="business-owner-photos.php"><span>▧</span> Manage Photos</a>
            <a href="business-owner-account.php"><span>♙</span> My Account</a>
            <a href="index.php"><span>⌂</span> Back to Home</a>
        </div>

        <div class="sidebar-decoration">
            <span>🌴</span>
            <p>THE HEART OF AL-AHSA</p>
        </div>
    </nav>

    <!-- Top Bar -->
    <header class="home-topbar">
        <div class="home-location">
            <span class="location-dot"></span>
            DISCOVER AL-AHSA, SAUDI ARABIA
        </div>
        <div class="home-account-links">
            <a href="business-owner-account.php" class="home-signup" id="account-button">
                <?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="business-owner-index.php" class="home-small-logo">
                <img src="images/logo.png" alt="Logo">
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="business-page">
        <div class="business-container">
            <header class="business-heading">
                <span class="business-label">BUSINESS OWNER PORTAL</span>
                <h1>Manage Photos</h1>
                <p>Organize the images that help visitors discover your business in Al-Ahsa.</p>
            </header>

            <section class="business-card">
                <div class="business-card-header">
                    <div class="business-icon" aria-hidden="true">▧</div>
                    <div class="business-title">
                        <h2>Business Photo Gallery</h2>
                        <p>Select images to preview them before adding them to your gallery.</p>
                    </div>
                </div>

                <div class="business-card-body">

                    <?php if ($uploadMessage): ?>
                        <div class="upload-message <?= $messageType ?>">
                            <?= htmlspecialchars($uploadMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="details-heading">Upload Photos</h3>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="photo_upload_token"
                               value="<?= htmlspecialchars($_SESSION['photo_upload_token'], ENT_QUOTES, 'UTF-8') ?>">

                        <div class="photo-upload-box">
                            <p>Choose clear photos of your restaurant, café, shop, or other business. You can select more than one image.</p>

                            <div class="business-field">
                                <label for="business-photos">Select image files</label>
                                <input type="file"
                                       id="business-photos"
                                       name="business_photos[]"
                                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                       multiple
                                       required>
                            </div>

                            <p>Allowed formats: JPG, PNG, WEBP. Maximum size: 5 MB per photo. Maximum 10 photos per upload.</p>

                            <div class="business-actions">
                                <button type="button" class="business-button" id="preview-photos">▧ Preview Selected Photos</button>
                                <button type="submit" name="upload_photos" class="business-button">Upload Photos</button>
                            </div>
                        </div>
                    </form>

                    <h3 class="details-heading" style="margin-top:28px;">Selected Photos</h3>

                    <div class="photo-preview-grid" id="photo-preview-grid">
                        <div class="photo-preview">Your selected photo previews will appear here.</div>
                    </div>

                    <h3 class="details-heading" style="margin-top:35px;">Uploaded Photos</h3>

                    <div class="photo-preview-grid">
                        <?php if (empty($photos)): ?>
                            <div class="photo-preview">No photos have been uploaded yet.</div>
                        <?php else: ?>
                            <?php foreach ($photos as $photo): ?>
                                <div class="photo-preview">
                                    <div>
                                        <img
                                            src="<?= htmlspecialchars($photo['photo_path'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="Business photo"
                                            loading="lazy">
                                        <div>Uploaded photo</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </section>

            <section class="business-note">
                <div class="business-note-icon" aria-hidden="true">ⓘ</div>
                <div>
                    <h3>Photo Tips</h3>
                    <p>Use bright, clear images that represent your business accurately. Uploaded photos are saved to your business owner account.</p>
                </div>
            </section>

            <script>
                const photoInput = document.getElementById("business-photos");
                const previewButton = document.getElementById("preview-photos");
                const previewGrid = document.getElementById("photo-preview-grid");

                previewButton.addEventListener("click", function () {
                    previewGrid.innerHTML = "";

                    if (!photoInput.files || photoInput.files.length === 0) {
                        previewGrid.innerHTML = '<div class="photo-preview">Please select at least one image first.</div>';
                        return;
                    }

                    Array.from(photoInput.files).forEach(function (file) {
                        if (!file.type.startsWith("image/")) return;

                        const card = document.createElement("div");
                        card.className = "photo-preview";

                        const wrapper = document.createElement("div");
                        const image = document.createElement("img");
                        image.src = URL.createObjectURL(file);
                        image.alt = file.name;

                        const name = document.createElement("div");
                        name.textContent = file.name;

                        wrapper.appendChild(image);
                        wrapper.appendChild(name);
                        card.appendChild(wrapper);
                        previewGrid.appendChild(card);
                    });

                    if (previewGrid.children.length === 0) {
                        previewGrid.innerHTML = '<div class="photo-preview">No valid image files were selected.</div>';
                    }
                });
            </script>
        </div>
    </main>

    <!-- Al-Ahsa Vision Hub Footer -->
    <footer class="site-footer">
        <div class="site-footer-main">
            <div class="site-footer-brand">
                <h3>Al-Ahsa Vision Hub</h3>
                <p>Discover the beauty, heritage, and experiences of Al-Ahsa.</p>
            </div>
            <div class="site-footer-links">
                <h4>Explore Al-Ahsa</h4>
                <p>Discover places, restaurants, cafés, and seasonal events.</p>
            </div>
            <div class="site-footer-contact">
                <h4>Our Vision</h4>
                <p>Making your journey through Al-Ahsa memorable.</p>
            </div>
            <div class="site-footer-contact">
                <h4>Contact Us</h4>
                <p>05123546798</p>
                <p><a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">◎ Instagram</a></p>
                <p><a href="https://x.com/" target="_blank" rel="noopener noreferrer">𝕏 X (Twitter)</a></p>
            </div>
        </div>
        <div class="site-footer-bottom">
            <p>©️ 2026 Al-Ahsa Vision Hub. All rights reserved.</p>
            <p>Explore Al-Ahsa, experience its beauty.</p>
        </div>
    </footer>

    <!-- AI Assistant Button -->
    <button
        class="chat-button"
        onclick="openAI()"
        aria-label="Open AI Assistant"
        title="AI Assistant">
    </button>

    <!-- Main JavaScript -->
<script src="script.js?v=2"></script>

</body>
</html>