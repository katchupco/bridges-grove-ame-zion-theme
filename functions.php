<?php
/**
 * Bridges Grove AME Zion Theme
 */
if (!defined('ABSPATH')) { exit; }

define('BG_THEME_VERSION', '2.8.20');
define('BG_THEME_DIR', get_template_directory());
define('BG_THEME_URI', get_template_directory_uri());

require_once BG_THEME_DIR . '/inc/customizer.php';
require_once BG_THEME_DIR . '/inc/github-updater.php';

/**
 * Theme setup
 */
add_action('after_setup_theme', function () {
    load_theme_textdomain('bridges-grove', BG_THEME_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', array('search-form','comment-form','comment-list','gallery','caption','style','script'));
    add_theme_support('custom-logo', array(
        'height'      => 80,
        'width'       => 320,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('editor-styles');
    add_editor_style('assets/css/editor.css');

    register_nav_menus(array(
        'primary' => __('Primary Menu', 'bridges-grove'),
        'footer'  => __('Footer Menu', 'bridges-grove'),
    ));
});

/**
 * Enqueue assets
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('bg-theme', BG_THEME_URI . '/assets/css/theme.css', array(), BG_THEME_VERSION);
    wp_enqueue_script('bg-theme', BG_THEME_URI . '/assets/js/theme.js', array(), BG_THEME_VERSION, true);

    // Inline CSS variables from Customizer
    $accent_maroon = sanitize_hex_color(get_theme_mod('bg_accent_maroon', '#7a0f1c')) ?: '#7a0f1c';
    $accent_green  = sanitize_hex_color(get_theme_mod('bg_accent_green',  '#1f5632')) ?: '#1f5632';
    $accent_gold   = sanitize_hex_color(get_theme_mod('bg_accent_gold',   '#d6a13a')) ?: '#d6a13a';
    $hero_overlay  = floatval(get_theme_mod('bg_hero_overlay', 0.45));
    if ($hero_overlay < 0) $hero_overlay = 0;
    if ($hero_overlay > 0.85) $hero_overlay = 0.85;

    $css = ":root{--bg-maroon:{$accent_maroon};--bg-green:{$accent_green};--bg-gold:{$accent_gold};--bg-hero-overlay:{$hero_overlay};}";
    wp_add_inline_style('bg-theme', $css);
});

/**
 * Easy event management in the WordPress dashboard.
 */
function bg_register_event_type(): void {
    register_post_type('bg_event', array(
        'labels' => array(
            'name'               => __('Church Events', 'bridges-grove'),
            'singular_name'      => __('Church Event', 'bridges-grove'),
            'add_new_item'       => __('Add New Church Event', 'bridges-grove'),
            'edit_item'          => __('Edit Church Event', 'bridges-grove'),
            'new_item'           => __('New Church Event', 'bridges-grove'),
            'view_item'          => __('View Church Event', 'bridges-grove'),
            'search_items'       => __('Search Church Events', 'bridges-grove'),
            'not_found'          => __('No church events found', 'bridges-grove'),
        ),
        'public'       => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-calendar-alt',
        'supports'     => array('title', 'editor', 'excerpt', 'thumbnail'),
        'has_archive'  => true,
        'rewrite'      => array('slug' => 'church-events'),
    ));
}
add_action('init', 'bg_register_event_type');

add_action('after_switch_theme', function () {
    bg_register_event_type();
    flush_rewrite_rules();
});

add_action('add_meta_boxes', function () {
    add_meta_box('bg_event_details', __('Event Details', 'bridges-grove'), 'bg_render_event_meta_box', 'bg_event', 'normal', 'high');
});

function bg_render_event_meta_box($post): void {
    wp_nonce_field('bg_save_event_details', 'bg_event_details_nonce');
    $start = get_post_meta($post->ID, '_bg_event_start', true);
    $location = get_post_meta($post->ID, '_bg_event_location', true);
    $button_label = get_post_meta($post->ID, '_bg_event_button_label', true);
    $button_url = get_post_meta($post->ID, '_bg_event_button_url', true);
    echo '<p><label><strong>' . esc_html__('Date and time', 'bridges-grove') . '</strong><br><input type="datetime-local" name="bg_event_start" value="' . esc_attr($start) . '" style="width:100%;max-width:420px"></label></p>';
    echo '<p><label><strong>' . esc_html__('Location', 'bridges-grove') . '</strong><br><input type="text" name="bg_event_location" value="' . esc_attr($location) . '" placeholder="' . esc_attr__('Sanctuary, Fellowship Hall, Online, etc.', 'bridges-grove') . '" style="width:100%;max-width:620px"></label></p>';
    echo '<p><label><strong>' . esc_html__('Button label', 'bridges-grove') . '</strong><br><input type="text" name="bg_event_button_label" value="' . esc_attr($button_label) . '" placeholder="' . esc_attr__('Learn More', 'bridges-grove') . '" style="width:100%;max-width:420px"></label></p>';
    echo '<p><label><strong>' . esc_html__('Button URL', 'bridges-grove') . '</strong><br><input type="url" name="bg_event_button_url" value="' . esc_attr($button_url) . '" placeholder="https://" style="width:100%;max-width:620px"></label></p>';
    echo '<p>' . esc_html__('Use the Featured Image box for an event photo. The Events page will display published Church Events automatically.', 'bridges-grove') . '</p>';
}

add_action('save_post_bg_event', function ($post_id) {
    if (!isset($_POST['bg_event_details_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bg_event_details_nonce'])), 'bg_save_event_details')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = array(
        '_bg_event_start' => 'bg_event_start',
        '_bg_event_location' => 'bg_event_location',
        '_bg_event_button_label' => 'bg_event_button_label',
        '_bg_event_button_url' => 'bg_event_button_url',
    );

    foreach ($fields as $meta_key => $field_name) {
        $value = isset($_POST[$field_name]) ? wp_unslash($_POST[$field_name]) : '';
        $value = $meta_key === '_bg_event_button_url' ? esc_url_raw($value) : sanitize_text_field($value);
        if ($value === '') {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $value);
        }
    }
});

add_action('admin_post_bg_contact_submit', 'bg_handle_contact_submit');
add_action('admin_post_nopriv_bg_contact_submit', 'bg_handle_contact_submit');

function bg_handle_contact_submit(): void {
    $redirect = wp_get_referer() ?: home_url('/contact/');

    if (!isset($_POST['bg_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bg_contact_nonce'])), 'bg_contact_submit')) {
        wp_safe_redirect(add_query_arg('bg-contact', 'error', $redirect));
        exit;
    }

    $honeypot = isset($_POST['bg_contact_company']) ? trim((string) wp_unslash($_POST['bg_contact_company'])) : '';
    if ($honeypot !== '') {
        wp_safe_redirect(add_query_arg('bg-contact', 'sent', $redirect));
        exit;
    }

    $name = isset($_POST['bg_contact_name']) ? sanitize_text_field(wp_unslash($_POST['bg_contact_name'])) : '';
    $email = isset($_POST['bg_contact_email']) ? sanitize_email(wp_unslash($_POST['bg_contact_email'])) : '';
    $phone = isset($_POST['bg_contact_phone']) ? sanitize_text_field(wp_unslash($_POST['bg_contact_phone'])) : '';
    $message = isset($_POST['bg_contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['bg_contact_message'])) : '';

    if (!$name || !$email || !is_email($email) || !$message) {
        wp_safe_redirect(add_query_arg('bg-contact', 'error', $redirect));
        exit;
    }

    $to = get_option('admin_email');
    $subject = sprintf(__('New website message from %s', 'bridges-grove'), $name);
    $body = sprintf(
        "Name: %s\nEmail: %s\nPhone: %s\n\nMessage:\n%s",
        $name,
        $email,
        $phone ?: __('Not provided', 'bridges-grove'),
        $message
    );
    $headers = array('Reply-To: ' . $name . ' <' . $email . '>');

    $sent = wp_mail($to, $subject, $body, $headers);
    wp_safe_redirect(add_query_arg('bg-contact', $sent ? 'sent' : 'error', $redirect));
    exit;
}

/**
 * Small inline SVG icon set used by templates.
 */
function bg_icon($name): string {
    $icons = array(
        'church' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2v4m-2-2h4m-8 8 6-5 6 5v9H6v-9Z"/><path d="M10 21v-5a2 2 0 0 1 4 0v5"/><path d="M4 21h16"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v4m10-4v4M4 9h16"/><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 13h3m2 0h3M8 17h3"/></svg>',
        'heart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.4 5.6a5.2 5.2 0 0 0-7.4 0L12 6.7l-1-1.1a5.2 5.2 0 0 0-7.4 7.4L12 21l8.4-8a5.2 5.2 0 0 0 0-7.4Z"/></svg>',
        'play' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m10 8 6 4-6 4V8Z"/></svg>',
        'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="4"/><path d="m10 9 5 3-5 3V9Z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.4"/><path d="M17.2 6.8h.01"/></svg>',
        'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18 3 21V6l6-3 6 3 6-3v15l-6 3-6-3Z"/><path d="M9 3v15m6-12v15"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 3 5.2 2 2 0 0 1 5 3h3a2 2 0 0 1 2 1.7l.4 2.5a2 2 0 0 1-.6 1.8L8.7 10a16 16 0 0 0 5.3 5.3l1.1-1.1a2 2 0 0 1 1.8-.6l2.5.4A2 2 0 0 1 22 16.9Z"/></svg>',
        'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.2a4 4 0 0 1 0 7.6"/></svg>',
        'book' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15Z"/></svg>',
        'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>',
        'leaf' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4C10 4 5 9 5 19c10 0 15-5 15-15Z"/><path d="M5 19c3-6 7-10 15-15"/></svg>',
        'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 8h2V4h-2a5 5 0 0 0-5 5v2H8v4h2v5h4v-5h3l1-4h-4V9a1 1 0 0 1 1-1Z"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
    );

    if (!isset($icons[$name])) {
        return '';
    }

    return $icons[$name];
}

/**
 * Register a simple widget area
 */
add_action('widgets_init', function () {
    register_sidebar(array(
        'name'          => __('Sidebar', 'bridges-grove'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here.', 'bridges-grove'),
        'before_widget' => '<section class="widget bg-card">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
});

/**
 * Helper: footer icon url
 */
function bg_footer_icon_url(): string {
    $id = absint(get_theme_mod('bg_footer_icon_id', 0));
    if ($id) {
        $src = wp_get_attachment_image_src($id, 'full');
        if (!empty($src[0])) return esc_url($src[0]);
    }
    return '';
}

function bg_theme_image_url_if_exists(string $filename): string {
    $path = BG_THEME_DIR . '/assets/img/' . $filename;
    if (file_exists($path)) {
        return esc_url(BG_THEME_URI . '/assets/img/' . $filename);
    }

    return '';
}

function bg_media_url_by_filename(string $filename): string {
    static $media_urls = array();
    if (array_key_exists($filename, $media_urls)) {
        return $media_urls[$filename];
    }

    global $wpdb;
    $attachment_id = 0;
    if ($wpdb instanceof wpdb) {
        $attachment_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
            '%' . $wpdb->esc_like($filename)
        ));
    }

    if (!$attachment_id) {
        $title = preg_replace('/\.[^.]+$/', '', $filename);
        $attachments = get_posts(array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            's'              => $title,
            'fields'         => 'ids',
        ));
        $attachment_id = !empty($attachments[0]) ? absint($attachments[0]) : 0;
    }

    $media_urls[$filename] = $attachment_id ? esc_url(wp_get_attachment_url($attachment_id)) : '';
    return $media_urls[$filename];
}

function bg_custom_logo_url(): string {
    $custom_logo_id = absint(get_theme_mod('custom_logo', 0));
    if (!$custom_logo_id) {
        return '';
    }

    $image = wp_get_attachment_image_src($custom_logo_id, 'full');
    return !empty($image[0]) ? esc_url($image[0]) : '';
}

function bg_default_header_logo_url(string $tone): string {
    if ($tone === 'light') {
        return esc_url('https://bridgesgrove.org/wp-content/uploads/2026/06/LIGHT-LogoBridgesNEW.png');
    }

    return esc_url('https://bridgesgrove.org/wp-content/uploads/2026/06/Dark-LogoBridgesNEW.png');
}

function bg_header_logo_url(string $tone): string {
    $tone = $tone === 'light' ? 'light' : 'dark';
    $theme_mod_id = absint(get_theme_mod("bg_{$tone}_logo_id", 0));
    if ($theme_mod_id) {
        $image = wp_get_attachment_image_src($theme_mod_id, 'full');
        if (!empty($image[0])) {
            return esc_url($image[0]);
        }
    }

    $filenames = $tone === 'light'
        ? array('LIGHT-LogoBridgesNEW.png')
        : array('Dark-LogoBridgesNEW.png', 'DARK-LogoBridgesNEW.png');

    foreach ($filenames as $filename) {
        $media_url = bg_media_url_by_filename($filename);
        if ($media_url) {
            return $media_url;
        }
    }

    foreach ($filenames as $filename) {
        $theme_url = bg_theme_image_url_if_exists($filename);
        if ($theme_url) {
            return $theme_url;
        }
    }

    $default_logo = bg_default_header_logo_url($tone);
    if ($default_logo) {
        return $default_logo;
    }

    return $tone === 'dark' ? bg_custom_logo_url() : '';
}

function bg_normalize_theme_url($url, $default_url): string {
    $url = trim((string) $url);
    $default_url = esc_url_raw($default_url);
    if ($url === '') {
        return $default_url;
    }

    $lower_url = strtolower($url);
    if ($lower_url === 'contact' || $lower_url === '/contact' || $lower_url === 'contact/' || $lower_url === '/contact/' || $lower_url === 'http://contact' || $lower_url === 'https://contact') {
        return esc_url_raw(home_url('/contact/'));
    }

    if (strpos($url, '/') === 0) {
        return esc_url_raw(home_url($url));
    }

    if (!preg_match('#^[a-z][a-z0-9+\-.]*://#i', $url) && strpos($url, '#') !== 0 && stripos($url, 'mailto:') !== 0 && stripos($url, 'tel:') !== 0) {
        return esc_url_raw(home_url('/' . ltrim($url, '/') . '/'));
    }

    return esc_url_raw($url);
}

/**
 * Helper: CTA urls/labels
 */
function bg_get_cta($key, $default_label, $default_url) {
    $label = sanitize_text_field(get_theme_mod("bg_{$key}_label", $default_label));
    $url   = bg_normalize_theme_url(get_theme_mod("bg_{$key}_url", $default_url), $default_url);
    if ($label === '') {
        $label = $default_label;
    }
    if ($url === '') {
        $url = bg_normalize_theme_url($default_url, $default_url);
    }
    return array($label, $url);
}

function bg_church_info(): array {
    $facebook = esc_url_raw(get_theme_mod('bg_social_facebook', 'https://www.facebook.com/BridgesGroveAMEZ'));
    return array(
        'name' => __('Bridges Grove A.M.E. Zion Church', 'bridges-grove'),
        'address' => __('251 Bridges Grove Church Rd, Shannon, NC 28386', 'bridges-grove'),
        'phone' => __('(910)-565-2226', 'bridges-grove'),
        'phone_url' => 'tel:+19105652226',
        'email' => 'info@bridgesgrove.org',
        'email_url' => 'mailto:info@bridgesgrove.org',
        'facebook' => $facebook ?: 'https://www.facebook.com/BridgesGroveAMEZ',
        'service_times' => array(
            __('Sunday School: 9:45 AM', 'bridges-grove'),
            __('Sunday Worship: 11:00 AM', 'bridges-grove'),
            __('Bible Study: Wednesdays 7:00 PM', 'bridges-grove'),
        ),
    );
}

function bg_page_slug(): string {
    global $post;
    if (!$post instanceof WP_Post) return '';
    return sanitize_title($post->post_name);
}

function bg_find_page_by_slugs($slugs) {
    foreach ((array) $slugs as $slug) {
        $page = get_page_by_path($slug);
        if ($page instanceof WP_Post) return $page;
    }
    return null;
}

function bg_enhanced_page_data($slug): ?array {
    $aliases = array(
        'firstfamily' => 'first-family',
        'pastor' => 'our-pastor',
        'watch-live' => 'watch',
        'live' => 'watch',
        'plan' => 'plan-your-visit',
        'visit' => 'plan-your-visit',
    );
    $slug = $aliases[$slug] ?? $slug;
    $info = bg_church_info();
    $pages = array(
        'about' => array(
            'kicker' => __('About Us', 'bridges-grove'),
            'title' => __('Rooted in Worship. Known for Love.', 'bridges-grove'),
            'intro' => __('Bridges Grove A.M.E. Zion Church is a historic faith community committed to Christ-centered worship, intentional discipleship, and serving Shannon, NC with compassion.', 'bridges-grove'),
            'body' => __('We are a welcoming church family where faith is lived out daily through worship, service, and genuine community. Our mission is simple and strong: entering to worship God, leaving to love one another.', 'bridges-grove'),
            'cards' => array(
                array('icon' => 'church', 'title' => __('Who We Are', 'bridges-grove'), 'text' => __('A church family shaped by worship, care, and meaningful connection.', 'bridges-grove')),
                array('icon' => 'heart', 'title' => __('Our Mission', 'bridges-grove'), 'text' => __('To worship faithfully, serve compassionately, and grow together in Christ.', 'bridges-grove')),
                array('icon' => 'users', 'title' => __('Our Community', 'bridges-grove'), 'text' => __('A place where generations gather, learn, serve, and belong.', 'bridges-grove')),
            ),
            'aside_title' => __('Visit Bridges Grove', 'bridges-grove'),
            'aside_items' => array_merge(array($info['address']), $info['service_times']),
            'actions' => array(
                array('label' => __('Plan Your Visit', 'bridges-grove'), 'url' => home_url('/contact/'), 'style' => 'solid'),
                array('label' => __('Contact Us', 'bridges-grove'), 'url' => home_url('/contact/'), 'style' => 'outline'),
            ),
        ),
        'first-family' => array(
            'kicker' => __('First Family', 'bridges-grove'),
            'title' => __('A family committed to faith, leadership, and service.', 'bridges-grove'),
            'intro' => __('Reverend James A. Hayes and First Lady Katrina Hayes, alongside Chelsea and Channing, embody a spirit of dedication that reflects the mission and values of Bridges Grove.', 'bridges-grove'),
            'body' => __('Reverend Hayes leads with vision, wisdom, and a deep love for God’s people. First Lady Katrina Hayes stands as a pillar of strength, compassion, and encouragement, faithfully supporting the ministry and uplifting the church family through service and grace.', 'bridges-grove'),
            'cards' => array(
                array('icon' => 'heart', 'title' => __('Faith in Action', 'bridges-grove'), 'text' => __('A model of humility, unity, and commitment to Christ.', 'bridges-grove')),
                array('icon' => 'users', 'title' => __('Family Leadership', 'bridges-grove'), 'text' => __('Serving together with warmth, consistency, and care.', 'bridges-grove')),
                array('icon' => 'leaf', 'title' => __('Welcoming Spirit', 'bridges-grove'), 'text' => __('Helping foster a church environment where people can grow and belong.', 'bridges-grove')),
            ),
            'aside_title' => __('The Hayes Family', 'bridges-grove'),
            'aside_items' => array(__('Rev. James A. Hayes', 'bridges-grove'), __('First Lady Katrina Hayes', 'bridges-grove'), __('Chelsea Hayes', 'bridges-grove'), __('Channing Hayes', 'bridges-grove')),
            'actions' => array(
                array('label' => __('Meet Our Pastor', 'bridges-grove'), 'url' => home_url('/our-pastor/'), 'style' => 'solid'),
                array('label' => __('Visit Us', 'bridges-grove'), 'url' => home_url('/contact/'), 'style' => 'outline'),
            ),
        ),
        'our-pastor' => array(
            'kicker' => __('Our Pastor', 'bridges-grove'),
            'title' => __('Meet Reverend James A. Hayes.', 'bridges-grove'),
            'intro' => __('Reverend James A. Hayes serves as Pastor of Bridges Grove A.M.E. Zion Church with a heart for ministry, a passion for preaching, and a deep commitment to shepherding God’s people.', 'bridges-grove'),
            'body' => __('Guided by faith and led by the Holy Spirit, Pastor Hayes brings wisdom, compassion, and vision to the church. Through biblical teaching and servant leadership, he equips the congregation to grow spiritually, live faithfully, and serve purposefully.', 'bridges-grove'),
            'image' => get_template_directory_uri() . '/assets/img/pastor-james-hayes.png',
            'image_alt' => __('Reverend James A. Hayes, Pastor of Bridges Grove A.M.E. Zion Church', 'bridges-grove'),
            'cards' => array(
                array('icon' => 'book', 'title' => __('Biblical Teaching', 'bridges-grove'), 'text' => __('Faithful preaching and teaching rooted in Scripture.', 'bridges-grove')),
                array('icon' => 'heart', 'title' => __('Servant Leadership', 'bridges-grove'), 'text' => __('Guiding the church with compassion, wisdom, and care.', 'bridges-grove')),
                array('icon' => 'users', 'title' => __('Community Vision', 'bridges-grove'), 'text' => __('Equipping the church to serve Shannon and beyond.', 'bridges-grove')),
            ),
            'aside_title' => __('Pastoral Focus', 'bridges-grove'),
            'aside_items' => array(__('Discipleship', 'bridges-grove'), __('Christ-centered worship', 'bridges-grove'), __('Community engagement', 'bridges-grove')),
            'actions' => array(
                array('label' => __('Contact the Church', 'bridges-grove'), 'url' => home_url('/contact/'), 'style' => 'solid'),
            ),
        ),
        'history' => array(
            'type' => 'history',
            'kicker' => __('History', 'bridges-grove'),
            'title' => __('Honoring the roots that still shape the church.', 'bridges-grove'),
            'intro' => __('Bridges Grove carries a legacy of worship, resilience, and faithful witness dating back to 1873.', 'bridges-grove'),
            'body' => __('Born from the Carter Bridge and Pleasant Grove communities, Bridges Grove A.M.E. Zion Church has grown through generations of prayer, sacrifice, leadership, and service in the Shannon community.', 'bridges-grove'),
            'timeline' => array(
                array('date' => __('1873', 'bridges-grove'), 'title' => __('Bridges Grove Is Formed', 'bridges-grove'), 'text' => __('Members from Carter Bridge and Pleasant Grove came together around a central place of worship. The church’s name and early identity grew from these two faith communities.', 'bridges-grove')),
                array('date' => __('1873', 'bridges-grove'), 'title' => __('Land Purchased in Antioch', 'bridges-grove'), 'text' => __('Ben Goodman sold two acres in the Antioch community for four dollars per acre, helping establish the original home for the church.', 'bridges-grove')),
                array('date' => __('Late 1800s', 'bridges-grove'), 'title' => __('AME Zion Connection', 'bridges-grove'), 'text' => __('Bridges Grove made its formal connection with the African Methodist Episcopal Zion Church, joining a broader tradition of worship and witness.', 'bridges-grove')),
                array('date' => __('1900', 'bridges-grove'), 'title' => __('Second Church Constructed', 'bridges-grove'), 'text' => __('A second church was built under Rev. T.B. McCain. After fires destroyed both early churches, worship continued at the Bridges Grove School House and later the Masonic Lodge.', 'bridges-grove')),
                array('date' => __('1919', 'bridges-grove'), 'title' => __('Third Church Built', 'bridges-grove'), 'text' => __('Under Rev. A.E. Gordon, a third church was constructed and remembered for its beautiful arched windows and three-section pews.', 'bridges-grove')),
                array('date' => __('1942-1965', 'bridges-grove'), 'title' => __('A Season of Renewal', 'bridges-grove'), 'text' => __('Rev. E.B. Bethea served during a pivotal season, helping the church grow through fundraising, church activities, and dedicated leadership.', 'bridges-grove')),
                array('date' => __('1971-1974', 'bridges-grove'), 'title' => __('Fourth Church Constructed', 'bridges-grove'), 'text' => __('Rev. L.J. Jefferies led during the construction of the fourth church. Mrs. Willa McLean became the first female Preacher Steward in the Laurinburg District.', 'bridges-grove')),
                array('date' => __('1977-1988', 'bridges-grove'), 'title' => __('Expansion and Improvements', 'bridges-grove'), 'text' => __('The fellowship hall was added, the church road improved, property records were secured, brickwork completed, and the sanctuary received new carpet, pews, heating, and air.', 'bridges-grove')),
                array('date' => __('1991-1999', 'bridges-grove'), 'title' => __('Ministry Growth', 'bridges-grove'), 'text' => __('New ministries and facility improvements took shape, including Children Church, accessibility upgrades, fellowship hall remodeling, and updates to the pastor’s study.', 'bridges-grove')),
                array('date' => __('2000-2013', 'bridges-grove'), 'title' => __('New Systems and New Vision', 'bridges-grove'), 'text' => __('The church added water service, safety improvements, audio support, transportation, facility upgrades, a metal roof, and new outreach energy under several faithful pastors.', 'bridges-grove')),
                array('date' => __('2015-2022', 'bridges-grove'), 'title' => __('Modern Ministry Tools', 'bridges-grove'), 'text' => __('The church advanced with technology, facility updates, pew reupholstery, new flooring, and added storage while continuing to serve with spiritual focus.', 'bridges-grove')),
                array('date' => __('2023', 'bridges-grove'), 'title' => __('150 Years of Faith', 'bridges-grove'), 'text' => __('As Bridges Grove celebrated 150 years, the church honored its forefathers and renewed its commitment to remain a vital spiritual force in the community.', 'bridges-grove')),
            ),
            'cards' => array(
                array('icon' => 'leaf', 'title' => __('Deep Roots', 'bridges-grove'), 'text' => __('A place shaped by prayer, perseverance, and community care.', 'bridges-grove')),
                array('icon' => 'church', 'title' => __('Faithful Witness', 'bridges-grove'), 'text' => __('Continuing a tradition of worship and service.', 'bridges-grove')),
                array('icon' => 'heart', 'title' => __('Living Legacy', 'bridges-grove'), 'text' => __('Honoring the past while serving the present generation.', 'bridges-grove')),
            ),
            'aside_title' => __('Add Historical Details', 'bridges-grove'),
            'aside_items' => array(__('Founding year', 'bridges-grove'), __('Former pastors', 'bridges-grove'), __('Church milestones', 'bridges-grove')),
        ),
        'events' => array(
            'type' => 'events',
            'kicker' => __('Events', 'bridges-grove'),
            'title' => __('What’s happening at Bridges Grove.', 'bridges-grove'),
            'intro' => __('Stay connected and be part of what God is doing through worship, fellowship, special services, and community outreach.', 'bridges-grove'),
            'body' => __('Add events from the Church Events menu in WordPress. Published events will appear here automatically with date, location, details, and photos.', 'bridges-grove'),
            'cards' => array(
                array('icon' => 'church', 'title' => __('Sunday Worship', 'bridges-grove'), 'text' => __('Join us weekly for uplifting worship and the Word.', 'bridges-grove')),
                array('icon' => 'calendar', 'title' => __('Special Services', 'bridges-grove'), 'text' => __('Guest speakers, revivals, and church-wide celebrations.', 'bridges-grove')),
                array('icon' => 'heart', 'title' => __('Community Events', 'bridges-grove'), 'text' => __('Outreach opportunities and fellowship gatherings.', 'bridges-grove')),
            ),
            'aside_title' => __('Regular Gatherings', 'bridges-grove'),
            'aside_items' => $info['service_times'],
            'actions' => array(
                array('label' => __('Contact Us', 'bridges-grove'), 'url' => home_url('/contact/'), 'style' => 'solid'),
            ),
            'show_events' => true,
        ),
        'watch' => array(
            'kicker' => __('Watch Online', 'bridges-grove'),
            'title' => __('Join worship from wherever you are.', 'bridges-grove'),
            'intro' => __('Join Bridges Grove A.M.E. Zion Church online for worship, prayer, and the preached Word.', 'bridges-grove'),
            'body' => __('When the church is live, connect through the Facebook page. You can also revisit recent messages and stay connected with weekly announcements.', 'bridges-grove'),
            'cards' => array(
                array('icon' => 'play', 'title' => __('Live Worship', 'bridges-grove'), 'text' => __('Watch the service when the church is broadcasting live.', 'bridges-grove')),
                array('icon' => 'book', 'title' => __('Past Messages', 'bridges-grove'), 'text' => __('Replay sermons and worship moments anytime.', 'bridges-grove')),
                array('icon' => 'users', 'title' => __('Stay Connected', 'bridges-grove'), 'text' => __('Follow announcements, ministry updates, and worship links.', 'bridges-grove')),
            ),
            'aside_title' => __('Watch on Facebook', 'bridges-grove'),
            'aside_items' => array(__('Live worship and weekly announcements', 'bridges-grove'), __('Replay past services anytime', 'bridges-grove'), __('Stay connected from anywhere', 'bridges-grove')),
            'actions' => array(
                array('label' => __('Visit Facebook', 'bridges-grove'), 'url' => $info['facebook'], 'style' => 'solid'),
            ),
        ),
        'contact' => array(
            'kicker' => __('Contact Us', 'bridges-grove'),
            'title' => __('We would love to hear from you.', 'bridges-grove'),
            'intro' => __('Send a message, request prayer, or reach out with questions about worship, ministries, or upcoming services.', 'bridges-grove'),
            'body' => __('Whether you are planning a visit, asking for prayer, or looking for service information, the Bridges Grove church family is ready to connect with you.', 'bridges-grove'),
            'cards' => array(
                array('icon' => 'map', 'title' => __('Address', 'bridges-grove'), 'text' => $info['address']),
                array('icon' => 'phone', 'title' => __('Phone', 'bridges-grove'), 'text' => $info['phone']),
                array('icon' => 'mail', 'title' => __('Email', 'bridges-grove'), 'text' => $info['email']),
            ),
            'aside_title' => __('Service Times', 'bridges-grove'),
            'aside_items' => $info['service_times'],
            'actions' => array(
                array('label' => __('Email Us', 'bridges-grove'), 'url' => $info['email_url'], 'style' => 'solid'),
                array('label' => __('Message on Facebook', 'bridges-grove'), 'url' => $info['facebook'], 'style' => 'outline'),
            ),
            'show_contact_form' => true,
        ),
        'plan-your-visit' => array(
            'kicker' => __('Plan a Visit', 'bridges-grove'),
            'title' => __('Come worship with Bridges Grove.', 'bridges-grove'),
            'intro' => __('We are honored you’re considering worshiping with Bridges Grove A.M.E. Zion Church. Here’s what to expect and how to prepare for your first visit.', 'bridges-grove'),
            'body' => __('Arrive a few minutes early, come as you are, and expect a welcoming church family ready to help you feel at home.', 'bridges-grove'),
            'cards' => array(
                array('icon' => 'clock', 'title' => __('Service Times', 'bridges-grove'), 'text' => __('Sunday School 9:45 AM, Sunday Worship 11:00 AM, Bible Study Wednesdays 7:00 PM', 'bridges-grove')),
                array('icon' => 'map', 'title' => __('Location', 'bridges-grove'), 'text' => $info['address']),
                array('icon' => 'heart', 'title' => __('What to Expect', 'bridges-grove'), 'text' => __('Warm welcome, meaningful worship, prayer, and the preached Word.', 'bridges-grove')),
            ),
            'aside_title' => __('Before You Come', 'bridges-grove'),
            'aside_items' => array(__('Arrive 10-15 minutes early', 'bridges-grove'), __('Ask a greeter if you need help', 'bridges-grove'), __('Bring family or friends with you', 'bridges-grove')),
            'actions' => array(
                array('label' => __('Get Directions', 'bridges-grove'), 'url' => 'https://www.google.com/maps/search/?api=1&query=251+Bridges+Grove+Church+Rd+Shannon+NC+28386', 'style' => 'solid'),
                array('label' => __('Contact Us', 'bridges-grove'), 'url' => home_url('/contact/'), 'style' => 'outline'),
            ),
        ),
    );

    return $pages[$slug] ?? null;
}

function bg_has_meaningful_page_content(): bool {
    $content = trim(wp_strip_all_tags(get_the_content()));
    return strlen($content) > 40;
}

function bg_page_has_gallery_content($page): bool {
    if (!$page instanceof WP_Post) return false;

    $content = (string) $page->post_content;
    if (trim($content) === '') return false;

    if (has_blocks($content)) {
        return true;
    }

    if (has_block('core/gallery', $page) || has_block('core/image', $page) || has_block('core/media-text', $page)) {
        return true;
    }

    $plugin_gallery_markers = array(
        'gallery',
        'foogallery',
        'modula',
        'envira',
        'nggallery',
        'nextgen',
        'metaslider',
        'smartslider',
        'wp:image',
        'wp-block-gallery',
        '<img',
    );

    foreach ($plugin_gallery_markers as $marker) {
        if (stripos($content, $marker) !== false) {
            return true;
        }
    }

    if (has_shortcode($content, 'gallery') || has_shortcode($content, 'foogallery') || has_shortcode($content, 'modula') || has_shortcode($content, 'envira-gallery')) {
        return true;
    }

    return trim(wp_strip_all_tags($content)) !== '';
}

function bg_render_page_content_as_post(WP_Post $page): void {
    global $post;

    $previous_post = $post;
    $post = $page;
    setup_postdata($post);
    echo apply_filters('the_content', $page->post_content);
    wp_reset_postdata();
    $post = $previous_post;
}

function bg_extract_home_gallery_shortcode(?WP_Post $page): string {
    if (!$page instanceof WP_Post) {
        return '[ml_gallery id="198"]';
    }

    $content = (string) $page->post_content;
    if (has_shortcode($content, 'ml_gallery') && preg_match('/\[ml_gallery[^\]]*\]/i', $content, $matches)) {
        return $matches[0];
    }

    return '[ml_gallery id="198"]';
}

function bg_get_home_gallery_rendered_content(): string {
    static $rendered_content = null;
    if ($rendered_content !== null) return $rendered_content;

    $gallery_page = bg_find_page_by_slugs(array('gallery', 'photos', 'media'));
    $shortcode = bg_extract_home_gallery_shortcode($gallery_page);
    if (shortcode_exists('ml_gallery')) {
        $rendered_content = trim((string) do_shortcode($shortcode));
        if ($rendered_content !== '') {
            return $rendered_content;
        }
    }

    if (bg_page_has_gallery_content($gallery_page)) {
        ob_start();
        bg_render_page_content_as_post($gallery_page);
        $rendered_content = trim((string) ob_get_clean());
        return $rendered_content;
    }

    return '';
}

function bg_extract_attribute_value(string $html, string $attribute): string {
    if (preg_match('/\s' . preg_quote($attribute, '/') . '\s*=\s*([\'"])(.*?)\1/i', $html, $matches)) {
        return html_entity_decode($matches[2], ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
    }

    return '';
}

function bg_get_home_gallery_images(int $limit = 9): array {
    $images = array();
    $seen = array();
    $gallery_page = bg_find_page_by_slugs(array('gallery', 'photos', 'media'));
    $content_sources = array(bg_get_home_gallery_rendered_content());

    if ($gallery_page instanceof WP_Post) {
        $content_sources[] = (string) $gallery_page->post_content;
    }

    foreach ($content_sources as $content) {
        if (!$content) {
            continue;
        }

        if (preg_match_all('/<img\b[^>]*>/i', $content, $matches)) {
            foreach ($matches[0] as $image_html) {
                $src = bg_extract_attribute_value($image_html, 'src');
                if (!$src) {
                    $src = bg_extract_attribute_value($image_html, 'data-src');
                }
                if (!$src) {
                    $srcset = bg_extract_attribute_value($image_html, 'srcset') ?: bg_extract_attribute_value($image_html, 'data-srcset');
                    if ($srcset) {
                        $srcset_parts = array_map('trim', explode(',', $srcset));
                        $first_srcset = trim((string) ($srcset_parts[0] ?? ''));
                        $src = trim((string) strtok($first_srcset, ' '));
                    }
                }
                if (!$src || isset($seen[$src])) {
                    continue;
                }
                $seen[$src] = true;
                $images[] = array(
                    'src' => esc_url_raw($src),
                    'alt' => sanitize_text_field(bg_extract_attribute_value($image_html, 'alt')),
                );
                if (count($images) >= $limit) {
                    return $images;
                }
            }
        }

        if (preg_match_all('/"id"\s*:\s*(\d+)/', $content, $id_matches)) {
            foreach ($id_matches[1] as $attachment_id) {
                $attachment_id = absint($attachment_id);
                if (!$attachment_id) {
                    continue;
                }
                $image = wp_get_attachment_image_src($attachment_id, 'large');
                if (empty($image[0]) || isset($seen[$image[0]])) {
                    continue;
                }
                $seen[$image[0]] = true;
                $images[] = array(
                    'src' => esc_url_raw($image[0]),
                    'alt' => sanitize_text_field(get_post_meta($attachment_id, '_wp_attachment_image_alt', true)),
                );
                if (count($images) >= $limit) {
                    return $images;
                }
            }
        }
    }

    return $images;
}

add_action('wp_enqueue_scripts', function () {
    if (is_front_page()) {
        bg_get_home_gallery_rendered_content();
    }
}, 20);

function bg_render_page_actions($actions): void {
    if (empty($actions)) return;
    echo '<div class="bg-enhanced-actions">';
    foreach ($actions as $action) {
        $style = ($action['style'] ?? '') === 'outline' ? 'bg-btn-outline' : 'bg-btn-solid';
        echo '<a class="bg-btn ' . esc_attr($style) . '" href="' . esc_url($action['url']) . '">' . esc_html($action['label']) . '</a>';
    }
    echo '</div>';
}

function bg_render_history_timeline($items): void {
    if (empty($items)) return;

    echo '<section class="bg-timeline-section" aria-label="' . esc_attr__('Church history timeline', 'bridges-grove') . '">';
    echo '<div class="bg-timeline-header">';
    echo '<span class="bg-kicker">' . esc_html__('Timeline', 'bridges-grove') . '</span>';
    echo '<h2>' . esc_html__('Milestones of faith and service.', 'bridges-grove') . '</h2>';
    echo '</div>';
    echo '<div class="bg-timeline">';
    foreach ($items as $item) {
        echo '<article class="bg-timeline-item">';
        echo '<div class="bg-timeline-marker"><span></span></div>';
        echo '<div class="bg-timeline-card">';
        echo '<span class="bg-timeline-date">' . esc_html($item['date']) . '</span>';
        echo '<h3>' . esc_html($item['title']) . '</h3>';
        echo '<p>' . esc_html($item['text']) . '</p>';
        echo '</div>';
        echo '</article>';
    }
    echo '</div>';
    echo '</section>';
}

function bg_format_event_date($value): string {
    if (!$value) return __('Date TBA', 'bridges-grove');
    $timestamp = strtotime($value);
    if (!$timestamp) return __('Date TBA', 'bridges-grove');
    return date_i18n('D, M j • g:i A', $timestamp);
}

function bg_get_event_posts($limit = 6): array {
    $query = new WP_Query(array(
        'post_type'      => 'bg_event',
        'post_status'    => 'publish',
        'posts_per_page' => absint($limit),
        'meta_key'       => '_bg_event_start',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    ));

    return $query->posts;
}

function bg_the_events_calendar_active(): bool {
    return post_type_exists('tribe_events') || class_exists('Tribe__Events__Main') || function_exists('tribe_get_events');
}

function bg_get_tribe_event_posts($limit = 6): array {
    if (!post_type_exists('tribe_events')) {
        return array();
    }

    $query = new WP_Query(array(
        'post_type'      => 'tribe_events',
        'post_status'    => 'publish',
        'posts_per_page' => absint($limit),
        'meta_key'       => '_EventStartDate',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => '_EventStartDate',
                'value'   => current_time('mysql'),
                'compare' => '>=',
                'type'    => 'DATETIME',
            ),
        ),
    ));

    return $query->posts;
}

