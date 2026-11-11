<?php
require_once __DIR__ . '/config.php';

/* =========================
   صفحه جزئیات اثر هنری
========================= */

$id = (int)($_GET['id'] ?? 0);
$p = db();
$artwork = null;

if ($p && $id > 0) {
    try {
        $s = $p->prepare(
            "SELECT aw.*, a.name AS artist_name, a.specialty, a.city,
                    a.avatar AS artist_avatar, a.rating AS artist_rating
             FROM artworks aw
             JOIN artists a ON a.id = aw.artist_id
             WHERE aw.id = ? LIMIT 1"
        );
        $s->execute([$id]);
        $artwork = $s->fetch();
    } catch (Throwable $e) {}
}

if (!$artwork) {
    flash('چنین اثری یافت نشد.', 'error');
    header('Location: gallery.php');
    exit;
}

$favArtworks = [];
if (is_logged_in() && $p) {
    try {
        $s = $p->prepare('SELECT artwork_id FROM favorites WHERE user_id = ? AND artwork_id IS NOT NULL');
        $s->execute([current_user()['id']]);
        $favArtworks = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {}
}

$otherWorks = [];
if ($p) {
    try {
        $s = $p->prepare(
            "SELECT id, title, image, price, status FROM artworks
             WHERE artist_id = ? AND id != ? AND status != 'hidden'
             LIMIT 3"
        );
        $s->execute([(int)$artwork['artist_id'], $id]);
        $otherWorks = $s->fetchAll();
    } catch (Throwable $e) {}
}

$pageTitle = $artwork['title'];
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1><?= e($artwork['title']) ?></h1>
        <p><?= e($artwork['artist_name']) ?> · <?= e($artwork['technique']) ?></p>
    </div>
</div>

<section class="content">
    <div class="container">
        <div class="detail-layout" style="grid-template-columns:1.4fr 1fr">

            <!-- ===== تصویر اثر ===== -->
            <div>
                <img class="detail-cover"
                     src="<?= e($artwork['image'] ?: 'assets/images/work1.jpg') ?>"
                     alt="<?= e($artwork['title']) ?>">

                <?php if ($otherWorks): ?>
                    <h3 style="margin-top:24px">آثار دیگر این هنرمند</h3>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:10px">
                        <?php foreach ($otherWorks as $ow): ?>
                            <a href="artwork.php?id=<?= e($ow['id']) ?>" class="card" style="padding:8px;display:block">
                                <img src="<?= e($ow['image'] ?: 'assets/images/work1.jpg') ?>"
                                     alt="<?= e($ow['title']) ?>"
                                     style="width:100%;height:110px;object-fit:cover;border-radius:8px">
                                <p style="margin:8px 0 0;font-size:12px;font-weight:600"><?= e($ow['title']) ?></p>
                                <small class="small-muted"><?= fa_num($ow['price']) ?> تومان</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== اطلاعات و خرید ===== -->
            <div>
                <div class="form-card" style="max-width:100%">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                        <div>
                            <h2 style="margin:0 0 4px"><?= e($artwork['title']) ?></h2>
                            <span class="pill"><?= e($artwork['technique']) ?></span>
                            <?php if ($artwork['size']): ?>
                                <span class="pill" style="margin-right:6px">ابعاد: <?= e($artwork['size']) ?></span>
                            <?php endif; ?>
                        </div>

                        <button type="button"
                                class="fav-btn <?= in_array((int)$artwork['id'], $favArtworks, true) ? 'fav-active' : '' ?>"
                                data-type="artwork"
                                data-id="<?= e($artwork['id']) ?>"
                                data-auth="<?= is_logged_in() ? '1' : '0' ?>"
                                style="font-size:26px"
                                aria-label="افزودن به علاقه‌مندی‌ها">♡</button>
                    </div>

                    <p style="margin:16px 0"><?= e($artwork['description'] ?? 'بدون توضیحات.') ?></p>

                    <div style="background:var(--pink-light);border-radius:14px;padding:14px 18px;margin-bottom:16px">
                        <b style="color:var(--pink);font-size:22px"><?= fa_num($artwork['price']) ?> تومان</b>
                        <?php if (($artwork['status'] ?? '') === 'sold'): ?>
                            <span class="pill" style="background:#eee;margin-right:8px">فروخته شد</span>
                        <?php endif; ?>
                    </div>

                    <a href="artist.php?id=<?= e($artwork['artist_id']) ?>"
                       style="display:flex;gap:10px;align-items:center;margin-bottom:16px">
                        <img class="avatar" style="width:40px;height:40px;border-radius:50%"
                             src="<?= e($artwork['artist_avatar'] ?: 'assets/images/artist1.jpg') ?>"
                             alt="<?= e($artwork['artist_name']) ?>">
                        <span>
                            <b><?= e($artwork['artist_name']) ?></b>
                            <small class="small-muted" style="display:block">
                                <?= e($artwork['specialty']) ?> · ⭐ <?= e($artwork['artist_rating']) ?> · <?= e($artwork['city']) ?>
                            </small>
                        </span>
                    </a>

                    <?php if (($artwork['status'] ?? '') === 'available'): ?>
                        <form method="post" action="cart.php">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="artwork_id" value="<?= e($artwork['id']) ?>">
                            <input type="hidden" name="title" value="<?= e($artwork['title']) ?>">
                            <input type="hidden" name="artist_id" value="<?= e($artwork['artist_id']) ?>">
                            <input type="hidden" name="price" value="<?= e($artwork['price']) ?>">
                            <button type="submit" class="btn btn-block">افزودن به سبد خرید 🛒</button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-light btn-block" disabled style="opacity:.5">فروخته شد</button>
                    <?php endif; ?>

                    <a class="btn btn-light btn-block" style="margin-top:10px" href="gallery.php">← بازگشت به گالری</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
