<?php
require_once __DIR__ . '/config.php';

$user = current_user();

// فقط مشتری‌های واردشده
if (!$user) {
    flash('برای مشاهده پروفایل، ابتدا وارد شو.', 'error');
    header('Location: login.php');
    exit;
}

if ($user['role'] === 'artist') {
    header('Location: artist-dashboard.php');
    exit;
}

$pageTitle = 'پروفایل من';

$projects = [];
$orders   = [];
$myBids    = [];
$favorites = [];
$p = db();

if ($p) {
    try {
        $s = $p->prepare('SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC');
        $s->execute([$user['id']]);
        $projects = $s->fetchAll();

        $s = $p->prepare(
            'SELECT o.*, a.name AS artist_name
             FROM orders o
             LEFT JOIN artists a ON a.id = o.artist_id
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC'
        );
        $s->execute([$user['id']]);
        $orders = $s->fetchAll();

        // پیشنهادهای دریافتی روی پروژه‌های باز من (برای تأیید)
        $s = $p->prepare(
            "SELECT b.*, a.name AS artist_name, pr.title AS project_title
             FROM bids b
             JOIN projects pr ON pr.id = b.project_id
             JOIN artists a ON a.id = b.artist_id
             WHERE pr.user_id = ? AND pr.status = 'pending'
             ORDER BY b.created_at DESC"
        );
        $s->execute([$user['id']]);
        $myBids = $s->fetchAll();

        // علاقه‌مندی‌ها (هنرمند + اثر)
        $s = $p->prepare(
            "SELECT f.*, a.name AS artist_name, a.avatar AS artist_avatar,
                    aw.title AS artwork_title, aw.image AS artwork_image, aw.price AS artwork_price
             FROM favorites f
             LEFT JOIN artists a ON a.id = f.artist_id
             LEFT JOIN artworks aw ON aw.id = f.artwork_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC"
        );
        $s->execute([$user['id']]);
        $favorites = $s->fetchAll();
    } catch (Throwable $e) {}
}