function bg_format_tribe_event_date($event): string {
    $start = get_post_meta($event->ID, '_EventStartDate', true);
    if (!$start) {
        return __('Date TBA', 'bridges-grove');
    }

    $timestamp = strtotime($start);
    if (!$timestamp) {
        return __('Date TBA', 'bridges-grove');
    }

    $all_day = get_post_meta($event->ID, '_EventAllDay', true);
    if ($all_day === 'yes') {
        return date_i18n('D, M j', $timestamp);
    }

    return date_i18n('D, M j • g:i A', $timestamp);
}

function bg_get_tribe_event_location($event): string {
    if (function_exists('tribe_get_venue')) {
        return (string) tribe_get_venue($event->ID);
    }

    $venue_id = absint(get_post_meta($event->ID, '_EventVenueID', true));
    return $venue_id ? (string) get_the_title($venue_id) : '';
}

function bg_get_tribe_events_url(): string {
    if (function_exists('tribe_get_events_link')) {
        return (string) tribe_get_events_link();
    }

    if (function_exists('tribe_get_events_url')) {
        return (string) tribe_get_events_url();
    }

    return home_url('/events/');
}

function bg_should_render_tribe_events_hero(): bool {
    if (!bg_the_events_calendar_active()) {
        return false;
    }

    if (is_singular('tribe_events') || is_post_type_archive('tribe_events')) {
        return true;
    }

    return function_exists('tribe_is_event_query') && tribe_is_event_query() && !is_page();
}

