<?php
require_once __DIR__ . '/config.php';

/* =========================
   تأیید پیشنهاد (bid) توسط مشتری
   → ساخت سفارش + لینک چت
========================= */

$user = current_user();
if (!$user) {
    flash('برای تأیید پیشنهاد، ابتدا وارد شو.', 'error');
    header('Location: login.php');
    exit;
}

$p = db();
$bidId = (int)($_POST['bid_id'] ?? $_GET['bid_id'] ?? 0);
$bid = null;

if ($p && $bidId > 0) {
    try {
        $s = $p->prepare(
            "SELECT b.*, pr.title AS project_title, pr.user_id AS customer_id,
                    a.name AS artist_name, a.user_id AS artist_user_id
             FROM bids b
             JOIN projects pr ON pr.id = b.project_id
             JOIN artists a ON a.id = b.artist_id
             WHERE b.id = ? LIMIT 1"
        );
        $s->execute([$bidId]);
        $bid = $s->fetch();
    } catch (Throwable $e) {}
}

if (!$bid) {
    flash('چنین پیشنهادی یافت نشد.', 'error');
    header('Location: profile.php');
    exit;
}

if ((int)$bid['customer_id'] !== (int)$user['id'] && $user['role'] !== 'admin') {
    flash('فقط صاحب پروژه می‌تواند پیشنهاد را تأیید کند.', 'error');
    header('Location: profile.php');
    exit;
}

try {
    $p->beginTransaction();

    // ساخت سفارش از پیشنهاد
    $ins = $p->prepare(
        'INSERT INTO orders (user_id, artist_id, amount, status, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $ins->execute([(int)$bid['customer_id'], (int)$bid['artist_id'], (int)$bid['amount'], 'in_progress']);
    $orderId = (int)$p->lastInsertId();

    // کد پیگیری
    $code = 'HMS-' . $orderId;
    $u = $p->prepare('UPDATE orders SET tracking_code = ? WHERE id = ?');
    $u->execute([$code, $orderId]);

    // پروژه به «در حال اجرا»
    $u2 = $p->prepare("UPDATE projects SET status = 'in_progress' WHERE id = ?");
    $u2->execute([(int)$bid['project_id']]);

    // بقیه پیشنهادهای همین پروژه بسته شوند (با حذف)
    // (بدون FK راحت‌تر: همان‌جا در پروژه دیگر نمایش داده نمی‌شوند چون status عوض شد)

    // پیام خوش‌آمد در چت
    $chat = $p->prepare(
        'INSERT INTO chat_messages (order_id, sender_id, message, created_at)
         VALUES (?, ?, ?, NOW())'
    );
    $chat->execute([
        $orderId, (int)$user['id'],
        "پیشنهاد «{$bid['artist_name']}» به مبلغ " . number_format((float)$bid['amount']) . " تومان تأیید شد. 🎉 از اینجا می‌توانید جزئیات و پیشرفت کار را هماهنگ کنید."
    ]);

    $p->commit();

    flash("پیشنهاد تأیید شد! کد پیگیری سفارش: $code 🎉");
    header('Location: chat.php?order=' . $orderId);
    exit;

} catch (Throwable $e) {
    if ($p->inTransaction()) $p->rollBack();
    flash('تأیید پیشنهاد ناموفق بود: ' . $e->getMessage(), 'error');
    header('Location: profile.php');
    exit;
}
