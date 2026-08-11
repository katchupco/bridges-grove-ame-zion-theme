<?php
get_header();
?>
<?php while (have_posts()) : the_post(); ?>
  <?php
    $enhanced_page = function_exists('bg_enhanced_page_data') ? bg_enhanced_page_data(bg_page_slug()) : null;
    $church_info = function_exists('bg_church_info') ? bg_church_info() : array(
      'address' => __('251 Bridges Grove Church Rd, Shannon, NC 28386', 'bridges-grove'),
      'service_times' => array(__('Sunday Worship: 11:00 AM', 'bridges-grove')),
    );
    $hero_img = '';
    if (has_post_thumbnail()) {
      $hero_img = get_the_post_thumbnail_url(get_the_ID(), 'full');
    } else {
      $hero_img = get_template_directory_uri() . '/assets/img/hero-fallback.jpg';
    }
  ?>
  <header class="bg-page-hero" style="background-image:url('<?php echo esc_url($hero_img); ?>')">
    <div class="bg-page-hero__overlay"></div>
    <div class="bg-container bg-page-hero__inner">
      <div class="bg-page-hero__copy">
        <?php if (!empty($enhanced_page['kicker'])) : ?>
          <span class="bg-page-hero__badge"><?php echo esc_html($enhanced_page['kicker']); ?></span>
        <?php endif; ?>
        <h1 class="bg-page-hero__title"><?php the_title(); ?></h1>
        <?php if (!empty($enhanced_page['intro'])) : ?>
          <p class="bg-page-hero__subtitle"><?php echo esc_html($enhanced_page['intro']); ?></p>
        <?php elseif (has_excerpt()) : ?>
          <p class="bg-page-hero__subtitle"><?php echo esc_html(get_the_excerpt()); ?></p>
        <?php endif; ?>
        <div class="bg-page-hero__meta" aria-label="<?php esc_attr_e('Church details', 'bridges-grove'); ?>">
          <span><?php echo bg_icon('map'); ?><?php echo esc_html($church_info['address']); ?></span>
          <span><?php echo bg_icon('clock'); ?><?php echo esc_html($church_info['service_times'][1] ?? $church_info['service_times'][0]); ?></span>
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
    if ($enhanced_page) {
      bg_render_enhanced_page($enhanced_page);
    } else {
      echo '<section class="bg-page">';
      echo '<div class="bg-container bg-content">';
      the_content();
      echo '</div>';
      echo '</section>';
    }
  ?>
<?php endwhile; ?>
<?php get_footer(); ?>