function bg_render_tribe_events_hero_html(): string {
    $data = bg_enhanced_page_data('events') ?: array();
    $info = bg_church_info();
    $events_page = bg_find_page_by_slugs(array('events', 'calendar'));
    $hero_img = get_template_directory_uri() . '/assets/img/hero-fallback.jpg';

    if (is_singular('tribe_events') && has_post_thumbnail()) {
        $hero_img = get_the_post_thumbnail_url(get_the_ID(), 'full');
    } elseif ($events_page instanceof WP_Post && has_post_thumbnail($events_page)) {
        $hero_img = get_the_post_thumbnail_url($events_page, 'full');
    }

    $is_single_event = is_singular('tribe_events');
    $title = $is_single_event ? get_the_title() : __('Events', 'bridges-grove');
    $kicker = $is_single_event ? __('Church Event', 'bridges-grove') : ($data['kicker'] ?? __('Events', 'bridges-grove'));
    $intro = $is_single_event
        ? __('Details for this upcoming gathering at Bridges Grove A.M.E. Zion Church.', 'bridges-grove')
        : ($data['intro'] ?? __('Stay connected with worship, fellowship, and community moments at Bridges Grove.', 'bridges-grove'));
    $service_time = $info['service_times'][1] ?? ($info['service_times'][0] ?? __('Sunday Worship: 11:00 AM', 'bridges-grove'));

    ob_start();
    ?>
    <header class="bg-page-hero bg-events-calendar-hero" style="background-image:url('<?php echo esc_url($hero_img); ?>')">
      <div class="bg-page-hero__overlay"></div>
      <div class="bg-container bg-page-hero__inner">
        <div class="bg-page-hero__copy">
          <span class="bg-page-hero__badge"><?php echo esc_html($kicker); ?></span>
          <h1 class="bg-page-hero__title"><?php echo esc_html($title); ?></h1>
          <p class="bg-page-hero__subtitle"><?php echo esc_html($intro); ?></p>
          <div class="bg-page-hero__meta" aria-label="<?php esc_attr_e('Church details', 'bridges-grove'); ?>">
            <span><?php echo bg_icon('map'); ?><?php echo esc_html($info['address']); ?></span>
            <span><?php echo bg_icon('clock'); ?><?php echo esc_html($service_time); ?></span>
          </div>
        </div>
        <div class="bg-page-hero__graphic" aria-hidden="true">
          <span class="bg-page-hero__orb"></span>
          <span class="bg-page-hero__window"></span>
          <span class="bg-page-hero__cross"></span>
          <span class="bg-page-hero__leaf"><?php echo bg_icon('leaf'); ?></span>
          <span class="bg-page-hero__line"></span>
        </div>
      </div>
    </header>
    <?php
    return (string) ob_get_clean();
}

