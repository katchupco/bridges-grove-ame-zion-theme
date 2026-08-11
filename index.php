<?php
get_header();
?>
<section class="bg-page">
  <div class="bg-page-inner">
    <header class="bg-page-header">
      <h1 class="bg-page-title"><?php echo esc_html(get_bloginfo('name')); ?></h1>
    </header>

    <div class="bg-post-grid">
      <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article class="bg-card bg-post-card">
          <a class="bg-post-card-link" href="<?php the_permalink(); ?>">
            <h2 class="bg-post-card-title"><?php the_title(); ?></h2>
            <div class="bg-post-card-meta"><?php echo esc_html(get_the_date()); ?></div>
            <div class="bg-post-card-excerpt"><?php the_excerpt(); ?></div>
          </a>
        </article>
      <?php endwhile; else: ?>
        <p><?php esc_html_e('No posts found.', 'bridges-grove'); ?></p>
      <?php endif; ?>
    </div>

    <div class="bg-pagination">
      <?php the_posts_pagination(); ?>
    </div>
  </div>
</section>
<?php get_footer(); ?>
