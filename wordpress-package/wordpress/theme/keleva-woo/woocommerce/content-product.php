<?php
defined('ABSPATH') || exit;
global $product;
if (!$product || !$product->is_visible()) { return; }

$image_id = $product->get_image_id();
$short_description = wp_trim_words(wp_strip_all_tags($product->get_short_description() ?: $product->get_description()), 15, '…');
$slug = sanitize_title($product->get_slug());
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
$primary_category = $velora_categories[$slug] ?? wp_strip_all_tags(wc_get_product_category_list($product->get_id(), ', '));
/* translators: %s is the product name. */
$favorite_label = sprintf(__('Ajouter %s aux favoris', 'keleva-woo'), $product->get_name());
?>
<li <?php wc_product_class('keleva-product-card', $product); ?> data-product-id="<?php echo esc_attr($product->get_id()); ?>">
  <div class="keleva-product-card__media">
    <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
      <?php echo wp_kses_post($image_id ? keleva_woo_picture($image_id, 'keleva-card', ['loading' => 'lazy', 'decoding' => 'async']) : wc_placeholder_img('woocommerce_thumbnail')); ?>
      <span class="screen-reader-text"><?php echo esc_html($product->get_name()); ?></span>
    </a>
    <?php if (class_exists('Keleva_Elementor_Product_Badges')) : ?>
      <div class="keleva-product-card__badge"><?php echo Keleva_Elementor_Product_Badges::render_product_badges($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
    <?php elseif ($product->is_on_sale()) : ?>
      <span class="keleva-product-card__badge"><?php esc_html_e('Promotion', 'keleva-woo'); ?></span>
    <?php endif; ?>
    <button class="keleva-product-card__quick-view" type="button" data-keleva-quick-view data-product-id="<?php echo esc_attr($product->get_id()); ?>">◌ <span><?php esc_html_e('Quick view', 'keleva-woo'); ?></span></button>
    <?php if (class_exists('Keleva_Saved_Product_Lists')) : ?>
      <?php echo Keleva_Saved_Product_Lists::toggle_form('wishlist', $product, 'card'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php else : ?>
      <a class="keleva-product-card__favorite" href="<?php echo esc_url(get_permalink($product->get_id())); ?>" aria-label="<?php echo esc_attr($favorite_label); ?>">♡</a>
    <?php endif; ?>
  </div>
  <div class="keleva-product-card__body">
    <p class="keleva-product-card__meta"><span><?php echo esc_html($primary_category); ?></span><i class="keleva-product-card__swatch" aria-hidden="true"></i></p>
    <h2><a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a></h2>
    <?php if ($short_description) : ?><p class="keleva-product-card__description"><?php echo esc_html($short_description); ?></p><?php endif; ?>
    <div class="keleva-product-card__buy">
      <?php echo wp_kses_post($product->get_price_html()); ?>
      <?php if ($product->is_purchasable() && $product->is_in_stock() && !$product->is_type('variable')) : ?>
        <a class="button" href="<?php echo esc_url($product->add_to_cart_url()); ?>" data-keleva-add-product="<?php echo esc_attr($product->get_id()); ?>"><span aria-hidden="true">+</span><?php esc_html_e('Ajouter', 'keleva-woo'); ?></a>
      <?php elseif ($product->is_type('variable')) : ?>
        <a class="button" href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php esc_html_e('Choisir', 'keleva-woo'); ?></a>
      <?php else : ?>
        <span class="button" aria-disabled="true"><?php esc_html_e('Indisponible', 'keleva-woo'); ?></span>
      <?php endif; ?>
    </div>
  </div>
</li>
