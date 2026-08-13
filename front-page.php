<?php
/**
 * Front Page Template (One-page landing + sections)
 * - Hero headline + background image (Customizer)
 * - 3 feature cards (Plan / Pastor / Events)
 * - Auto-renders inner-page content as sections on the same landing page
 */
if (!defined('ABSPATH')) { exit; }
get_header();

// Hero background
$hero_id  = absint(get_theme_mod('bg_hero_image_id', 0));
$hero_url = '';
if ($hero_id) {
  $src = wp_get_attachment_image_src($hero_id, 'full');
  if (!empty($src[0])) $hero_url = esc_url($src[0]);
}
if (!$hero_url) {
  $hero_url = esc_url(get_template_directory_uri() . '/assets/img/bridges-grove-church-hero.jpg');
}

// Hero text (Customizer with safe defaults)
$hero_title    = trim((string) get_theme_mod('bg_hero_title', "Entering to Worship God.\nLeaving to Love One Another."));
$hero_title    = str_replace(array('\\r\\n', '\\n', '\\r'), "\n", $hero_title);
$hero_subtitle = trim((string) get_theme_mod('bg_hero_subtitle', ''));

if (!function_exists('bg_section_fallback')) {
  function bg_section_fallback($id) {
    $fallbacks = array(
      'about' => array(
        'kicker' => 'Our Story',
        'title' => 'A faith community with deep roots and open doors.',
        'body' => 'Bridges Grove A.M.E. Zion Church is a place for worship, fellowship, service, and spiritual growth in Shannon, North Carolina.',
        'items' => array('Christ-centered worship', 'Generational care', 'Community outreach')
      ),
      'first-family' => array(
        'kicker' => 'First Family',
        'title' => 'Leadership with warmth, presence, and purpose.',
        'body' => 'Meet the family serving alongside the church with prayer, hospitality, and a heart for people.',
        'items' => array('Faithful service', 'Pastoral support', 'Congregational care')
      ),
      'our-pastor' => array(
        'kicker' => 'Our Pastor',
        'title' => 'Guiding the church with vision and compassion.',
        'body' => 'Learn more about the pastoral leadership, ministry focus, and the message shaping this season at Bridges Grove.',
        'items' => array('Biblical teaching', 'Community leadership', 'Prayerful vision')
      ),
      'events' => array(
        'kicker' => 'Upcoming Events',
        'title' => 'There is room for you in what God is doing here.',
        'body' => 'Stay connected through worship services, church gatherings, outreach, and special moments for the whole family.',
        'items' => array('Sunday worship', 'Church events', 'Community programs')
      ),
      'watch' => array(
        'kicker' => 'Watch',
        'title' => 'Join worship from wherever you are.',
        'body' => 'Watch live services, revisit recent messages, and stay connected when you cannot be in the sanctuary.',
        'items' => array('Live stream', 'Recent messages', 'Worship moments')
      ),
      'contact' => array(
        'kicker' => 'Contact',
        'title' => 'We would love to hear from you.',
        'body' => 'Reach out for prayer, directions, ministry questions, or help planning your first visit.',
        'items' => array('Prayer requests', 'Directions', 'Ministry questions')
      ),
      'plan-your-visit' => array(
        'kicker' => 'Visit',
        'title' => 'Plan a Sunday with Bridges Grove.',
        'body' => 'Come as you are. You can expect a welcoming congregation, meaningful worship, and people ready to greet you.',
        'items' => array('Friendly welcome', 'Worship guidance', 'Family atmosphere')
      ),
    );

    return $fallbacks[$id] ?? $fallbacks['about'];
  }
}

if (!function_exists('bg_render_section')) {
  function bg_render_section($id, $label, $slugs) {
    $p = bg_find_page_by_slugs($slugs);
    $data = function_exists('bg_enhanced_page_data') ? bg_enhanced_page_data($id) : null;
    $fallback = $data ?: bg_section_fallback($id);
    $section_class = 'bg-section bg-section-' . sanitize_html_class($id);

    echo '<section id="' . esc_attr($id) . '" class="' . esc_attr($section_class) . '">';
    echo '  <div class="bg-section-inner">';

    echo '    <div class="bg-fallback-panel">';
    echo '      <div>';
    echo '        <span class="bg-kicker">' . esc_html($fallback['kicker']) . '</span>';
    echo '        <h2>' . esc_html($fallback['title']) . '</h2>';
    echo '        <p>' . esc_html($fallback['body'] ?? $fallback['intro']) . '</p>';
    if (!empty($fallback['actions']) && function_exists('bg_render_page_actions')) {
      bg_render_page_actions($fallback['actions']);
    }
    echo '      </div>';
    echo '      <ul class="bg-check-list">';
    $items = array();
    if (!empty($fallback['items'])) {
      $items = $fallback['items'];
    } elseif (!empty($fallback['cards'])) {
      foreach ($fallback['cards'] as $card) {
        $items[] = $card['title'];
      }
    }
    foreach (array_slice($items, 0, 4) as $item) {
      echo '        <li><span>' . bg_icon('leaf') . '</span>' . esc_html($item) . '</li>';
    }
    echo '      </ul>';
    echo '    </div>';

    echo '  </div>';
    echo '</section>';
  }
}

