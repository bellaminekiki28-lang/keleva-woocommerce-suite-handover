<?php
defined('ABSPATH') || exit;

global $product;

do_action('woocommerce_before_single_product');
if (post_password_required()) {
    echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return;
}

if (!$product instanceof WC_Product) {
    return;
}

$shop_url = wc_get_page_permalink('shop');
$availability = $product->is_in_stock()
    ? __('Disponible à la commande', 'keleva-woo')
    : __('Indisponible actuellement', 'keleva-woo');
$has_restaurant_extras = (string) get_post_meta($product->get_id(), '_keleva_restaurant_sauces', true) !== '';
$purchase_helper = $has_restaurant_extras
    ? __('Personnalisez, ajoutez, puis retrouvez votre sélection dans le panier.', 'keleva-woo')
    : __('Choisissez votre quantité, ajoutez, puis retrouvez votre sélection dans le panier.', 'keleva-woo');
$velora_categories = [
    'mug-nomade-sienna' => __('Équipement', 'keleva-woo'),
    'pochette-field-olive' => __('Équipement', 'keleva-woo'),
    'vase-forme-02' => __('Maison', 'keleva-woo'),
    'lampe-halo-portable' => __('Maison', 'keleva-woo'),
    'carnet-ligne-claire' => __('Coffrets', 'keleva-woo'),
    'tote-canvas-03' => __('Équipement', 'keleva-woo'),
    'plateau-ondulation' => __('Maison', 'keleva-woo'),
    'duo-pause-juste' => __('Coffrets', 'keleva-woo'),
];
$primary_category = $velora_categories[sanitize_title($product->get_slug())] ?? wp_strip_all_tags(wc_get_product_category_list($product->get_id(), ', '));
?>
<article id="product-<?php the_ID(); ?>" <?php wc_product_class('velora-single-product', $product); ?>>
  <nav class="velora-product-breadcrumb" aria-label="<?php esc_attr_e('Fil d’Ariane produit', 'keleva-woo'); ?>">
    <a href="<?php echo esc_url($shop_url); ?>">← <?php esc_html_e('La sélection', 'keleva-woo'); ?></a>
    <span aria-hidden="true">/</span>
    <span><?php echo esc_html($primary_category); ?></span>
  </nav>

  <div class="velora-single-product__top">
    <section class="velora-single-product__gallery" aria-label="<?php esc_attr_e('Média produit', 'keleva-woo'); ?>">
      <div class="velora-product-media-frame">
        <?php
        if (class_exists('Keleva_Elementor_Product_Media')) {
            // Le widget partagé conserve le même rendu dans Elementor et dans le fallback SSR du thème.
            echo Keleva_Elementor_Product_Media::render_product_media($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            do_action('woocommerce_before_single_product_summary');
        }
        ?>
        <div class="velora-product-media-frame__caption">
          <span><?php esc_html_e('Sélection Velora', 'keleva-woo'); ?></span>
          <strong><?php echo esc_html($product->get_name()); ?></strong>
          <span aria-hidden="true">↗</span>
        </div>
      </div>
    </section>

    <section class="summary entry-summary velora-single-product__summary" aria-label="<?php esc_attr_e('Informations et achat', 'keleva-woo'); ?>">
      <header class="velora-product-intro">
        <p class="velora-eyebrow"><?php echo esc_html($primary_category); ?></p>
        <?php if ($product->is_on_sale()) : ?>
          <span class="velora-product-intro__badge"><?php esc_html_e('Offre en cours', 'keleva-woo'); ?></span>
        <?php endif; ?>
        <?php woocommerce_template_single_title(); ?>
        <div class="velora-product-price-line">
          <?php woocommerce_template_single_price(); ?>
          <span class="velora-product-availability <?php echo $product->is_in_stock() ? 'is-available' : 'is-unavailable'; ?>">
            <i aria-hidden="true"></i><?php echo esc_html($availability); ?>
          </span>
        </div>
        <?php woocommerce_template_single_excerpt(); ?>
      </header>

      <div class="velora-product-purchase">
        <div class="velora-product-purchase__heading">
          <p class="velora-eyebrow"><span></span><?php esc_html_e('Votre commande', 'keleva-woo'); ?></p>
          <p><?php echo esc_html($purchase_helper); ?></p>
        </div>
        <?php woocommerce_template_single_add_to_cart(); ?>
        <div class="velora-product-purchase__reassurance" aria-label="<?php esc_attr_e('Informations de commande', 'keleva-woo'); ?>">
          <span>◈ <?php esc_html_e('Paiement protégé', 'keleva-woo'); ?></span>
          <span>◈ <?php esc_html_e('Panier conservé pendant votre navigation', 'keleva-woo'); ?></span>
        </div>
      </div>

      <div class="velora-product-meta">
        <p class="velora-eyebrow"><span></span><?php esc_html_e('Référence produit', 'keleva-woo'); ?></p>
        <?php woocommerce_template_single_meta(); ?>
      </div>
    </section>
  </div>

  <section class="velora-single-product__details" aria-labelledby="keleva-product-details-title">
    <header class="velora-product-details-heading">
      <p class="velora-eyebrow"><span></span><?php esc_html_e('Décider en confiance', 'keleva-woo'); ?></p>
      <h2 id="keleva-product-details-title"><?php esc_html_e('Les détails utiles, sans surcharge.', 'keleva-woo'); ?></h2>
      <p><?php esc_html_e('Retrouvez les informations fournies pour ce produit et les suggestions associées, dans un format lisible sur tous les écrans.', 'keleva-woo'); ?></p>
    </header>
    <div class="velora-product-details-content">
      <?php
      if (has_term(['assises', 'tables', 'rangements', 'luminaires'], 'product_cat', $product->get_id())) {
          add_filter('woocommerce_product_tabs', static function (array $tabs): array {
              unset(
                  $tabs['additional_information'],
                  $tabs['wcfm_product_multivendor_tab'],
                  $tabs['wcfm_policies_tab'],
                  $tabs['wcfm_enquiry_tab']
              );
              return $tabs;
          }, 99);
      }
      ?>
      <?php do_action('woocommerce_after_single_product_summary'); ?>
    </div>
  </section>
</article>
<?php do_action('woocommerce_after_single_product'); ?>