function bg_render_events_panel(): void {
    $use_calendar_plugin = bg_the_events_calendar_active();
    $events = $use_calendar_plugin ? bg_get_tribe_event_posts(6) : bg_get_event_posts(6);
    $calendar_url = $use_calendar_plugin ? bg_get_tribe_events_url() : home_url('/church-events/');

    echo '<section class="bg-managed-events" aria-label="' . esc_attr__('Church events', 'bridges-grove') . '">';
    echo '<div class="bg-managed-events-header">';
    echo '<div>';
    echo '<span class="bg-kicker">' . esc_html__('Church Events', 'bridges-grove') . '</span>';
    echo '<h2>' . esc_html__('Upcoming moments to gather, serve, and grow.', 'bridges-grove') . '</h2>';
    echo '</div>';
    echo '<a class="bg-link bg-managed-events-calendar-link" href="' . esc_url($calendar_url) . '">' . esc_html__('View Full Calendar', 'bridges-grove') . '</a>';
    echo '</div>';

    if (!empty($events)) {
        echo '<div class="bg-managed-event-grid">';
        foreach ($events as $event) {
            if ($use_calendar_plugin) {
                $date = bg_format_tribe_event_date($event);
                $location = bg_get_tribe_event_location($event);
                $button_label = __('Event Details', 'bridges-grove');
                $button_url = get_permalink($event);
            } else {
                $start = get_post_meta($event->ID, '_bg_event_start', true);
                $date = bg_format_event_date($start);
                $location = get_post_meta($event->ID, '_bg_event_location', true);
                $button_label = get_post_meta($event->ID, '_bg_event_button_label', true) ?: __('Learn More', 'bridges-grove');
                $button_url = get_post_meta($event->ID, '_bg_event_button_url', true) ?: get_permalink($event);
            }
            $image = has_post_thumbnail($event) ? get_the_post_thumbnail_url($event, 'large') : get_template_directory_uri() . '/assets/img/hero-fallback.jpg';

            echo '<article class="bg-managed-event-card">';
            echo '<figure><img src="' . esc_url($image) . '" alt="" loading="lazy"></figure>';
            echo '<div class="bg-managed-event-body">';
            echo '<span class="bg-date-badge">' . esc_html($date) . '</span>';
            echo '<h3>' . esc_html(get_the_title($event)) . '</h3>';
            if ($location) {
                echo '<p class="bg-managed-event-location">' . bg_icon('map') . esc_html($location) . '</p>';
            }
            $excerpt = has_excerpt($event) ? get_the_excerpt($event) : wp_trim_words(wp_strip_all_tags($event->post_content), 24);
            if ($excerpt) {
                echo '<p>' . esc_html($excerpt) . '</p>';
            }
            echo '<a class="bg-link" href="' . esc_url($button_url) . '">' . esc_html($button_label) . '</a>';
            echo '</div>';
            echo '</article>';
        }
        echo '</div>';
    } else {
        echo '<div class="bg-managed-events-empty">';
        echo '<h3>' . esc_html__('No upcoming events are on the calendar yet.', 'bridges-grove') . '</h3>';
        echo '<p>' . esc_html($use_calendar_plugin ? __('Add an event in The Events Calendar and it will show here automatically.', 'bridges-grove') : __('Add a Church Event in the WordPress dashboard and it will show here automatically.', 'bridges-grove')) . '</p>';
        if (current_user_can('edit_posts')) {
            $add_event_url = $use_calendar_plugin ? admin_url('post-new.php?post_type=tribe_events') : admin_url('post-new.php?post_type=bg_event');
            echo '<a class="bg-btn bg-btn-solid" href="' . esc_url($add_event_url) . '">' . esc_html__('Add Event', 'bridges-grove') . '</a>';
        }
        echo '</div>';
    }

    if (current_user_can('edit_posts')) {
        $manage_event_url = $use_calendar_plugin ? admin_url('edit.php?post_type=tribe_events') : admin_url('edit.php?post_type=bg_event');
        echo '<div class="bg-section-edit"><a class="bg-link" href="' . esc_url($manage_event_url) . '">' . esc_html__('Manage events', 'bridges-grove') . '</a></div>';
    }
    echo '</section>';
}

