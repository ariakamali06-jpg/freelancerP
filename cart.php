<?php
require_once __DIR__ . '/config.php';

$user = current_user();

if (!$user) {
    flash('برای مشاهده سبد خرید، ابتدا وارد شو.', 'error');
    header('Location: login.php');
    exit;
}

if ($user['role'] === 'artist') {
    header('Location: artist-dashboard.php');
    exit;
}

$p = db();
$cart = [];

/* =========================
   افزودن / حذف آیتم
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && $p) {
        $artworkId = (int)($_POST['artwork_id'] ?? 0);
        $title     = trim($_POST['title'] ?? '');
        $artistId  = (int)($_POST['artist_id'] ?? 0);
        $price     = (int)($_POST['price'] ?? 0);

        if ($artworkId && $title && $price > 0) {
            try {
                $s = $p->prepare(
                    'INSERT INTO cart_items (user_id, artwork_id, title, artist_id, price, created_at)
                     VALUES (?, ?, ?, ?, ?, NOW())'
                );
                $s->execute([$user['id'], $artworkId, $title, $artistId, $price]);
                flash('به سبد خرید اضافه شد. 🛒');
            } catch (Throwable $e) {
                flash('امکان افزودن به سبد وجود ندارد (جدول cart_items را بساز).', 'error');
            }
        }
    }

    if ($action === 'remove' && $p) {
        $itemId = (int)($_POST['item_id'] ?? 0);
        try {
            $s = $p->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
            $s->execute([$itemId, $user['id']]);
            flash('آیتم از سبد حذف شد.');
        } catch (Throwable $e) {
            flash('حذف ناموفق بود.', 'error');
        }
    }

    if ($action === 'checkout' && $p) {
        try {
            $s = $p->prepare('SELECT * FROM cart_items WHERE user_id = ?');
            $s->execute([$user['id']]);
            $items = $s->fetchAll();

            if ($items) {
                foreach ($items as $item) {
                    $ins = $p->prepare(
                        'INSERT INTO orders (user_id, artist_id, amount, status, created_at)
                         VALUES (?, ?, ?, ?, NOW())'
                    );
                    $ins->execute([
                        $user['id'],
                        $item['artist_id'] ?: null,
                        $item['price'],
                        'pending'
                    ]);
                }
                $del = $p->prepare('DELETE FROM cart_items WHERE user_id = ?');
                $del->execute([$user['id']]);
                flash('پرداخت با موفقیت انجام شد! 🎉 سفارش‌هایت در پروفایلت قابل مشاهده است.');
            } else {
                flash('سبد خرید خالی است.', 'error');
            }
        } catch (Throwable $e) {
            flash('خطا در ثبت سفارش. جدول orders و cart_items را بررسی کن.', 'error');
        }
    }

    header('Location: cart.php');
    exit;
}

/* =========================
   خواندن سبد
========================= */
if ($p) {
    try {
        $s = $p->prepare(
            'SELECT c.*, a.name AS artist_name
             FROM cart_items c
             LEFT JOIN artists a ON a.id = c.artist_id
             WHERE c.user_id = ?
             ORDER BY c.created_at DESC'
        );
        $s->execute([$user['id']]);
        $cart = $s->fetchAll();
    } catch (Throwable $e) {}
}

$total = array_sum(array_column($cart, 'price'));

$pageTitle = 'سبد خرید';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>سبد خرید</h1>
        <p>آثار انتخابی‌ات را بررسی و پرداخت کن</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <?php if (empty($cart)): ?>
            <div class="empty-state">
                <p style="font-size:16px">🛒 سبد خریدت خالی است.</p>
                <p>از صفحه هنرمندان، یک اثر هنری را انتخاب کن تا اینجا اضافه شود.</p>
                <a class="btn" href="artists.php">مشاهده هنرمندان</a>
            </div>
        <?php else: ?>

            <div class="detail-layout" style="grid-template-columns:2fr 1fr">

                <!-- ===== لیست آیتم‌ها ===== -->
                <div>
                    <?php foreach ($cart as $item): ?>
                        <div class="card" style="display:flex;gap:15px;align-items:center;margin-bottom:14px;padding:15px">
                            <img
                                src="assets/images/work<?= ((int)$item['id'] % 6) + 1 ?>.jpg"
                                alt="<?= e($item['title']) ?>"
                                style="width:90px;height:70px;object-fit:cover;border-radius:10px"
                            >
                            <div style="flex:1">
                                <h4 style="margin:0 0 4px"><?= e($item['title']) ?></h4>
                                <small class="small-muted">
                                    هنرمند: <?= e($item['artist_name'] ?? '—') ?>
                                </small>
                            </div>
                            <div style="text-align:left">
                                <b style="color:var(--pink-dark)">
                                    <?= number_format((float)$item['price']) ?> تومان
                                </b>
                                <form method="post" style="margin-top:8px">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
                                    <button type="submit" class="btn btn-light" style="padding:5px 12px;font-size:11px">
                                        حذف ✕
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ===== جمع کل و پرداخت ===== -->
                <div>
                    <div class="form-card" style="max-width:100%;background:var(--pink-light)">
                        <h3 style="margin-top:0">خلاصه سفارش</h3>
                        <p>تعداد آیتم‌ها: <b><?= count($cart) ?></b></p>
                        <p>جمع کل: <b style="color:var(--pink);font-size:18px"><?= number_format($total) ?> تومان</b></p>
                        <p class="small-muted" style="margin-bottom:16px">
                            هزینه ارسال به‌صورت جداگانه با هنرمند توافق می‌شود.
                        </p>

                        <form method="post">
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="btn btn-block">
                                پرداخت و ثبت سفارش 💳
                            </button>
                        </form>

                        <a class="btn btn-light btn-block" style="margin-top:10px" href="artists.php">
                            ادامه خرید
                        </a>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
