<?php
get_header();
?>
<section class="bg-page">
  <div class="bg-page-inner">
    <header class="bg-page-header">
      <h1 class="bg-page-title"><?php esc_html_e('Page not found', 'bridges-grove'); ?></h1>
    </header>
    <div class="bg-page-content">
      <p><?php esc_html_e('Sorry, we could not find what you were looking for.', 'bridges-grove'); ?></p>
      <p><a class="bg-btn bg-btn-solid" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to Home', 'bridges-grove'); ?></a></p>
    </div>
  </div>
</section>
<?php get_footer(); ?>
