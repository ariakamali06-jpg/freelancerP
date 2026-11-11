<?php
require_once __DIR__ . '/config.php';

/* =========================
   پردازش فرم چندمرحله‌ای
========================= */
$errors = [];
$step   = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$step   = max(1, min(3, $step));

$data = [
    'title'       => trim($_POST['title'] ?? ($_GET['title'] ?? '')),
    'category'    => trim($_POST['category'] ?? ($_GET['category'] ?? '')),
    'description' => trim($_POST['description'] ?? ''),
    'size'        => $_POST['size'] ?? 'A4',
    'deadline'    => $_POST['deadline'] ?? '',
    'budget'      => $_POST['budget'] ?? '',
    'name'        => trim($_POST['name'] ?? ''),
    'phone'       => trim($_POST['phone'] ?? ''),
    'email'       => trim($_POST['email'] ?? ''),
    'artist_id'   => (int)($_POST['artist_id'] ?? ($_GET['artist_id'] ?? 0)),
];

$categories = [
    'رنگ روغن', 'آبرنگ', 'سیاه‌قلم', 'اکریلیک',
    'مداد رنگی', 'نقاشی دیجیتال', 'سبک فانتزی'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // مرحله ۱: ایده پروژه
    if ($step === 1) {
        if (mb_strlen($data['title']) < 3)      $errors[] = 'عنوان پروژه را کامل و واضح بنویس (حداقل ۳ کاراکتر).';
        if (!in_array($data['category'], $categories, true)) $errors[] = 'یک دسته‌بندی را انتخاب کن.';
        if (mb_strlen($data['description']) < 10) $errors[] = 'توضیحات پروژه را بنویس (حداقل ۱۰ کاراکتر).';

        if (!$errors) {
            $step = 2;
        }
    }

    // مرحله ۲: جزئیات سفارش
    elseif ($step === 2) {
        if ($data['budget'] !== '' && (int)$data['budget'] < 10000) {
            $errors[] = 'بودجه پیشنهادی باید حداقل ۱۰,۰۰۰ تومان باشد.';
        }

        if (!$errors) {
            $step = 3;
        }
    }

    // مرحله ۳: اطلاعات تماس + ثبت نهایی
    else {
        if (mb_strlen($data['name']) < 2)  $errors[] = 'نام و نام خانوادگی را وارد کن.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'ایمیل معتبر وارد کن.';

        if (!$errors) {
            $p = db();

            if ($p) {
                try {
                    $s = $p->prepare(
                        'INSERT INTO projects
                         (user_id, artist_id, title, category, description,
                          size, deadline, budget, status, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                    );
                    $s->execute([
                        current_user()['id'] ?? null,
                        $data['artist_id'] ?: null,
                        $data['title'],
                        $data['category'],
                        $data['description'],
                        $data['size'],
                        $data['deadline'] ?: null,
                        $data['budget'] !== '' ? (int)$data['budget'] : null,
                        'pending'
                    ]);
                } catch (Throwable $e) {
                    // بدون دیتابیس، لاگ در session
                    $_SESSION['last_project'] = $data;
                    flash('مشکل در اتصال به دیتابیس — ابتدا database.sql را اجرا کن.', 'error');
                    header('Location: project-new.php?step=1');
                    exit;
                }
            } else {
                $_SESSION['last_project'] = $data;
                flash('مشکلی در اتصال به دیتابیس رخ داد. اطمینان حاصل کن database.sql را اجرا کرده‌ای.', 'error');
                header('Location: project-new.php?step=1');
                exit;
            }

            flash('پروژه‌ات با موفقیت ثبت شد! 🎉 هنرمندان به‌زودی پیشنهاد می‌دهند.');
            header('Location: track-order.php');
            exit;
        }
    }
}

$pageTitle = 'ثبت پروژه';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div class="container">
        <h1>ثبت پروژه</h1>
        <p>در سه قدم ساده، ایده‌ات را به هنرمندان برسان</p>
    </div>
</div>

<section class="content">
    <div class="container">

        <?php if ($errors): ?>
            <div class="notice error">
                <?php foreach ($errors as $err): ?>
                    <div>⚠ <?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- نشانگر مراحل -->
        <div class="status-list" style="justify-content:center;margin-bottom:24px">
            <span <?= $step>=1 ? '' : 'style=opacity:.5' ?>>۱ · ایده</span>
            <span <?= $step>=2 ? '' : 'style=opacity:.5' ?>>۲ · جزئیات</span>
            <span <?= $step>=3 ? '' : 'style=opacity:.5' ?>>۳ · اطلاعات تماس</span>
        </div>

        <div class="form-card" style="max-width:760px">

            <form method="post" action="project-new.php">

                <!-- ===== قدم ۱: ایده ===== -->
                <?php if ($step === 1): ?>
                    <h2>۱ · ایده‌ات را تعریف کن</h2>
                    <p class="small-muted">هرچه دقیق‌تر بنویسی، پیشنهادهای بهتری دریافت می‌کنی.</p>

                    <input type="hidden" name="step" value="1">

                    <div class="field">
                        <label for="title">عنوان پروژه</label>
                        <input type="text" id="title" name="title" required minlength="3"
                               value="<?= e($data['title']) ?>"
                               placeholder="مثلاً: پرتره سیاه و سفید پدرم">
                    </div>

                    <div class="field">
                        <label for="category">دسته‌بندی (تکنیک)</label>
                        <select id="category" name="category" required>
                            <option value="">انتخاب کن…</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= e($c) ?>" <?= $data['category']===$c?'selected':'' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="description">توضیحات</label>
                        <textarea id="description" name="description" rows="6" required minlength="10"
                                  placeholder="مثل: یک پرتره از عکس پدرم در جوانی، سبک واقع‌گرا، بدون کادر…"><?= e($data['description']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-block">ادامه ←</button>

                <?php endif; ?>

                <!-- ===== قدم ۲: جزئیات ===== -->
                <?php if ($step === 2): ?>
                    <h2>۲ · جزئیات سفارش</h2>
                    <p class="small-muted">ابعاد، مهلت و بودجه‌ات را مشخص کن.</p>

                    <input type="hidden" name="step" value="2">
                    <?php foreach (['title','category','description'] as $k): ?>
                        <input type="hidden" name="<?= $k ?>" value="<?= e($data[$k]) ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="artist_id" value="<?= (int)$data['artist_id'] ?>">

                    <div class="form-grid">
                        <div class="field">
                            <label for="size">ابعاد</label>
                            <select id="size" name="size">
                                <option value="A4" <?= $data['size']==='A4'?'selected':'' ?>>A4 (۲۹×۲۱ سانتی‌متر)</option>
                                <option value="A3" <?= $data['size']==='A3'?'selected':'' ?>>A3 (۴۲×۲۹ سانتی‌متر)</option>
                                <option value="A2" <?= $data['size']==='A2'?'selected':'' ?>>A2 (۵۹×۴۲ سانتی‌متر)</option>
                                <option value="A1" <?= $data['size']==='A1'?'selected':'' ?>>A1 (۸۴×۵۹ سانتی‌متر)</option>
                                <option value="custom" <?= $data['size']==='custom'?'selected':'' ?>>سفارشی</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="deadline">مهلت تحویل</label>
                            <input type="date" id="deadline" name="deadline" value="<?= e($data['deadline']) ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label for="budget">بودجه پیشنهادی (تومان)</label>
                        <input type="number" id="budget" name="budget" min="10000" step="50000"
                               value="<?= e($data['budget']) ?>" placeholder="مثلاً: 500000">
                        <small class="small-muted">این مقدار به عنوان «حد بالای هزینه» در نظر گرفته می‌شود.</small>
                    </div>

                    <div style="display:flex;gap:10px">
                        <a class="btn btn-light" href="?step=1">← قبلی</a>
                        <button type="submit" class="btn btn-block">ادامه ←</button>
                    </div>

                <?php endif; ?>

                <!-- ===== قدم ۳: اطلاعات تماس + ثبت ===== -->
                <?php if ($step === 3): ?>
                    <h2>۳ · اطلاعات تماس</h2>
                    <p class="small-muted">برای دریافت پیشنهادها، راهی برای تماس بگذار.</p>

                    <input type="hidden" name="step" value="3">
                    <?php foreach (['title','category','description','size','deadline','budget','artist_id'] as $k): ?>
                        <input type="hidden" name="<?= $k ?>" value="<?= e($data[$k]) ?>">
                    <?php endforeach; ?>

                    <div class="detail-layout" style="grid-template-columns:1fr">
                        <div style="background:var(--pink-light);border-radius:14px;padding:14px 18px">
                            <b>خلاصه پروژه</b>
                            <p style="margin:6px 0 0;font-size:13px">
                                «<?= e($data['title']) ?>» ·
                                <span class="pill"><?= e($data['category']) ?></span> ·
                                <span class="small-muted"><?= e($data['size']) ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label for="name">نام و نام خانوادگی</label>
                            <input type="text" id="name" name="name" required minlength="2"
                                   value="<?= e($data['name']) ?>"
                                   <?= current_user() ? 'readonly' : '' ?>>
                        </div>

                        <div class="field">
                            <label for="phone">شماره تماس</label>
                            <input type="tel" id="phone" name="phone" dir="ltr"
                                   value="<?= e($data['phone']) ?>" placeholder="مثلاً: 0912xxxxxxx">
                        </div>
                    </div>

                    <div class="field">
                        <label for="email">ایمیل</label>
                        <input type="email" id="email" name="email" required
                               value="<?= e($data['email']) ?>"
                               <?= current_user() ? 'readonly' : '' ?>>
                    </div>

                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <a class="btn btn-light" href="?step=2">← قبلی</a>
                        <button type="submit" class="btn btn-block" style="flex:1">
                            ثبت نهایی پروژه ✓
                        </button>
                    </div>

                <?php endif; ?>

            </form>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