add_shortcode('bridges_grove_events', function () {
    ob_start();
    bg_render_events_panel();
    return ob_get_clean();
});

function bg_clean_page_content(): string {
    $content = apply_filters('the_content', get_the_content());
    $content = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $content);
    $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
    return trim((string) $content);
}

function bg_render_pastor_bio_panel(): void {
    $content = bg_clean_page_content();

    if (!bg_has_meaningful_page_content() && !current_user_can('edit_page', get_the_ID())) {
        return;
    }

    echo '<section class="bg-pastor-bio-panel" aria-label="' . esc_attr__('Pastor biography', 'bridges-grove') . '">';
    echo '<div class="bg-pastor-bio-heading">';
    echo '<span class="bg-kicker">' . esc_html__('Pastor Bio', 'bridges-grove') . '</span>';
    echo '<h2>' . esc_html__('A place to share Pastor Hayes’ story.', 'bridges-grove') . '</h2>';
    echo '</div>';

    if (bg_has_meaningful_page_content()) {
        echo '<div class="bg-pastor-bio-content">';
        echo $content;
        echo '</div>';
    } else {
        echo '<div class="bg-pastor-bio-empty">';
        echo '<p>' . esc_html__('Add Pastor Hayes’ biography directly in the Our Pastor page editor. It will appear in this section automatically.', 'bridges-grove') . '</p>';
        echo '<a class="bg-btn bg-btn-solid" href="' . esc_url(get_edit_post_link(get_the_ID())) . '">' . esc_html__('Add Pastor Bio', 'bridges-grove') . '</a>';
        echo '</div>';
    }

    echo '</section>';
}

