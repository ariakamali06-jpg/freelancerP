<?php
require_once __DIR__ . '/config.php';

/* =========================
   جستجوی سراسری
   (هنرمند + اثر + تخصص)
========================= */

$q = trim($_GET['q'] ?? '');
$artists = [];
$artworks = [];

if ($q !== '' && $p = db()) {
    $like = '%' . $q . '%';
    try {
        $s = $p->prepare(
            "SELECT id, name, specialty, city, rating, reviews, avatar, cover
             FROM artists WHERE status = 'approved'
               AND (name LIKE ? OR specialty LIKE ? OR city LIKE ? OR bio LIKE ?)
             ORDER BY rating DESC LIMIT 12"
        );
        $s->execute([$like, $like, $like, $like]);
        $artists = $s->fetchAll();
    } catch (Throwable $e) {}

    try {
        $s = $p->prepare(
            "SELECT aw.*, a.name AS artist_name
             FROM artworks aw JOIN artists a ON a.id = aw.artist_id
             WHERE aw.status != 'hidden'
               AND (aw.title LIKE ? OR aw.technique LIKE ? OR aw.description LIKE ? OR a.name LIKE ?)
             ORDER BY aw.id DESC LIMIT 12"
        );
        $s->execute([$like, $like, $like, $like]);
        $artworks = $s->fetchAll();
    } catch (Throwable $e) {}
}

$pageTitle = $q !== '' ? "جستجو: $q" : 'جستجو';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>جستجو</h1>
        <p>
            <?php if ($q === ''): ?>
                چیزی که دنبالشی را بنویس — نام هنرمند، تخصص یا عنوان اثر
            <?php else: ?>
                نتایج برای «<b><?= e($q) ?></b>» — <?= count($artists) ?> هنرمند و <?= count($artworks) ?> اثر
            <?php endif; ?>
        </p>
    </div>
</div>

<section class="content">
    <div class="container">

        <?php if ($q === ''): ?>
            <div class="empty-state">
                <p>⌕ از نوار جستجوی بالای صفحه استفاده کن.</p>
            </div>

        <?php else: ?>

            <?php if ($artists): ?>
                <div class="section-heading"><div><h2>هنرمندان</h2></div></div>
                <div class="artist-grid" style="margin-bottom:30px">
                    <?php foreach ($artists as $a): ?>
                        <article class="artist-card">
                            <a class="artist-photo" href="artist.php?id=<?= e($a['id']) ?>">
                                <img src="<?= e($a['cover'] ?: 'assets/images/work1.jpg') ?>" alt="<?= e($a['name']) ?>">
                                <span class="verified">✓ تأییدشده</span>
                            </a>
                            <div class="artist-info">
                                <img class="avatar" src="<?= e($a['avatar'] ?: 'assets/images/artist1.jpg') ?>" alt="<?= e($a['name']) ?>">
                                <div class="artist-name">
                                    <h3><?= e($a['name']) ?></h3>
                                    <p><?= e($a['specialty']) ?></p>
                                </div>
                            </div>
                            <div class="artist-meta">
                                <span>⭐ <?= e($a['rating']) ?> (<?= e($a['reviews']) ?>)</span>
                                <span>⌖ <?= e($a['city']) ?></span>
                            </div>
                            <a class="btn btn-soft btn-block" href="artist.php?id=<?= e($a['id']) ?>">مشاهده پروفایل</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($artworks): ?>
                <div class="section-heading"><div><h2>آثار</h2></div></div>
                <div class="artist-grid">
                    <?php foreach ($artworks as $w): ?>
                        <article class="artist-card">
                            <a class="artist-photo" href="artwork.php?id=<?= e($w['id']) ?>">
                                <img src="<?= e($w['image'] ?: 'assets/images/work1.jpg') ?>" alt="<?= e($w['title']) ?>">
                                <?php if ($w['status'] === 'sold'): ?>
                                    <span class="verified" style="background:#8a8a8a">فروخته شد</span>
                                <?php endif; ?>
                            </a>
                            <div class="artist-info">
                                <div class="artist-name">
                                    <h3><?= e($w['title']) ?></h3>
                                    <p><?= e($w['artist_name']) ?> · <?= e($w['technique']) ?></p>
                                </div>
                            </div>
                            <div class="artist-meta">
                                <b style="color:var(--pink-dark)"><?= fa_num($w['price']) ?> تومان</b>
                            </div>
                            <a class="btn btn-soft btn-block" href="artwork.php?id=<?= e($w['id']) ?>">مشاهده اثر</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$artists && !$artworks): ?>
                <div class="empty-state">
                    <p>نتیجه‌ای برای «<?= e($q) ?>» پیدا نشد.</p>
                    <a class="btn" href="artists.php">مشاهده همه هنرمندان</a>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