list($cta1_label, $cta1_url) = bg_get_cta('cta1', __('Watch Live','bridges-grove'), '#watch');
list($cta2_label, $cta2_url) = bg_get_cta('cta2', __('Plan Your Visit','bridges-grove'), home_url('/contact/'));
$church_info = function_exists('bg_church_info') ? bg_church_info() : array(
  'address' => __('251 Bridges Grove Church Rd, Shannon, NC 28386', 'bridges-grove'),
  'phone' => __('(910)-565-2226', 'bridges-grove'),
  'phone_url' => 'tel:+19105652226',
  'email' => 'info@bridgesgrove.org',
  'email_url' => 'mailto:info@bridgesgrove.org',
  'service_times' => array(__('Sunday School: 9:45 AM', 'bridges-grove'), __('Sunday Worship: 11:00 AM', 'bridges-grove'), __('Bible Study: Wednesdays 7:00 PM', 'bridges-grove')),
);

?>
<section class="bg-hero" style="background-image:url('<?php echo $hero_url; ?>');">
  <div class="bg-hero-overlay"></div>
  <div class="bg-hero-inner">
    <div class="bg-hero-copy">
      <span class="bg-hero-kicker"><?php esc_html_e('Bridges Grove AME Zion Church', 'bridges-grove'); ?></span>
      <h1><?php echo nl2br(esc_html($hero_title)); ?></h1>
      <?php if (!empty($hero_subtitle)) : ?>
        <p class="bg-hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
      <?php else : ?>
        <p class="bg-hero-subtitle"><?php esc_html_e('A welcoming church family in Shannon, NC, rooted in worship, service, and love for one another.', 'bridges-grove'); ?></p>
      <?php endif; ?>
      <div class="bg-hero-actions">
        <a class="bg-btn bg-btn-solid bg-btn-lg" href="<?php echo esc_url($cta2_url); ?>"><?php echo bg_icon('map'); ?><?php echo esc_html($cta2_label); ?></a>
        <a class="bg-btn bg-btn-ghost bg-btn-lg" href="<?php echo esc_url($cta1_url); ?>"><?php echo bg_icon('play'); ?><?php echo esc_html($cta1_label); ?></a>
      </div>
      <div class="bg-hero-note">
        <strong><?php esc_html_e('All are welcome', 'bridges-grove'); ?></strong>
      </div>
    </div>
    <aside class="bg-hero-panel" aria-label="<?php esc_attr_e('Church details', 'bridges-grove'); ?>">
      <div class="bg-hero-panel-top">
        <span><?php echo bg_icon('leaf'); ?></span>
        <p><?php esc_html_e('Covered. Centered. Kept in God’s Will.', 'bridges-grove'); ?></p>
      </div>
      <div class="bg-hero-service">
        <span class="bg-hero-service-label"><?php esc_html_e('This Week', 'bridges-grove'); ?></span>
        <strong><?php esc_html_e('Sunday Worship', 'bridges-grove'); ?></strong>
        <p><?php esc_html_e('11:00 AM in Shannon, NC', 'bridges-grove'); ?></p>
      </div>
      <ul class="bg-hero-detail-list">
        <li><span><?php echo bg_icon('clock'); ?></span><?php echo esc_html(implode(' • ', $church_info['service_times'])); ?></li>
        <li><span><?php echo bg_icon('map'); ?></span><?php echo esc_html($church_info['address']); ?></li>
      </ul>
    </aside>
  </div>
</section>

