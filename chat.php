<?php
require_once __DIR__ . '/config.php';

/* =========================
   چت داخل سفارش (مشتری ↔ هنرمند)
   دسترسی: صاحب سفارش، هنرمند سفارش یا ادمین
========================= */

$user = current_user();
if (!$user) {
    flash('برای مشاهده گفت‌وگو، ابتدا وارد شو.', 'error');
    header('Location: login.php');
    exit;
}

$orderId = (int)($_GET['order'] ?? 0);
$p = db();
$order = null;

if ($p && $orderId > 0) {
    try {
        $s = $p->prepare(
            "SELECT o.*, a.id AS a_id, a.name AS artist_name, a.user_id AS artist_user_id,
                    u.name AS customer_name
             FROM orders o
             LEFT JOIN artists a ON a.id = o.artist_id
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.id = ? LIMIT 1"
        );
        $s->execute([$orderId]);
        $order = $s->fetch();
    } catch (Throwable $e) {}
}

// مجوز دسترسی
$allowed = false;
$isArtistSide = false;
if ($order) {
    if ($user['role'] === 'admin') $allowed = true;
    elseif ((int)$order['user_id'] === (int)$user['id']) $allowed = true;
    elseif ($user['role'] === 'artist' && (int)$order['artist_user_id'] === (int)$user['id']) {
        $allowed = true; $isArtistSide = true;
    }
}

if (!$order || !$allowed) {
    flash('به این گفت‌وگو دسترسی نداری.', 'error');
    header('Location: ' . ($user['role'] === 'artist' ? 'artist-dashboard.php' : 'profile.php'));
    exit;
}

/* --------- ارسال پیام --------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    $imagePath = null;

    // آپلود عکس پیشرفت (اختیاری)
    if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'] ?? '')) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (isset($allowed[$mime]) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
            $dir = __DIR__ . '/uploads/chat';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $name = 'order_' . $orderId . '_' . time() . '.' . $allowed[$mime];
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $name)) {
                $imagePath = 'uploads/chat/' . $name;
            }
        } else {
            flash('فرمت یا حجم عکس مجاز نیست (فقط JPG/PNG/WebP تا ۵ مگ).', 'error');
        }
    }

    if ($msg !== '' || $imagePath) {
        try {
            $s = $p->prepare(
                'INSERT INTO chat_messages (order_id, sender_id, message, image, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $s->execute([$orderId, $user['id'], $msg ?: null, $imagePath]);
        } catch (Throwable $e) {
            flash('ارسال پیام ناموفق بود.', 'error');
        }
    }

    header('Location: chat.php?order=' . $orderId);
    exit;
}

/* --------- خواندن پیام‌ها --------- */
$messages = [];
try {
    $s = $p->prepare(
        "SELECT cm.*, u.name AS sender_name, u.role AS sender_role
         FROM chat_messages cm JOIN users u ON u.id = cm.sender_id
         WHERE cm.order_id = ? ORDER BY cm.created_at ASC, cm.id ASC"
    );
    $s->execute([$orderId]);
    $messages = $s->fetchAll();
} catch (Throwable $e) {}

$statusLabels = [
    'pending'     => 'در انتظار تأیید',
    'in_progress' => 'در حال اجرا',
    'completed'   => 'تکمیل شده',
    'cancelled'   => 'لغو شده',
];

$pageTitle = 'گفت‌وگوی سفارش #' . $orderId;
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>گفت‌وگوی سفارش #<?= e($orderId) ?></h1>
        <p>
            <?= e($isArtistSide ? ('مشتری: ' . ($order['customer_name'] ?? '—')) : ('هنرمند: ' . ($order['artist_name'] ?? '—'))) ?>
            · <span class="pill"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
        </p>
    </div>
</div>

<section class="content">
    <div class="container" style="max-width:820px">

        <!-- کارت سفارش -->
        <div class="card" style="padding:14px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
            <div>
                <b>مبلغ: <?= fa_num($order['amount']) ?> تومان</b>
                <small class="small-muted" style="display:block">ثبت: <?= fa_date($order['created_at']) ?></small>
            </div>
            <a class="btn btn-light" href="<?= $isArtistSide ? 'artist-dashboard.php' : 'profile.php' ?>">← بازگشت</a>
        </div>

        <!-- پیام‌ها -->
        <div class="chat-box" id="chat-box">
            <?php if (!$messages): ?>
                <div class="empty-state" style="margin:0">
                    <p>💬 هنوز پیامی رد و بدل نشده. اولین پیام را بفرست!</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                    <div class="chat-msg <?= ((int)$m['sender_id'] === (int)$user['id']) ? 'mine' : '' ?>">
                        <div class="chat-bubble">
                            <small class="chat-sender"><?= e($m['sender_name']) ?> · <?= fa_date($m['created_at'], 'Y/m/d') ?></small>
                            <?php if ($m['message']): ?><p><?= nl2br(e($m['message'])) ?></p><?php endif; ?>
                            <?php if ($m['image']): ?>
                                <a href="<?= e($m['image']) ?>" target="_blank" rel="noopener">
                                    <img src="<?= e($m['image']) ?>" alt="پیوست" class="chat-image">
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- فرم ارسال -->
        <form class="form-card" method="post" action="chat.php?order=<?= e($orderId) ?>"
              enctype="multipart/form-data" style="max-width:100%;margin-top:18px">
            <div class="field">
                <label for="message">پیام</label>
                <textarea id="message" name="message" rows="3" placeholder="پیامت را بنویس…"></textarea>
            </div>
            <div class="field">
                <label for="image">عکس پیشرفت کار (اختیاری — JPG/PNG/WebP تا ۵ مگ)</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit" class="btn">ارسال پیام ↗</button>
        </form>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
