/* =========================
   هنرمندشو — اسکریپت‌های تعاملی
========================= */
(function () {
    'use strict';

    /* -------------------------
       ۱) منوی موبایل (همبرگر)
    ------------------------- */
    var hamburger = document.getElementById('hamburger-btn');
    var mobileMenu = document.getElementById('mobile-menu');
    var mobileOverlay = document.getElementById('mobile-menu-overlay');
    var mobileClose = document.getElementById('mobile-menu-close');

    function openMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.add('open');
        if (mobileOverlay) mobileOverlay.classList.add('show');
        if (hamburger) {
            hamburger.classList.add('active');
            hamburger.setAttribute('aria-expanded', 'true');
        }
        if (mobileMenu) mobileMenu.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.remove('open');
        if (mobileOverlay) mobileOverlay.classList.remove('show');
        if (hamburger) {
            hamburger.classList.remove('active');
            hamburger.setAttribute('aria-expanded', 'false');
        }
        if (mobileMenu) mobileMenu.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (hamburger) {
        hamburger.addEventListener('click', function () {
            if (mobileMenu && mobileMenu.classList.contains('open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }

    if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileMenu);

    // بستن منو با کلید Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMobileMenu();
    });

    // بستن منو پس از کلیک روی لینک‌ها
    if (mobileMenu) {
        mobileMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMobileMenu);
        });
    }

    // ریست هنگام بزرگ‌شدن صفحه به دسکتاپ
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 768) closeMobileMenu();
    });


    /* -------------------------
       ۲) ویزارد مراحل ثبت پروژه (project-new.php)
    ------------------------- */
    var wizard = document.querySelector('.step-wizard');

    if (wizard) {
        var steps = Array.prototype.slice.call(wizard.querySelectorAll('.step'));
        var panels = Array.prototype.slice.call(document.querySelectorAll('.step-panel'));
        var currentStep = 0;

        var prevBtn = document.querySelector('.step-nav .btn-prev');
        var nextBtn = document.querySelector('.step-nav .btn-next');
        var form = document.getElementById('project-form') ||
                   (panels[0] ? panels[0].closest('form') : null);

        function goToStep(index) {
            if (index < 0 || index >= steps.length) return;
            currentStep = index;

            steps.forEach(function (step, i) {
                step.classList.toggle('active', i === index);
                step.classList.toggle('completed', i < index);
            });

            panels.forEach(function (panel, i) {
                panel.classList.toggle('active', i === index);
            });

            if (prevBtn) {
                prevBtn.classList.toggle('hidden-prev', index === 0);
            }

            if (nextBtn) {
                var isLast = index === steps.length - 1;
                nextBtn.textContent = isLast ? 'ثبت نهایی پروژه ✨' : 'مرحله بعد ←';
                nextBtn.type = isLast ? 'submit' : 'button';
                nextBtn.name = isLast ? 'submit_project' : '';
            }
        }

        function validateCurrentStep() {
            var panel = panels[currentStep];
            if (!panel) return true;
            var valid = true;
            panel.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#e05a75';
                } else {
                    field.style.borderColor = '';
                }
            });
            return valid;
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                if (currentStep < steps.length - 1) {
                    e.preventDefault();
                    if (validateCurrentStep()) {
                        goToStep(currentStep + 1);
                        window.scrollTo({ top: wizard.offsetTop - 90, behavior: 'smooth' });
                    }
                }
                // در مرحله آخر دکمه از نوع submit است و فرم ارسال می‌شود
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function (e) {
                if (currentStep > 0) {
                    e.preventDefault();
                    goToStep(currentStep - 1);
                    window.scrollTo({ top: wizard.offsetTop - 90, behavior: 'smooth' });
                }
            });
        }

        goToStep(0);
    }


    /* -------------------------
       ۳) دکمه بازگشت به بالا
    ------------------------- */
    var backToTop = document.getElementById('back-to-top');

    if (backToTop) {
        function toggleBackToTop() {
            backToTop.classList.toggle('show', window.scrollY > 320);
        }
        window.addEventListener('scroll', toggleBackToTop, { passive: true });
        toggleBackToTop();

        backToTop.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }


    /* -------------------------
       ۴) کنترل تعداد سبد خرید (qty +/-)
    ------------------------- */
    document.querySelectorAll('.qty-control').forEach(function (control) {
        var minus = control.querySelector('.qty-btn.minus');
        var plus = control.querySelector('.qty-btn.plus');
        var value = control.querySelector('.qty-value');
        if (!minus || !plus || !value) return;

        function update(delta) {
            var current = parseInt(value.textContent, 10) || 1;
            var next = Math.min(99, Math.max(1, current + delta));
            value.textContent = next;
            control.dispatchEvent(new CustomEvent('qtychange', {
                bubbles: true,
                detail: { qty: next }
            }));
        }

        minus.addEventListener('click', function () { update(-1); });
        plus.addEventListener('click', function () { update(1); });
    });


    /* -------------------------
       ۵) حذف آیتم سبد خرید (بدون رفرش صفحه)
    ------------------------- */
    document.querySelectorAll('.cart-item-row .cart-remove').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('.cart-item-row');
            if (!row) return;
            var form = btn.closest('form');
            if (form) {
                form.submit();
                return;
            }
            row.style.transition = '0.25s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(20px)';
            setTimeout(function () { row.remove(); }, 250);
        });
    });


    /* -------------------------
       ۶) فیلترها: ارسال خودکار فرم فیلتر هنرمندان
    ------------------------- */
    var filterForm = document.getElementById('artist-filter-form') ||
                     document.querySelector('.filter-sidebar form');

    if (filterForm) {
        filterForm.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach(function (input) {
            input.addEventListener('change', function () {
                filterForm.submit();
            });
        });
    }


    /* -------------------------
       ۷) ناحیه آپلود نمونه‌کار (داشبورد هنرمند)
    ------------------------- */
    document.querySelectorAll('.portfolio-upload-zone').forEach(function (zone) {
        var input = zone.querySelector('input[type="file"]');

        zone.addEventListener('click', function () {
            if (input) input.click();
        });

        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });

        zone.addEventListener('dragleave', function () {
            zone.classList.remove('dragover');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (input && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                zone.dispatchEvent(new CustomEvent('fileselected', {
                    bubbles: true,
                    detail: { files: e.dataTransfer.files }
                }));
            }
        });

        if (input) {
            input.addEventListener('change', function () {
                if (input.files.length) {
                    var label = zone.querySelector('.upload-label');
                    if (label) label.textContent = input.files[0].name;
                }
            });
        }
    });


    /* -------------------------
       ۸) ردیف‌های انتخابی جداول مدیریت (تأیید قبل از عملیات خطرناک)
    ------------------------- */
    document.querySelectorAll('.admin-action-btn.danger').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!window.confirm('آیا از انجام این عملیات مطمئن هستید؟')) {
                e.preventDefault();
            }
        });
    });
})();
