<?php
/**
 * Optional GitHub release updater for the theme.
 */
if (!defined('ABSPATH')) { exit; }

const BG_GITHUB_UPDATER_REPO = 'https://github.com/katchupco/bridges-grove-ame-zion-theme';

function bg_github_updater_config(): array {
    $repo_url = trim((string) get_theme_mod('bg_github_repo_url', BG_GITHUB_UPDATER_REPO));
    if ($repo_url === '') {
        $repo_url = BG_GITHUB_UPDATER_REPO;
    }
    $asset_name = trim((string) get_theme_mod('bg_github_asset_name', ''));
    $enabled = (bool) get_theme_mod('bg_github_updates_enabled', true);
    $auto_updates = (bool) get_theme_mod('bg_github_auto_updates_enabled', true);
    $repo = bg_github_updater_parse_repo($repo_url);

    return array(
        'enabled'      => $enabled,
        'auto_updates' => $auto_updates,
        'repo_url'     => $repo_url,
        'asset_name'   => $asset_name,
        'owner'        => $repo['owner'] ?? '',
        'repo'         => $repo['repo'] ?? '',
    );
}

function bg_github_updater_parse_repo(string $value): array {
    $value = trim($value);
    if ($value === '') {
        return array();
    }

    if (preg_match('~^([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)$~', $value, $matches)) {
        return array('owner' => $matches[1], 'repo' => preg_replace('/\.git$/', '', $matches[2]));
    }

    if (preg_match('~github\.com[:/]+([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)(?:\.git)?/?~i', $value, $matches)) {
        return array('owner' => $matches[1], 'repo' => preg_replace('/\.git$/', '', $matches[2]));
    }

    return array();
}

function bg_github_updater_enabled(): bool {
    $config = bg_github_updater_config();
    return !empty($config['enabled']) && !empty($config['owner']) && !empty($config['repo']);
}

function bg_github_updater_auto_updates_enabled(): bool {
    $config = bg_github_updater_config();
    return bg_github_updater_enabled() && !empty($config['auto_updates']);
}

function bg_github_updater_cache_key(): string {
    $config = bg_github_updater_config();
    return 'bg_github_release_' . md5(($config['owner'] ?? '') . '/' . ($config['repo'] ?? '') . '/' . ($config['asset_name'] ?? ''));
}

function bg_github_updater_error_key(): string {
    return bg_github_updater_cache_key() . '_error';
}

function bg_github_updater_set_error(string $message): void {
    set_transient(bg_github_updater_error_key(), sanitize_text_field($message), 30 * MINUTE_IN_SECONDS);
}

function bg_github_updater_get_error(): string {
    return (string) get_transient(bg_github_updater_error_key());
}

function bg_github_updater_normalize_version(string $tag): string {
    $version = preg_replace('/^[^\d]*/', '', trim($tag));
    return $version ?: trim($tag);
}

function bg_github_updater_get_latest_release($force = false) {
    if (!bg_github_updater_enabled()) {
        return false;
    }

    $cache_key = bg_github_updater_cache_key();
    if (!$force) {
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    }

    $config = bg_github_updater_config();
    $url = sprintf(
        'https://api.github.com/repos/%s/%s/releases/latest',
        rawurlencode($config['owner']),
        rawurlencode($config['repo'])
    );

    $response = wp_remote_get($url, array(
        'timeout' => 12,
        'headers' => array(
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'Bridges-Grove-AME-Zion-Theme-Updater',
        ),
    ));

    if (is_wp_error($response)) {
        bg_github_updater_set_error($response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        bg_github_updater_set_error(sprintf(
            /* translators: %d is a GitHub API response code. */
            __('GitHub returned response code %d.', 'bridges-grove'),
            $response_code
        ));
        return false;
    }

    $release = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($release) || empty($release['tag_name'])) {
        bg_github_updater_set_error(__('GitHub did not return a valid published release.', 'bridges-grove'));
        return false;
    }

    delete_transient(bg_github_updater_error_key());
    set_transient($cache_key, $release, 30 * MINUTE_IN_SECONDS);
    return $release;
}

