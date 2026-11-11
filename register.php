
<?php
require_once __DIR__ . '/config.php';

/* =========================
   پردازش فرم ثبت‌نام
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role  = $_POST['role'] ?? 'customer';
    $pass  = $_POST['password'] ?? '';

    // فقط مشتری و هنرمند اجازه ثبت‌نام دارند.
    if (!in_array($role, ['customer', 'artist'], true)) {
        $role = 'customer';
    }

    // اعتبارسنجی اطلاعات واردشده
    if (
        mb_strlen($name) < 2 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($pass) < 6
    ) {
        flash(
            'نام، ایمیل معتبر و رمز حداقل ۶ کاراکتری وارد کن.',
            'error'
        );

        header('Location: register.php');
        exit;
    }

    // اتصال به دیتابیس
    $p = db();

    if (!$p) {
        flash(
            'ابتدا دیتابیس را طبق فایل database.sql بساز.',
            'error'
        );

        header('Location: register.php');
        exit;
    }

    try {
        // ذخیره حساب کاربری با رمز عبور هش‌شده
        $stmt = $p->prepare(
            'INSERT INTO users (name, email, role, password)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            $name,
            $email,
            $role,
            password_hash($pass, PASSWORD_DEFAULT)
        ]);

        flash('ثبت‌نام انجام شد؛ حالا وارد شو.');

        header('Location: login.php');
        exit;

    } catch (Throwable $e) {
        flash(
            'این ایمیل قبلاً ثبت شده یا خطای دیتابیس رخ داده است.',
            'error'
        );

        header('Location: register.php');
        exit;
    }
}


/* =========================
   نمایش صفحه ثبت‌نام
========================= */

$pageTitle = 'ثبت‌نام';

require __DIR__ . '/includes/header.php';
?>

<section class="auth-wrap">
    <div class="container auth-grid">

        <!-- تصویر صفحه ثبت‌نام -->
<div class="auth-art">
    <img
       src="assets/images/register-image.jpg"
        alt="هنرمندشو"
        class="auth-image"
    >
</div>


        <!-- فرم ساخت حساب کاربری -->
        <div class="auth-form">
            <h1>ساخت حساب کاربری</h1>

            <form method="post" action="register.php">

                <div class="field">
                    <label for="name">
                        نام و نام خانوادگی
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        minlength="2"
                        autocomplete="name"
                    >
                </div>


                <div class="field">
                    <label for="email">ایمیل</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autocomplete="email"
                    >
                </div>


                <div class="field">
                    <label for="role">من به عنوان</label>

                    <select id="role" name="role">
                        <option value="customer">
                            مشتری
                        </option>

                        <option value="artist">
                            هنرمند
                        </option>
                    </select>
                </div>


                <div class="field">
                    <label for="password">
                        رمز عبور (حداقل ۶ کاراکتر)
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="6"
                        required
                        autocomplete="new-password"
                    >
                </div>


                <button type="submit" class="btn btn-block">
                    ثبت‌نام
                </button>

            </form>

            <p>
                حساب داری؟
                <a class="text-link" href="login.php">
                    وارد شو
                </a>
            </p>
        </div>

    </div>
</section>

<?php
require __DIR__ . '/includes/footer.php';
?>
