<?php
require_once __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Try to load from DB first, fall back to demo
$artist = null;
$p = db();
if ($p && $id > 0) {
    try {
        $s = $p->prepare('SELECT * FROM artists WHERE id = ? LIMIT 1');
        $s->execute([$id]);
        $artist = $s->fetch();
    } catch (Throwable $e) {}
}

if (!$artist) {
    $all = all_artists();
    $artist = array_values(array_filter($all, fn($a) => (int)$a['id'] === $id));
    $artist = $artist[0] ?? $all[0];
}

$pageTitle = e($artist['name']);
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1><?= e($artist['name']) ?></h1>
        <p><?= e($artist['specialty']) ?> · <?= e($artist['city']) ?></p>
    </div>
</div>

<section class="content">
    <div class="container">

        <div class="detail-layout">
            <!-- ===== ستون چپ: نمونه‌کارها ===== -->
            <div>
                <img
                    class="detail-cover"
                    src="<?= e($artist['cover'] ?? 'assets/images/work1.jpg') ?>"
                    alt="نمونه‌کار اصلی <?= e($artist['name']) ?>"
                >

                <!-- اطلاعات کلی -->
                <div class="detail-top">
                    <img
                        class="detail-avatar"
                        src="<?= e($artist['avatar'] ?? 'assets/images/artist1.jpg') ?>"
                        alt="<?= e($artist['name']) ?>"
                    >
                    <div>
                        <h3 style="margin:0 0 4px"><?= e($artist['name']) ?></h3>
                        <span class="pill"><?= e($artist['specialty']) ?></span>
                        <span class="pill" style="margin-right:6px">⌖ <?= e($artist['city']) ?></span>
                    </div>
                </div>

                <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:16px">
                    <div style="background:var(--pink-light);border-radius:12px;padding:10px 14px;text-align:center">
                        <b style="color:var(--pink);font-size:20px">⭐ <?= e($artist['rating']) ?></b>
                        <small class="small-muted" style="display:block">امتیاز</small>
                    </div>
                    <div style="background:var(--pink-light);border-radius:12px;padding:10px 14px;text-align:center">
                        <b style="color:var(--pink);font-size:20px">💬 <?= e($artist['reviews']) ?></b>
                        <small class="small-muted" style="display:block">نظر</small>
                    </div>
                    <div style="background:var(--pink-light);border-radius:12px;padding:10px 14px;text-align:center">
                        <b style="color:var(--pink);font-size:20px">✓</b>
                        <small class="small-muted" style="display:block">تأییدشده</small>
                    </div>
                </div>

                <h3 style="margin-top:20px">درباره <?= e($artist['name']) ?></h3>
                <p><?= e($artist['bio']) ?></p>

                <?php
                /* --------- نظرات واقعی --------- */
                $reviews = [];
                if ($p) {
                    try {
                        $s = $p->prepare(
                            "SELECT r.*, u.name AS user_name
                             FROM reviews r JOIN users u ON u.id = r.user_id
                             WHERE r.artist_id = ? ORDER BY r.created_at DESC LIMIT 10"
                        );
                        $s->execute([(int)$artist['id']]);
                        $reviews = $s->fetchAll();
                    } catch (Throwable $e) {}
                }
                ?>

                <?php if ($reviews): ?>
                    <h3 style="margin-top:24px">نظرات مشتری‌ها</h3>
                    <?php foreach ($reviews as $rv): ?>
                        <div class="card" style="padding:12px 16px;margin-bottom:10px">
                            <b style="font-size:13px"><?= e($rv['user_name']) ?></b>
                            <span style="color:var(--pink);font-size:12px"> · <?= str_repeat('⭐', (int)$rv['rating']) ?></span>
                            <small class="small-muted" style="display:block;font-size:11px"><?= fa_date($rv['created_at']) ?></small>
                            <?php if ($rv['comment']): ?>
                                <p style="margin:6px 0 0;font-size:13px"><?= e($rv['comment']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php
                /* --------- فرم ثبت نظر (فقط با سفارش تکمیل‌شده) --------- */
                $canReview = false;
                if ($p && is_logged_in() && is_customer()) {
                    try {
                        $s = $p->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND artist_id = ? AND status = 'completed'");
                        $s->execute([(int)$user['id'], (int)$artist['id']]);
                        $canReview = (bool)$s->fetchColumn();

                        if ($canReview) {
                            $s2 = $p->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ? AND artist_id = ?');
                            $s2->execute([(int)$user['id'], (int)$artist['id']]);
                            $canReview = !(bool)$s2->fetchColumn();
                        }
                    } catch (Throwable $e) {}
                }
                ?>

                <?php if ($canReview): ?>
                    <div class="form-card" style="max-width:100%;margin-top:18px">
                        <h3 style="margin-top:0">تجربه‌ات را بنویس</h3>
                        <form method="post" action="review-submit.php">
                            <input type="hidden" name="artist_id" value="<?= e($artist['id']) ?>">
                            <div class="field">
                                <label for="rating">امتیاز</label>
                                <select id="rating" name="rating" required>
                                    <option value="5">⭐⭐⭐⭐⭐ — عالی</option>
                                    <option value="4">⭐⭐⭐⭐ — خوب</option>
                                    <option value="3">⭐⭐⭐ — متوسط</option>
                                    <option value="2">⭐⭐ — ضعیف</option>
                                    <option value="1">⭐ — بد</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="comment">نظر</label>
                                <textarea id="comment" name="comment" rows="3" placeholder="کیفیت کار، تعامل و تحویل چطور بود؟"></textarea>
                            </div>
                            <button type="submit" class="btn">ثبت نظر</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== ستون راست: سفارش ===== -->
            <div>
                <div class="form-card">
                    <h2>سفارش از <?= e($artist['name']) ?></h2>
                    <p class="small-muted">فرم زیر را تکمیل کن تا درخواستت برای این هنرمند ارسال شود.</p>

                    <form method="post" action="project-new.php">
                        <input type="hidden" name="artist_id" value="<?= e($artist['id']) ?>">

                        <div class="field">
                            <label for="title">عنوان سفارش</label>
                            <input type="text" id="title" name="title" required placeholder="مثلاً: پرتره پدرم">
                        </div>

                        <div class="field">
                            <label for="desc">توضیحات</label>
                            <textarea id="desc" name="description" rows="4" placeholder="جزئیات طرح مورد نظرت را بنویس…"></textarea>
                        </div>

                        <div class="field">
                            <label for="size">ابعاد</label>
                            <select id="size" name="size">
                                <option value="A4">A4</option>
                                <option value="A3">A3</option>
                                <option value="A2">A2</option>
                                <option value="A1">A1</option>
                                <option value="custom">سفارشی</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="deadline">مهلت تحویل</label>
                            <input type="date" id="deadline" name="deadline">
                        </div>

                        <div class="field">
                            <label for="budget">بودجه پیشنهادی (تومان)</label>
                            <input type="number" id="budget" name="budget" min="0" placeholder="مثلاً: 500000">
                        </div>

                        <button type="submit" class="btn btn-block">
                            ارسال درخواست ↻
                        </button>
                    </form>

                    <div style="margin-top:16px;padding:12px;background:var(--pink-light);border-radius:12px;font-size:12px;color:var(--muted)">
                        <b>راهنما:</b> پس از ارسال درخواست، هنرمند در صورت تمایل با شما تماس می‌گیرد.
                        پرداخت پس از تأیید نهایی و توافق انجام می‌شود.
                    </div>

                    <a class="btn btn-light btn-block" style="margin-top:10px" href="artists.php">
                        ← بازگشت به لیست
                    </a>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
