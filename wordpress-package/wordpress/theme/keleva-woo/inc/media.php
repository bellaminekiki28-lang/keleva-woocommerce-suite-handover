<?php
defined('ABSPATH') || exit;

function keleva_woo_variant_path(string $path, string $format): string {
    return $path . '.' . strtolower($format);
}

function keleva_woo_is_valid_variant(string $path, string $format): bool {
    if (!is_file($path) || filesize($path) < 1024 || !class_exists('Imagick')) {
        return false;
    }

    try {
        $image = new \Imagick($path);
        $valid = strtoupper($format) === strtoupper($image->getImageFormat());
        $image->clear();
        $image->destroy();
        return $valid;
    } catch (\Throwable $error) {
        return false;
    }
}

function keleva_woo_finish_variant_candidate(string $candidate_path, string $source_path, string $format): ?string {
    if (keleva_woo_is_valid_variant($candidate_path, $format) && filesize($candidate_path) < filesize($source_path)) {
        return $candidate_path;
    }

    if (is_file($candidate_path)) {
        wp_delete_file($candidate_path);
    }

    return null;
}

function keleva_woo_open_gd_source(string $source_path, string $mime_type): \GdImage|false {
    return match ($mime_type) {
        'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($source_path) : false,
        'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($source_path) : false,
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source_path) : false,
        default => false,
    };
}

function keleva_woo_generate_variant(string $source_path, string $mime_type, string $format): ?string {
    if (!is_readable($source_path)) {
        return null;
    }

    $format = strtoupper($format);
    $candidate_path = keleva_woo_variant_path($source_path, strtolower($format));
    $quality = 'AVIF' === $format ? 62 : 78;

    if (class_exists('Imagick') && in_array($format, \Imagick::queryFormats($format), true)) {
        try {
            $image = new \Imagick($source_path);
            $image->stripImage();
            $image->setImageFormat($format);
            $image->setImageCompressionQuality($quality);
            $image->writeImage($candidate_path);
            $image->clear();
            $image->destroy();
            $valid_candidate = keleva_woo_finish_variant_candidate($candidate_path, $source_path, $format);
            if ($valid_candidate) {
                return $valid_candidate;
            }
        } catch (\Throwable $error) {
            if (is_file($candidate_path)) {
                wp_delete_file($candidate_path);
            }
        }
    }

    if ('AVIF' === $format && defined('KELEVA_AVIF_BINARY') && is_string(KELEVA_AVIF_BINARY) && is_executable(KELEVA_AVIF_BINARY) && function_exists('exec')) {
        $command = sprintf('%s -q %d -o %s %s 2>&1', escapeshellcmd(KELEVA_AVIF_BINARY), $quality, escapeshellarg($candidate_path), escapeshellarg($source_path));
        exec($command, $output, $status);
        if (0 === $status) {
            $valid_candidate = keleva_woo_finish_variant_candidate($candidate_path, $source_path, $format);
            if ($valid_candidate) {
                return $valid_candidate;
            }
        }
    }

    $image = keleva_woo_open_gd_source($source_path, $mime_type);
    if (!$image) {
        return null;
    }

    imagealphablending($image, true);
    imagesavealpha($image, true);
    $saved = match ($format) {
        'AVIF' => function_exists('imageavif') ? @imageavif($image, $candidate_path, $quality) : false,
        'WEBP' => function_exists('imagewebp') ? @imagewebp($image, $candidate_path, $quality) : false,
        default => false,
    };
    imagedestroy($image);

    return $saved ? keleva_woo_finish_variant_candidate($candidate_path, $source_path, $format) : null;
}