<section class="bg-feature">
  <div class="bg-feature-inner">
    <a class="bg-card bg-card-green" href="<?php echo esc_url(home_url('/contact/')); ?>" aria-label="Plan Your Visit">
      <div class="bg-card-icon" aria-hidden="true"><?php echo bg_icon('church'); ?></div>
      <div class="bg-card-body">
        <div class="bg-card-title">Plan Your Visit</div>
        <div class="bg-card-text">Service times, directions, and what to expect.</div>
      </div>
      <div class="bg-card-arrow" aria-hidden="true"><?php echo bg_icon('arrow'); ?></div>
    </a>

    <a class="bg-card bg-card-maroon" href="#our-pastor" aria-label="Meet Our Pastor">
      <div class="bg-card-icon" aria-hidden="true"><?php echo bg_icon('heart'); ?></div>
      <div class="bg-card-body">
        <div class="bg-card-title">Meet Our Pastor</div>
        <div class="bg-card-text">Learn more about our leadership and vision.</div>
      </div>
      <div class="bg-card-arrow" aria-hidden="true"><?php echo bg_icon('arrow'); ?></div>
    </a>

    <a class="bg-card bg-card-gold" href="#events" aria-label="Upcoming Events">
      <div class="bg-card-icon" aria-hidden="true"><?php echo bg_icon('calendar'); ?></div>
      <div class="bg-card-body">
        <div class="bg-card-title">Upcoming Events</div>
        <div class="bg-card-text">See what’s happening at Bridges Grove.</div>
      </div>
      <div class="bg-card-arrow" aria-hidden="true"><?php echo bg_icon('arrow'); ?></div>
    </a>
  </div>
</section>

<section class="bg-home-modern" aria-label="<?php esc_attr_e('Worship information', 'bridges-grove'); ?>">
  <div class="bg-home-modern-inner">
    <div class="bg-home-lead">
      <span class="bg-kicker"><?php esc_html_e('Worship + Community', 'bridges-grove'); ?></span>
      <h2><?php esc_html_e('A church home for worship, care, and purpose.', 'bridges-grove'); ?></h2>
      <p><?php esc_html_e('Bridges Grove brings together rooted tradition and a fresh, welcoming experience for families, guests, and longtime members.', 'bridges-grove'); ?></p>
    </div>
    <div class="bg-modern-stat-grid">
      <article>
        <span><?php echo bg_icon('clock'); ?></span>
        <strong><?php esc_html_e('Sunday 11:00 AM', 'bridges-grove'); ?></strong>
        <p><?php esc_html_e('Worship service', 'bridges-grove'); ?></p>
      </article>
      <article>
        <span><?php echo bg_icon('book'); ?></span>
        <strong><?php esc_html_e('Wednesday 7:00 PM', 'bridges-grove'); ?></strong>
        <p><?php esc_html_e('Bible study', 'bridges-grove'); ?></p>
      </article>
      <article>
        <span><?php echo bg_icon('map'); ?></span>
        <strong><?php esc_html_e('Shannon, NC', 'bridges-grove'); ?></strong>
        <p><?php echo esc_html($church_info['address']); ?></p>
      </article>
    </div>
  </div>
</section>

<section class="bg-pastor-strip" aria-label="<?php esc_attr_e('Pastor introduction', 'bridges-grove'); ?>">
  <div class="bg-pastor-strip-inner">
    <figure>
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/pastor-james-hayes.png'); ?>" alt="<?php esc_attr_e('Reverend James A. Hayes', 'bridges-grove'); ?>" loading="lazy">
    </figure>
    <div>
      <span class="bg-kicker"><?php esc_html_e('Pastoral Leadership', 'bridges-grove'); ?></span>
      <h2><?php esc_html_e('Meet Rev. James A. Hayes.', 'bridges-grove'); ?></h2>
      <p><?php esc_html_e('Pastor Hayes leads with biblical teaching, servant leadership, and a heart for the people of Bridges Grove and the Shannon community.', 'bridges-grove'); ?></p>
      <div class="bg-enhanced-actions">
        <a class="bg-btn bg-btn-solid" href="<?php echo esc_url(home_url('/our-pastor/')); ?>"><?php esc_html_e('Meet Our Pastor', 'bridges-grove'); ?></a>
        <a class="bg-btn bg-btn-outline" href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact the Church', 'bridges-grove'); ?></a>
      </div>
    </div>
  </div>
</section>

<?php
if (function_exists('bg_render_home_gallery')) {
  bg_render_home_gallery();
}
?>

<?php
// Render one-page sections (pulls content from real pages)
bg_render_section('about', 'About', array('about'));
bg_render_section('first-family', 'First Family', array('first-family', 'firstfamily'));
bg_render_section('our-pastor', 'Our Pastor', array('our-pastor', 'pastor'));
bg_render_section('events', 'Events', array('events'));
bg_render_section('watch', 'Watch', array('watch', 'live', 'watch-live'));
bg_render_section('contact', 'Contact', array('contact'));
bg_render_section('plan-your-visit', 'Plan Your Visit', array('plan-your-visit', 'plan', 'visit'));

get_footer();
