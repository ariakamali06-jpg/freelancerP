-- ============================================================
--  هنرمندشو (Honarmandshoo) — Structure + Sample Data
--  MySQL/MariaDB (utf8mb4, for Laragon / XAMPP)
--  Run once:  source database.sql   (or import in phpMyAdmin)
--  Database name matches config.php:  DB_NAME = honarmandsho_v2
-- ============================================================

CREATE DATABASE IF NOT EXISTS honarmandsho_v2
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE honarmandsho_v2;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS bids;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS artists;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
--  کاربران (مشتری / هنرمند / مدیر)
-- ------------------------------------------------------------
CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('customer','artist','admin') NOT NULL DEFAULT 'customer',
    phone       VARCHAR(30)  DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  دسته‌بندی‌ها (تکنیک‌های نقاشی)
-- ------------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80) NOT NULL,
    slug        VARCHAR(100) NOT NULL,
    image       VARCHAR(255) DEFAULT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  هنرمندان
-- ------------------------------------------------------------
CREATE TABLE artists (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED DEFAULT NULL,
    name        VARCHAR(120) NOT NULL,
    specialty   VARCHAR(80)  NOT NULL,
    city        VARCHAR(80)  DEFAULT NULL,
    bio         TEXT,
    rating      DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    reviews     INT UNSIGNED NOT NULL DEFAULT 0,
    avatar      VARCHAR(255) DEFAULT NULL,
    cover       VARCHAR(255) DEFAULT NULL,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_artists_status (status),
    CONSTRAINT fk_artists_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  پروژه‌ها (ثبت سفارش کار از طرف مشتری)
-- ------------------------------------------------------------
CREATE TABLE projects (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED DEFAULT NULL,
    artist_id   INT UNSIGNED DEFAULT NULL,
    title       VARCHAR(190) NOT NULL,
    category    VARCHAR(80)  DEFAULT NULL,
    description TEXT,
    size        VARCHAR(30)  DEFAULT 'A4',
    deadline    DATE DEFAULT NULL,
    budget      INT UNSIGNED DEFAULT NULL,
    status      ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_projects_status (status),
    KEY idx_projects_user (user_id),
    KEY idx_projects_artist (artist_id),
    CONSTRAINT fk_projects_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL,
    CONSTRAINT fk_projects_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  سفارش‌ها (خرید + پیگیری)
-- ------------------------------------------------------------
CREATE TABLE orders (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED DEFAULT NULL,
    artist_id     INT UNSIGNED DEFAULT NULL,
    amount        INT UNSIGNED NOT NULL DEFAULT 0,
    status        ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    tracking_code VARCHAR(30)  DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_tracking (tracking_code),
    KEY idx_orders_user (user_id),
    KEY idx_orders_artist (artist_id),
    CONSTRAINT fk_orders_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL,
    CONSTRAINT fk_orders_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  نظرات/امتیازها
-- ------------------------------------------------------------
CREATE TABLE reviews (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED DEFAULT NULL,
    artist_id   INT UNSIGNED NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL DEFAULT 5 CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reviews_artist (artist_id),
    CONSTRAINT fk_reviews_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL,
    CONSTRAINT fk_reviews_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  سبد خرید
-- ------------------------------------------------------------
CREATE TABLE cart_items (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    artwork_id  INT UNSIGNED DEFAULT NULL,
    title       VARCHAR(190) NOT NULL,
    artist_id   INT UNSIGNED DEFAULT NULL,
    price       INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cart_user (user_id),
    CONSTRAINT fk_cart_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_cart_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  پیشنهادهای هنرمندان روی پروژه‌ها
-- ------------------------------------------------------------
CREATE TABLE bids (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id  INT UNSIGNED NOT NULL,
    artist_id   INT UNSIGNED NOT NULL,
    amount      INT UNSIGNED NOT NULL DEFAULT 0,
    note        TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bids_project (project_id),
    KEY idx_bids_artist (artist_id),
    CONSTRAINT fk_bids_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_bids_artist  FOREIGN KEY (artist_id)  REFERENCES artists(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  پیام‌های تماس
-- ------------------------------------------------------------
CREATE TABLE contact_messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    subject     VARCHAR(190) DEFAULT NULL,
    message     TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  داده‌های نمونه
-- ============================================================

-- حساب‌های کاربری (رمز همه: 123456)
INSERT INTO users (name, email, password, role, phone) VALUES
('مدیر سایت', 'admin@honarmandshoo.ir', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeF1K8N5kXQj3GtZb0Lb6yF8pO0y5n7aW', 'admin', '09120000000'),
('سارا یوسفی',   'sara@example.com',     '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeF1K8N5kXQj3GtZb0Lb6yF8pO0y5n7aW', 'artist',  '09120000001'),
('مهراد حیدری',  'mehrad@example.com',   '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeF1K8N5kXQj3GtZb0Lb6yF8pO0y5n7aW', 'artist',  '09120000002'),
('نیلوفر احمدی', 'niloufar@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeF1K8N5kXQj3GtZb0Lb6yF8pO0y5n7aW', 'artist',  '09120000003'),
('علی رضایی',    'ali@example.com',      '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeF1K8N5kXQj3GtZb0Lb6yF8pO0y5n7aW', 'artist',  '09120000004'),
('پردیس کریمی',  'pardis@example.com',   '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeF1K8N5kXQj3GtZb0Lb6yF8pO0y5n7aW', 'artist',  '09120000005'),
('نگار عباسی',   'negar@example.com',    '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeF1K8N5kXQj3GtZb0Lb6yF8pO0y5n7aW', 'artist',  '09120000006'),
('مریم راد',     'maryam@example.com',   '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeF1K8N5kXQj3GtZb0Lb6yF8pO0y5n7aW', 'customer', '09120000007');

-- دسته‌بندی‌ها
INSERT INTO categories (name, slug, image, sort_order) VALUES
('رنگ روغن',       'oil',        'assets/images/catt-oil.jpg',        1),
('آبرنگ',          'watercolor', 'assets/images/ccat-watercolor.jpg', 2),
('سیاه‌قلم',       'pencil',     'assets/images/catt-pencil.jpg',     3),
('اکریلیک',        'acrylic',    'assets/images/ccat-acrylic.jpg',    4),
('مداد رنگی',      'pencilcolor','assets/images/catt-pencilcolor.jpg',5),
('نقاشی دیجیتال',  'digital',    'assets/images/ccat-digital.jpg',    6),
('سبک فانتزی',     'fantasy',    'assets/images/ccat-fantasy.jpg',    7);

-- هنرمندان (مطابق demo_artists در config.php)
INSERT INTO artists (user_id, name, specialty, city, bio, rating, reviews, avatar, cover, status) VALUES
(2, 'سارا یوسفی',   'رنگ روغن',      'تهران', 'خلق پرتره‌های احساسی با رنگ‌های گرم و جزئیات ظریف.',                       4.9, 128, 'assets/images/artist1.jpg', 'assets/images/work1.jpg', 'approved'),
(3, 'مهراد حیدری',  'سیاه‌قلم',      'اصفهان', 'طراحی چهره با تمرکز بر سایه‌روشن و جزئیات.',                              4.8,  96, 'assets/images/artist2.jpg', 'assets/images/work2.jpg', 'approved'),
(4, 'نیلوفر احمدی', 'آبرنگ',         'شیراز', 'تصویرسازی لطیف با رنگ‌های آرام و ترکیب‌بندی شاعرانه.',                      4.9, 112, 'assets/images/artist3.jpg', 'assets/images/work3.jpg', 'approved'),
(5, 'علی رضایی',    'اکریلیک',       'تبریز', 'آثار مدرن با رنگ‌های جسورانه و نگاه متفاوت.',                              4.7,  74, 'assets/images/artist4.jpg', 'assets/images/work4.jpg', 'approved'),
(6, 'پردیس کریمی',  'نقاشی دیجیتال', 'مشهد', 'تصویرسازی دیجیتال و طراحی اختصاصی بر اساس سلیقه مشتری.',                   4.8,  85, 'assets/images/artist5.jpg', 'assets/images/work5.jpg', 'approved'),
(7, 'نگار عباسی',   'مداد رنگی',     'تهران', 'نقاشی‌های ظریف و پرجزئیات از چهره و طبیعت.',                              4.9, 135, 'assets/images/artist6.jpg', 'assets/images/work6.jpg', 'approved');

-- پروژه‌های نمونه
INSERT INTO projects (user_id, artist_id, title, category, description, size, deadline, budget, status) VALUES
(8, 1, 'پرتره پدرم', 'رنگ روغن', 'پرتره واقع‌گرا از روی عکس قدیمی پدرم در جوانی، بدون قاب.', 'A3', '2026-12-01', 2500000, 'in_progress'),
(8, NULL, 'لوگوی فانتزی برای برند', 'نقاشی دیجیتال', 'یک کاراکتر فانتزی به‌عنوان لوگوی برند من.', 'A4', '2026-11-25', 800000, 'pending'),
(2, 2, 'سفره هفت‌سین', 'آبرنگ', 'طرح آبرنگ از سفره هفت‌سین با گل‌های بهاری.', 'A2', '2026-03-15', 600000, 'pending');

-- سفارش‌های نمونه (کدهای پیگیری برای صفحه track-order)
INSERT INTO orders (id, user_id, artist_id, amount, status, tracking_code) VALUES
(4803, 8, 1, 850000, 'in_progress', 'HMS-4803'),
(4815, 8, 2, 420000, 'completed',   'HMS-4815'),
(4827, 8, 3, 610000, 'pending',     'HMS-4827');

-- نظرات نمونه
INSERT INTO reviews (user_id, artist_id, rating, comment) VALUES
(8, 1, 5, 'اثر فوق‌العاده بود؛ دقیقاً مطابق تصورم.'),
(8, 2, 5, 'بسیار حرفه‌ای و با جزئیات بالا.'),
(8, 3, 4, 'کیفیت خوب و تحویل به‌موقع.');

-- پیشنهادهای نمونه
INSERT INTO bids (project_id, artist_id, amount, note) VALUES
(2, 1, 750000, 'می‌توانم با کیفیت بالا انجام دهم.'),
(2, 5, 800000, 'تخصص من تصویرسازی دیجیتال است.');

-- پیام نمونه
INSERT INTO contact_messages (name, email, subject, message) VALUES
('آوا نادری', 'ava@example.com', 'سوال درباره سفارش', 'سلام، می‌خواستم بدانم هزینه ارسال به کرج چقدر است؟');

-- ============================================================
--  پایان
-- ============================================================
