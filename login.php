
<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $p = db();

    if ($p) {
        $s = $p->prepare(
            'SELECT id, name, email, role, password
             FROM users WHERE email = ? LIMIT 1'
        );
        $s->execute([$email]);
        $u = $s->fetch();

        if ($u && password_verify($pass, $u['password'])) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => (int)$u['id'],
                'name' => $u['name'],
                'email' => $u['email'],
                'role' => $u['role']
            ];

            flash('با موفقیت وارد شدی.');

if ($u['role'] === 'artist') {
    header('Location: artist-dashboard.php');
    exit;
}

header('Location: index.php');
exit;
        }
    }

    flash('ورود انجام نشد. ایمیل و رمز عبور را بررسی کن.', 'error');
    header('Location: login.php');
    exit;
}

$pageTitle = 'ورود';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-wrap">
    <div class="container auth-grid">
       <div class="auth-art">
    <img
        src="assets/images/auth-image.jpg"
        alt="هنرمندشو"
        class="auth-image"
    >
</div>

        <div class="auth-form">
            <h1>ورود به هنرمندشو</h1>
            <p class="small-muted">اطلاعات حساب خود را وارد کن.</p>

            <form method="post">
                <div class="field">
                    <label>ایمیل</label>
                    <input type="email" name="email"
                           required autocomplete="email">
                </div>

                <div class="field">
                    <label>رمز عبور</label>
                    <input type="password" name="password"
                           required autocomplete="current-password">
                </div>

                <button class="btn btn-block">ورود</button>
            </form>

            <p>حساب نداری؟
                <a class="text-link" href="register.php">ثبت‌نام کن</a>
            </p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