function bg_github_updater_release_package(array $release): string {
    $config = bg_github_updater_config();
    $assets = $release['assets'] ?? array();
    $asset_name = $config['asset_name'];
    $first_zip = '';

    if (is_array($assets)) {
        foreach ($assets as $asset) {
            $name = $asset['name'] ?? '';
            $download = $asset['browser_download_url'] ?? '';
            if (!$name || !$download || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') {
                continue;
            }
            if ($first_zip === '') {
                $first_zip = esc_url_raw($download);
            }
            if ($asset_name === '' || strcasecmp($asset_name, $name) === 0) {
                return esc_url_raw($download);
            }
        }

        if ($first_zip !== '') {
            return $first_zip;
        }
    }

    return '';
}

function bg_github_updater_update_data($force = false) {
    $release = bg_github_updater_get_latest_release($force);
    if (!$release) {
        return false;
    }

    $stylesheet = get_stylesheet();
    $theme = wp_get_theme($stylesheet);
    $current_version = $theme->get('Version') ?: BG_THEME_VERSION;
    $new_version = bg_github_updater_normalize_version((string) $release['tag_name']);

    if (!version_compare($new_version, $current_version, '>')) {
        return false;
    }

    $package = bg_github_updater_release_package($release);
    if (!$package) {
        return false;
    }

    return array(
        'theme'        => $stylesheet,
        'new_version'  => $new_version,
        'url'          => esc_url_raw($release['html_url'] ?? bg_github_updater_config()['repo_url']),
        'package'      => $package,
        'requires'     => '6.0',
        'requires_php' => '7.4',
    );
}

add_filter('pre_set_site_transient_update_themes', function ($transient) {
    if (!is_object($transient) || !bg_github_updater_enabled()) {
        return $transient;
    }

    $force = is_admin() && isset($_GET['force-check']) && sanitize_text_field(wp_unslash($_GET['force-check'])) === '1';
    $update = bg_github_updater_update_data($force);
    if (!$update) {
        return $transient;
    }

    $stylesheet = get_stylesheet();
    $transient->response[$stylesheet] = $update;
    if (isset($transient->no_update[$stylesheet])) {
        unset($transient->no_update[$stylesheet]);
    }

    return $transient;
});

add_action('delete_site_transient_update_themes', function (): void {
    delete_transient(bg_github_updater_cache_key());
});

add_filter('themes_api', function ($result, $action, $args) {
    if ($action !== 'theme_information' || empty($args->slug) || $args->slug !== get_stylesheet() || !bg_github_updater_enabled()) {
        return $result;
    }

    $release = bg_github_updater_get_latest_release();
    if (!$release) {
        return $result;
    }

    $theme = wp_get_theme(get_stylesheet());
    return (object) array(
        'name'          => $theme->get('Name'),
        'slug'          => get_stylesheet(),
        'version'       => bg_github_updater_normalize_version((string) $release['tag_name']),
        'author'        => $theme->get('Author'),
        'homepage'      => esc_url_raw($release['html_url'] ?? ''),
        'requires'      => '6.0',
        'requires_php'  => '7.4',
        'download_link' => bg_github_updater_release_package($release),
        'sections'      => array(
            'description' => esc_html($theme->get('Description')),
            'changelog'   => wpautop(esc_html($release['body'] ?? __('See the GitHub release notes for details.', 'bridges-grove'))),
        ),
    );
}, 10, 3);

add_filter('auto_update_theme', function ($update, $item) {
    if (!bg_github_updater_auto_updates_enabled()) {
        return $update;
    }

    $theme_slug = '';
    if (is_object($item) && isset($item->theme)) {
        $theme_slug = (string) $item->theme;
    } elseif (is_array($item) && isset($item['theme'])) {
        $theme_slug = (string) $item['theme'];
    }

    return $theme_slug === get_stylesheet() ? true : $update;
}, 10, 2);

