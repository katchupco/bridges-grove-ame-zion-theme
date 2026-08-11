<?php
/**
 * Theme Customizer options
 */
if (!defined('ABSPATH')) { exit; }

add_action('customize_register', function ($wp_customize) {

    // Panel
    $wp_customize->add_panel('bg_panel', array(
        'title'       => __('Bridges Grove Theme Options', 'bridges-grove'),
        'description' => __('Control the header buttons, landing hero image, and footer icon.', 'bridges-grove'),
        'priority'    => 160,
    ));

    // Colors
    $wp_customize->add_section('bg_colors', array(
        'title'    => __('Brand Colors', 'bridges-grove'),
        'panel'    => 'bg_panel',
        'priority' => 10,
    ));

    $wp_customize->add_setting('bg_accent_maroon', array('default' => '#7a0f1c', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'bg_accent_maroon', array(
        'label'   => __('Maroon', 'bridges-grove'),
        'section' => 'bg_colors',
    )));

    $wp_customize->add_setting('bg_accent_green', array('default' => '#1f5632', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'bg_accent_green', array(
        'label'   => __('Green', 'bridges-grove'),
        'section' => 'bg_colors',
    )));

    $wp_customize->add_setting('bg_accent_gold', array('default' => '#d6a13a', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'bg_accent_gold', array(
        'label'   => __('Gold', 'bridges-grove'),
        'section' => 'bg_colors',
    )));

    // Hero
    $wp_customize->add_section('bg_hero', array(
        'title'    => __('Landing Hero', 'bridges-grove'),
        'panel'    => 'bg_panel',
        'priority' => 20,
    ));

    $wp_customize->add_setting('bg_hero_image_id', array('default' => 0, 'sanitize_callback' => 'absint'));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'bg_hero_image_id', array(
        'label'     => __('Hero Background Image', 'bridges-grove'),
        'section'   => 'bg_hero',
        'mime_type' => 'image',
    )));

    // Hero text
    $wp_customize->add_setting('bg_hero_title', array(
        'default'           => "Entering to Worship God.\nLeaving to Love One Another.",
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    $wp_customize->add_control('bg_hero_title', array(
        'label'       => __('Hero Headline', 'bridges-grove'),
        'description' => __('Use a line break to match the two-line headline.', 'bridges-grove'),
        'section'     => 'bg_hero',
        'type'        => 'textarea',
    ));

    $wp_customize->add_setting('bg_hero_subtitle', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('bg_hero_subtitle', array(
        'label'   => __('Hero Subtitle (optional)', 'bridges-grove'),
        'section' => 'bg_hero',
        'type'    => 'text',
    ));


    $wp_customize->add_setting('bg_hero_overlay', array('default' => 0.45, 'sanitize_callback' => function($v){
        $v = floatval($v);
        if ($v < 0) $v = 0;
        if ($v > 0.85) $v = 0.85;
        return $v;
    }));
    $wp_customize->add_control('bg_hero_overlay', array(
        'label'       => __('Hero Overlay Darkness (0–0.85)', 'bridges-grove'),
        'section'     => 'bg_hero',
        'type'        => 'number',
        'input_attrs' => array('min' => 0, 'max' => 0.85, 'step' => 0.05),
    ));

    // Header logos
    $wp_customize->add_section('bg_header_logos', array(
        'title'    => __('Header Logos', 'bridges-grove'),
        'panel'    => 'bg_panel',
        'priority' => 28,
    ));

    $wp_customize->add_setting('bg_light_logo_id', array('default' => 0, 'sanitize_callback' => 'absint'));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'bg_light_logo_id', array(
        'label'       => __('Light Logo for Dark Backgrounds', 'bridges-grove'),
        'description' => __('Use LIGHT-LogoBridgesNEW.png here. This appears over the dark hero image.', 'bridges-grove'),
        'section'     => 'bg_header_logos',
        'mime_type'   => 'image',
    )));

    $wp_customize->add_setting('bg_dark_logo_id', array('default' => 0, 'sanitize_callback' => 'absint'));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'bg_dark_logo_id', array(
        'label'       => __('Dark Logo for Light Backgrounds', 'bridges-grove'),
        'description' => __('Use Dark-LogoBridgesNEW.png here. This appears after scrolling onto light sections.', 'bridges-grove'),
        'section'     => 'bg_header_logos',
        'mime_type'   => 'image',
    )));

    // Header buttons
    $wp_customize->add_section('bg_header_cta', array(
        'title'    => __('Header Buttons', 'bridges-grove'),
        'panel'    => 'bg_panel',
        'priority' => 30,
    ));

    $pairs = array(
        'cta1' => array('Watch Live', 'https://www.facebook.com/BridgesGroveAMEZ'),
        'cta2' => array('Plan Your Visit', home_url('/contact/')),
    );

    foreach ($pairs as $key => $defaults) {
        $wp_customize->add_setting("bg_{$key}_label", array('default' => $defaults[0], 'sanitize_callback' => 'sanitize_text_field'));
        $wp_customize->add_control("bg_{$key}_label", array(
            'label'   => sprintf(__('%s Button Label', 'bridges-grove'), strtoupper($key)),
            'section' => 'bg_header_cta',
            'type'    => 'text',
        ));

        $wp_customize->add_setting("bg_{$key}_url", array('default' => $defaults[1], 'sanitize_callback' => 'esc_url_raw'));
        $wp_customize->add_control("bg_{$key}_url", array(
            'label'   => sprintf(__('%s Button URL', 'bridges-grove'), strtoupper($key)),
            'section' => 'bg_header_cta',
            'type'    => 'url',
        ));
    }

    // Page photos
    $wp_customize->add_section('bg_page_photos', array(
        'title'    => __('Page Photos', 'bridges-grove'),
        'panel'    => 'bg_panel',
        'priority' => 35,
    ));

    $wp_customize->add_setting('bg_first_family_image_id', array('default' => 0, 'sanitize_callback' => 'absint'));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'bg_first_family_image_id', array(
        'label'       => __('First Family Photo', 'bridges-grove'),
        'section'     => 'bg_page_photos',
        'mime_type'   => 'image',
        'description' => __('This photo appears on the First Family page. You can also use the page Featured Image.', 'bridges-grove'),
    )));

    // Social media
    $wp_customize->add_section('bg_social', array(
        'title'    => __('Social Media', 'bridges-grove'),
        'panel'    => 'bg_panel',
        'priority' => 38,
    ));

    $social_links = array(
        'facebook' => array(__('Facebook URL', 'bridges-grove'), 'https://www.facebook.com/BridgesGroveAMEZ'),
        'youtube'  => array(__('YouTube URL', 'bridges-grove'), ''),
        'instagram'=> array(__('Instagram URL', 'bridges-grove'), ''),
    );

    foreach ($social_links as $key => $social) {
        $wp_customize->add_setting("bg_social_{$key}", array('default' => $social[1], 'sanitize_callback' => 'esc_url_raw'));
        $wp_customize->add_control("bg_social_{$key}", array(
            'label'   => $social[0],
            'section' => 'bg_social',
            'type'    => 'url',
        ));
    }

    // Footer icon
    $wp_customize->add_section('bg_footer', array(
        'title'    => __('Footer', 'bridges-grove'),
        'panel'    => 'bg_panel',
        'priority' => 40,
    ));

    $wp_customize->add_setting('bg_footer_icon_id', array('default' => 0, 'sanitize_callback' => 'absint'));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'bg_footer_icon_id', array(
        'label'     => __('Footer Icon Image', 'bridges-grove'),
        'section'   => 'bg_footer',
        'mime_type' => 'image',
        'description' => __('This replaces the round icon shown in the footer “badge” area.', 'bridges-grove'),
    )));

    // GitHub updates
    $wp_customize->add_section('bg_github_updates', array(
        'title'       => __('GitHub Updates', 'bridges-grove'),
        'panel'       => 'bg_panel',
        'priority'    => 45,
        'description' => __('Let WordPress check a public GitHub release for theme updates.', 'bridges-grove'),
    ));

    $wp_customize->add_setting('bg_github_updates_enabled', array(
        'default'           => false,
        'sanitize_callback' => function ($value) {
            return (bool) $value;
        },
    ));
    $wp_customize->add_control('bg_github_updates_enabled', array(
        'label'       => __('Enable GitHub theme updates', 'bridges-grove'),
        'description' => __('When enabled, WordPress checks the latest GitHub release and shows an update when the release tag is newer than this theme version.', 'bridges-grove'),
        'section'     => 'bg_github_updates',
        'type'        => 'checkbox',
    ));

    $wp_customize->add_setting('bg_github_repo_url', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('bg_github_repo_url', array(
        'label'       => __('GitHub Repository', 'bridges-grove'),
        'description' => __('Example: https://github.com/yourname/bridges-grove-ame-zion or yourname/bridges-grove-ame-zion', 'bridges-grove'),
        'section'     => 'bg_github_updates',
        'type'        => 'text',
    ));

    $wp_customize->add_setting('bg_github_asset_name', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('bg_github_asset_name', array(
        'label'       => __('Release ZIP Asset Name', 'bridges-grove'),
        'description' => __('Optional but recommended. Example: bridges-grove-ame-zion-wordpress-ready-v2.8.12.zip. Leave blank to use the first ZIP attached to the latest release.', 'bridges-grove'),
        'section'     => 'bg_github_updates',
        'type'        => 'text',
    ));
});
