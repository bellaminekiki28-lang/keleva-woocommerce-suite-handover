<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: '/home/ubuntu/keleva-local-wordpress/site';
require rtrim($siteRoot, '/') . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

global $wpdb;
$productId = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_title = %s ORDER BY ID ASC LIMIT 1",
    'Vase Forme 02'
));
$product = $productId ? wc_get_product($productId) : false;
if (!$product) {
    fwrite(STDERR, "Vase Forme 02 introuvable dans le laboratoire local.\n");
    exit(1);
}

$files = [
    '/home/ubuntu/webdev-static-assets/keleva-vase-gallery-reference.jpg',
    '/home/ubuntu/webdev-static-assets/keleva-vase-gallery-unsplash-collection.jpg',
    '/home/ubuntu/webdev-static-assets/keleva-vase-gallery-unsplash-studio.jpg',
];
$attachmentIds = [];
foreach ($files as $path) {
    if (!is_file($path) || filesize($path) < 10_000) {
        fwrite(STDERR, "Média local indisponible ou incomplet : {$path}\n");
        exit(1);
    }
    $tmp = wp_tempnam(basename($path));
    if (!$tmp || !copy($path, $tmp)) {
        fwrite(STDERR, "Copie temporaire impossible : {$path}\n");
        exit(1);
    }
    $attachmentId = media_handle_sideload([
        'name' => sanitize_file_name(basename($path)),
        'tmp_name' => $tmp,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($path),
    ], $productId, 'Visuel de galerie local Keleva');
    if (is_wp_error($attachmentId)) {
        @unlink($tmp);
        fwrite(STDERR, $attachmentId->get_error_message() . "\n");
        exit(1);
    }
    $attachmentIds[] = (int) $attachmentId;
}

$product->set_image_id($attachmentIds[0]);
$product->set_gallery_image_ids(array_slice($attachmentIds, 1));
$product->save();

echo wp_json_encode(['product_id' => $productId, 'attachment_ids' => $attachmentIds], JSON_UNESCAPED_SLASHES) . "\n";
