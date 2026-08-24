<?php
/**
 * Formulaire de variation Velora sans dépendance jQuery.
 *
 * @package KelevaWoo
 */

defined('ABSPATH') || exit;

global $product;

if (!$product instanceof WC_Product_Variable) {
    return;
}

$attributes = $product->get_variation_attributes();
$variation_data = array_map(static function (array $variation): array {
    $image = $variation['image'] ?? [];
    return [
        'id' => (int) ($variation['variation_id'] ?? 0),
        'attributes' => (array) ($variation['attributes'] ?? []),
        'price_html' => wp_kses_post((string) ($variation['price_html'] ?? '')),
        'can_add' => !empty($variation['is_purchasable']) && !empty($variation['is_in_stock']),
        'image' => [
            'src' => esc_url_raw((string) ($image['src'] ?? '')),
            'alt' => sanitize_text_field((string) ($image['alt'] ?? '')),
        ],
    ];
}, $product->get_available_variations());

do_action('woocommerce_before_add_to_cart_form');
?>
<form class="variations_form cart velora-variation-form" action="<?php echo esc_url($product->get_permalink()); ?>" method="post" enctype="multipart/form-data" data-velora-variable-form data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>" data-variations="<?php echo esc_attr(wp_json_encode($variation_data)); ?>">
  <?php do_action('woocommerce_before_variations_form'); ?>
  <div class="velora-variation-form__choices">
    <?php foreach ($attributes as $attribute_name => $options) : ?>
      <?php $field_name = 'attribute_' . sanitize_title($attribute_name); ?>
      <label class="velora-variation-form__field" for="<?php echo esc_attr($field_name); ?>">
        <span><?php echo esc_html(wc_attribute_label($attribute_name, $product)); ?></span>
        <select id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" data-velora-variation-select>
          <option value=""><?php esc_html_e('Choisir', 'keleva-woo'); ?></option>
          <?php foreach ($options as $option) : ?>
            <?php $keleva_term = taxonomy_exists($attribute_name) ? get_term_by('slug', $option, $attribute_name) : false; ?>
            <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($keleva_term instanceof WP_Term ? $keleva_term->name : $option); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endforeach; ?>
  </div>
  <p class="velora-variation-form__status" data-velora-variation-status aria-live="polite"><?php esc_html_e('Choisissez vos options.', 'keleva-woo'); ?></p>
  <div class="single_variation_wrap">
    <input type="hidden" name="variation_id" value="0" data-velora-variation-id>
    <div class="woocommerce-variation single_variation" data-velora-variation-price aria-live="polite"></div>
    <div class="woocommerce-variation-add-to-cart variations_button">
      <?php woocommerce_quantity_input(['min_value' => 1, 'input_value' => 1]); ?>
      <button type="submit" class="single_add_to_cart_button button alt" disabled data-velora-variation-submit><?php echo esc_html($product->single_add_to_cart_text()); ?></button>
      <input type="hidden" name="add-to-cart" value="<?php echo esc_attr((string) $product->get_id()); ?>">
      <input type="hidden" name="product_id" value="<?php echo esc_attr((string) $product->get_id()); ?>">
    </div>
  </div>
  <?php do_action('woocommerce_after_variations_form'); ?>
</form>
<?php do_action('woocommerce_after_add_to_cart_form'); ?>
