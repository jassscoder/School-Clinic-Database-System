<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: auth/login.php");
    exit();
}

$message = '';
$error = '';

// Handle logo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $file = $_FILES['logo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowed_types)) {
        $error = "Invalid file type. Allowed: JPG, PNG, GIF, WebP";
    } elseif ($file['size'] > $max_size) {
        $error = "File size exceeds 5MB limit";
    } elseif ($file['size'] === 0) {
        $error = "File is empty";
    } else {
        $upload_dir = 'uploads/logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'schord_logo_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $old_logo_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'logo_path'");
            if ($old_logo_result && $old_logo_row = $old_logo_result->fetch_assoc()) {
                $old_logo = $old_logo_row['setting_value'];
                if ($old_logo !== 'assets/default-logo.png' && file_exists($old_logo)) {
                    unlink($old_logo);
                }
            }
            
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_path', '$upload_path') 
                         ON DUPLICATE KEY UPDATE setting_value = '$upload_path'");
            
            $message = "Logo uploaded successfully!";
        } else {
            $error = "Failed to upload file. Please check permissions.";
        }
    }
}

// Handle background upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bg_upload'])) {
    $file = $_FILES['bg_upload'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 10 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowed_types)) {
        $error = "Invalid file type. Allowed: JPG, PNG, WebP";
    } elseif ($file['size'] > $max_size) {
        $error = "File size exceeds 10MB limit";
    } elseif ($file['size'] === 0) {
        $error = "File is empty";
    } else {
        $upload_dir = 'uploads/backgrounds/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'register_bg_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $old_bg_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'register_bg_path'");
            if ($old_bg_result && $old_bg_row = $old_bg_result->fetch_assoc()) {
                $old_bg = $old_bg_row['setting_value'];
                if ($old_bg !== 'none' && file_exists($old_bg)) {
                    unlink($old_bg);
                }
            }
            
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('register_bg_path', '$upload_path') 
                         ON DUPLICATE KEY UPDATE setting_value = '$upload_path'");
            
            $message = "Background uploaded successfully!";
        } else {
            $error = "Failed to upload file. Please check permissions.";
        }
    }
}

// Handle login background upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['login_bg_upload'])) {
    $file = $_FILES['login_bg_upload'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 10 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowed_types)) {
        $error = "Invalid file type. Allowed: JPG, PNG, WebP";
    } elseif ($file['size'] > $max_size) {
        $error = "File size exceeds 10MB limit";
    } elseif ($file['size'] === 0) {
        $error = "File is empty";
    } else {
        $upload_dir = 'uploads/backgrounds/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'login_bg_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $old_bg_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'login_bg_path'");
            if ($old_bg_result && $old_bg_row = $old_bg_result->fetch_assoc()) {
                $old_bg = $old_bg_row['setting_value'];
                if ($old_bg !== 'none' && file_exists($old_bg)) {
                    unlink($old_bg);
                }
            }
            
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('login_bg_path', '$upload_path') 
                         ON DUPLICATE KEY UPDATE setting_value = '$upload_path'");
            
            $message = "Login background uploaded successfully!";
        } else {
            $error = "Failed to upload file. Please check permissions.";
        }
    }
}

// Get current logo
$current_logo = 'assets/default-logo.png';
$logo_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'logo_path'");
if ($logo_result && $logo_row = $logo_result->fetch_assoc()) {
    $current_logo = $logo_row['setting_value'];
}

// Get current register background
$current_bg = 'none';
$bg_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'register_bg_path'");
if ($bg_result && $bg_row = $bg_result->fetch_assoc()) {
    $current_bg = $bg_row['setting_value'];
}

// Get current login background  
$current_login_bg = 'none';
$login_bg_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'login_bg_path'");
if ($login_bg_result && $login_bg_row = $login_bg_result->fetch_assoc()) {
    $current_login_bg = $login_bg_row['setting_value'];
}

include '../includes/header.php';
?>