function bg_github_updater_admin_page(): void {
    if (!current_user_can('update_themes')) {
        wp_die(esc_html__('You do not have permission to manage theme updates.', 'bridges-grove'));
    }

    $config = bg_github_updater_config();
    $theme = wp_get_theme(get_stylesheet());
    $current_version = $theme->get('Version') ?: BG_THEME_VERSION;
    $release = bg_github_updater_get_latest_release();
    $latest_version = is_array($release) && !empty($release['tag_name'])
        ? bg_github_updater_normalize_version((string) $release['tag_name'])
        : '';
    $package = is_array($release) ? bg_github_updater_release_package($release) : '';
    $error = bg_github_updater_get_error();
    $enabled = bg_github_updater_enabled();
    $update_available = $enabled && $latest_version !== '' && $package !== '' && version_compare($latest_version, $current_version, '>');

    if (!$enabled) {
        $status = __('GitHub updates are disabled', 'bridges-grove');
        $status_class = 'is-warning';
    } elseif ($error !== '') {
        $status = __('GitHub could not be reached', 'bridges-grove');
        $status_class = 'is-error';
    } elseif ($update_available) {
        $status = __('A theme update is available', 'bridges-grove');
        $status_class = 'is-update';
    } elseif ($latest_version !== '') {
        $status = __('The theme is up to date', 'bridges-grove');
        $status_class = 'is-current';
    } else {
        $status = __('Update status is unavailable', 'bridges-grove');
        $status_class = 'is-warning';
    }

    $check_url = wp_nonce_url(
        admin_url('admin-post.php?action=bg_github_check_updates'),
        'bg_github_check_updates'
    );
    $customizer_url = admin_url('customize.php?autofocus[section]=bg_github_updates');
    $updates_url = self_admin_url('update-core.php');
    ?>
    <div class="wrap bg-github-updates-page">
      <h1><?php esc_html_e('Bridges Grove Theme Updates', 'bridges-grove'); ?></h1>
      <p class="description"><?php esc_html_e('Check the official GitHub releases and manage how this WordPress theme receives updates.', 'bridges-grove'); ?></p>

      <?php if (isset($_GET['bg-checked'])) : ?>
        <div class="notice notice-info is-dismissible"><p><?php esc_html_e('The GitHub release check has finished.', 'bridges-grove'); ?></p></div>
      <?php endif; ?>

      <section class="bg-update-status" aria-labelledby="bg-update-status-title">
        <div class="bg-update-status-head">
          <div>
            <span class="bg-update-kicker"><?php esc_html_e('Release status', 'bridges-grove'); ?></span>
            <h2 id="bg-update-status-title"><?php echo esc_html($status); ?></h2>
          </div>
          <span class="bg-update-indicator <?php echo esc_attr($status_class); ?>" aria-hidden="true"></span>
        </div>

        <dl class="bg-update-details">
          <div><dt><?php esc_html_e('Installed version', 'bridges-grove'); ?></dt><dd><?php echo esc_html($current_version); ?></dd></div>
          <div><dt><?php esc_html_e('Latest GitHub release', 'bridges-grove'); ?></dt><dd><?php echo esc_html($latest_version ?: __('Not available', 'bridges-grove')); ?></dd></div>
          <div><dt><?php esc_html_e('Automatic updates', 'bridges-grove'); ?></dt><dd><?php echo esc_html(bg_github_updater_auto_updates_enabled() ? __('Enabled', 'bridges-grove') : __('Disabled', 'bridges-grove')); ?></dd></div>
          <div><dt><?php esc_html_e('Update package', 'bridges-grove'); ?></dt><dd><?php echo esc_html($package !== '' ? __('Ready', 'bridges-grove') : __('Not detected', 'bridges-grove')); ?></dd></div>
        </dl>

        <?php if ($error !== '') : ?>
          <p class="bg-update-error"><strong><?php esc_html_e('GitHub response:', 'bridges-grove'); ?></strong> <?php echo esc_html($error); ?></p>
        <?php endif; ?>

        <div class="bg-update-actions">
          <?php if ($enabled) : ?>
            <a class="button button-primary" href="<?php echo esc_url($check_url); ?>"><?php esc_html_e('Check GitHub Now', 'bridges-grove'); ?></a>
          <?php endif; ?>
          <?php if ($update_available) : ?>
            <a class="button" href="<?php echo esc_url($updates_url); ?>"><?php esc_html_e('Open WordPress Updates', 'bridges-grove'); ?></a>
          <?php endif; ?>
          <a class="button" href="<?php echo esc_url($customizer_url); ?>"><?php esc_html_e('Update Settings', 'bridges-grove'); ?></a>
        </div>
      </section>

      <section class="bg-update-repository" aria-labelledby="bg-update-repository-title">
        <h2 id="bg-update-repository-title"><?php esc_html_e('Connected Repository', 'bridges-grove'); ?></h2>
        <p><a href="<?php echo esc_url($config['repo_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($config['owner'] . '/' . $config['repo']); ?></a></p>
        <p class="description"><?php esc_html_e('WordPress checks the latest published release and installs the attached WordPress-ready ZIP when its version is newer.', 'bridges-grove'); ?></p>
      </section>
    </div>
    <style>
      .bg-github-updates-page{max-width:900px}
      .bg-github-updates-page > .description{margin:4px 0 24px;font-size:14px}
      .bg-update-status,.bg-update-repository{margin-top:20px;padding:24px;border:1px solid #c8d1cc;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04)}
      .bg-update-status{border-top:4px solid #1f5632}
      .bg-update-status-head{display:flex;align-items:center;justify-content:space-between;gap:20px}
      .bg-update-status h2,.bg-update-repository h2{margin:4px 0 0;font-size:22px}
      .bg-update-kicker{color:#7a0f1c;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
      .bg-update-indicator{width:14px;height:14px;flex:0 0 14px;border-radius:50%;background:#8c8f94;box-shadow:0 0 0 5px rgba(140,143,148,.14)}
      .bg-update-indicator.is-current{background:#1f7a45;box-shadow:0 0 0 5px rgba(31,122,69,.14)}
      .bg-update-indicator.is-update{background:#d6a13a;box-shadow:0 0 0 5px rgba(214,161,58,.18)}
      .bg-update-indicator.is-warning,.bg-update-indicator.is-error{background:#b32d2e;box-shadow:0 0 0 5px rgba(179,45,46,.14)}
      .bg-update-details{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));margin:24px 0;border-top:1px solid #e2e6e3;border-bottom:1px solid #e2e6e3}
      .bg-update-details div{padding:16px;border-right:1px solid #e2e6e3}
      .bg-update-details div:first-child{padding-left:0}
      .bg-update-details div:last-child{border-right:0}
      .bg-update-details dt{margin-bottom:6px;color:#646970;font-size:12px}
      .bg-update-details dd{margin:0;color:#1d3127;font-size:16px;font-weight:700}
      .bg-update-actions{display:flex;flex-wrap:wrap;gap:8px}
      .bg-update-actions .button-primary{background:#1f5632;border-color:#1f5632}
      .bg-update-error{padding:12px 14px;border-left:4px solid #b32d2e;background:#fcf0f1}
      .bg-update-repository p:last-child{margin-bottom:0}
      @media (max-width:782px){.bg-update-details{grid-template-columns:1fr 1fr}.bg-update-details div:nth-child(2){border-right:0}.bg-update-details div:nth-child(-n+2){border-bottom:1px solid #e2e6e3}.bg-update-details div:nth-child(3){padding-left:0}}
      @media (max-width:480px){.bg-update-status,.bg-update-repository{padding:18px}.bg-update-details{grid-template-columns:1fr}.bg-update-details div{padding:13px 0;border-right:0;border-bottom:1px solid #e2e6e3}.bg-update-details div:last-child{border-bottom:0}.bg-update-actions .button{width:100%;text-align:center}}
    </style>
    <?php
}

add_action('admin_menu', function (): void {
    add_theme_page(
        __('Theme Updates', 'bridges-grove'),
        __('Theme Updates', 'bridges-grove'),
        'update_themes',
        'bg-github-updates',
        'bg_github_updater_admin_page'
    );
});

add_action('admin_post_bg_github_check_updates', function (): void {
    if (!current_user_can('update_themes')) {
        wp_die(esc_html__('You do not have permission to check theme updates.', 'bridges-grove'));
    }

    check_admin_referer('bg_github_check_updates');
    delete_transient(bg_github_updater_cache_key());
    delete_transient(bg_github_updater_error_key());
    delete_site_transient('update_themes');
    bg_github_updater_get_latest_release(true);
    wp_update_themes();

    wp_safe_redirect(add_query_arg('bg-checked', '1', admin_url('themes.php?page=bg-github-updates')));
    exit;
});

add_action('upgrader_process_complete', function () {
    if (bg_github_updater_enabled()) {
        delete_transient(bg_github_updater_cache_key());
    }
});
