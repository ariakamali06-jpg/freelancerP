<?php
/**
 * آپلود نمونه‌کار جدید (پنل هنرمند)
 * POST: title, technique, size, price, description, image (file)
 */
require_once __DIR__ . '/config.php';

$user = current_user();
if (!$user || $user['role'] !== 'artist') {
    flash('فقط هنرمندان می‌توانند اثر آپلود کنند.', 'error');
    header('Location: login.php');
    exit;
}

$p = db();

// پیدا کردن رکورد هنرمند این کاربر
$artistId = null;
if ($p) {
    try {
        $s = $p->prepare('SELECT id FROM artists WHERE user_id = ? LIMIT 1');
        $s->execute([(int)$user['id']]);
        $artistId = $s->fetchColumn();
    } catch (Throwable $e) {}
}

if (!$artistId) {
    flash('پروفایل هنرمند شما یافت نشد؛ با پشتیبانی تماس بگیر.', 'error');
    header('Location: artist-dashboard.php');
    exit;
}

$title      = trim($_POST['title'] ?? '');
$technique  = trim($_POST['technique'] ?? '');
$size       = trim($_POST['size'] ?? '');
$price      = (int)($_POST['price'] ?? 0);
$description= trim($_POST['description'] ?? '');

if (mb_strlen($title) < 2 || $price <= 0) {
    flash('عنوان و قیمت معتبر وارد کن.', 'error');
    header('Location: artist-dashboard.php');
    exit;
}

$imagePath = null;
if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'] ?? '')) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($_FILES['image']['tmp_name']);
    if (isset($allowed[$mime]) && $_FILES['image']['size'] <= 8 * 1024 * 1024) {
        $dir = __DIR__ . '/uploads/artworks';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $name = 'artist_' . $artistId . '_' . time() . '.' . $allowed[$mime];
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $name)) {
            $imagePath = 'uploads/artworks/' . $name;
        }
    } else {
        flash('فرمت یا حجم عکس مجاز نیست (فقط JPG/PNG/WebP تا ۸ مگ).', 'error');
        header('Location: artist-dashboard.php');
        exit;
    }
}

if (!$imagePath) {
    $imagePath = 'assets/images/work' . (random_int(1, 6)) . '.jpg'; // تصویر پیش‌فرض
}

try {
    $s = $p->prepare(
        'INSERT INTO artworks (artist_id, title, description, technique, size, price, image, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $s->execute([
        (int)$artistId, $title, $description ?: null,
        $technique ?: null, $size ?: null, $price, $imagePath, 'available'
    ]);
    flash('اثر جدیدت به گالری اضافه شد! 🎨');
} catch (Throwable $e) {
    flash('آپلود ناموفق بود: ' . $e->getMessage(), 'error');
}

header('Location: artist-dashboard.php');
exit;