add_filter('wp_generate_attachment_metadata', static function (array $metadata, int $attachment_id): array {
    $mime_type = get_post_mime_type($attachment_id);
    if (!in_array($mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return $metadata;
    }

    $original = get_attached_file($attachment_id);
    if (!$original) {
        return $metadata;
    }

    foreach (['avif' => 'AVIF', 'webp' => 'WEBP'] as $meta_key => $format) {
        $generated = [];
        $original_variant = keleva_woo_generate_variant($original, $mime_type, $format);
        if ($original_variant) {
            $generated['full'] = wp_basename($original_variant);
        }

        $directory = dirname($original);
        foreach (($metadata['sizes'] ?? []) as $size => $data) {
            $path = trailingslashit($directory) . $data['file'];
            $variant = keleva_woo_generate_variant($path, $mime_type, $format);
            if ($variant) {
                $generated[$size] = wp_basename($variant);
            }
        }

        $metadata['keleva_' . $meta_key] = $generated;
    }

    return $metadata;
}, 30, 2);

function keleva_woo_get_variant_path(int $attachment_id, string $size, string $format): ?string {
    $metadata = wp_get_attachment_metadata($attachment_id);
    $filename = $metadata['keleva_' . strtolower($format)][$size] ?? null;
    $attached = get_attached_file($attachment_id);
    if (!$attached) {
        return null;
    }

    if ($filename) {
        $path = dirname($attached) . '/' . $filename;
        if (keleva_woo_is_valid_variant($path, $format)) {
            return $path;
        }
    }

    // Fallback for pre-generated derivatives uploaded beside an original image on hosts
    // where PHP cannot encode AVIF/WebP. The file remains WordPress-managed and is only
    // served when its exact sibling filename exists and has a non-trivial size.
    $extension = strtolower($format);
    $base = pathinfo($attached, PATHINFO_FILENAME);
    $candidate = dirname($attached) . '/' . $base . '.' . $extension;
    if (!is_file($candidate) || filesize($candidate) < 1024) {
        return null;
    }

    return $candidate;
}

function keleva_woo_get_variant_url(int $attachment_id, string $size, string $format): ?string {
    if (!keleva_woo_get_variant_path($attachment_id, $size, $format)) {
        return null;
    }

    return home_url(user_trailingslashit(sprintf('keleva-media/%d/%s', $attachment_id, strtolower($format))));
}

add_action('init', static function (): void {
    add_rewrite_rule('^keleva-media/([0-9]+)/(avif|webp)/?$', 'index.php?keleva_media_attachment=$matches[1]&keleva_media_format=$matches[2]', 'top');

    $theme_version = wp_get_theme()->get('Version');
    if (get_option('keleva_woo_media_routes_version') !== $theme_version) {
        update_option('keleva_woo_media_routes_version', $theme_version, false);
        flush_rewrite_rules(false);
    }
}, 20);

add_filter('query_vars', static function (array $vars): array {
    $vars[] = 'keleva_media_attachment';
    $vars[] = 'keleva_media_format';
    return $vars;
});

add_action('template_redirect', static function (): void {
    $attachment_id = absint(get_query_var('keleva_media_attachment'));
    $format = sanitize_key((string) get_query_var('keleva_media_format'));
    if (!$attachment_id || !in_array($format, ['avif', 'webp'], true)) {
        return;
    }

    if ('attachment' !== get_post_type($attachment_id)) {
        status_header(404);
        exit;
    }

    $path = keleva_woo_get_variant_path($attachment_id, 'full', strtoupper($format));
    if (!$path || !is_readable($path)) {
        status_header(404);
        exit;
    }

    header('Content-Type: image/' . $format, true);
    header('Content-Length: ' . (string) filesize($path), true);
    header('Cache-Control: public, max-age=31536000, immutable', true);
    header('X-Keleva-Cache-Policy: public-media', true);
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Stream immutable media directly to preserve range/cache behavior.
    readfile($path);
    exit;
}, 0);

function keleva_woo_picture(int $attachment_id, string $size, array $attributes = []): string {
    if (!$attachment_id) {
        return '';
    }

    $fallback = wp_get_attachment_image($attachment_id, $size, false, $attributes);
    $avif = keleva_woo_get_variant_url($attachment_id, $size, 'AVIF');
    $webp = keleva_woo_get_variant_url($attachment_id, $size, 'WEBP');
    if (!$avif && !$webp) {
        return $fallback;
    }

    $sources = '';
    if ($avif) {
        $sources .= sprintf('<source type="image/avif" srcset="%s">', esc_url($avif));
    }
    if ($webp) {
        $sources .= sprintf('<source type="image/webp" srcset="%s">', esc_url($webp));
    }

    return '<picture>' . $sources . $fallback . '</picture>';
}
