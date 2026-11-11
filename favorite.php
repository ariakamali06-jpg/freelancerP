<?php
require_once __DIR__ . '/config.php';

/* =========================
   API علاقه‌مندی‌ها (fetch/AJAX)
   اکشن‌ها: toggle (بدون ورود: 401)
========================= */

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'auth'], JSON_UNESCAPED_UNICODE);
    exit;
}

$p = db();
if (!$p) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'db'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)current_user()['id'];
$type   = $_POST['type'] ?? $_GET['type'] ?? '';
$target = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if (!in_array($type, ['artist', 'artwork'], true) || $target <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'bad_request'], JSON_UNESCAPED_UNICODE);
    exit;
}

$col   = $type === 'artist' ? 'artist_id' : 'artwork_id';
$table = $type === 'artist' ? 'artists'  : 'artworks';

// وجود داشتن هدف
try {
    $s = $p->prepare("SELECT 1 FROM $table WHERE id = ? LIMIT 1");
    $s->execute([$target]);
    if (!$s->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_error'], JSON_UNESCAPED_UNICODE);
    exit;
}

// toggle
try {
    $s = $p->prepare("SELECT id FROM favorites WHERE user_id = ? AND $col = ? LIMIT 1");
    $s->execute([$userId, $target]);
    $existing = $s->fetch();

    if ($existing) {
        $d = $p->prepare('DELETE FROM favorites WHERE id = ?');
        $d->execute([$existing['id']]);
        echo json_encode(['ok' => true, 'faved' => false], JSON_UNESCAPED_UNICODE);
    } else {
        $i = $p->prepare("INSERT INTO favorites (user_id, $col) VALUES (?, ?)");
        $i->execute([$userId, $target]);
        echo json_encode(['ok' => true, 'faved' => true], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_error'], JSON_UNESCAPED_UNICODE);
}
