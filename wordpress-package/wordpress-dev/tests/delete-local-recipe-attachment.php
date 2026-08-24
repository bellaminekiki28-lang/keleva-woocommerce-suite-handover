<?php
declare(strict_types=1);

$siteRoot = getenv('KELEVA_SITE_ROOT') ?: '/home/ubuntu/keleva-local-wordpress/site';
require rtrim($siteRoot, '/') . '/wp-load.php';

$attachmentId = isset($argv[1]) ? absint($argv[1]) : 0;
if (!$attachmentId || 'attachment' !== get_post_type($attachmentId)) {
    fwrite(STDERR, "Identifiant de média de recette invalide.\n");
    exit(2);
}

wp_delete_attachment($attachmentId, true);
echo "Média de recette supprimé : {$attachmentId}\n";
