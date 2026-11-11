<?php
require_once __DIR__ . '/config.php';

/* =========================
   پردازش فرم تماس
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 5) {
        flash('نام، ایمیل معتبر و متن پیام (حداقل ۵ کاراکتر) را وارد کن.', 'error');
        header('Location: contact.php');
        exit;
    }

    $p = db();
    if ($p) {
        try {
            $s = $p->prepare(
                'INSERT INTO contact_messages (name, email, subject, message, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $s->execute([$name, $email, $subject, $message]);
        } catch (Throwable $e) {
            // اگر جدول وجود نداشت فقط پیام موفق نشان بده
        }
    }

    flash('پیامت ارسال شد؛ در اسرع وقت پاسخ می‌دهیم. 🌸');
    header('Location: contact.php');
    exit;
}

$pageTitle = 'تماس با ما';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>تماس با ما</h1>
        <p>سوالی داری؟ خوشحال می‌شویم بشنویم.</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <div class="detail-layout" style="grid-template-columns:1fr 1fr">

            <!-- ===== فرم تماس ===== -->
            <div>
                <div class="form-card" style="max-width:100%">
                    <h2>فرم تماس</h2>
                    <p class="small-muted">پیامت را بنویس؛ معمولاً در کمتر از ۲۴ ساعت پاسخ می‌دهیم.</p>

                    <form method="post" action="contact.php">
                        <div class="form-grid">
                            <div class="field">
                                <label for="name">نام و نام خانوادگی</label>
                                <input type="text" id="name" name="name" required minlength="2">
                            </div>

                            <div class="field">
                                <label for="email">ایمیل</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="subject">موضوع</label>
                            <input type="text" id="subject" name="subject" placeholder="مثلاً: سوال درباره سفارش">
                        </div>

                        <div class="field">
                            <label for="message">پیام</label>
                            <textarea id="message" name="message" rows="6" required minlength="5"></textarea>
                        </div>

                        <button type="submit" class="btn btn-block">
                            ارسال پیام ✉
                        </button>
                    </form>
                </div>
            </div>

            <!-- ===== اطلاعات تماس ===== -->
            <div>
                <div class="form-card" style="max-width:100%;background:var(--pink-light);border-color:var(--line)">
                    <h2>اطلاعات تماس</h2>

                    <div style="display:flex;flex-direction:column;gap:16px;margin-top:14px">

                        <div style="display:flex;gap:12px;align-items:flex-start">
                            <span style="font-size:22px">📧</span>
                            <div>
                                <b style="display:block;font-size:13px">ایمیل</b>
                                <span class="small-muted">info@honarmandshoo.ir</span>
                            </div>
                        </div>

                        <div style="display:flex;gap:12px;align-items:flex-start">
                            <span style="font-size:22px">📱</span>
                            <div>
                                <b style="display:block;font-size:13px">تلفن پشتیبانی</b>
                                <span class="small-muted" dir="ltr">۰۲۱-۹۱۰۰۰۰۰۰</span>
                            </div>
                        </div>

                        <div style="display:flex;gap:12px;align-items:flex-start">
                            <span style="font-size:22px">⌖</span>
                            <div>
                                <b style="display:block;font-size:13px">آدرس</b>
                                <span class="small-muted">تهران، خیابان ولیعصر، بالاتر از پارک ساعی، پلاک ۱۲</span>
                            </div>
                        </div>

                        <div style="display:flex;gap:12px;align-items:flex-start">
                            <span style="font-size:22px">🕘</span>
                            <div>
                                <b style="display:block;font-size:13px">ساعات پاسخگویی</b>
                                <span class="small-muted">شنبه تا پنجشنبه، ۹ صبح تا ۶ عصر</span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="form-card" style="max-width:100%;margin-top:18px">
                    <h3 style="margin-top:0">سوالات پرتکرار</h3>

                    <div style="margin-bottom:14px">
                        <b style="font-size:13px">چطور سفارش ثبت کنم؟</b>
                        <p class="small-muted" style="margin:4px 0 0">
                            از صفحه <a class="text-link" href="project-new.php">ثبت پروژه</a> یک فرم کوتاه را پر کن؛
                            هنرمندان پیشنهادشان را برایت ارسال می‌کنند.
                        </p>
                    </div>

                    <div style="margin-bottom:14px">
                        <b style="font-size:13px">می‌خواهم هنرمند شوم؛ چه کنم؟</b>
                        <p class="small-muted" style="margin:4px 0 0">
                            <a class="text-link" href="register.php">ثبت‌نام</a> کن و نقش «هنرمند» را انتخاب کن.
                            بعد از تأیید، پروفایلت فعال می‌شود.
                        </p>
                    </div>

                    <div>
                        <b style="font-size:13px">پرداخت چطور انجام می‌شود؟</b>
                        <p class="small-muted" style="margin:4px 0 0">
                            پس از توافق با هنرمند و تأیید طرح، پرداخت از طریق درگاه امن انجام می‌شود.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