function bg_render_contact_form_panel(): void {
    $status = isset($_GET['bg-contact']) ? sanitize_key(wp_unslash($_GET['bg-contact'])) : '';

    echo '<section class="bg-contact-form-panel" aria-label="' . esc_attr__('Contact form', 'bridges-grove') . '">';
    echo '<div class="bg-contact-form-copy">';
    echo '<span class="bg-kicker">' . esc_html__('Send a Message', 'bridges-grove') . '</span>';
    echo '<h2>' . esc_html__('Reach out to Bridges Grove.', 'bridges-grove') . '</h2>';
    echo '<p>' . esc_html__('Use this form for prayer requests, visit questions, ministry information, or general messages for the church office.', 'bridges-grove') . '</p>';
    echo '</div>';

    echo '<form class="bg-contact-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    if ($status === 'sent') {
        echo '<div class="bg-form-alert bg-form-alert-success">' . esc_html__('Thank you. Your message has been sent.', 'bridges-grove') . '</div>';
    } elseif ($status === 'error') {
        echo '<div class="bg-form-alert bg-form-alert-error">' . esc_html__('Please check the required fields and try again.', 'bridges-grove') . '</div>';
    }
    echo '<input type="hidden" name="action" value="bg_contact_submit">';
    wp_nonce_field('bg_contact_submit', 'bg_contact_nonce');
    echo '<p class="bg-contact-hidden"><label>' . esc_html__('Company', 'bridges-grove') . '<input type="text" name="bg_contact_company" tabindex="-1" autocomplete="off"></label></p>';
    echo '<div class="bg-form-grid">';
    echo '<label>' . esc_html__('Name', 'bridges-grove') . '<input type="text" name="bg_contact_name" autocomplete="name" required></label>';
    echo '<label>' . esc_html__('Email', 'bridges-grove') . '<input type="email" name="bg_contact_email" autocomplete="email" required></label>';
    echo '</div>';
    echo '<label>' . esc_html__('Phone', 'bridges-grove') . '<input type="tel" name="bg_contact_phone" autocomplete="tel"></label>';
    echo '<label>' . esc_html__('Message', 'bridges-grove') . '<textarea name="bg_contact_message" rows="6" required></textarea></label>';
    echo '<button class="bg-btn bg-btn-solid" type="submit">' . esc_html__('Send Message', 'bridges-grove') . '</button>';
    echo '</form>';
    echo '</section>';
}

