<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'درباره ما';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>درباره هنرمندشو</h1>
        <p>جایی برای ارتباط میان ایده‌های شما و هنرمندان خلاق</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <!-- معرفی -->
        <div class="form-card" style="margin-bottom:25px">
            <h2>ما چه کار می‌کنیم؟</h2>
            <p>
                <b>هنرمندشو</b> یک بازارگاه آنلاین برای سفارش آثار هنری است.
                اگر به دنبال یک نقاشی اختصاصی هستید — پرتره، تصویرسازی، آبرنگ یا
                نقاشی دیجیتال — کافی است ایده‌تان را ثبت کنید و پیشنهاد
                هنرمندان را مقایسه کنید. از سوی دیگر، هنرمندان می‌توانند
                نمونه‌کارهای خود را نمایش دهند و با مشتریان ارتباط برقرار کنند.
            </p>
            <p>
                مأموریت ما ساده است: حمایت از هنرمندان مستقل ایرانی و
                ساختن پلی میان ایده و اثر.
            </p>
        </div>

        <!-- آمار -->
        <div class="section-heading">
            <div>
                <h2>هنرمندشو در یک نگاه</h2>
            </div>
        </div>

        <div class="detail-layout" style="margin-bottom:30px">
            <div class="card" style="background:var(--pink-light);border:1px solid var(--line);border-radius:18px;padding:22px;text-align:center">
                <b style="color:var(--pink);font-size:32px;display:block">+۱۲۰</b>
                <span class="small-muted">هنرمند فعال</span>
            </div>
            <div class="card" style="background:var(--pink-light);border:1px solid var(--line);border-radius:18px;padding:22px;text-align:center">
                <b style="color:var(--pink);font-size:32px;display:block">+۳۵۰</b>
                <span class="small-muted">سفارش تکمیل‌شده</span>
            </div>
            <div class="card" style="background:var(--pink-light);border:1px solid var(--line);border-radius:18px;padding:22px;text-align:center">
                <b style="color:var(--pink);font-size:32px;display:block">۹۸٪</b>
                <span class="small-muted">رضایت مشتریان</span>
            </div>
        </div>

        <!-- ارزش‌ها -->
        <div class="section-heading">
            <div>
                <h2>ارزش‌های ما</h2>
            </div>
        </div>

        <div class="detail-layout" style="grid-template-columns:1fr 1fr 1fr;margin-bottom:30px">
            <div class="card">
                <h3 style="color:var(--pink);margin-top:0">🎨 اصالت</h3>
                <p class="small-muted">
                    همه آثار دست‌ساز و منحصربه‌فرد هستند؛ هیچ اثر کپی یا چاپی
                    در هنرمندشو جای ندارد.
                </p>
            </div>
            <div class="card">
                <h3 style="color:var(--pink);margin-top:0">🛡 امنیت</h3>
                <p class="small-muted">
                    پرداخت‌ها پس از تأیید سفارش انجام می‌شود و اطلاعات مشتریان
                    نزد ما محفوظ است.
                </p>
            </div>
            <div class="card">
                <h3 style="color:var(--pink);margin-top:0">🤝 شفافیت</h3>
                <p class="small-muted">
                    قیمت، مهلت و جزئیات هر سفارش از ابتدا روشن است؛ بدون هزینه
                    پنهان.
                </p>
            </div>
        </div>

        <!-- تیم -->
        <div class="section-heading">
            <div>
                <h2>تیم ما</h2>
            </div>
        </div>

        <div class="artist-grid" style="margin-bottom:35px">
            <?php
            $team = [
                ['name' => 'سارا یوسفی',  'role' => 'هم‌بنیان‌گذار و مدیر هنری', 'img' => 'assets/images/artist1.jpg'],
                ['name' => 'مهراد حیدری', 'role' => 'هم‌بنیان‌گذار و مدیر فنی',   'img' => 'assets/images/artist2.jpg'],
                ['name' => 'نیلوفر احمدی','role' => 'مدیر ارتباط با هنرمندان',    'img' => 'assets/images/artist3.jpg'],
            ];
            foreach ($team as $member): ?>
                <article class="artist-card">
                    <a class="artist-photo" href="contact.php">
                        <img src="<?= e($member['img']) ?>" alt="<?= e($member['name']) ?>">
                    </a>
                    <div class="artist-info">
                        <img class="avatar" src="<?= e($member['img']) ?>" alt="<?= e($member['name']) ?>">
                        <div class="artist-name">
                            <h3><?= e($member['name']) ?></h3>
                            <p><?= e($member['role']) ?></p>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- دعوت به اقدام -->
        <div class="bottom-banner" style="margin:0 auto">
            <div>
                <span class="eyebrow">آماده‌ی شروعی؟</span>
                <h2>اولین اثر هنری‌ات را سفارش بده</h2>
                <p>ثبت پروژه کمتر از ۵ دقیقه طول می‌کشد.</p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a class="btn" href="project-new.php">ثبت پروژه ←</a>
                <a class="btn btn-light" href="artists.php">مشاهده هنرمندان</a>
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
