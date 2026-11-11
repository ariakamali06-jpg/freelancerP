
-- ============================================================
--  به‌روزرسانی نسخه ۲ — گالری آثار، چت، علاقه‌مندی‌ها
--  (برای نصب تازه، این بخش هم اجرا می‌شود؛ برای دیتابیس موجود
--   فقط CREATE TABLE IF NOT EXISTS ها اعمال می‌شوند)
-- ============================================================

USE honarmandsho_v2;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS artworks;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
--  آثار هنری (گالری قابل خرید)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS artworks (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    artist_id   INT UNSIGNED NOT NULL,
    title       VARCHAR(190) NOT NULL,
    description TEXT,
    technique   VARCHAR(80)  DEFAULT NULL,
    size        VARCHAR(40)  DEFAULT NULL,
    price       INT UNSIGNED NOT NULL DEFAULT 0,
    image       VARCHAR(255) DEFAULT NULL,
    status      ENUM('available','sold','hidden') NOT NULL DEFAULT 'available',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_artworks_artist (artist_id),
    KEY idx_artworks_status (status),
    CONSTRAINT fk_artworks_artist FOREIGN KEY (artist_id)
        REFERENCES artists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  چت داخل سفارش (مشتری ↔ هنرمند)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id    INT UNSIGNED NOT NULL,
    sender_id   INT UNSIGNED NOT NULL,
    message     TEXT,
    image       VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chat_order (order_id),
    CONSTRAINT fk_chat_order FOREIGN KEY (order_id)
        REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_sender FOREIGN KEY (sender_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  علاقه‌مندی‌ها (هنرمند یا اثر)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favorites (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    artist_id   INT UNSIGNED DEFAULT NULL,
    artwork_id  INT UNSIGNED DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fav_artist (user_id, artist_id),
    UNIQUE KEY uq_fav_artwork (user_id, artwork_id),
    CONSTRAINT fk_fav_user    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_artist  FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  آثار نمونه (تصاویر موجود در assets/images/work*.jpg)
-- ------------------------------------------------------------
INSERT INTO artworks (artist_id, title, description, technique, size, price, image, status) VALUES
(1, 'پرتره زن با گل سرخ', 'رنگ روغن روی بوم، سبک واقع‌گرا.', 'رنگ روغن', '۵۰×۷۰', 4500000, 'assets/images/work1.jpg', 'available'),
(2, 'چهره مرد پیر', 'سیاه‌قلم روی کاغذ فابریانو.', 'سیاه‌قلم', 'A3', 2800000, 'assets/images/work2.jpg', 'available'),
(3, 'بهار و شکوفه‌ها', 'آبرنگ با ترکیب‌بندی شاعرانه.', 'آبرنگ', 'A2', 3200000, 'assets/images/work3.jpg', 'available'),
(4, 'رنگ‌های شهر', 'اکریلیک مدرن با رنگ‌های جسورانه.', 'اکریلیک', '۶۰×۸۰', 5900000, 'assets/images/work4.jpg', 'available'),
(5, 'دختر مهتاب', 'نقاشی دیجیتال با مجوز چاپ.', 'نقاشی دیجیتال', 'دیجیتال', 1500000, 'assets/images/work5.jpg', 'available'),
(6, 'طبیعت بی‌جان میوه', 'مداد رنگی روی کاغذ، پرجزئیات.', 'مداد رنگی', 'A3', 2100000, 'assets/images/work6.jpg', 'sold');
