<?php
/**
 * Optional GitHub release updater for the theme.
 */
if (!defined('ABSPATH')) { exit; }

function bg_github_updater_config(): array {
    $repo_url = trim((string) get_theme_mod('bg_github_repo_url', ''));
    $asset_name = trim((string) get_theme_mod('bg_github_asset_name', ''));
    $enabled = (bool) get_theme_mod('bg_github_updates_enabled', false);
    $auto_updates = (bool) get_theme_mod('bg_github_auto_updates_enabled', false);
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

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        set_transient($cache_key, false, 10 * MINUTE_IN_SECONDS);
        return false;
    }

    $release = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($release) || empty($release['tag_name'])) {
        set_transient($cache_key, false, 10 * MINUTE_IN_SECONDS);
        return false;
    }

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

function bg_github_updater_update_data() {
    $release = bg_github_updater_get_latest_release();
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

    $update = bg_github_updater_update_data();
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

add_action('upgrader_process_complete', function () {
    if (bg_github_updater_enabled()) {
        delete_transient(bg_github_updater_cache_key());
    }
});
