<?php
require_once __DIR__ . '/config.php';

$user = current_user();

if (!$user || $user['role'] !== 'artist') {
    flash('برای ورود به پنل هنرمند، ابتدا با حساب هنرمند وارد شو.', 'error');
    header('Location: login.php');
    exit;
}

/* =========================
   به‌روزرسانی وضعیت پروژه / سفارش
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $p = db();

    if ($action === 'bid' && $p) {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $amount    = (int)($_POST['amount'] ?? 0);
        $note      = trim($_POST['note'] ?? '');

        if ($projectId && $amount > 0) {
            try {
                $s = $p->prepare(
                    'INSERT INTO bids (project_id, artist_id, amount, note, created_at)
                     VALUES (?, ?, ?, ?, NOW())'
                );
                $s->execute([$projectId, $user['id'], $amount, $note]);
                flash('پیشنهادت ثبت شد. 🌟');
            } catch (Throwable $e) {
                flash('ثبت پیشنهاد ممکن نشد (جدول bids را بساز).', 'error');
            }
        } else {
            flash('مبلغ معتبر وارد کن.', 'error');
        }
    }

    if ($action === 'status' && $p) {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status  = $_POST['status'] ?? '';

        if ($orderId && in_array($status, ['in_progress','completed','cancelled'], true)) {
            try {
                $s = $p->prepare('UPDATE orders SET status = ? WHERE id = ? AND artist_id = ?');
                $s->execute([$status, $orderId, $user['id']]);
                flash('وضعیت سفارش به‌روزرسانی شد.');
            } catch (Throwable $e) {
                flash('به‌روزرسانی ممکن نشد.', 'error');
            }
        }
    }

    header('Location: artist-dashboard.php');
    exit;
}

$p = db();
$myProjects = [];
$myOrders   = [];

if ($p) {
    try {
        $s = $p->prepare(
            'SELECT b.*, pr.title AS project_title, pr.category,
                    pr.budget AS project_budget
             FROM bids b
             LEFT JOIN projects pr ON pr.id = b.project_id
             WHERE b.artist_id = ?
             ORDER BY b.created_at DESC'
        );
        $s->execute([$user['id']]);
        $myProjects = $s->fetchAll();
    } catch (Throwable $e) {}

    try {
        $s = $p->prepare(
            'SELECT * FROM orders
             WHERE artist_id = ?
             ORDER BY created_at DESC'
        );
        $s->execute([$user['id']]);
        $myOrders = $s->fetchAll();
    } catch (Throwable $e) {}
}

$allProjects = [];
if ($p) {
    try {
        $s = $p->prepare(
            "SELECT * FROM projects
             WHERE status = 'pending'
             ORDER BY created_at DESC LIMIT 9"
        );
        $s->execute();
        $allProjects = $s->fetchAll();
    } catch (Throwable $e) {}
}

if (empty($allProjects) && !$p) {
    $allProjects = [
        ['id' => 101, 'title' => 'پرتره مادربزرگ', 'category' => 'رنگ روغن', 'budget' => 2500000, 'description' => 'پرتره واقع‌گرا از روی عکس قدیمی.'],
        ['id' => 102, 'title' => 'لوگوی فانتزی', 'category' => 'نقاشی دیجیتال', 'budget' => 800000, 'description' => 'کاراکتر فانتزی برای برند من.'],
        ['id' => 103, 'title' => 'سفره هفت‌سین', 'category' => 'آبرنگ', 'budget' => 600000, 'description' => 'طرح آبرنگ از سفره هفت‌سین با گل‌های بهاری.'],
    ];
}

$pageTitle = 'پنل هنرمند';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>پنل هنرمند</h1>
        <p>سلام <?= e($user['name']) ?> 🎨 — مدیریت سفارش‌ها و پیشنهادها</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <!-- خلاصه آمار -->
        <div class="detail-layout" style="grid-template-columns:repeat(4,1fr);margin-bottom:28px">
            <div class="card" style="background:var(--pink-light);text-align:center">
                <b style="color:var(--pink);font-size:26px;display:block"><?= count($myProjects) ?></b>
                <small class="small-muted">پیشنهاد ثبت‌شده</small>
            </div>
            <div class="card" style="background:var(--pink-light);text-align:center">
                <b style="color:var(--pink);font-size:26px;display:block"><?= count($myOrders) ?></b>
                <small class="small-muted">سفارش</small>
            </div>
            <div class="card" style="background:var(--pink-light);text-align:center">
                <b style="color:var(--pink);font-size:26px;display:block">4.9</b>
                <small class="small-muted">امتیاز من</small>
            </div>
            <div class="card" style="background:var(--pink-light);text-align:center">
                <b style="color:var(--pink);font-size:26px;display:block">⭐</b>
                <small class="small-muted">وضعیت: فعال</small>
            </div>
        </div>

        <!-- پروژه‌های باز برای پیشنهاد -->
        <div class="section-heading">
            <div><h2>پروژه‌های باز</h2></div>
            <span class="small-muted">پروژه‌هایی که منتظر پیشنهاد هنرمندان هستند</span>
        </div>

        <?php if (empty($allProjects)): ?>
            <div class="empty-state" style="margin-bottom:28px">
                <p>فعلاً پروژه بازی وجود ندارد.</p>
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px">
                <?php foreach ($allProjects as $pr): ?>
                    <div class="form-card" style="max-width:100%;display:flex;flex-direction:column">
                        <h4 style="margin:0 0 6px"><?= e($pr['title']) ?></h4>
                        <p class="small-muted" style="margin:0 0 8px"><?= e(mb_substr($pr['description'], 0, 90)) ?></p>
                        <div class="status-list" style="margin-bottom:10px">
                            <span><?= e($pr['category']) ?></span>
                            <span>💰 <?= isset($pr['budget']) && $pr['budget'] ? number_format((float)$pr['budget']) . ' تومان' : '—' ?></span>
                        </div>

                        <form method="post" style="margin-top:auto">
                            <input type="hidden" name="action" value="bid">
                            <input type="hidden" name="project_id" value="<?= e($pr['id']) ?>">
                            <div class="field" style="margin-bottom:8px;flex:0">
                                <label for="amount">پیشنهاد قیمت (تومان)</label>
                                <input type="number" id="amount" name="amount" min="10000" required placeholder="مثلاً: 500000">
                            </div>
                            <div class="field" style="margin-bottom:8px;flex:0">
                                <input type="text" name="note" placeholder="توضیح کوتاه…">
                            </div>
                            <button type="submit" class="btn btn-block" style="margin-top:0">ثبت پیشنهاد</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- سفارش‌های من -->
        <div class="section-heading">
            <div><h2>سفارش‌های من</h2></div>
        </div>

        <?php if (empty($myOrders)): ?>
            <div class="empty-state" style="margin-bottom:28px">
                <p>هنوز سفارشی نداری. با ثبت پیشنهاد خوب، اولین سفارش را بگیر!</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;margin-bottom:28px">
                <table class="card" style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="text-align:right;color:var(--muted);border-bottom:2px solid var(--line)">
                            <th style="padding:12px">کد</th>
                            <th style="padding:12px">مبلغ</th>
                            <th style="padding:12px">وضعیت</th>
                            <th style="padding:12px">تاریخ</th>
                            <th style="padding:12px">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myOrders as $order): ?>
                            <tr style="border-bottom:1px solid var(--line)">
                                <td style="padding:12px" dir="ltr">#<?= e($order['id']) ?></td>
                                <td style="padding:12px"><?= number_format((float)$order['amount']) ?> تومان</td>
                                <td style="padding:12px">
                                    <span class="pill"><?= e($order['status']) ?></span>
                                </td>
                                <td style="padding:12px" dir="ltr"><?= e($order['created_at']) ?></td>
                                <td style="padding:12px">
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="order_id" value="<?= e($order['id']) ?>">
                                        <select name="status" onchange="this.form.submit()" style="font:inherit;padding:6px;border-radius:8px;border:1px solid var(--line)">
                                            <option value="in_progress" <?= $order['status']==='in_progress'?'selected':'' ?>>در حال اجرا</option>
                                            <option value="completed"  <?= $order['status']==='completed'?'selected':'' ?>>تکمیل شد</option>
                                            <option value="cancelled"  <?= $order['status']==='cancelled'?'selected':'' ?>>لغو شد</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
