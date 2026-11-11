<?php
require_once __DIR__ . '/config.php';

/* =========================
   جستجوی سفارش با کد پیگیری
========================= */
$query = trim($_GET['track'] ?? $_GET['order_id'] ?? '');
$order = null;

if ($query !== '') {
    $p = db();

    if ($p) {
        try {
            $s = $p->prepare(
                'SELECT o.*, a.name AS artist_name, a.specialty
                 FROM orders o
                 LEFT JOIN artists a ON a.id = o.artist_id
                 WHERE o.id = ? OR o.tracking_code = ?
                 LIMIT 1'
            );
            $s->execute([(int)$query, $query]);
            $order = $s->fetch();
        } catch (Throwable $e) {}
    }

    // بازگشت نمونه نمایشی وقتی دیتابیس موجود نیست
    if (!$order) {
        $samples = [
            4803 => ['status' => 'in_progress', 'artist' => 'سارا یوسفی', 'specialty' => 'رنگ روغن', 'amount' => 850000, 'date' => '2026-11-05'],
            4815 => ['status' => 'completed',   'artist' => 'مهراد حیدری', 'specialty' => 'سیاه‌قلم', 'amount' => 420000, 'date' => '2026-10-28'],
            4827 => ['status' => 'pending',     'artist' => 'نیلوفر احمدی', 'specialty' => 'آبرنگ', 'amount' => 610000, 'date' => '2026-11-09'],
        ];

        $num = (int)$query;
        if (isset($samples[$num])) {
            $s = $samples[$num];
            $order = [
                'id'           => $num,
                'tracking_code'=> 'HMS-' . $num,
                'artist_name'  => $s['artist'],
                'specialty'    => $s['specialty'],
                'amount'       => $s['amount'],
                'status'       => $s['status'],
                'created_at'   => $s['date'],
            ];
        } else {
            $order = null;
        }
    }
}

$stepLabels = [
    'pending'     => 'در انتظار تأیید هنرمند',
    'in_progress' => 'در حال نقاشی و اجرا',
    'completed'   => 'تکمیل و ارسال شده',
    'cancelled'   => 'لغو شده',
];

$stepIcons = [
    'pending'     => '📋',
    'in_progress' => '🎨',
    'completed'   => '📦',
    'cancelled'   => '✕',
];

$pageTitle = 'پیگیری سفارش';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>پیگیری سفارش</h1>
        <p>کد پیگیری سفارش یا شماره سفارش را وارد کن</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <!-- فرم جستجو -->
        <div class="form-card" style="margin-bottom:26px">
            <form method="get" action="track-order.php" style="display:flex;gap:10px;flex-wrap:wrap">
                <div class="field" style="flex:1;min-width:220px;margin:0">
                    <label for="track">کد پیگیری / شماره سفارش</label>
                    <input type="text" id="track" name="track" dir="ltr" required
                           placeholder="مثلاً: HMS-4827 یا 4827"
                           value="<?= e($query) ?>">
                </div>
                <button type="submit" class="btn" style="align-self:flex-end">
                    پیگیری ⌕
                </button>
            </form>
        </div>

        <?php if ($query !== '' && !$order): ?>
            <div class="empty-state">
                <p>سفارشی با این کد پیدا نشد.</p>
                <p class="small-muted">کد پیگیری را دوباره بررسی کن یا با پشتیبانی {{ تماس بگیر. }}</p>
                <p class="small-muted"><a class="text-link" href="contact.php">تماس با پشتیبانی</a></p>
            </div>
        <?php endif; ?>

        <?php if ($order): ?>

            <!-- خلاصه سفارش -->
            <div class="form-card" style="margin-bottom:20px">
                <div class="detail-top" style="margin-top:0">
                    <div style="flex:1">
                        <h3 style="margin:0 0 4px">سفارش <?= e($order['tracking_code'] ?? ('#' . $order['id'])) ?></h3>
                        <span class="small-muted">
                            <?= e($order['artist_name'] ?? '—') ?> ·
                            <?= e($order['specialty'] ?? '') ?> ·
                            <span dir="ltr"><?= e($order['created_at']) ?></span>
                        </span>
                    </div>
                    <div style="text-align:center">
                        <b style="color:var(--pink);font-size:18px;display:block">
                            <?= number_format((float)$order['amount']) ?> تومان
                        </b>
                        <span class="pill"><?= e($stepLabels[$order['status']] ?? $order['status']) ?></span>
                    </div>
                </div>
            </div>

            <!-- مراحل پیشرفت -->
            <div class="form-card">
                <h3 style="margin-top:0">مراحل سفارش</h3>
                <div class="status-list">
                    <?php
                    $active = $order['status'] === 'cancelled' ? 0 : (['pending'=>1,'in_progress'=>2,'completed'=>3][$order['status']] ?? 1);
                    $steps = ['pending' => 'ثبت سفارش', 'in_progress' => 'در حال اجرا', 'completed' => 'تحویل'];
                    $i = 0;
                    foreach ($steps as $key => $label):
                        $i++;
                        if ($order['status'] === 'cancelled') break;
                    ?>
                        <span style="<?= $i <= $active ? 'background:var(--pink);color:#fff' : '' ?>">
                            <?= $stepIcons[$key] ?> <?= e($label) ?>
                            <?= $i === $active ? '— در جریان' : '' ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if ($order['status'] === 'cancelled'): ?>
                        <span style="background:#fff0f0;color:#a52e42">✕ این سفارش لغو شده است</span>
                    <?php endif; ?>
                </div>

                <p class="small-muted" style="margin:16px 0 0">
                    <?php if ($order['status'] === 'pending'): ?>
                        سفارش ثبت شده و در انتظار تأیید هنرمند است. معمولاً پاسخ در کمتر از ۲۴ ساعت اعلام می‌شود.
                    <?php elseif ($order['status'] === 'in_progress'): ?>
                        هنرمند در حال خلق اثر شماست! 🎨 پیشرفت کار از طریق پیام با شما در میان گذاشته می‌شود.
                    <?php elseif ($order['status'] === 'completed'): ?>
                        اثر آماده شده و ارسال گردیده است. از پروفایل خود یا تماس با پشتیبانی، رضایتت را ثبت کن.
                    <?php endif; ?>
                </p>
            </div>

            <!-- عقب‌نشینی -->
            <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap">
                <a class="btn btn-light" href="javascript:history.back()">← بازگشت</a>
                <a class="btn btn-light" href="contact.php">نیاز به کمک دارم</a>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
