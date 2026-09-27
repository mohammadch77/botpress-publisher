<?php

defined('ABSPATH') || exit;

class BPAI_Admin {
    private const CAP = 'manage_options';

    public static function init(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
        add_action('in_admin_header', [self::class, 'hide_foreign_notices'], 1000);
        foreach (['save_settings', 'test_llm', 'list_models', 'test_dfs', 'create_license', 'license_action'] as $action) {
            add_action('admin_post_bpai_' . $action, [self::class, 'handle_' . $action]);
        }
    }

    public static function menu(): void {
        add_menu_page('سرور هوش مصنوعی', 'سرور هوش مصنوعی', self::CAP, 'bpai', [self::class, 'page_settings'], 'dashicons-superhero', 31);
        add_submenu_page('bpai', 'تنظیمات و اتصال', 'تنظیمات و اتصال', self::CAP, 'bpai', [self::class, 'page_settings']);
        add_submenu_page('bpai', 'لایسنس‌ها و اعتبار', 'لایسنس‌ها و اعتبار', self::CAP, 'bpai-licenses', [self::class, 'page_licenses']);
        add_submenu_page('bpai', 'گزارش هزینه', 'گزارش هزینه', self::CAP, 'bpai-usage', [self::class, 'page_usage']);
    }

    private static function is_our_screen(): bool {
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        return strpos($page, 'bpai') === 0;
    }

    public static function assets(): void {
        if (!self::is_our_screen()) {
            return;
        }
        // Version by file time so an updated stylesheet is never served from browser or page cache.
        $file = BPAI_PATH . 'assets/admin.css';
        wp_enqueue_style('bpai-admin', BPAI_URL . 'assets/admin.css', ['dashicons'], (string) (file_exists($file) ? filemtime($file) : BPAI_VERSION));
    }

    /** Other plugins' promo notices (Elementor, WooCommerce, ...) clutter these pages; keep only ours. */
    public static function hide_foreign_notices(): void {
        if (self::is_our_screen()) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }

    /* ---------- helpers ---------- */

    private static function guard(string $action): void {
        if (!current_user_can(self::CAP)) {
            wp_die('دسترسی ندارید.');
        }
        check_admin_referer('bpai_' . $action);
    }

    private static function flash(string $type, string $message, array $extra = []): void {
        set_transient('bpai_flash_' . get_current_user_id(), ['type' => $type, 'message' => $message] + $extra, 120);
    }

    private static function take_flash(): ?array {
        $key = 'bpai_flash_' . get_current_user_id();
        $flash = get_transient($key);
        delete_transient($key);
        return $flash ?: null;
    }

    private static function back(string $page = 'bpai'): void {
        $tab = sanitize_key((string) ($_POST['tab'] ?? ''));
        wp_safe_redirect(admin_url('admin.php?page=' . $page) . ($tab !== '' ? '#' . $tab : ''));
        exit;
    }

    private static function toman($value): string {
        return number_format_i18n((float) $value) . ' تومان';
    }

    private static function render_flash(): void {
        $flash = self::take_flash();
        if (!$flash) {
            return;
        }
        printf('<div class="bpai-notice bpai-notice--%s">%s', esc_attr($flash['type']), esc_html($flash['message']));
        if (!empty($flash['detail'])) {
            printf('<pre dir="auto">%s</pre>', esc_html($flash['detail']));
        }
        echo '</div>';
    }

    private static function field_open(string $action): void {
        printf('<form method="post" action="%s">', esc_url(admin_url('admin-post.php')));
        printf('<input type="hidden" name="action" value="bpai_%s">', esc_attr($action));
        wp_nonce_field('bpai_' . $action);
    }

    /* ---------- pages ---------- */

    private static function short_toman(float $value): string {
        if ($value >= 1000000) {
            return number_format_i18n($value / 1000000, $value % 1000000 ? 1 : 0) . ' میلیون';
        }
        if ($value >= 1000) {
            return number_format_i18n($value / 1000) . ' هزار';
        }
        return number_format_i18n($value);
    }

