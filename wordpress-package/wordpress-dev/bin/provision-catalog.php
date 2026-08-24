<?php
/**
 * Jeu de données exclusivement local : aucune revue ni donnée client simulée.
 */
$siteRoot = getenv('KELEVA_SITE_ROOT') ?: (defined('ABSPATH') ? untrailingslashit(ABSPATH) : dirname(__DIR__) . '/site');
if (!defined('ABSPATH')) {
    require_once $siteRoot . '/wp-load.php';
}

if (!function_exists('wc_get_product_id_by_sku')) {
    fwrite(STDERR, "WooCommerce doit être actif avant le provisionnement.\n");
    exit(1);
}

$categories = [
    'maison' => 'Maison',
    'cuisine' => 'Cuisine',
    'jardin' => 'Jardin',
    'edition' => 'Éditions',
    'rupture-qa' => 'Rupture QA',
];
$categoryIds = [];
foreach ($categories as $slug => $name) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        $created = wp_insert_term($name, 'product_cat', ['slug' => $slug]);
        if (is_wp_error($created)) {
            fwrite(STDERR, $created->get_error_message() . "\n");
            exit(1);
        }
        $categoryIds[$slug] = (int) $created['term_id'];
    } else {
        $categoryIds[$slug] = (int) $term->term_id;
    }
}

$catalog = [
    ['sku' => 'KLV-LMP-01', 'name' => 'Lampe Halo 01', 'price' => '89.00', 'category' => 'maison'],
    ['sku' => 'KLV-MIR-02', 'name' => 'Miroir Arche 02', 'price' => '129.00', 'category' => 'maison'],
    ['sku' => 'KLV-PLD-03', 'name' => 'Plaid Tissé 03', 'price' => '56.00', 'category' => 'maison'],
    ['sku' => 'KLV-TRAY-01', 'name' => 'Plateau Ovale 01', 'price' => '34.00', 'category' => 'cuisine'],
    ['sku' => 'KLV-MUG-02', 'name' => 'Tasse Grès 02', 'price' => '22.00', 'category' => 'cuisine'],
    ['sku' => 'KLV-CARAFE-03', 'name' => 'Carafe Ligne 03', 'price' => '46.00', 'category' => 'cuisine'],
    ['sku' => 'KLV-POT-01', 'name' => 'Pot Terre 01', 'price' => '39.00', 'category' => 'jardin'],
    ['sku' => 'KLV-SCISSOR-02', 'name' => 'Ciseaux Jardin 02', 'price' => '27.00', 'category' => 'jardin'],
    ['sku' => 'KLV-LANTERN-03', 'name' => 'Lanterne Verre 03', 'price' => '61.00', 'category' => 'jardin'],
    ['sku' => 'KLV-BOOK-01', 'name' => 'Livre Matières 01', 'price' => '38.00', 'category' => 'edition'],
    ['sku' => 'KLV-PRINT-02', 'name' => 'Affiche Équilibre 02', 'price' => '48.00', 'category' => 'edition'],
    ['sku' => 'KLV-NOTE-03', 'name' => 'Carnet Atelier 03', 'price' => '18.00', 'category' => 'edition'],
    ['sku' => 'KLV-CANDLE-04', 'name' => 'Bougie Minérale 04', 'price' => '31.00', 'category' => 'maison'],
    ['sku' => 'KLV-BOWL-05', 'name' => 'Bowl Signature 05', 'price' => '19.00', 'category' => 'cuisine'],
    ['sku' => 'KLV-STOCK-00', 'name' => 'Produit stock épuisé QA', 'price' => '15.00', 'category' => 'rupture-qa', 'stock_status' => 'outofstock'],
];

$createdOrUpdated = 0;
foreach ($catalog as $item) {
    $productId = (int) wc_get_product_id_by_sku($item['sku']);
    $product = $productId ? wc_get_product($productId) : new WC_Product_Simple();
    if (!$product instanceof WC_Product) {
        continue;
    }
    $product->set_name($item['name']);
    $product->set_sku($item['sku']);
    $product->set_regular_price($item['price']);
    $product->set_catalog_visibility('visible');
    $product->set_status('publish');
    if (isset($item['stock_status'])) {
        $product->set_stock_status($item['stock_status']);
    }
    $product->set_category_ids([$categoryIds[$item['category']]]);
    $product->set_description('Produit de recette local Keleva, prévu uniquement pour les contrôles fonctionnels et visuels.');
    $product->update_meta_data('_keleva_local_seed', '1');
    $product->save();
    $createdOrUpdated++;
}