function bg_render_home_gallery(): void {
    $gallery_page = bg_find_page_by_slugs(array('gallery', 'photos', 'media'));
    $gallery_url = $gallery_page instanceof WP_Post ? get_permalink($gallery_page) : home_url('/gallery/');
    $gallery_images = bg_get_home_gallery_images(9);

    echo '<section id="gallery" class="bg-home-gallery">';
    echo '<div class="bg-home-gallery-inner">';
    echo '<header class="bg-home-gallery-header">';
    echo '<div>';
    echo '<span class="bg-kicker">' . esc_html__('Church Life', 'bridges-grove') . '</span>';
    echo '<h2>' . esc_html__('Moments from Bridges Grove.', 'bridges-grove') . '</h2>';
    echo '</div>';
    echo '<a class="bg-btn bg-btn-outline" href="' . esc_url($gallery_url) . '">' . esc_html__('View Full Gallery', 'bridges-grove') . '</a>';
    echo '</header>';

    if (!empty($gallery_images)) {
        echo '<div class="bg-home-gallery-preview" data-bg-living-gallery aria-label="' . esc_attr__('Featured church photos', 'bridges-grove') . '">';
        foreach ($gallery_images as $index => $image) {
            $class = $index === 0 ? 'bg-home-gallery-photo is-featured' : 'bg-home-gallery-photo';
            $slot = ($index % 5) + 1;
            echo '<a class="' . esc_attr($class) . '" data-bg-gallery-slot="' . esc_attr((string) $slot) . '" href="' . esc_url($gallery_url) . '">';
            echo '<img src="' . esc_url($image['src']) . '" alt="' . esc_attr($image['alt']) . '" loading="lazy">';
            echo '<span>' . esc_html__('Open Gallery', 'bridges-grove') . '</span>';
            echo '</a>';
        }
        echo '</div>';
    } else {
        if (current_user_can('edit_pages')) {
            echo '<div class="bg-home-gallery-stage">';
            echo '<div class="bg-home-gallery-content">';
            echo '<p>' . esc_html__('Add images to the Gallery page or confirm the gallery plugin is outputting image markup. The homepage will build a contained preview from those images.', 'bridges-grove') . '</p>';
            echo '</div>';
            echo '</div>';
        }
    }

    echo '</div>';
    echo '</section>';
}

