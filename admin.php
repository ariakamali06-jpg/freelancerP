<?php
require_once __DIR__ . '/config.php';

$user = current_user();

if (!$user || $user['role'] !== 'admin') {
    flash('این بخش فقط مخصوص مدیران است.', 'error');
    header('Location: login.php');
    exit;
}

/* =========================
   پردازش اقدامات مدیریتی
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $p = db();

    // تأیید / رد هنرمند
    if (in_array($action, ['approve_artist', 'reject_artist'], true) && $p) {
        $artistId = (int)($_POST['artist_id'] ?? 0);
        $status   = $action === 'approve_artist' ? 'approved' : 'rejected';
        try {
            $s = $p->prepare('UPDATE artists SET status = ? WHERE id = ?');
            $s->execute([$status, $artistId]);
            flash('وضعیت هنرمند به‌روزرسانی شد.');
        } catch (Throwable $e) {
            flash('خطا در به‌روزرسانی هنرمند.', 'error');
        }
    }

    // حذف کاربر
    elseif ($action === 'delete_user' && $p) {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId && $userId !== (int)$user['id']) {
            try {
                $s = $p->prepare('DELETE FROM users WHERE id = ?');
                $s->execute([$userId]);
                flash('کاربر حذف شد.');
            } catch (Throwable $e) {
                flash('حذف کاربر ممکن نشد.', 'error');
            }
        }
    }

    // تغییر وضعیت پروژه
    elseif ($action === 'project_status' && $p) {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $status    = $_POST['status'] ?? '';
        if (in_array($status, ['pending','in_progress','completed','cancelled'], true)) {
            try {
                $s = $p->prepare('UPDATE projects SET status = ? WHERE id = ?');
                $s->execute([$status, $projectId]);
                flash('وضعیت پروژه تغییر کرد.');
            } catch (Throwable $e) {
                flash('خطا در تغییر وضعیت پروژه.', 'error');
            }
        }
    }
    // علامت‌گذاری پیام خوانده‌شده
    elseif ($action === 'read_message' && $p) {
        $msgId = (int)($_POST['message_id'] ?? 0);
        try {
            $s = $p->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
            $s->execute([$msgId]);
            flash('پیام خوانده‌شده شد.');
        } catch (Throwable $e) {}
    }

    header('Location: admin.php');
    exit;
}

$p = db();
$users      = [];
$artists    = all_artists();
$projects   = [];
$orders     = [];
$messages   = [];

if ($p) {
    try {
        $users = $p->query('SELECT id, name, email, role, created_at FROM users ORDER BY id DESC')->fetchAll();
    } catch (Throwable $e) {}

    try {
        $artists = $p->query("SELECT * FROM artists ORDER BY id DESC")->fetchAll();
    } catch (Throwable $e) {}

    try {
        $projects = $p->query('SELECT * FROM projects ORDER BY created_at DESC LIMIT 50')->fetchAll();
    } catch (Throwable $e) {}

    try {
        $orders = $p->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 50')->fetchAll();
    } catch (Throwable $e) {}

    try {
        $messages = $p->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 20')->fetchAll();
    } catch (Throwable $e) {}
}

$counts = [
    'users'     => count($users) ?: 0,
    'artists'   => count($artists),
    'projects'  => count($projects),
    'orders'    => count($orders) ?: 0,
];

$pageTitle = 'پنل مدیریت';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>پنل مدیریت</h1>
        <p>مدیریت کاربران، هنرمندان، پروژه‌ها و سفارش‌ها</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <!-- آمار کلی -->
        <div class="detail-layout" style="grid-template-columns:repeat(4,1fr);margin-bottom:28px">
            <div class="card" style="background:var(--pink-light);text-align:center">
                <b style="color:var(--pink);font-size:26px;display:block"><?= $counts['users'] ?></b>
                <small class="small-muted">کاربر</small>
            </div>
            <div class="card" style="background:var(--pink-light);text-align:center">
                <b style="color:var(--pink);font-size:26px;display:block"><?= $counts['artists'] ?></b>
                <small class="small-muted">هنرمند</small>
            </div>
            <div class="card" style="background:var(--pink-light);text-align:center">
                <b style="color:var(--pink);font-size:26px;display:block"><?= $counts['projects'] ?></b>
                <small class="small-muted">پروژه</small>
            </div>
            <div class="card" style="background:var(--pink-light);text-align:center">
                <b style="color:var(--pink);font-size:26px;display:block"><?= $counts['orders'] ?></b>
                <small class="small-muted">سفارش</small>
            </div>
        </div>

        <!-- هنرمندان -->
        <div class="section-heading">
            <div><h2>هنرمندان</h2></div>
        </div>

        <?php if (empty($artists)): ?>
            <div class="empty-state" style="margin-bottom:24px"><p>هنرمندی ثبت نشده است.</p></div>
        <?php else: ?>
            <div style="overflow-x:auto;margin-bottom:28px">
                <table class="card" style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="text-align:right;color:var(--muted);border-bottom:2px solid var(--line)">
                            <th style="padding:12px">هنرمند</th>
                            <th style="padding:12px">تخصص</th>
                            <th style="padding:12px">شهر</th>
                            <th style="padding:12px">امتیاز</th>
                            <th style="padding:12px">وضعیت</th>
                            <th style="padding:12px">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($artists as $a): ?>
                            <tr style="border-bottom:1px solid var(--line)">
                                <td style="padding:12px"><?= e($a['name']) ?></td>
                                <td style="padding:12px"><?= e($a['specialty']) ?></td>
                                <td style="padding:12px"><?= e($a['city']) ?></td>
                                <td style="padding:12px">⭐ <?= e($a['rating'] ?? '—') ?></td>
                                <td style="padding:12px">
                                    <span class="pill"><?= e($a['status'] ?? 'approved') ?></span>
                                </td>
                                <td style="padding:12px">
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="artist_id" value="<?= e($a['id']) ?>">
                                        <?php if (($a['status'] ?? 'approved') !== 'approved'): ?>
                                            <button type="submit" name="action" value="approve_artist" class="btn btn-soft" style="padding:5px 12px;font-size:11px">تأیید</button>
                                        <?php endif; ?>
                                        <button type="submit" name="action" value="reject_artist" class="btn btn-light" style="padding:5px 12px;font-size:11px">رد</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- کاربران -->
        <div class="section-heading">
            <div><h2>کاربران</h2></div>
        </div>

        <?php if (empty($users)): ?>
            <div class="empty-state" style="margin-bottom:24px"><p>کاربری یافت نشد.</p></div>
        <?php else: ?>
            <div style="overflow-x:auto;margin-bottom:28px">
                <table class="card" style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="text-align:right;color:var(--muted);border-bottom:2px solid var(--line)">
                            <th style="padding:12px">نام</th>
                            <th style="padding:12px">ایمیل</th>
                            <th style="padding:12px">نقش</th>
                            <th style="padding:12px">تاریخ عضویت</th>
                            <th style="padding:12px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr style="border-bottom:1px solid var(--line)">
                                <td style="padding:12px"><?= e($u['name']) ?></td>
                                <td style="padding:12px" dir="ltr"><?= e($u['email']) ?></td>
                                <td style="padding:12px"><span class="pill"><?= e($u['role']) ?></span></td>
                                <td style="padding:12px"><?= fa_date($u['created_at'] ?? null) ?></td>
                                <td style="padding:12px">
                                    <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                                        <form method="post" onsubmit="return confirm('این کاربر حذف شود؟')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                            <button type="submit" class="btn btn-light" style="padding:5px 12px;font-size:11px">حذف</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- پروژه‌ها -->
        <div class="section-heading">
            <div><h2>پروژه‌ها</h2></div>
        </div>

        <?php if (empty($projects)): ?>
            <div class="empty-state" style="margin-bottom:24px"><p>پروژه‌ای ثبت نشده است.</p></div>
        <?php else: ?>
            <div style="overflow-x:auto;margin-bottom:28px">
                <table class="card" style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="text-align:right;color:var(--muted);border-bottom:2px solid var(--line)">
                            <th style="padding:12px">عنوان</th>
                            <th style="padding:12px">دسته‌بندی</th>
                            <th style="padding:12px">بودجه</th>
                            <th style="padding:12px">وضعیت</th>
                            <th style="padding:12px">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $pr): ?>
                            <tr style="border-bottom:1px solid var(--line)">
                                <td style="padding:12px"><?= e($pr['title']) ?></td>
                                <td style="padding:12px"><?= e($pr['category']) ?></td>
                                <td style="padding:12px">
                                    <?= ($pr['budget'] ?? 0) ? number_format((float)$pr['budget']) . ' تومان' : '—' ?>
                                </td>
                                <td style="padding:12px"><span class="pill"><?= e($pr['status'] ?? 'pending') ?></span></td>
                                <td style="padding:12px">
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="action" value="project_status">
                                        <input type="hidden" name="project_id" value="<?= e($pr['id']) ?>">
                                        <select name="status" onchange="this.form.submit()" style="font:inherit;padding:6px;border-radius:8px;border:1px solid var(--line)">
                                            <option value="pending"     <?= ($pr['status'] ?? '')==='pending'?'selected':'' ?>>در انتظار</option>
                                            <option value="in_progress" <?= ($pr['status'] ?? '')==='in_progress'?'selected':'' ?>>در حال اجرا</option>
                                            <option value="completed"   <?= ($pr['status'] ?? '')==='completed'?'selected':'' ?>>تکمیل شد</option>
                                            <option value="cancelled"    <?= ($pr['status'] ?? '')==='cancelled'?'selected':'' ?>>لغو شد</option>
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

<!-- صندوق پیام‌های تماس -->
<div class="section-heading" style="width:min(1200px,94%);margin:0 auto 12px">
    <div><h2>📩 صندوق پیام‌ها</h2></div>
    <span class="small-muted"><?= count(array_filter($messages, fn($m) => !$m['is_read'])) ?> پیام خوانده‌نشده</span>
</div>

<section class="content" style="padding-top:0">
    <div class="container">

        <?php if (empty($messages)): ?>
            <div class="empty-state" style="margin-bottom:24px"><p>پیامی وجود ندارد.</p></div>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
                <div class="card" style="padding:14px 18px;margin-bottom:12px;<?= $m['is_read'] ? 'opacity:.65' : 'border-right:4px solid var(--pink)' ?>">
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                        <div>
                            <b style="font-size:13px"><?= e($m['name']) ?></b>
                            <small dir="ltr" class="small-muted" style="margin-right:8px"><?= e($m['email']) ?></small>
                            <?php if ($m['subject']): ?>
                                <span class="pill" style="margin-right:8px"><?= e($m['subject']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center">
                            <small class="small-muted"><?= fa_date($m['created_at']) ?></small>
                            <?php if (!$m['is_read']): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="action" value="read_message">
                                    <input type="hidden" name="message_id" value="<?= e($m['id']) ?>">
                                    <button type="submit" class="btn btn-light" style="padding:4px 12px;font-size:11px">خوانده شد ✓</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p style="margin:8px 0 0;font-size:13px"><?= nl2br(e($m['message'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