$vaseId = (int) wc_get_product_id_by_sku('KLV-VASE-02');
$vase = $vaseId ? wc_get_product($vaseId) : new WC_Product_Variable();
if (!$vase instanceof WC_Product_Variable) {
    fwrite(STDERR, "Le SKU KLV-VASE-02 existe mais n’est pas un produit variable.\n");
    exit(1);
}
$vase->set_name('Vase Forme 02');
$vase->set_sku('KLV-VASE-02');
$vase->set_status('publish');
$vase->set_catalog_visibility('visible');
$vase->set_category_ids([$categoryIds['maison']]);
$vase->set_description('Une pièce variable de recette locale pour vérifier les sélecteurs Taille et Couleur.');
$size = new WC_Product_Attribute();
$size->set_id(0);
$size->set_name('Taille');
$size->set_options(['Petit', 'Moyen', 'Grand']);
$size->set_visible(true);
$size->set_variation(true);
$color = new WC_Product_Attribute();
$color->set_id(0);
$color->set_name('Couleur');
$color->set_options(['Naturelle', 'Noir Onyx']);
$color->set_visible(true);
$color->set_variation(true);
$vase->set_attributes([$size, $color]);
$vase->update_meta_data('_keleva_local_seed', '1');
$vase->save();

foreach (['Petit', 'Moyen', 'Grand'] as $sizeValue) {
    foreach (['Naturelle', 'Noir Onyx'] as $colorValue) {
        $variationSku = 'KLV-VASE-02-' . strtoupper(substr($sizeValue, 0, 1)) . '-' . ($colorValue === 'Naturelle' ? 'N' : 'O');
        $variationId = (int) wc_get_product_id_by_sku($variationSku);
        $variation = $variationId ? wc_get_product($variationId) : new WC_Product_Variation();
        if (!$variation instanceof WC_Product_Variation) {
            continue;
        }
        $variation->set_parent_id($vase->get_id());
        $variation->set_sku($variationSku);
        $variation->set_regular_price((string) (49 + (['Petit' => 0, 'Moyen' => 8, 'Grand' => 16][$sizeValue])));
        $variation->set_manage_stock(true);
        $variation->set_stock_quantity(12);
        $variation->set_stock_status('instock');
        $variation->set_attributes(['taille' => $sizeValue, 'couleur' => $colorValue]);
        $variation->save();
    }
}
WC_Product_Variable::sync($vase->get_id());
wc_delete_product_transients($vase->get_id());

$options = [
    [
        'id' => 'finition',
        'label' => 'Finition',
        'display' => 'buttons',
        'max' => 1,
        'required' => true,
        'options' => [
            ['id' => 'naturelle', 'label' => 'Naturelle', 'price' => 0],
            ['id' => 'satin', 'label' => 'Émail satin', 'price' => 5],
            ['id' => 'minerale', 'label' => 'Texture minérale', 'price' => 10],
        ],
    ],
    [
        'id' => 'presentation',
        'label' => 'Présentation',
        'display' => 'checkbox',
        'max' => 2,
        'required' => false,
        'options' => [
            ['id' => 'ruban', 'label' => 'Ruban en coton', 'price' => 2],
            ['id' => 'carte', 'label' => 'Carte de composition', 'price' => 1],
            ['id' => 'papier', 'label' => 'Papier de soie', 'price' => 0],
        ],
    ],
    [
        'id' => 'services',
        'label' => 'Services de préparation',
        'display' => 'checkbox',
        'max' => 3,
        'required' => false,
        'options' => [
            ['id' => 'cadeau', 'label' => 'Emballage cadeau', 'price' => 4],
            ['id' => 'mot', 'label' => 'Mot manuscrit', 'price' => 0],
            ['id' => 'prioritaire', 'label' => 'Préparation prioritaire', 'price' => 7],
        ],
    ],
];
update_post_meta($vase->get_id(), '_keleva_product_option_groups', wp_slash(wp_json_encode($options)));
update_post_meta($vase->get_id(), '_keleva_options_source', 'custom');
update_post_meta($vase->get_id(), '_keleva_options_category_id', 0);

$bowlId = (int) wc_get_product_id_by_sku('KLV-BOWL-05');
if ($bowlId > 0) {
    update_post_meta($bowlId, '_keleva_product_option_groups', wp_slash(wp_json_encode([
        [
            'id' => 'sauces',
            'label' => 'Sauces',
            'display' => 'checkbox',
            'max' => 2,
            'required' => false,
            'options' => [
                ['id' => 'sesame', 'label' => 'Sésame citronné', 'price' => 0],
                ['id' => 'piment', 'label' => 'Piment fumé', 'price' => 1],
                ['id' => 'yaourt', 'label' => 'Yaourt herbes', 'price' => 0],
            ],
        ],
    ])));
    update_post_meta($bowlId, '_keleva_options_source', 'custom');
    update_post_meta($bowlId, '_keleva_options_category_id', 0);
}

printf("Catégories : %d\n", count($categoryIds));
printf("Produits simples : %d\n", $createdOrUpdated);
printf("Produit variable : %d\n", $vase->get_id());
printf("Variations Vase : %d\n", count($vase->get_children()));
printf("Produits catalogue attendus : %d\n", $createdOrUpdated + 1);
