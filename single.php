<?php
get_header();
?>
<section class="bg-page">
  <div class="bg-page-inner">
    <?php while (have_posts()) : the_post(); ?>
      <header class="bg-page-header">
        <h1 class="bg-page-title"><?php the_title(); ?></h1>
        <div class="bg-post-meta"><?php echo esc_html(get_the_date()); ?></div>
      </header>
      <div class="bg-page-content">
        <?php the_content(); ?>
      </div>
    <?php endwhile; ?>
  </div>
</section>
<?php get_footer(); ?>
