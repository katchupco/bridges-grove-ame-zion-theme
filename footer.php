<?php
if (!defined('ABSPATH')) { exit; }
$footer_icon = bg_footer_icon_url();
$site_name = get_bloginfo('name');
$church_address = __('251 Bridges Grove Church Rd, Shannon, NC 28386', 'bridges-grove');
list($watch_label, $watch_url) = bg_get_cta('cta1', __('Watch Live','bridges-grove'), '#watch');
list($visit_label, $visit_url) = bg_get_cta('cta2', __('Plan Your Visit','bridges-grove'), home_url('/contact/'));
$social_links = array(
  'facebook' => array('label' => __('Facebook', 'bridges-grove'), 'url' => esc_url_raw(get_theme_mod('bg_social_facebook', 'https://www.facebook.com/BridgesGroveAMEZ'))),
  'youtube' => array('label' => __('YouTube', 'bridges-grove'), 'url' => esc_url_raw(get_theme_mod('bg_social_youtube', ''))),
  'instagram' => array('label' => __('Instagram', 'bridges-grove'), 'url' => esc_url_raw(get_theme_mod('bg_social_instagram', ''))),
);
$social_links = array_filter($social_links, function ($social) {
  return !empty($social['url']);
});
?>
</main>

<footer class="bg-footer" role="contentinfo">
  <div class="bg-footer-trees" aria-hidden="true"></div>
  <div class="bg-footer-inner">
    <div class="bg-footer-about">
      <div class="bg-footer-badge">
        <div class="bg-footer-icon">
          <?php if ($footer_icon) : ?>
            <img src="<?php echo esc_url($footer_icon); ?>" alt="" loading="lazy">
          <?php else : ?>
            <span class="bg-footer-icon-fallback" aria-hidden="true"><?php echo bg_icon('leaf'); ?></span>
          <?php endif; ?>
        </div>
        <div class="bg-footer-meta">
          <div class="bg-footer-title"><?php echo esc_html($site_name ?: __('Bridges Grove A.M.E. Zion Church', 'bridges-grove')); ?></div>
          <div class="bg-footer-sub"><?php echo esc_html($church_address); ?></div>
        </div>
      </div>
      <p class="bg-footer-blessing"><?php esc_html_e('Rooted in faith, growing in love, and serving the Shannon community with grace.', 'bridges-grove'); ?></p>
      <div class="bg-footer-actions">
        <a class="bg-footer-action" href="<?php echo esc_url($visit_url); ?>"><?php echo bg_icon('map'); ?><?php echo esc_html($visit_label); ?></a>
        <a class="bg-footer-action" href="<?php echo esc_url($watch_url); ?>"><?php echo bg_icon('play'); ?><?php echo esc_html($watch_label); ?></a>
      </div>
      <?php if (!empty($social_links)) : ?>
        <div class="bg-footer-social-wrap">
          <span><?php esc_html_e('Connect With Us', 'bridges-grove'); ?></span>
          <div class="bg-footer-social">
            <?php foreach ($social_links as $key => $social) : ?>
              <a href="<?php echo esc_url($social['url']); ?>" aria-label="<?php echo esc_attr($social['label']); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo bg_icon($key); ?>
                <span><?php echo esc_html($social['label']); ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="bg-footer-column">
      <h2><?php esc_html_e('Gather With Us', 'bridges-grove'); ?></h2>
      <p><?php esc_html_e('Sunday worship, Bible study, community care, and special services throughout the year.', 'bridges-grove'); ?></p>
      <div class="bg-footer-connectional">
        <h2><?php esc_html_e('Connectional Links', 'bridges-grove'); ?></h2>
        <ul class="bg-footer-links">
          <li><a href="https://amezion.org" target="_blank" rel="noopener noreferrer"><?php esc_html_e('A.M.E. Zion Church', 'bridges-grove'); ?></a></li>
          <li><a href="https://laurinburgdistrict.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Laurinburg District', 'bridges-grove'); ?></a></li>
        </ul>
      </div>
    </div>

    <div class="bg-footer-column">
      <h2><?php esc_html_e('Explore', 'bridges-grove'); ?></h2>
      <?php if (has_nav_menu('footer')) : ?>
        <nav class="bg-footer-nav" aria-label="<?php esc_attr_e('Footer menu', 'bridges-grove'); ?>">
          <?php wp_nav_menu(array('theme_location'=>'footer','container'=>false,'menu_class'=>'bg-footer-links','depth'=>1)); ?>
        </nav>
      <?php else : ?>
        <ul class="bg-footer-links">
          <li><a href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('About', 'bridges-grove'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/our-pastor/')); ?>"><?php esc_html_e('Our Pastor', 'bridges-grove'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/events/')); ?>"><?php esc_html_e('Events', 'bridges-grove'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'bridges-grove'); ?></a></li>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="bg-footer-bottom">
    <span>&copy; <?php echo esc_html(date('Y')); ?> <?php echo esc_html($site_name); ?> <?php esc_html_e('All rights reserved.', 'bridges-grove'); ?></span>
    <a class="bg-footer-credit-badge" href="https://katchupmedia.com" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Website by Katch-Up Media LLC', 'bridges-grove'); ?>">
      <span class="bg-footer-credit-label"><?php esc_html_e('Website by', 'bridges-grove'); ?></span>
      <span class="bg-footer-credit-wordmark" aria-hidden="true">
        <span>KATCH</span><span class="bg-footer-credit-hyphen">-</span><span class="bg-footer-credit-up">UP</span><span class="bg-footer-credit-dot">.</span>
      </span>
    </a>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