    private static function price_chip(string $model): string {
        [$in, $out] = BPAI_Settings::price_for($model);
        if (!$in && !$out) {
            return '<span class="bpai-chip bpai-chip--warn">قیمت ثبت نشده</span>';
        }
        return sprintf(
            '<span class="bpai-chip" title="تومان برای هر یک میلیون توکن">ورودی %s · خروجی %s</span>',
            esc_html(self::short_toman($in)),
            esc_html(self::short_toman($out))
        );
    }

    private static function status_tile(string $icon, string $label, string $value, string $state, string $hint = ''): void {
        printf(
            '<div class="bpai-tile bpai-tile--%s"><span class="bpai-tile__icon dashicons dashicons-%s" aria-hidden="true"></span><span class="bpai-tile__label">%s</span><span class="bpai-tile__value">%s</span>%s</div>',
            esc_attr($state),
            esc_attr($icon),
            esc_html($label),
            esc_html($value),
            $hint !== '' ? '<span class="bpai-tile__hint">' . esc_html($hint) . '</span>' : ''
        );
    }

    public static function page_settings(): void {
        $s = BPAI_Settings::all();
        $price_models = array_keys($s['prices']);
        $llm_ready = $s['llm_base_url'] !== '' && $s['llm_api_key_enc'] !== '';
        $dfs_ready = $s['dfs_login'] !== '' && $s['dfs_password_enc'] !== '';
        $active_licenses = count(array_filter(BPAI_Licenses::all(), static fn($l) => $l['status'] === 'active'));
        $month_cost = array_sum(array_map(static fn($r) => (float) $r['cost_toman'], BPAI_Usage::summary(30)));

        echo '<div class="wrap bpai" dir="rtl">';
        echo '<header class="bpai-head"><div><h1>سرور هوش مصنوعی</h1><p>مدیریت اتصال‌ها، مدل‌ها و قیمت‌گذاری سرویس تولید مقاله. این صفحه فقط برای شماست و مشتری‌ها آن را نمی‌بینند.</p></div></header>';
        self::render_flash();

        echo '<section class="bpai-tiles">';
        self::status_tile('admin-network', 'مدل زبانی', $llm_ready ? 'تنظیم شده' : 'تنظیم نشده', $llm_ready ? 'ok' : 'warn', $llm_ready ? wp_parse_url($s['llm_base_url'], PHP_URL_HOST) : 'آدرس و کلید درگاه را وارد کنید');
        self::status_tile('search', 'DataForSEO', $dfs_ready ? 'تنظیم شده' : 'تنظیم نشده', $dfs_ready ? 'ok' : 'warn', $dfs_ready ? $s['dfs_login'] : 'برای تحقیق رقبا لازم است');
        self::status_tile('id-alt', 'لایسنس‌های فعال', number_format_i18n($active_licenses), 'neutral');
        self::status_tile('chart-area', 'هزینهٔ ۳۰ روز اخیر', self::short_toman($month_cost) . ' تومان', 'neutral');
        echo '</section>';

        // Test forms live outside the settings form (forms cannot nest); buttons reach them via the form attribute.
        foreach (['test_llm', 'list_models', 'test_dfs'] as $action) {
            printf('<form id="bpai-form-%1$s" method="post" action="%2$s" hidden><input type="hidden" name="action" value="bpai_%1$s">', esc_attr($action), esc_url(admin_url('admin-post.php')));
            wp_nonce_field('bpai_' . $action);
            echo '</form>';
        }

        $tabs = [
            'connection' => ['اتصال', 'admin-network'],
            'models'     => ['مدل‌ها', 'layout'],
            'seo'        => ['DataForSEO', 'search'],
            'sales'      => ['فروش و اعتبار', 'cart'],
            'prices'     => ['قیمت مدل‌ها', 'money-alt'],
        ];
        echo '<nav class="bpai-tabs" role="tablist">';
        foreach ($tabs as $key => [$label, $icon]) {
            printf('<button type="button" role="tab" class="bpai-tab" data-tab="%1$s" id="bpai-tab-%1$s" aria-controls="bpai-panel-%1$s"><span class="dashicons dashicons-%3$s" aria-hidden="true"></span>%2$s</button>', esc_attr($key), esc_html($label), esc_attr($icon));
        }
        echo '</nav>';

        self::field_open('save_settings');
        ?>
        <section class="bpai-panel" id="bpai-panel-connection" role="tabpanel" data-panel="connection">
            <div class="bpai-panel__intro">
                <h2>اتصال به درگاه هوش مصنوعی</h2>
                <p>آدرس درگاه را از پنل آروان‌کلاد، بخش «لیست درگاه‌های AI» کپی کنید. هر سرویس سازگار با OpenAI (مثل OpenRouter) هم کار می‌کند.</p>
            </div>
            <div class="bpai-fields">
                <label class="bpai-field bpai-field--wide" for="llm_base_url"><span>آدرس درگاه (Base URL)</span>
                    <input id="llm_base_url" name="llm_base_url" type="url" dir="ltr" value="<?php echo esc_attr($s['llm_base_url']); ?>" placeholder="https://.../v1"></label>
                <label class="bpai-field" for="llm_auth_scheme"><span>نوع احراز هویت</span>
                    <select id="llm_auth_scheme" name="llm_auth_scheme">
                        <option value="apikey" <?php selected($s['llm_auth_scheme'], 'apikey'); ?>>apikey — آروان‌کلاد</option>
                        <option value="bearer" <?php selected($s['llm_auth_scheme'], 'bearer'); ?>>Bearer — OpenAI / OpenRouter</option>
                    </select></label>
                <label class="bpai-field" for="llm_timeout"><span>حداکثر زمان انتظار (ثانیه)</span>
                    <input id="llm_timeout" name="llm_timeout" type="number" min="20" max="600" value="<?php echo esc_attr($s['llm_timeout']); ?>"></label>
                <label class="bpai-field bpai-field--wide" for="llm_api_key"><span>کلید API</span>
                    <input id="llm_api_key" name="llm_api_key" type="password" dir="ltr" autocomplete="off"
                        placeholder="<?php echo esc_attr($s['llm_api_key_enc'] ? BPAI_Crypto::mask(BPAI_Settings::llm_api_key()) : 'کلید را وارد کنید'); ?>">
                    <?php if ($s['llm_api_key_enc']) : ?><small>کلید ذخیره شده است. فقط برای تغییر، مقدار جدید وارد کنید.</small><?php endif; ?></label>
            </div>
            <div class="bpai-testbar">
                <span>تست با یک درخواست بسیار کوچک (قبلش ذخیره کنید):</span>
                <select name="stage" form="bpai-form-test_llm" aria-label="مرحلهٔ تست">
                    <?php foreach (BPAI_Settings::stages() as $key => $stage) :
                        if (in_array($key, ['image', 'image_pro', 'embedding'], true)) {
                            continue;
                        } ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($stage['label']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bpai-btn bpai-btn--ghost" form="bpai-form-test_llm">تست مدل</button>
                <button type="submit" class="bpai-btn bpai-btn--ghost" form="bpai-form-list_models">لیست مدل‌های در دسترس</button>
            </div>
        </section>

        <section class="bpai-panel" id="bpai-panel-models" role="tabpanel" data-panel="models" hidden>
            <div class="bpai-panel__intro">
                <h2>مدل هر مرحله</h2>
                <p>هر مرحلهٔ تولید مقاله با مدل جداگانه‌ای اجرا می‌شود. قیمت کنار هر مدل، هزینهٔ هر یک میلیون توکن به تومان است.</p>
            </div>
            <div class="bpai-stages">
                <?php foreach (BPAI_Settings::stages() as $key => $stage) :
                    $current = $s['models'][$key];
                    $options = $price_models;
                    if ($current !== '' && !in_array($current, $options, true)) {
                        $options[] = $current;
                    } ?>
                    <div class="bpai-stage">
                        <label for="model_<?php echo esc_attr($key); ?>"><?php echo esc_html($stage['label']); ?></label>
                        <select id="model_<?php echo esc_attr($key); ?>" name="models[<?php echo esc_attr($key); ?>]" dir="ltr">
                            <?php foreach ($options as $m) : ?>
                                <option value="<?php echo esc_attr($m); ?>" <?php selected($current, $m); ?>><?php echo esc_html($m); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php echo self::price_chip($current); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="bpai-note">مدلی در لیست نیست؟ اول در تب «قیمت مدل‌ها» اضافه‌اش کنید.</p>
        </section>

        <section class="bpai-panel" id="bpai-panel-seo" role="tabpanel" data-panel="seo" hidden>
            <div class="bpai-panel__intro">
                <h2>DataForSEO</h2>
                <p>نتایج گوگل، سؤال‌های پرتکرار، ترندها و حجم جست‌وجو از این سرویس گرفته می‌شود. اطلاعات را از داشبورد DataForSEO، بخش API Access بردارید.</p>
            </div>
            <div class="bpai-fields">
                <label class="bpai-field" for="dfs_login"><span>نام کاربری API (ایمیل)</span>
                    <input id="dfs_login" name="dfs_login" dir="ltr" value="<?php echo esc_attr($s['dfs_login']); ?>"></label>
                <label class="bpai-field" for="dfs_password"><span>رمز API</span>
                    <input id="dfs_password" name="dfs_password" type="password" dir="ltr" autocomplete="off"
                        placeholder="<?php echo esc_attr($s['dfs_password_enc'] ? '••••••••' : 'رمز را وارد کنید'); ?>">
                    <?php if ($s['dfs_password_enc']) : ?><small>رمز ذخیره شده است.</small><?php endif; ?></label>
                <label class="bpai-field" for="dfs_location"><span>کد کشور پیش‌فرض</span>
                    <input id="dfs_location" name="dfs_location" type="number" value="<?php echo esc_attr($s['dfs_location']); ?>"><small>۲۳۶۴ یعنی ایران</small></label>
                <label class="bpai-field" for="dfs_language"><span>زبان پیش‌فرض</span>
                    <input id="dfs_language" name="dfs_language" dir="ltr" value="<?php echo esc_attr($s['dfs_language']); ?>"></label>
                <label class="bpai-field bpai-field--wide" for="dfs_proxy_url"><span>آدرس پروکسی (اختیاری)</span>
                    <input id="dfs_proxy_url" name="dfs_proxy_url" type="url" dir="ltr" value="<?php echo esc_attr($s['dfs_proxy_url']); ?>" placeholder="https://dfs-proxy.example.workers.dev">
                    <small>فقط اگر تست به‌خاطر IP ایران ناموفق شد پر کنید.</small></label>
            </div>
            <div class="bpai-testbar">
                <span>بعد از ذخیره، اتصال و موجودی حساب را بررسی کنید:</span>
                <button type="submit" class="bpai-btn bpai-btn--ghost" form="bpai-form-test_dfs">تست DataForSEO</button>
            </div>
        </section>

        <section class="bpai-panel" id="bpai-panel-sales" role="tabpanel" data-panel="sales" hidden>
            <div class="bpai-panel__intro">
                <h2>فروش و اعتبار</h2>
                <p>هر مقالهٔ استاندارد یک اعتبار مصرف می‌کند. قیمت اعتبار در مرحلهٔ پرداخت آنلاین به مشتری نمایش داده می‌شود.</p>
            </div>
            <div class="bpai-fields">
                <label class="bpai-field" for="credit_price"><span>قیمت هر اعتبار (تومان)</span>
                    <input id="credit_price" name="credit_price" type="number" min="0" step="1000" value="<?php echo esc_attr($s['credit_price']); ?>"></label>
                <label class="bpai-field" for="pro_credit_cost"><span>اعتبار لازم برای مقالهٔ حرفه‌ای</span>
                    <input id="pro_credit_cost" name="pro_credit_cost" type="number" min="1" max="10" value="<?php echo esc_attr($s['pro_credit_cost']); ?>"></label>
            </div>
        </section>

        <section class="bpai-panel" id="bpai-panel-prices" role="tabpanel" data-panel="prices" hidden>
            <div class="bpai-panel__intro">
                <h2>قیمت مدل‌ها</h2>
                <p>فقط برای محاسبهٔ هزینهٔ واقعی هر مقاله در «گزارش هزینه» استفاده می‌شود. اگر آروان قیمتی را تغییر داد، اینجا به‌روز کنید. برای حذف یک مدل، نامش را خالی کنید.</p>
            </div>
            <div class="bpai-table-wrap">
                <table class="bpai-prices">
                    <thead><tr><th>مدل</th><th>ورودی (تومان / ۱ میلیون توکن)</th><th>خروجی (تومان / ۱ میلیون توکن)</th></tr></thead>
                    <tbody>
                    <?php $i = 0; foreach ($s['prices'] as $model => $price) : ?>
                        <tr>
                            <td><input name="prices[<?php echo $i; ?>][model]" dir="ltr" value="<?php echo esc_attr($model); ?>" aria-label="نام مدل"></td>
                            <td><input name="prices[<?php echo $i; ?>][in]" type="number" min="0" value="<?php echo esc_attr($price[0]); ?>" aria-label="قیمت ورودی"></td>
                            <td><input name="prices[<?php echo $i; ?>][out]" type="number" min="0" value="<?php echo esc_attr($price[1]); ?>" aria-label="قیمت خروجی"></td>
                        </tr>
                    <?php $i++; endforeach; ?>
                        <tr class="bpai-prices__new">
                            <td><input name="prices[<?php echo $i; ?>][model]" dir="ltr" placeholder="+ مدل جدید" aria-label="نام مدل جدید"></td>
                            <td><input name="prices[<?php echo $i; ?>][in]" type="number" min="0" aria-label="قیمت ورودی"></td>
                            <td><input name="prices[<?php echo $i; ?>][out]" type="number" min="0" aria-label="قیمت خروجی"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <input type="hidden" name="tab" id="bpai-current-tab" value="connection">
        <div class="bpai-savebar">
            <button type="submit" class="bpai-btn bpai-btn--primary">ذخیرهٔ تنظیمات</button>
            <span>تغییرات همهٔ تب‌ها با هم ذخیره می‌شود.</span>
        </div>
        </form>
        <script>
        (function () {
            var tabs = document.querySelectorAll('.bpai-tab');
            var panels = document.querySelectorAll('.bpai-panel');
            var field = document.getElementById('bpai-current-tab');
            function show(name) {
                var found = false;
                tabs.forEach(function (t) { var on = t.dataset.tab === name; found = found || on; t.setAttribute('aria-selected', on); });
                if (!found) { return show('connection'); }
                panels.forEach(function (p) { p.hidden = p.dataset.panel !== name; });
                field.value = name;
                document.querySelectorAll('form[id^="bpai-form-"]').forEach(function (f) {
                    f.action = f.action.split('#')[0];
                    var input = f.querySelector('input[name="tab"]') || f.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name: 'tab' }));
                    input.value = name;
                });
                if (history.replaceState) { history.replaceState(null, '', '#' + name); }
            }
            tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); }); });
            show((location.hash || '#connection').slice(1));
        })();
        </script>
        <?php
        echo '</div>';
    }

    public static function page_licenses(): void {
        echo '<div class="wrap bpai" dir="rtl"><header class="bpai-head"><div><h1>لایسنس‌ها و اعتبار</h1><p>برای هر مشتری یک کلید بسازید و اعتبارش را مدیریت کنید. هر کلید به اولین سایتی که با آن وصل شود قفل می‌شود.</p></div></header>';
        self::render_flash();

        self::field_open('create_license');
        ?>
        <div class="bpai-card">
            <h2>ساخت لایسنس جدید</h2>
            <div class="bpai-grid">
                <label>نام مشتری<input name="customer_name" required></label>
                <label>راه ارتباطی (موبایل / ایمیل)<input name="customer_contact" dir="auto"></label>
                <label>اعتبار اولیه<input name="credits" type="number" value="1" min="0"></label>
                <label>یادداشت<input name="note"></label>
            </div>
            <?php submit_button('ساخت لایسنس', 'primary', 'submit', false); ?>
        </div>
        </form>
        <?php

        $licenses = BPAI_Licenses::all();
        echo '<div class="bpai-card"><h2>لیست لایسنس‌ها</h2>';
        if (!$licenses) {
            echo '<p>هنوز لایسنسی ساخته نشده است.</p></div></div>';
            return;
        }
        echo '<table class="widefat striped"><thead><tr><th>مشتری</th><th>کلید</th><th>سایت متصل</th><th>وضعیت</th><th>اعتبار</th><th>آخرین اتصال</th><th>عملیات</th></tr></thead><tbody>';
        foreach ($licenses as $l) {
            echo '<tr>';
            printf('<td><strong>%s</strong><br><small>%s</small></td>', esc_html($l['customer_name']), esc_html($l['customer_contact']));
            printf('<td dir="ltr">BPAI-…%s</td>', esc_html($l['key_hint']));
            printf('<td dir="ltr">%s</td>', $l['site_url'] ? esc_html($l['site_url']) : '<span class="bpai-muted">هنوز متصل نشده</span>');
            printf('<td>%s</td>', $l['status'] === 'active' ? '<span class="bpai-badge bpai-badge--ok">فعال</span>' : '<span class="bpai-badge bpai-badge--bad">غیرفعال</span>');
            printf('<td><strong>%s</strong></td>', esc_html(number_format_i18n((int) $l['credits'])));
            printf('<td>%s</td>', $l['last_seen_at'] ? esc_html(mysql2date('Y/m/d H:i', $l['last_seen_at'])) : '—');
            echo '<td class="bpai-row-actions">';
            self::field_open('license_action');
            printf('<input type="hidden" name="license_id" value="%d">', (int) $l['id']);
            echo '<input name="delta" type="number" placeholder="+/- اعتبار" class="small-text"> ';
            echo '<button class="button" name="do" value="credits">اعمال اعتبار</button> ';
            if ($l['status'] === 'active') {
                echo '<button class="button" name="do" value="suspend">غیرفعال‌سازی</button> ';
            } else {
                echo '<button class="button" name="do" value="activate">فعال‌سازی</button> ';
            }
            if ($l['site_url']) {
                echo '<button class="button" name="do" value="reset_site" onclick="return confirm(\'اتصال این لایسنس به سایت فعلی حذف شود؟\')">آزادسازی سایت</button>';
            }
            echo '</form></td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    public static function page_usage(): void {
        echo '<div class="wrap bpai" dir="rtl"><header class="bpai-head"><div><h1>گزارش هزینه</h1><p>هزینهٔ واقعی هر درخواست به مدل‌ها در ۳۰ روز اخیر، بر اساس قیمت‌های ثبت‌شده.</p></div></header>';
        $summary = BPAI_Usage::summary(30);
        $total = array_sum(array_map(static fn($r) => (float) $r['cost_toman'], $summary));
        printf('<div class="bpai-card"><h2>جمع هزینه: %s</h2>', esc_html(self::toman($total)));
        if (!$summary) {
            echo '<p>هنوز درخواستی ثبت نشده است.</p></div>';
        } else {
            echo '<table class="widefat striped"><thead><tr><th>مرحله</th><th>مدل</th><th>تعداد درخواست</th><th>توکن ورودی</th><th>توکن خروجی</th><th>ناموفق</th><th>هزینه</th></tr></thead><tbody>';
            $stages = BPAI_Settings::stages();
            foreach ($summary as $r) {
                printf(
                    '<tr><td>%s</td><td dir="ltr">%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    esc_html($stages[$r['stage']]['label'] ?? ($r['stage'] === 'test' ? 'تست اتصال' : $r['stage'])),
                    esc_html($r['model']),
                    esc_html(number_format_i18n((int) $r['calls'])),
                    esc_html(number_format_i18n((int) $r['input_tokens'])),
                    esc_html(number_format_i18n((int) $r['output_tokens'])),
                    esc_html(number_format_i18n((int) $r['failures'])),
                    esc_html(self::toman($r['cost_toman']))
                );
            }
            echo '</tbody></table></div>';
        }

        echo '<div class="bpai-card"><h2>آخرین درخواست‌ها</h2><table class="widefat striped"><thead><tr><th>زمان</th><th>مرحله</th><th>مدل</th><th>توکن (ورودی / خروجی)</th><th>مدت</th><th>هزینه</th><th>نتیجه</th></tr></thead><tbody>';
        foreach (BPAI_Usage::recent(30) as $r) {
            printf(
                '<tr><td>%s</td><td>%s</td><td dir="ltr">%s</td><td>%s / %s</td><td>%s ثانیه</td><td>%s</td><td>%s</td></tr>',
                esc_html(mysql2date('Y/m/d H:i', $r['created_at'])),
                esc_html($r['stage']),
                esc_html($r['model']),
                esc_html(number_format_i18n((int) $r['input_tokens'])),
                esc_html(number_format_i18n((int) $r['output_tokens'])),
                esc_html(number_format_i18n((int) $r['duration_ms'] / 1000, 1)),
                esc_html(self::toman($r['cost_toman'])),
                $r['success'] ? '<span class="bpai-badge bpai-badge--ok">موفق</span>' : '<span class="bpai-badge bpai-badge--bad" title="' . esc_attr((string) $r['error']) . '">ناموفق</span>'
            );
        }
        echo '</tbody></table></div></div>';
    }

    /* ---------- handlers ---------- */

    public static function handle_save_settings(): void {
        self::guard('save_settings');
        $in = wp_unslash($_POST);
        $values = [
            'llm_base_url'    => esc_url_raw(trim((string) ($in['llm_base_url'] ?? ''))),
            'llm_auth_scheme' => ($in['llm_auth_scheme'] ?? '') === 'bearer' ? 'bearer' : 'apikey',
            'llm_timeout'     => max(20, min(600, (int) ($in['llm_timeout'] ?? 120))),
            'dfs_login'       => sanitize_text_field((string) ($in['dfs_login'] ?? '')),
            'dfs_proxy_url'   => esc_url_raw(trim((string) ($in['dfs_proxy_url'] ?? ''))),
            'dfs_location'    => (int) ($in['dfs_location'] ?? 2364),
            'dfs_language'    => sanitize_key((string) ($in['dfs_language'] ?? 'fa')),
            'credit_price'    => max(0, (int) ($in['credit_price'] ?? 0)),
            'pro_credit_cost' => max(1, min(10, (int) ($in['pro_credit_cost'] ?? 2))),
        ];
        if (!empty($in['llm_api_key'])) {
            $values['llm_api_key_enc'] = BPAI_Crypto::encrypt(trim((string) $in['llm_api_key']));
        }
        if (!empty($in['dfs_password'])) {
            $values['dfs_password_enc'] = BPAI_Crypto::encrypt(trim((string) $in['dfs_password']));
        }

        $models = [];
        foreach (array_keys(BPAI_Settings::stages()) as $stage) {
            $models[$stage] = sanitize_text_field((string) ($in['models'][$stage] ?? ''));
        }
        $values['models'] = $models;

        $prices = [];
        foreach ((array) ($in['prices'] ?? []) as $row) {
            $model = sanitize_text_field((string) ($row['model'] ?? ''));
            if ($model !== '') {
                $prices[$model] = [max(0, (float) ($row['in'] ?? 0)), max(0, (float) ($row['out'] ?? 0))];
            }
        }
        $values['prices'] = $prices;

        BPAI_Settings::update($values);
        self::flash('success', 'تنظیمات ذخیره شد.');
        self::back();
    }

    public static function handle_test_llm(): void {
        self::guard('test_llm');
        $stage = sanitize_key((string) ($_POST['stage'] ?? 'research'));
        $model = BPAI_Settings::model_for($stage);
        $result = (new BPAI_LLM_Client())->chat($model, [
            ['role' => 'system', 'content' => 'You are a connectivity check. Reply in Persian, one short sentence.'],
            ['role' => 'user', 'content' => 'سلام؛ فقط بگو اتصال برقرار است.'],
        ], ['max_tokens' => 60], ['stage' => 'test']);

        if (is_wp_error($result)) {
            self::flash('error', "تست مدل «{$model}» ناموفق بود: " . $result->get_error_message());
        } else {
            self::flash('success', "مدل «{$model}» پاسخ داد.", [
                'detail' => sprintf(
                    "پاسخ: %s\nتوکن ورودی/خروجی: %d / %d\nهزینه: %s",
                    $result['content'], $result['input_tokens'], $result['output_tokens'], self::toman($result['cost_toman'])
                ),
            ]);
        }
        self::back();
    }

    public static function handle_list_models(): void {
        self::guard('list_models');
        $models = (new BPAI_LLM_Client())->list_models();
        if (is_wp_error($models)) {
            self::flash('error', 'دریافت لیست مدل‌ها ناموفق بود: ' . $models->get_error_message());
        } else {
            self::flash('success', 'تعداد مدل‌های در دسترس: ' . number_format_i18n(count($models)), ['detail' => implode("\n", $models)]);
        }
        self::back();
    }

    public static function handle_test_dfs(): void {
        self::guard('test_dfs');
        $account = (new BPAI_DataForSEO())->account();
        if (is_wp_error($account)) {
            self::flash('error', $account->get_error_message());
        } else {
            self::flash('success', sprintf('اتصال به DataForSEO برقرار است. حساب: %s — موجودی: %s دلار', $account['login'], number_format($account['balance'], 2)));
        }
        self::back();
    }

    public static function handle_create_license(): void {
        self::guard('create_license');
        $name = sanitize_text_field(wp_unslash((string) ($_POST['customer_name'] ?? '')));
        if ($name === '') {
            self::flash('error', 'نام مشتری الزامی است.');
            self::back('bpai-licenses');
        }
        $created = BPAI_Licenses::create(
            $name,
            sanitize_text_field(wp_unslash((string) ($_POST['customer_contact'] ?? ''))),
            max(0, (int) ($_POST['credits'] ?? 0)),
            sanitize_text_field(wp_unslash((string) ($_POST['note'] ?? '')))
        );
        self::flash('success', 'لایسنس ساخته شد. این کلید فقط همین یک بار نمایش داده می‌شود؛ آن را برای مشتری بفرستید:', ['detail' => $created['key']]);
        self::back('bpai-licenses');
    }

    public static function handle_license_action(): void {
        self::guard('license_action');
        $id = (int) ($_POST['license_id'] ?? 0);
        if (!BPAI_Licenses::find($id)) {
            self::flash('error', 'لایسنس پیدا نشد.');
            self::back('bpai-licenses');
        }
        switch (sanitize_key((string) ($_POST['do'] ?? ''))) {
            case 'credits':
                $delta = (int) ($_POST['delta'] ?? 0);
                if ($delta === 0) {
                    self::flash('error', 'مقدار اعتبار را وارد کنید (مثبت برای افزودن، منفی برای کسر).');
                    break;
                }
                $balance = BPAI_Licenses::adjust_credits($id, $delta, 'manual', 'تغییر دستی توسط مدیر');
                is_wp_error($balance)
                    ? self::flash('error', $balance->get_error_message())
                    : self::flash('success', 'اعتبار جدید: ' . number_format_i18n($balance));
                break;
            case 'suspend':
                BPAI_Licenses::set_status($id, 'suspended');
                self::flash('success', 'لایسنس غیرفعال شد.');
                break;
            case 'activate':
                BPAI_Licenses::set_status($id, 'active');
                self::flash('success', 'لایسنس فعال شد.');
                break;
            case 'reset_site':
                BPAI_Licenses::reset_site($id);
                self::flash('success', 'سایت آزاد شد؛ اولین سایتی که با این کلید متصل شود ثبت می‌شود.');
                break;
        }
        self::back('bpai-licenses');
    }
}
