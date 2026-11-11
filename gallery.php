<?php
require_once __DIR__ . '/config.php';

/* =========================
   گالری آثار هنری
========================= */

$p = db();
$artworks = [];
$techniques = [];
$technique = trim($_GET['technique'] ?? '');
$sort      = $_GET['sort'] ?? 'newest';

if ($p) {
    try {
        $rows = $p->query(
            "SELECT aw.*, a.name AS artist_name, a.specialty, a.avatar AS artist_avatar
             FROM artworks aw
             JOIN artists a ON a.id = aw.artist_id
             WHERE aw.status IN ('available','sold')
             ORDER BY aw.created_at DESC, aw.id DESC"
        )->fetchAll();
        $artworks = $rows;
    } catch (Throwable $e) {}
}

// فیلتر تکنیک
if ($technique !== '') {
    $artworks = array_values(array_filter($artworks, fn($w) => ($w['technique'] ?? '') === $technique));
}

// مرتب‌سازی
if ($sort === 'cheap')    usort($artworks, fn($a, $b) => ($a['price'] ?? 0) <=> ($b['price'] ?? 0));
if ($sort === 'expensive') usort($artworks, fn($a, $b) => ($b['price'] ?? 0) <=> ($a['price'] ?? 0));

// لیست تکنیک‌ها برای فیلتر
$allForTechniques = [];
if ($p) {
    try {
        $allForTechniques = $p->query(
            "SELECT DISTINCT technique FROM artworks WHERE technique IS NOT NULL AND technique != ''"
        )->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {}
}
$allForTechniques = array_values(array_filter((array)$allForTechniques));

// علاقه‌مندی‌های کاربر
$favArtworks = [];
if (is_logged_in() && $p) {
    try {
        $s = $p->prepare('SELECT artwork_id FROM favorites WHERE user_id = ? AND artwork_id IS NOT NULL');
        $s->execute([current_user()['id']]);
        $favArtworks = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {}
}

$pageTitle = 'گالری آثار';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>گالری آثار</h1>
        <p>آثار آماده‌ی خرید از هنرمندان تأییدشده — اثر موردعلاقه‌ات را انتخاب کن</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <!-- فیلترها -->
        <form class="filters" method="get" action="gallery.php">
            <div class="field" style="min-width:160px;flex:0">
                <label for="technique">تکنیک</label>
                <select id="technique" name="technique">
                    <option value="">همه تکنیک‌ها</option>
                    <?php foreach ($allForTechniques as $t): ?>
                        <option value="<?= e($t) ?>" <?= $technique === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field" style="min-width:150px;flex:0">
                <label for="sort">مرتب‌سازی</label>
                <select id="sort" name="sort">
                    <option value="newest"    <?= $sort === 'newest'    ? 'selected' : '' ?>>جدیدترین</option>
                    <option value="cheap"     <?= $sort === 'cheap'     ? 'selected' : '' ?>>ارزان‌ترین</option>
                    <option value="expensive" <?= $sort === 'expensive' ? 'selected' : '' ?>>گران‌ترین</option>
                </select>
            </div>

            <button type="submit" class="btn" style="align-self:flex-end">فیلتر</button>
            <a class="btn btn-light" style="align-self:flex-end" href="gallery.php">پاک کردن</a>
        </form>

        <p class="small-muted" style="margin-bottom:18px">
            <?= count($artworks) ?> اثر یافت شد
            <?php if ($technique): ?> — تکنیک: <?= e($technique) ?><?php endif; ?>
        </p>

        <?php if (empty($artworks)): ?>
            <div class="empty-state">
                <p>اثری با این فیلترها یافت نشد.</p>
                <a class="btn" href="gallery.php">نمایش همه آثار</a>
            </div>
        <?php else: ?>
            <div class="artist-grid">
                <?php foreach ($artworks as $w): ?>
                    <article class="artist-card artwork-card" data-artwork-id="<?= e($w['id']) ?>">
                        <a class="artist-photo" href="artwork.php?id=<?= e($w['id']) ?>">
                            <img src="<?= e($w['image'] ?: 'assets/images/work1.jpg') ?>"
                                 alt="<?= e($w['title']) ?>">
                            <?php if (($w['status'] ?? '') === 'sold'): ?>
                                <span class="verified" style="background:#8a8a8a">فروخته شد</span>
                            <?php else: ?>
                                <span class="verified">✓ موجود</span>
                            <?php endif; ?>
                        </a>

                        <div class="artist-info">
                            <img class="avatar"
                                 src="<?= e($w['artist_avatar'] ?: 'assets/images/artist1.jpg') ?>"
                                 alt="<?= e($w['artist_name']) ?>">
                            <div class="artist-name">
                                <h3><?= e($w['title']) ?></h3>
                                <p><?= e($w['artist_name']) ?> · <?= e($w['technique']) ?></p>
                            </div>

                            <!-- قلب علاقه‌مندی -->
                            <button type="button"
                                    class="fav-btn <?= in_array((int)$w['id'], $favArtworks, true) ? 'fav-active' : '' ?>"
                                    data-type="artwork"
                                    data-id="<?= e($w['id']) ?>"
                                    data-auth="<?= is_logged_in() ? '1' : '0' ?>"
                                    aria-label="افزودن به علاقه‌مندی‌ها">♡</button>
                        </div>

                        <div class="artist-meta">
                            <span><?= e($w['size'] ?? '') ?></span>
                            <b style="color:var(--pink-dark)"><?= fa_num($w['price']) ?> تومان</b>
                        </div>

                        <?php if (($w['status'] ?? '') === 'available'): ?>
                            <form method="post" action="cart.php" style="margin-top:10px">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="artwork_id" value="<?= e($w['id']) ?>">
                                <input type="hidden" name="title" value="<?= e($w['title']) ?>">
                                <input type="hidden" name="artist_id" value="<?= e($w['artist_id']) ?>">
                                <input type="hidden" name="price" value="<?= e($w['price']) ?>">
                                <button type="submit" class="btn btn-soft btn-block">افزودن به سبد 🛒</button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="btn btn-light btn-block" disabled style="margin-top:10px;opacity:.5">فروخته شد</button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