$statusLabels = [
    'pending'    => 'در انتظار بررسی',
    'in_progress'=> 'در حال اجرا',
    'completed'  => 'تکمیل شده',
    'cancelled'  => 'لغو شده',
];

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>پروفایل من</h1>
        <p>سلام <?= e($user['name']) ?> 👋 — خوش آمدی</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <!-- اطلاعات حساب -->
        <div class="detail-layout" style="grid-template-columns:1fr 1fr;margin-bottom:25px">
            <div class="form-card" style="max-width:100%">
                <h3 style="margin-top:0">اطلاعات حساب</h3>
                <p><b>نام:</b> <?= e($user['name']) ?></p>
                <p><b>ایمیل:</b> <span dir="ltr"><?= e($user['email']) ?></span></p>
                <p><b>نقش:</b> مشتری</p>
                <a class="btn btn-light" href="project-new.php">ثبت پروژه جدید</a>
                <a class="btn btn-light" href="cart.php" style="margin-right:8px">سبد خرید</a>
                <a class="btn btn-light" href="gallery.php" style="margin-right:8px">گالری آثار</a>
                <a class="btn btn-light" href="track-order.php" style="margin-right:8px">پیگیری سفارش</a>
            </div>

            <div class="form-card" style="max-width:100%;background:var(--pink-light)">
                <h3 style="margin-top:0">خلاصه فعالیت</h3>
                <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:10px">
                    <div style="background:#fff;border-radius:14px;padding:14px 20px;text-align:center;flex:1;min-width:100px">
                        <b style="color:var(--pink);font-size:26px;display:block"><?= count($projects) ?></b>
                        <small class="small-muted">پروژه ثبت‌شده</small>
                    </div>
                    <div style="background:#fff;border-radius:14px;padding:14px 20px;text-align:center;flex:1;min-width:100px">
                        <b style="color:var(--pink);font-size:26px;display:block"><?= count($orders) ?></b>
                        <small class="small-muted">سفارش</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- پروژه‌های من -->
        <div class="section-heading">
            <div><h2>پروژه‌های من</h2></div>
            <a class="text-link" href="project-new.php">＋ ثبت پروژه جدید ←</a>
        </div>

        <?php if (empty($projects)): ?>
            <div class="empty-state" style="margin-bottom:30px">
                <p>هنوز پروژه‌ای ثبت نکرده‌ای. اولین ایده‌ات را ثبت کن!</p>
                <a class="btn" href="project-new.php">ثبت پروژه</a>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;margin-bottom:30px">
                <table class="card" style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="text-align:right;color:var(--muted);border-bottom:2px solid var(--line)">
                            <th style="padding:12px">عنوان</th>
                            <th style="padding:12px">دسته‌بندی</th>
                            <th style="padding:12px">بودجه</th>
                            <th style="padding:12px">وضعیت</th>
                            <th style="padding:12px">تاریخ ثبت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $proj): ?>
                            <tr style="border-bottom:1px solid var(--line)">
                                <td style="padding:12px"><?= e($proj['title']) ?></td>
                                <td style="padding:12px"><?= e($proj['category']) ?></td>
                                <td style="padding:12px">
                                    <?= $proj['budget'] ? fa_num($proj['budget']) . ' تومان' : '—' ?>
                                </td>
                                <td style="padding:12px">
                                    <span class="pill"><?= e($statusLabels[$proj['status']] ?? $proj['status']) ?></span>
                                </td>
                                <td style="padding:12px"><?= fa_date($proj['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- پیشنهادهای دریافتی (تأیید bid) -->
        <?php if ($myBids): ?>
        <div class="section-heading">
            <div><h2>پیشنهادهای هنرمندان</h2></div>
        </div>

        <div style="margin-bottom:30px">
            <?php foreach ($myBids as $bid): ?>
                <div class="card" style="padding:14px 18px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                    <div>
                        <b style="font-size:13px"><?= e($bid['artist_name']) ?></b>
                        <small class="small-muted" style="display:block">
                            برای پروژه «<?= e($bid['project_title']) ?>»
                        </small>
                        <?php if ($bid['note']): ?>
                            <small class="small-muted" style="display:block">«<?= e($bid['note']) ?>»</small>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:left;display:flex;gap:10px;align-items:center">
                        <b style="color:var(--pink-dark)"><?= fa_num($bid['amount']) ?> تومان</b>
                        <form method="post" action="bid-accept.php"
                              onsubmit="return confirm('این پیشنهاد تأیید شود؟ یک سفارش ساخته می‌شود.')">
                            <input type="hidden" name="bid_id" value="<?= e($bid['id']) ?>">
                            <button type="submit" class="btn" style="padding:6px 16px;font-size:12px">تأیید و شروع ✓</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- سفارش‌های من -->
        <div class="section-heading">
            <div><h2>سفارش‌های من</h2></div>
            <a class="text-link" href="cart.php">مشاهده سبد خرید ←</a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <p>هنوز سفارشی نداری. از صفحه هنرمندان، یک اثر را انتخاب کن.</p>
                <a class="btn" href="artists.php">مشاهده هنرمندان</a>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table class="card" style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="text-align:right;color:var(--muted);border-bottom:2px solid var(--line)">
                            <th style="padding:12px">کد سفارش</th>
                            <th style="padding:12px">هنرمند</th>
                            <th style="padding:12px">مبلغ</th>
                            <th style="padding:12px">وضعیت</th>
                            <th style="padding:12px">تاریخ</th>
                            <th style="padding:12px">گفت‌وگو</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr style="border-bottom:1px solid var(--line)">
                                <td style="padding:12px" dir="ltr">#<?= e($order['id']) ?></td>
                                <td style="padding:12px"><?= e($order['artist_name'] ?? '—') ?></td>
                                <td style="padding:12px"><?= fa_num($order['amount']) ?> تومان</td>
                                <td style="padding:12px">
                                    <span class="pill"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
                                </td>
                                <td style="padding:12px"><?= fa_date($order['created_at']) ?></td>
                                <td style="padding:12px">
                                    <a class="btn btn-soft" style="padding:5px 12px;font-size:11px"
                                       href="chat.php?order=<?= e($order['id']) ?>">💬 گفت‌وگو</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- علاقه‌مندی‌های من -->
        <?php if ($favorites): ?>
        <div class="section-heading">
            <div><h2>علاقه‌مندی‌های من ♡</h2></div>
        </div>

        <div class="artist-grid" style="margin-bottom:30px">
            <?php foreach ($favorites as $fav): ?>
                <article class="artist-card">
                    <a class="artist-photo" href="<?= $fav['artist_name'] ? ('artist.php?id=' . e($fav['artist_id'])) : ('artwork.php?id=' . e($fav['artwork_id'])) ?>">
                        <img src="<?= e($fav['artist_avatar'] ?: ($fav['artwork_image'] ?: 'assets/images/work1.jpg')) ?>" alt="">
                        <span class="verified"><?= $fav['artist_name'] ? '👩‍🎨 هنرمند' : '🖼 اثر' ?></span>
                    </a>
                    <div class="artist-info">
                        <div class="artist-name">
                            <h3><?= e($fav['artist_name'] ?: $fav['artwork_title']) ?></h3>
                            <?php if ($fav['artwork_price']): ?>
                                <p><?= fa_num($fav['artwork_price']) ?> تومان</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
