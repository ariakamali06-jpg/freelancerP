<?php
// تنظیمات اتصال دیتابیس برای Laragon
session_start();
define('SITE_NAME', 'هنرمندشو');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'honarmandsho_v2');
define('DB_USER', 'root');
define('DB_PASS', '');
function db(): ?PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (Throwable $e) { return null; }
}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
require_once __DIR__ . '/includes/jalali.php';
function url(string $path=''): string { return $path === '' ? 'index.php' : $path; }
function flash(string $message, string $type='success'): void { $_SESSION['flash'] = ['message'=>$message, 'type'=>$type]; }
function show_flash(): void { if (!empty($_SESSION['flash'])) { $f=$_SESSION['flash']; echo '<div class="notice '.e($f['type']).'">'.e($f['message']).'</div>'; unset($_SESSION['flash']); } }
function demo_artists(): array { return [
 ['id'=>1,'name'=>'سارا یوسفی','specialty'=>'رنگ روغن','city'=>'تهران','rating'=>'4.9','reviews'=>128,'bio'=>'خلق پرتره‌های احساسی با رنگ‌های گرم و جزئیات ظریف.','avatar'=>'assets/images/artist1.jpg','cover'=>'assets/images/work1.jpg'],
 ['id'=>2,'name'=>'مهراد حیدری','specialty'=>'سیاه‌قلم','city'=>'اصفهان','rating'=>'4.8','reviews'=>96,'bio'=>'طراحی چهره با تمرکز بر سایه‌روشن و جزئیات.','avatar'=>'assets/images/artist2.jpg','cover'=>'assets/images/work2.jpg'],
 ['id'=>3,'name'=>'نیلوفر احمدی','specialty'=>'آبرنگ','city'=>'شیراز','rating'=>'4.9','reviews'=>112,'bio'=>'تصویرسازی لطیف با رنگ‌های آرام و ترکیب‌بندی شاعرانه.','avatar'=>'assets/images/artist3.jpg','cover'=>'assets/images/work3.jpg'],
 ['id'=>4,'name'=>'علی رضایی','specialty'=>'اکریلیک','city'=>'تبریز','rating'=>'4.7','reviews'=>74,'bio'=>'آثار مدرن با رنگ‌های جسورانه و نگاه متفاوت.','avatar'=>'assets/images/artist4.jpg','cover'=>'assets/images/work4.jpg'],
 ['id'=>5,'name'=>'پردیس کریمی','specialty'=>'نقاشی دیجیتال','city'=>'مشهد','rating'=>'4.8','reviews'=>85,'bio'=>'تصویرسازی دیجیتال و طراحی اختصاصی بر اساس سلیقه مشتری.','avatar'=>'assets/images/artist5.jpg','cover'=>'assets/images/work5.jpg'],
 ['id'=>6,'name'=>'نگار عباسی','specialty'=>'مداد رنگی','city'=>'تهران','rating'=>'4.9','reviews'=>135,'bio'=>'نقاشی‌های ظریف و پرجزئیات از چهره و طبیعت.','avatar'=>'assets/images/artist6.jpg','cover'=>'assets/images/work6.jpg'],
 ]; }
function all_artists(): array { $p=db(); if($p){ try { $rows=$p->query("SELECT id,name,specialty,city,rating,reviews,bio,avatar,cover FROM artists WHERE status='approved' ORDER BY id DESC")->fetchAll(); if($rows) return $rows; } catch(Throwable $e){} } return demo_artists(); }
function current_user(): ?array { return $_SESSION['user'] ?? null; }

/* -------------------------
   هلپرهای احراز هویت و نقش
------------------------- */
function is_logged_in(): bool { return !empty($_SESSION['user']); }
function is_artist(): bool { return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'artist'; }
function is_customer(): bool { return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'customer'; }
function require_auth(): void {
    if (!is_logged_in()) {
        flash('برای دسترسی به این صفحه ابتدا وارد شوید.', 'error');
        header('Location: login.php');
        exit;
    }
}