function bg_render_enhanced_page($data): void {
    $page_slug = function_exists('bg_page_slug') ? bg_page_slug() : '';
    if ($page_slug === 'firstfamily') {
        $page_slug = 'first-family';
    }
    if ($page_slug === 'pastor') {
        $page_slug = 'our-pastor';
    }
    $image = $data['image'] ?? '';
    $image_alt = $data['image_alt'] ?? '';
    if (!$image && $page_slug === 'first-family') {
        $first_family_image_id = absint(get_theme_mod('bg_first_family_image_id', 0));
        if ($first_family_image_id) {
            $first_family_image = wp_get_attachment_image_src($first_family_image_id, 'large');
            if (!empty($first_family_image[0])) {
                $image = $first_family_image[0];
            }
        }
    }
    if (!$image && $page_slug === 'first-family' && has_post_thumbnail()) {
        $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
    }
    if ($image && $page_slug === 'first-family' && !$image_alt) {
        $image_alt = get_the_title() . ' ' . __('photo', 'bridges-grove');
    }

    echo '<section class="bg-enhanced-page">';
    echo '<div class="bg-container">';
    echo '<div class="bg-enhanced-shell">';
    echo '<div class="bg-enhanced-main">';
    echo '<span class="bg-kicker">' . esc_html($data['kicker']) . '</span>';
    echo '<h2>' . esc_html($data['title']) . '</h2>';
    echo '<p class="bg-enhanced-intro">' . esc_html($data['intro']) . '</p>';
    echo '<p>' . esc_html($data['body']) . '</p>';
    bg_render_page_actions($data['actions'] ?? array());
    echo '</div>';

    if (!empty($image)) {
        echo '<figure class="bg-enhanced-portrait">';
        echo '<img src="' . esc_url($image) . '" alt="' . esc_attr($image_alt) . '" loading="lazy">';
        echo '<figcaption>' . esc_html($image_alt ?: get_the_title()) . '</figcaption>';
        echo '</figure>';
    } else {
        echo '<aside class="bg-enhanced-aside">';
        echo '<h3>' . esc_html($data['aside_title'] ?? __('Details', 'bridges-grove')) . '</h3>';
        echo '<ul>';
        foreach (($data['aside_items'] ?? array()) as $item) {
            echo '<li><span>' . bg_icon('leaf') . '</span>' . esc_html($item) . '</li>';
        }
        echo '</ul>';
        echo '</aside>';
    }
    echo '</div>';

    if (!empty($data['cards'])) {
        echo '<div class="bg-enhanced-grid">';
        foreach ($data['cards'] as $card) {
            echo '<article class="bg-enhanced-card">';
            echo '<span class="bg-enhanced-icon">' . bg_icon($card['icon'] ?? 'leaf') . '</span>';
            echo '<h3>' . esc_html($card['title']) . '</h3>';
            echo '<p>' . esc_html($card['text']) . '</p>';
            echo '</article>';
        }
        echo '</div>';
    }

    if (!empty($data['timeline'])) {
        bg_render_history_timeline($data['timeline']);
    }

    if (!empty($data['show_events'])) {
        bg_render_events_panel();
    }

    if ($page_slug === 'our-pastor') {
        bg_render_pastor_bio_panel();
    }

    if (!empty($data['show_contact_form'])) {
        bg_render_contact_form_panel();
    }

    if (!empty($image) && !empty($data['aside_items'])) {
        echo '<aside class="bg-enhanced-aside bg-enhanced-aside-wide">';
        echo '<h3>' . esc_html($data['aside_title'] ?? __('Details', 'bridges-grove')) . '</h3>';
        echo '<ul>';
        foreach (($data['aside_items'] ?? array()) as $item) {
            echo '<li><span>' . bg_icon('leaf') . '</span>' . esc_html($item) . '</li>';
        }
        echo '</ul>';
        echo '</aside>';
    }

    echo '</div>';
    echo '</section>';
}

/**
 * Block Pattern: Bridges Grove Landing
 */
add_action('init', function () {
    if (!function_exists('register_block_pattern')) return;

    register_block_pattern_category('bg', array(
        'label' => __('Bridges Grove', 'bridges-grove')
    ));

    $pattern = file_get_contents(BG_THEME_DIR . '/templates/pattern-landing.html');
    if ($pattern) {
        register_block_pattern('bg/landing', array(
            'title'       => __('Landing Page (Hero + Cards)', 'bridges-grove'),
            'description' => __('A ready-to-edit landing layout that matches the theme style.', 'bridges-grove'),
            'categories'  => array('bg'),
            'content'     => $pattern,
        ));
    // Additional inner-page patterns
    $patterns = array(
        'about'         => array('pattern-about.html', __('About Page Sections', 'bridges-grove')),
        'first-family'  => array('pattern-first-family.html', __('First Family Page Sections', 'bridges-grove')),
        'our-pastor'    => array('pattern-our-pastor.html', __('Our Pastor Page Sections', 'bridges-grove')),
        'history-timeline' => array('pattern-history-timeline.html', __('History Timeline Section', 'bridges-grove')),
        'events'        => array('pattern-events.html', __('Events Page Sections', 'bridges-grove')),
        'watch'         => array('pattern-watch.html', __('Watch Page Sections', 'bridges-grove')),
        'contact'       => array('pattern-contact.html', __('Contact Page Sections', 'bridges-grove')),
        'plan-your-visit' => array('pattern-plan-your-visit.html', __('Plan Your Visit Page Sections', 'bridges-grove')),
    );

    foreach ($patterns as $key => $meta) {
        $file = BG_THEME_DIR . '/templates/' . $meta[0];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content) {
                register_block_pattern('bg/' . $key, array(
                    'title'       => $meta[1],
                    'description' => __('A ready-to-edit page layout that matches the Bridges Grove theme style.', 'bridges-grove'),
                    'categories'  => array('bg'),
                    'content'     => $content,
                ));
            }
        }
    }

    }
});

/**
 * Template helper: if no menu assigned, show a fallback
 */
function bg_primary_menu() {
    if (has_nav_menu('primary')) {
        wp_nav_menu(array(
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'bg-nav',
            'fallback_cb'    => false,
            'depth'          => 2,
        ));
    } else {
        echo '<ul class="bg-nav">';
        echo '<li><a href="' . esc_url(home_url('/')) . '">Home</a></li>';
        echo '<li><a href="' . esc_url(home_url('/about/')) . '">About</a></li>';
        echo '<li><a href="' . esc_url(home_url('/first-family/')) . '">First Family</a></li>';
        echo '<li><a href="' . esc_url(home_url('/our-pastor/')) . '">Our Pastor</a></li>';
        echo '<li><a href="' . esc_url(home_url('/history/')) . '">History</a></li>';
        echo '<li><a href="' . esc_url(home_url('/events/')) . '">Events</a></li>';
        echo '<li><a href="' . esc_url(home_url('/watch/')) . '">Watch</a></li>';
        echo '<li><a href="' . esc_url(home_url('/contact/')) . '">Contact</a></li>';
        echo '</ul>';
    }
}