<div class="container">
    <div class="settings-panel">
        <h1>System Settings</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="settings-section">
            <h2>SCHoRD Logo Settings</h2>
            
            <div class="logo-preview">
                <h3>Current Logo:</h3>
                <?php if (file_exists($current_logo)): ?>
                    <img src="<?php echo htmlspecialchars($current_logo); ?>" alt="Current Logo" style="max-width: 200px; max-height: 200px; border: 2px solid #ddd; padding: 10px; border-radius: 8px;">
                <?php else: ?>
                    <p><em>No logo uploaded. Using default.</em></p>
                <?php endif; ?>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="logo-upload-form">
                <div class="form-group">
                    <label for="logo">Upload New Logo:</label>
                    <input type="file" id="logo" name="logo" accept="image/*" required>
                    <small>Supported formats: JPG, PNG, GIF, WebP | Max size: 5MB</small>
                </div>
                <button type="submit" class="btn btn-primary">Upload Logo</button>
                
                <?php if ($current_logo !== 'assets/default-logo.png'): ?>
                    <a href="?reset_logo=1" class="btn btn-secondary" onclick="return confirm('Reset logo to default?')">Reset to Default</a>
                <?php endif; ?>
            </form>
            
            <div class="logo-info">
                <h4>Logo Guidelines:</h4>
                <ul>
                    <li>Recommended size: 200x200px to 400x400px</li>
                    <li>Aspect ratio: Square (1:1) works best</li>
                    <li>Background: Transparent or solid color</li>
                    <li>Format: PNG recommended for transparency</li>
                </ul>
            </div>
        </div>

        <div class="settings-section">
            <h2>Register Page Background Image</h2>
            
            <div class="background-preview">
                <h3>Current Background:</h3>
                <?php if ($current_bg !== 'none' && file_exists($current_bg)): ?>
                    <img src="<?php echo htmlspecialchars($current_bg); ?>" alt="Current Background" style="max-width: 300px; max-height: 200px; border: 2px solid #ddd; padding: 10px; border-radius: 8px; object-fit: cover;">
                <?php else: ?>
                    <p><em>No background uploaded. Using default gradient.</em></p>
                <?php endif; ?>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="bg-upload-form">
                <div class="form-group">
                    <label for="bg_upload">Upload Register Background Image:</label>
                    <input type="file" id="bg_upload" name="bg_upload" accept="image/*" required>
                    <small>Supported formats: JPG, PNG, WebP | Max size: 10MB | Recommended: 1920x1080px or larger</small>
                </div>
                <button type="submit" name="upload_bg" class="btn btn-primary">Upload Background</button>
                
                <?php if ($current_bg !== 'none'): ?>
                    <a href="?reset_bg=1" class="btn btn-secondary" onclick="return confirm('Reset background to default?')">Reset to Default</a>
                <?php endif; ?>
            </form>
            
            <div class="bg-info">
                <h4>Background Guidelines:</h4>
                <ul>
                    <li>Recommended size: 1920x1080px or larger</li>
                    <li>High quality images work best</li>
                    <li>Healthcare/professional themed images recommended</li>
                    <li>Ensure good contrast for text readability</li>
                </ul>
            </div>
        </div>

        <div class="settings-section">
            <h2>Login Page Background Image</h2>
            
            <div class="background-preview">
                <h3>Current Background:</h3>
                <?php if ($current_login_bg !== 'none' && file_exists($current_login_bg)): ?>
                    <img src="<?php echo htmlspecialchars($current_login_bg); ?>" alt="Current Login Background" style="max-width: 300px; max-height: 200px; border: 2px solid #ddd; padding: 10px; border-radius: 8px; object-fit: cover;">
                <?php else: ?>
                    <p><em>No background uploaded. Using default red gradient.</em></p>
                <?php endif; ?>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="bg-upload-form">
                <div class="form-group">
                    <label for="login_bg_upload">Upload Login Background Image:</label>
                    <input type="file" id="login_bg_upload" name="login_bg_upload" accept="image/*" required>
                    <small>Supported formats: JPG, PNG, WebP | Max size: 10MB | Recommended: 1920x1080px or larger</small>
                </div>
                <button type="submit" name="upload_login_bg" class="btn btn-primary">Upload Login Background</button>
                
                <?php if ($current_login_bg !== 'none'): ?>
                    <a href="?reset_login_bg=1" class="btn btn-secondary" onclick="return confirm('Reset background to default?')">Reset to Default</a>
                <?php endif; ?>
            </form>
            
            <div class="bg-info">
                <h4>Background Guidelines:</h4>
                <ul>
                    <li>Recommended size: 1920x1080px or larger</li>
                    <li>High quality, professional images work best</li>
                    <li>Healthcare/medical/hospital themed images recommended</li>
                    <li>Ensure text on login form remains readable (contrast)</li>
                    <li>The image will have a red gradient overlay for better text visibility</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 2rem;
}

.settings-panel {
    background: white;
    border-radius: 12px;
    padding: 2.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.settings-panel h1 {
    color: #2c3e50;
    margin-bottom: 2rem;
    border-bottom: 3px solid #e74c3c;
    padding-bottom: 1rem;
    font-size: 2rem;
}

.settings-section {
    margin: 3rem 0;
}

.settings-section h2 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    font-size: 1.4rem;
    border-left: 4px solid #e74c3c;
    padding-left: 1rem;
}

.logo-preview, .background-preview {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    text-align: center;
    border: 2px dashed #ddd;
}

.logo-preview h3, .background-preview h3 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.logo-upload-form, .bg-upload-form {
    background: #fff5f5;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    border-left: 4px solid #e74c3c;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #2c3e50;
    font-weight: 600;
}

.form-group input {
    display: block;
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
}

.form-group small {
    display: block;
    margin-top: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    margin-right: 0.5rem;
    display: inline-block;
    text-decoration: none;
    transition: all 0.3s;
    margin-bottom: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
}

.logo-info, .bg-info {
    background: #ffe5e5;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #e74c3c;
}

.logo-info h4, .bg-info h4 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.logo-info ul, .bg-info ul {
    list-style-position: inside;
    color: #555;
    line-height: 2;
}

.alert {
    padding: 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .container {
        padding: 0 1rem;
    }

    .settings-panel {
        padding: 1.5rem;
    }

    .btn {
        display: block;
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<?php
include 'includes/footer.php';
?>