/* -------------------------
   گرفتن هنرمند بر اساس شناسه
------------------------- */
function get_artist_by_id(int $id): ?array {
    $p = db();
    if ($p) {
        try {
            $stmt = $p->prepare("SELECT * FROM artists WHERE id = ? AND status = 'approved'");
            $stmt->execute([$id]);
            $artist = $stmt->fetch();
            if ($artist) return $artist;
        } catch (Throwable $e) { /* به دمو برمی‌گردد */ }
    }
    foreach (demo_artists() as $artist) {
        if ((int)$artist['id'] === $id) return $artist;
    }
    return null;
}

/* -------------------------
   داده‌های نمونه: پروژه‌ها و سفارش‌ها
------------------------- */
function demo_projects(): array {
    return [
        ['id'=>101, 'customer'=>'مهدی رستمی', 'title'=>'پرتره رنگ روغن از عکس خانوادگی', 'category'=>'رنگ روغن', 'budget'=>'۴٬۵۰۰٬۰۰۰', 'status'=>'pending', 'date'=>'1404-08-15'],
        ['id'=>102, 'customer'=>'سارا محمدی', 'title'=>'تابلو آبرنگ گل‌های بهاری', 'category'=>'آبرنگ', 'budget'=>'۲٬۸۰۰٬۰۰۰', 'status'=>'in_progress', 'date'=>'1404-08-10'],
        ['id'=>103, 'customer'=>'امیر کاظمی', 'title'=>'طراحی سیاه‌قلم چهره پدر', 'category'=>'سیاه‌قلم', 'budget'=>'۳٬۲۰۰٬۰۰۰', 'status'=>'in_progress', 'date'=>'1404-08-05'],
        ['id'=>104, 'customer'=>'نگار صادقی', 'title'=>'کاور دیجیتال برای کانال یوتیوب', 'category'=>'نقاشی دیجیتال', 'budget'=>'۱٬۵۰۰٬۰۰۰', 'status'=>'delivered', 'date'=>'1404-07-28'],
        ['id'=>105, 'customer'=>'رضا قاسمی', 'title'=>'تابلو اکریلیک مدرن برای دفتر', 'category'=>'اکریلیک', 'budget'=>'۵٬۹۰۰٬۰۰۰', 'status'=>'pending', 'date'=>'1404-08-18'],
    ];
}

function demo_orders(): array {
    return [
        ['id'=>5001, 'code'=>'HS-5001', 'title'=>'پرتره رنگ روغن ۵۰×۷۰', 'artist'=>'سارا یوسفی', 'amount'=>'۴٬۵۰۰٬۰۰۰', 'status'=>'delivered', 'date'=>'1404-08-02'],
        ['id'=>5002, 'code'=>'HS-5002', 'title'=>'تابلو آبرنگ طبیعت بی‌جان', 'artist'=>'نیلوفر احمدی', 'amount'=>'۲٬۸۰۰٬۰۰۰', 'status'=>'shipped', 'date'=>'1404-08-06'],
        ['id'=>5003, 'code'=>'HS-5003', 'title'=>'طراحی سیاه‌قلم پرتره', 'artist'=>'مهراد حیدری', 'amount'=>'۳٬۲۰۰٬۰۰۰', 'status'=>'in_progress', 'date'=>'1404-08-09'],
        ['id'=>5004, 'code'=>'HS-5004', 'title'=>'کاور دیجیتال اختصاصی', 'artist'=>'پردیس کریمی', 'amount'=>'۱٬۵۰۰٬۰۰۰', 'status'=>'pending', 'date'=>'1404-08-14'],
    ];
}
function get_demo_order_by_code(string $code): ?array {
    foreach (demo_orders() as $order) {
        if (strcasecmp($order['code'], $code) === 0) return $order;
    }
    return null;
}
