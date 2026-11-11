<?php
/* هلپر مستقل — بدون وابستگی به config */

/** تبدیل میلادی به جلالی (خروجی رشته فارسی) */
function jalali(string $format, $timestamp = null): string {
    if ($timestamp === null) $timestamp = time();
    if (is_string($timestamp)) $timestamp = strtotime($timestamp);

    $gy = (int)date('Y', $timestamp);
    $gm = (int)date('n', $timestamp);
    $gd = (int)date('j', $timestamp);

    // تبدیل به جلالی
    $g_days = [0,31,59,90,120,151,181,212,243,273,304,334];
    $gy2 = ($gm > 2) ? $gy + 1 : $gy;
    $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100))
          + ((int)(($gy2 + 399) / 400)) + $gd + $g_days[$gm - 1];

    $jy = -1595 + (33 * ((int)($days / 12053)));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + (int)($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }

    $jmonths = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
                'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    // فرمت‌های پشتیبانی‌شده
    if ($format === 'Y/m/d') return fa_num_raw(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
    if ($format === 'Y-m-d') return fa_num_raw(sprintf('%04d-%02d-%02d', $jy, $jm, $jd));
    if ($format === 'd M Y') return fa_num_raw(sprintf('%02d', $jd)) . ' ' . $jmonths[$jm] . ' ' . fa_num_raw($jy);
    if ($format === 'M Y')   return $jmonths[$jm] . ' ' . fa_num_raw($jy);
    return fa_num_raw(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
}

/** تبدیل ارقام لاتین به فارسی (بدون جداکننده هزارگان) */
function fa_num_raw(string $s): string {
    return strtr($s, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
}

/** نمایش تاریخ مخلوط: اگر ورودی جلالی بود دست‌نخورده، وگرنه تبدیل */
function fa_date($value, string $format = 'd M Y'): string {
    if ($value === null || $value === '') return '—';
    $s = (string)$value;
    // اگر الگوی تاریخ شمسی (مثل 1404-08-15) بود، همان را نشان بده
    if (preg_match('/^1[34]\d{2}-/', $s)) return $s;
    return jalali($format, $s);
}

/** اعداد فارسی */
function fa_num($n): string {
    return strtr(number_format((float)$n), [
        '0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴',
        '5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹',
        ','=>'،'
    ]);
}
