<?php
/**
 * Galerie produit Keleva, compatible avec WooCommerce 10.5.0.
 * Le storefront Keleva ne charge pas la galerie jQuery WooCommerce : une image
 * principale stable et responsive évite l’opacité initiale et les CLS associés.
 */
defined('ABSPATH') || exit;

global $product;

if (!$product instanceof WC_Product) {
    return;
}

$image_id = absint($product->get_image_id());
$gallery_ids = array_values(array_unique(array_filter(array_merge([$image_id], $product->get_gallery_image_ids()))));
?>
<div class="woocommerce-product-gallery keleva-product-gallery <?php echo $image_id ? 'woocommerce-product-gallery--with-images' : 'woocommerce-product-gallery--without-images'; ?> images">
  <div class="woocommerce-product-gallery__wrapper">
    <?php if ($image_id) : ?>
      <?php
      $full_url = wp_get_attachment_image_url($image_id, 'full');
      $alt = (string) get_post_meta($image_id, '_wp_attachment_image_alt', true);
      $image = keleva_woo_picture($image_id, 'woocommerce_single', [
          'class' => 'wp-post-image',
          'alt' => $alt ?: $product->get_name(),
          'loading' => 'eager',
          'fetchpriority' => 'high',
          'decoding' => 'async',
      ]);
      ?>
      <div class="woocommerce-product-gallery__image keleva-product-gallery__image" data-keleva-gallery-main data-large_image="<?php echo esc_url($full_url); ?>">
        <a href="<?php echo esc_url($full_url); ?>" aria-label="<?php esc_attr_e('Afficher l’image produit en grand', 'keleva-woo'); ?>"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
      </div>
    <?php else : ?>
      <div class="woocommerce-product-gallery__image keleva-product-gallery__image--placeholder">
        <?php echo wc_placeholder_img('woocommerce_single'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
    <?php endif; ?>
  </div>
  <?php if (count($gallery_ids) > 1) : ?>
    <ul class="keleva-product-gallery__thumbs" aria-label="<?php esc_attr_e('Choisir une image produit', 'keleva-woo'); ?>">
      <?php foreach ($gallery_ids as $thumb_id) :
        $thumb_full = wp_get_attachment_image_url($thumb_id, 'full');
        $thumb_alt = (string) get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
        $thumb_avif = keleva_woo_get_variant_url($thumb_id, 'full', 'AVIF');
        $thumb_webp = keleva_woo_get_variant_url($thumb_id, 'full', 'WEBP');
      ?>
        <li>
        <?php /* translators: %s: image alternative text or product name. */ ?>
        <button type="button" class="keleva-product-gallery__thumb" data-keleva-gallery-image data-src="<?php echo esc_url($thumb_full); ?>" data-alt="<?php echo esc_attr($thumb_alt ?: $product->get_name()); ?>" data-avif="<?php echo esc_url($thumb_avif ?: ''); ?>" data-webp="<?php echo esc_url($thumb_webp ?: ''); ?>" aria-label="<?php echo esc_attr(sprintf(__('Afficher l’image : %s', 'keleva-woo'), $thumb_alt ?: $product->get_name())); ?>" aria-pressed="<?php echo $thumb_id === $image_id ? 'true' : 'false'; ?>">
          <?php echo wp_get_attachment_image($thumb_id, 'woocommerce_gallery_thumbnail', false, ['loading' => 'lazy', 'decoding' => 'async', 'alt' => '']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </button>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
