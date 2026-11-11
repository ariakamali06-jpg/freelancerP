
<?php
require_once __DIR__ . '/config.php';

$artists = array_slice(all_artists(), 0, 6);
$pageTitle = 'خانه';

// علاقه‌مندی‌های کاربر (هنرمند)
$favArtists = [];
if (is_logged_in() && ($p = db())) {
    try {
        $s = $p->prepare('SELECT artist_id FROM favorites WHERE user_id = ? AND artist_id IS NOT NULL');
        $s->execute([current_user()['id']]);
        $favArtists = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {}
}

// آخرین آثار گالری
$latestWorks = [];
if ($p) {
    try {
        $latestWorks = $p->query(
            "SELECT aw.id, aw.title, aw.image, aw.price, aw.status
             FROM artworks aw WHERE aw.status != 'hidden'
             ORDER BY aw.id DESC LIMIT 3"
        )->fetchAll();
    } catch (Throwable $e) {}
}

require __DIR__ . '/includes/header.php';
?>

<!-- بخش بنر اصلی -->
<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">دنیای خلاقیت و هنر</span>

        <h1>
            ایده‌هات را به
            <br>
            <em>یک اثر هنری</em> تبدیل کن
        </h1>

        <p>
            با بهترین هنرمندان نقاش ارتباط بگیر، نمونه‌کارها را ببین
            و نقاشی اختصاصی خودت را سفارش بده.
        </p>

        <div class="hero-actions">
            <a class="btn" href="project-new.php">
                ＋ ثبت پروژه جدید
            </a>

            <a class="btn btn-light" href="artists.php">
                ⌕ پیدا کردن هنرمند
            </a>
        </div>

        <div class="trust-row">
            <div>
                <b>✓</b>
                <span>
                    هنرمندان تأییدشده
                    <small>پروفایل و نمونه‌کار</small>
                </span>
            </div>

            <div>
                <b>♡</b>
                <span>
                    سفارش مطابق سلیقه شما
                    <small>انتخاب سبک و بودجه</small>
                </span>
            </div>

            <div>
                <b>♧</b>
                <span>
                    ارتباط مستقیم
                    <small>گفت‌وگو با هنرمند</small>
                </span>
            </div>
        </div>
    </div>

    <div class="hero-art">
        <img
            src="assets/images/banner.jpg"
            alt="هنرمند در حال نقاشی"
        >

        <div class="image-note">
            <span class="note-icon">✿</span>

            <span>
                <b>هنر، با سلیقه‌ی تو</b>
                <small>از ایده تا اثر نهایی</small>
            </span>
        </div>
    </div>
</section>


<!-- دسته‌بندی تکنیک‌های نقاشی -->
<section class="section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">انتخاب کن و کشف کن</span>
            <h2>دسته‌بندی تکنیک‌های نقاشی</h2>
        </div>

        <a class="text-link" href="artists.php">
            مشاهده همه ←
        </a>
    </div>

    <?php
    $cats = [
        ['رنگ روغن', 'catt-oil.jpg', '◉'],
        ['آبرنگ', 'ccat-watercolor.jpg', '◌'],
        ['سیاه‌قلم', 'catt-pencil.jpg', '✎'],
        ['اکریلیک', 'ccat-acrylic.jpg', '✿'],
        ['مداد رنگی', 'catt-pencilcolor.jpg', '✐'],
        ['نقاشی دیجیتال', 'ccat-digital.jpg', '▧'],
        ['سبک فانتزی', 'ccat-fantasy.jpg', '☆'],
    ];
    ?>

    <div class="category-grid">
        <?php foreach ($cats as $category): ?>
            <a
                class="category-card"
                href="artists.php?specialty=<?= urlencode($category[0]) ?>"
            >
                <img
                    src="assets/images/<?= e($category[1]) ?>"
                    alt="<?= e($category[0]) ?>"
                >

                <span class="category-label">
                    <i><?= e($category[2]) ?></i>
                    <?= e($category[0]) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>


<!-- جدیدترین آثار گالری -->
<?php if ($latestWorks): ?>
<section class="section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">تازه‌ها</span>
            <h2>جدیدترین آثار گالری</h2>
        </div>
        <a class="text-link" href="gallery.php">مشاهده همه آثار ←</a>
    </div>

    <div class="category-grid">
        <?php foreach ($latestWorks as $w): ?>
            <a class="category-card" href="artwork.php?id=<?= e($w['id']) ?>">
                <img src="<?= e($w['image'] ?: 'assets/images/work1.jpg') ?>" alt="<?= e($w['title']) ?>">
                <span class="category-label">
                    <?= e($w['title']) ?> — <?= fa_num($w['price']) ?> تومان
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- هنرمندان تأییدشده -->
<section class="section artists-section">
    <div class="section-heading">
        <div>
            <span class="eyebrow">استعدادهای درخشان</span>
            <h2>هنرمندان تأییدشده</h2>
        </div>

        <a class="text-link" href="artists.php">
            مشاهده همه هنرمندان ←
        </a>
    </div>

    <div class="artist-grid">
        <?php foreach ($artists as $artist): ?>
            <article class="artist-card">

                <a
                    class="artist-photo"
                    href="artist.php?id=<?= e($artist['id']) ?>"
                >
                    <img
                        src="<?= e(
                            $artist['cover'] ?: 'assets/images/work1.jpg'
                        ) ?>"
                        alt="نمونه‌کار <?= e($artist['name']) ?>"
                    >

                    <span class="verified">
                        ✓ تأییدشده
                    </span>
                </a>

                <div class="artist-info">
                    <img
                        class="avatar"
                        src="<?= e(
                            $artist['avatar'] ?: 'assets/images/artist1.jpg'
                        ) ?>"
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
                    <span>
                        ⭐ <?= e($artist['rating']) ?>
                        (<?= e($artist['reviews']) ?>)
                    </span>

                    <span>
                        ⌖ <?= e($artist['city']) ?>
                    </span>
                </div>

                <a
                    class="btn btn-soft btn-block"
                    href="artist.php?id=<?= e($artist['id']) ?>"
                >
                    مشاهده پروفایل
                </a>

            </article>
        <?php endforeach; ?>
    </div>
</section>


<!-- بنر پایانی دعوت به ثبت پروژه -->
<section class="bottom-banner">
    <div>
        <span class="eyebrow">
            وقتشه ایده‌ات رو زنده کنی
        </span>

        <h2>
            نقاشی دلخواهت فقط چند قدم فاصله دارد
        </h2>

        <p>
            پروژه‌ات را ثبت کن و پیشنهاد هنرمندان را مقایسه کن.
        </p>
    </div>

    <a class="btn" href="project-new.php">
        ثبت پروژه من ←
    </a>
</section>


<?php
require __DIR__ . '/includes/footer.php';
?>
