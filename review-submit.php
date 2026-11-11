<?php
require_once __DIR__ . '/config.php';

/* =========================
   ثبت نظر برای هنرمند
   فقط پس از سفارش «تکمیل‌شده» همان هنرمند
========================= */

$user = current_user();
if (!$user) {
    flash('برای ثبت نظر، ابتدا وارد شو.', 'error');
    header('Location: login.php');
    exit;
}

$p = db();
$artistId = (int)($_POST['artist_id'] ?? $_GET['artist_id'] ?? 0);
$rating   = (int)($_POST['rating'] ?? 0);
$comment  = trim($_POST['comment'] ?? '');

// اعتبار امتیاز
if ($rating < 1 || $rating > 5) $rating = 5;

// هنرمند باید موجود باشد
$artist = null;
if ($p && $artistId > 0) {
    try {
        $s = $p->prepare("SELECT id, name FROM artists WHERE id = ? LIMIT 1");
        $s->execute([$artistId]);
        $artist = $s->fetch();
    } catch (Throwable $e) {}
}

if (!$artist) {
    flash('هنرمند یافت نشد.', 'error');
    header('Location: artists.php');
    exit;
}

// سهم سفارش تکمیل‌شده کاربر از این هنرمند
$hasCompleted = false;
if ($p) {
    try {
        $s = $p->prepare(
            "SELECT COUNT(*) FROM orders
             WHERE user_id = ? AND artist_id = ? AND status = 'completed'"
        );
        $s->execute([(int)$user['id'], $artistId]);
        $hasCompleted = (bool)$s->fetchColumn();
    } catch (Throwable $e) {}
}

if (!$hasCompleted) {
    flash('ثبت نظر فقط بعد از یک سفارش تکمیل‌شده از این هنرمند ممکن است.', 'error');
    header('Location: artist.php?id=' . $artistId);
    exit;
}

// جلوگیری از نظر تکراری
try {
    $s = $p->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ? AND artist_id = ?');
    $s->execute([(int)$user['id'], $artistId]);
    if ((int)$s->fetchColumn() > 0) {
        flash('شما قبلاً برای این هنرمند نظر ثبت کرده‌اید. 🙏', 'error');
        header('Location: artist.php?id=' . $artistId);
        exit;
    }
} catch (Throwable $e) {}

// درج نظر
try {
    $s = $p->prepare(
        'INSERT INTO reviews (user_id, artist_id, rating, comment, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $s->execute([(int)$user['id'], $artistId, $rating, $comment ?: null]);

    // به‌روزرسانی امتیاز و تعداد نظرات هنرمند
    $s2 = $p->prepare(
        "SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE artist_id = ?"
    );
    $s2->execute([$artistId]);
    $agg = $s2->fetch();

    $u = $p->prepare('UPDATE artists SET rating = ?, reviews = ? WHERE id = ?');
    $u->execute([round((float)$agg['avg_r'], 1), (int)$agg['cnt'], $artistId]);

    flash('نظرت ثبت شد. ممنون که تجربه‌ات را به اشتراک گذاشتی! 💜');
} catch (Throwable $e) {
    flash('ثبت نظر ناموفق بود.', 'error');
}

header('Location: artist.php?id=' . $artistId);
exit;
