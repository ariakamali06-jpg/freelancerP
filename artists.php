<?php
require_once __DIR__ . '/config.php';

$pageTitle = 'هنرمندان';
$specialty = trim($_GET['specialty'] ?? '');
$city      = trim($_GET['city'] ?? '');
$sort      = $_GET['sort'] ?? 'newest';

$artists = all_artists();

// فیلتر بر اساس تخصص
if ($specialty !== '') {
    $artists = array_filter($artists, fn($a) => stripos($a['specialty'], $specialty) !== false);
}

// فیلتر بر اساس شهر
if ($city !== '') {
    $artists = array_filter($artists, fn($a) => stripos($a['city'] ?? '', $city) !== false);
}

// مرتب‌سازی
usort($artists, function($a, $b) use ($sort) {
    if ($sort === 'rating') return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
    if ($sort === 'reviews') return ($b['reviews'] ?? 0) <=> ($a['reviews'] ?? 0);
    return $a['id'] <=> $b['id'];
});

// لیست شهرهای منحصربه‌فرد
$allCities = array_unique(array_column(all_artists(), 'city'));
sort($allCities);

// علاقه‌مندی‌های کاربر (هنرمند)
$favArtists = [];
if (is_logged_in() && ($p = db())) {
    try {
        $s = $p->prepare('SELECT artist_id FROM favorites WHERE user_id = ? AND artist_id IS NOT NULL');
        $s->execute([current_user()['id']]);
        $favArtists = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {}
}

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>هنرمندان</h1>
        <p>پروفایل و نمونه‌کار هنرمندان نقاش را مشاهده کن</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <!-- فیلترها -->
        <form class="filters" method="get" action="artists.php">
            <div class="field" style="min-width:160px;flex:0">
                <label for="specialty">تخصص</label>
                <select id="specialty" name="specialty">
                    <option value="">همه تخصص‌ها</option>
                    <option value="رنگ روغن"      <?= $specialty==='رنگ روغن'       ?'selected':'' ?>>رنگ روغن</option>
                    <option value="آبرنگ"         <?= $specialty==='آبرنگ'            ?'selected':'' ?>>آبرنگ</option>
                    <option value="سیاه‌قلم"       <?= $specialty==='سیاه‌قلم'         ?'selected':'' ?>>سیاه‌قلم</option>
                    <option value="اکریلیک"       <?= $specialty==='اکریلیک'         ?'selected':'' ?>>اکریلیک</option>
                    <option value="مداد رنگی"     <?= $specialty==='مداد رنگی'       ?'selected':'' ?>>مداد رنگی</option>
                    <option value="نقاشی دیجیتال" <?= $specialty==='نقاشی دیجیتال'   ?'selected':'' ?>>نقاشی دیجیتال</option>
                </select>
            </div>

            <div class="field" style="min-width:140px;flex:0">
                <label for="city">شهر</label>
                <select id="city" name="city">
                    <option value="">همه شهرها</option>
                    <?php foreach ($allCities as $c): ?>
                        <option value="<?= e($c) ?>" <?= $city===$c?'selected':'' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field" style="min-width:140px;flex:0">
                <label for="sort">مرتب‌سازی</label>
                <select id="sort" name="sort">
                    <option value="newest"  <?= $sort==='newest'  ?'selected':'' ?>>جدیدترین</option>
                    <option value="rating"   <?= $sort==='rating'   ?'selected':'' ?>>بیشترین امتیاز</option>
                    <option value="reviews"  <?= $sort==='reviews'  ?'selected':'' ?>>بیشترین نظرات</option>
                </select>
            </div>

            <button type="submit" class="btn" style="align-self:flex-end">فیلتر</button>
            <a class="btn btn-light" style="align-self:flex-end" href="artists.php">پاک کردن</a>
        </form>

        <!-- تعداد نتایج -->
        <p class="small-muted" style="margin-bottom:18px">
            <?= count($artists) ?> هنرمند یافت شد
            <?php if ($specialty): ?> — تخصص: <?= e($specialty) ?><?php endif; ?>
            <?php if ($city): ?> — شهر: <?= e($city) ?><?php endif; ?>
        </p>

        <?php if (empty($artists)): ?>
            <div class="empty-state">
                <p>هنرمندی با این فیلترها یافت نشد.</p>
                <a class="btn" href="artists.php">نمایش همه</a>
            </div>
        <?php else: ?>
            <div class="artist-grid">
                <?php foreach ($artists as $artist): ?>
                    <article class="artist-card">
                        <a class="artist-photo" href="artist.php?id=<?= e($artist['id']) ?>">
                            <img
                                src="<?= e($artist['cover'] ?? 'assets/images/work1.jpg') ?>"
                                alt="نمونه‌کار <?= e($artist['name']) ?>"
                            >
                            <span class="verified">✓ تأییدشده</span>
                        </a>

                        <div class="artist-info">
                            <img
                                class="avatar"
                                src="<?= e($artist['avatar'] ?? 'assets/images/artist1.jpg') ?>"
                                alt="<?= e($artist['name']) ?>"
                            >
                            <div class="artist-name">
                                <h3><?= e($artist['name']) ?></h3>
                                <p><?= e($artist['specialty']) ?></p>
                            </div>

                            <button type="button"
                                    class="fav-btn <?= in_array((int)$artist['id'], $favArtists ?? [], true) ? 'fav-active' : '' ?>"
                                    data-type="artist"
                                    data-id="<?= e($artist['id']) ?>"
                                    data-auth="<?= is_logged_in() ? '1' : '0' ?>"
                                    aria-label="افزودن به علاقه‌مندی‌ها">♡</button>
                        </div>

                        <div class="artist-meta">
                            <span>⭐ <?= e($artist['rating']) ?> (<?= e($artist['reviews']) ?>)</span>
                            <span>⌖ <?= e($artist['city']) ?></span>
                        </div>

                        <p style="font-size:11px;color:var(--muted);padding:4px 3px 0;margin:0">
                            <?= e(mb_substr($artist['bio'], 0, 72)) ?><?= mb_strlen($artist['bio']) > 72 ? '…' : '' ?>
                        </p>

                        <a class="btn btn-soft btn-block" href="artist.php?id=<?= e($artist['id']) ?>">
                            مشاهده پروفایل
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
