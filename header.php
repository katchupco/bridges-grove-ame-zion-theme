<?php
if (!defined('ABSPATH')) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="bg-skip-link" href="#primary"><?php esc_html_e('Skip to content', 'bridges-grove'); ?></a>

<header class="bg-header is-over-dark" role="banner">
  <div class="bg-header-inner">
    <div class="bg-brand">
      <?php
        $dark_logo = function_exists('bg_header_logo_url') ? bg_header_logo_url('dark') : '';
        $light_logo = function_exists('bg_header_logo_url') ? bg_header_logo_url('light') : '';
        if (!$dark_logo && $light_logo) {
            $dark_logo = $light_logo;
        }
        if (!$light_logo && $dark_logo) {
            $light_logo = $dark_logo;
        }
      ?>
      <?php if ($dark_logo || $light_logo) : ?>
        <a class="bg-adaptive-logo-link" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
          <span class="bg-adaptive-logo-frame">
            <img class="bg-adaptive-logo bg-adaptive-logo-dark" src="<?php echo esc_url($dark_logo); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
            <img class="bg-adaptive-logo bg-adaptive-logo-light" src="<?php echo esc_url($light_logo); ?>" alt="" aria-hidden="true">
          </span>
        </a>
      <?php elseif (has_custom_logo()) : ?>
        <?php the_custom_logo(); ?>
      <?php else : ?>
        <a class="bg-brand-link" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
          <span class="bg-brand-mark" aria-hidden="true"><?php echo bg_icon('leaf'); ?></span>
          <div class="bg-brand-text">
            <span class="bg-brand-top"><?php echo esc_html(get_bloginfo('name')); ?></span>
            <span class="bg-brand-sub"><?php echo esc_html(get_bloginfo('description') ?: 'AME Zion Church'); ?></span>
          </div>
        </a>
      <?php endif; ?>
    </div>

    <nav class="bg-nav-wrap" aria-label="<?php esc_attr_e('Primary menu', 'bridges-grove'); ?>">
      <?php bg_primary_menu(); ?>
    </nav>

    <div class="bg-header-cta">
      <?php
        list($cta1_label, $cta1_url) = bg_get_cta('cta1', __('Watch Live','bridges-grove'), 'https://www.facebook.com/BridgesGroveAMEZ');
        list($cta2_label, $cta2_url) = bg_get_cta('cta2', __('Plan Your Visit','bridges-grove'), home_url('/contact/'));
      ?>
      <a class="bg-btn bg-btn-outline" href="<?php echo esc_url($cta1_url); ?>"><?php echo esc_html($cta1_label); ?></a>
      <a class="bg-btn bg-btn-solid" href="<?php echo esc_url($cta2_url); ?>"><?php echo esc_html($cta2_label); ?></a>
      <button class="bg-mobile-toggle" type="button" aria-label="<?php esc_attr_e('Open menu', 'bridges-grove'); ?>" aria-expanded="false" aria-controls="bg-mobile-menu">
        <span class="bg-mobile-toggle-lines" aria-hidden="true"><span></span><span></span></span>
      </button>
    </div>
  </div>

  <button class="bg-mobile-backdrop" type="button" aria-label="<?php esc_attr_e('Close menu', 'bridges-grove'); ?>" hidden></button>
  <div class="bg-mobile-panel" id="bg-mobile-menu" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Site menu', 'bridges-grove'); ?>" hidden>
    <div class="bg-mobile-panel-head">
      <div>
        <span class="bg-mobile-panel-kicker"><?php esc_html_e('Bridges Grove', 'bridges-grove'); ?></span>
        <strong><?php esc_html_e('Explore Our Church', 'bridges-grove'); ?></strong>
      </div>
      <button class="bg-mobile-close" type="button" aria-label="<?php esc_attr_e('Close menu', 'bridges-grove'); ?>">
        <span aria-hidden="true"></span>
      </button>
    </div>
    <nav class="bg-mobile-nav" aria-label="<?php esc_attr_e('Mobile menu', 'bridges-grove'); ?>">
      <?php bg_primary_menu(); ?>
    </nav>
    <div class="bg-mobile-service">
      <span><?php echo bg_icon('clock'); ?></span>
      <div>
        <small><?php esc_html_e('Sunday Worship', 'bridges-grove'); ?></small>
        <strong><?php esc_html_e('11:00 AM', 'bridges-grove'); ?></strong>
      </div>
    </div>
    <div class="bg-mobile-cta">
      <a class="bg-btn bg-btn-outline" href="<?php echo esc_url($cta1_url); ?>"><?php echo bg_icon('play'); ?><?php echo esc_html($cta1_label); ?></a>
      <a class="bg-btn bg-btn-solid" href="<?php echo esc_url($cta2_url); ?>"><?php echo bg_icon('map'); ?><?php echo esc_html($cta2_label); ?></a>
    </div>
    <p class="bg-mobile-address"><?php esc_html_e('251 Bridges Grove Church Rd, Shannon, NC 28386', 'bridges-grove'); ?></p>
  </div>
</header>

<main id="primary" class="bg-site" role="main">
<?php
if (function_exists('bg_should_render_tribe_events_hero') && bg_should_render_tribe_events_hero() && function_exists('bg_render_tribe_events_hero_html')) {
    echo bg_render_tribe_events_hero_html();
}
?>
