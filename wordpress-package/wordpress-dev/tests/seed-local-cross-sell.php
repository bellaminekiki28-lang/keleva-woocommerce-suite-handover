<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: '/home/ubuntu/keleva-local-wordpress/site';
require rtrim($siteRoot, '/') . '/wp-load.php';

$sourceId = 11;
$targetId = 12;
$backupKey = '_keleva_local_cross_sell_recipe_backup';
$action = $argv[1] ?? 'create';

$source = wc_get_product($sourceId);
$target = wc_get_product($targetId);
if (!$source instanceof WC_Product || !$target instanceof WC_Product) {
    fwrite(STDERR, "Produits de recette cross-sell introuvables.\n");
    exit(1);
}

if ('cleanup' === $action) {
    $backup = get_option($backupKey, null);
    if (is_array($backup) && isset($backup['cross_sell_ids'])) {
        $source->set_cross_sell_ids(array_map('absint', (array) $backup['cross_sell_ids']));
        $source->save();
        delete_option($backupKey);
    }
    echo "cross-sell restauré\n";
    exit(0);
}

if (false === get_option($backupKey, false)) {
    add_option($backupKey, ['cross_sell_ids' => $source->get_cross_sell_ids()], '', false);
}
$source->set_cross_sell_ids([$targetId]);
$source->save();

echo wp_json_encode(['sourceId' => $sourceId, 'targetId' => $targetId], JSON_UNESCAPED_SLASHES) . "\n";
