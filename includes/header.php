
<?php
$pageTitle = $pageTitle ?? 'هنرمندشو';
$user = current_user();

// تشخیص صفحه فعلی برای هایلایت لینک فعال منو
$currentPage = basename($_SERVER['PHP_SELF']);
$navItems = [
    'index.php'   => 'خانه',
    'artists.php' => 'هنرمندان',
    'about.php'   => 'درباره ما',
    'contact.php' => 'تماس با ما',
];
?>
<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | هنرمندشو</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="site-header">
    <div class="container nav">

        <!-- لوگو -->
        <a class="brand" href="index.php">
            <img
                class="brand-logo"
                src="assets/images/honarmandsho-loogo.png"
                alt="لوگوی هنرمندشو"
            >
        </a>

        <!-- منوی اصلی (دسکتاپ) -->
        <nav class="nav-links">
            <?php foreach ($navItems as $itemPage => $itemLabel): ?>
                <a href="<?= e($itemPage) ?>"
                   class="<?= $currentPage === $itemPage ? 'active' : '' ?>">
                    <?= e($itemLabel) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- دکمه همبرگری (فقط موبایل) -->
        <button class="hamburger-btn" id="hamburger-btn" type="button"
                aria-label="باز کردن منو" aria-controls="mobile-menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- حساب کاربری -->
        <div class="nav-actions">

            <?php if ($user): ?>

                <span class="small-muted">
                    <?= e($user['name']) ?>
                </span>

                <?php if ($user['role'] === 'artist'): ?>

                    <a class="btn btn-light"
                       href="artist-dashboard.php">
                        پنل هنرمند
                    </a>

                <?php elseif ($user['role'] === 'customer'): ?>

                    <a class="btn btn-light"
                       href="profile.php">
                        پروفایل من
                    </a>

                    <a class="btn btn-light"
                       href="project-new.php">
                        ثبت پروژه
                    </a>

                    <!-- سبد خرید مشتری -->
                    <a class="btn btn-light cart-link"
                      href="cart.php"
                       aria-label="سبد خرید و سفارش‌های من"
                       title="سبد خرید و سفارش‌های من">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="21"
                            height="21"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true">

                            <circle cx="9" cy="21" r="1"/>
                            <circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>

                        <span>سبد خرید</span>
                    </a>

                <?php elseif ($user['role'] === 'admin'): ?>

                    <a class="btn btn-light"
                       href="admin.php">
                        پنل مدیریت
                    </a>

                <?php endif; ?>

                <a class="btn btn-light" href="logout.php">
                    خروج
                </a>

            <?php else: ?>

                <a class="btn btn-light" href="login.php">
                    ورود / ثبت‌نام
                </a>

            <?php endif; ?>

        </div>

    </div>
</header>

<!-- منوی موبایل -->
<div class="mobile-menu" id="mobile-menu" aria-hidden="true">
    <button class="mobile-menu-close" id="mobile-menu-close" type="button"
            aria-label="بستن منو">✕</button>

    <?php foreach ($navItems as $itemPage => $itemLabel): ?>
        <a href="<?= e($itemPage) ?>"
           class="<?= $currentPage === $itemPage ? 'active' : '' ?>">
            <?= e($itemLabel) ?>
        </a>
    <?php endforeach; ?>

    <?php if ($user): ?>
        <div class="mobile-menu-user">
            <span class="small-muted"><?= e($user['name']) ?></span>
        </div>
        <?php if ($user['role'] === 'artist'): ?>
            <a href="artist-dashboard.php">پنل هنرمند</a>
        <?php elseif ($user['role'] === 'customer'): ?>
            <a href="profile.php">پروفایل من</a>
            <a href="project-new.php">ثبت پروژه</a>
            <a href="cart.php">سبد خرید</a>
        <?php elseif ($user['role'] === 'admin'): ?>
            <a href="admin.php">پنل مدیریت</a>
        <?php endif; ?>
        <a href="logout.php">خروج</a>
    <?php else: ?>
        <a href="login.php">ورود / ثبت‌نام</a>
    <?php endif; ?>
</div>
<div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>

<main>

<?php show_flash(); ?>
