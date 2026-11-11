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
