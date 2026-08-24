<?php
defined('ABSPATH') || exit;

final class Keleva_Elementor_Product_Media extends \Elementor\Widget_Base {
    public function get_name(): string { return 'keleva-product-media'; }
    public function get_title(): string { return __('Keleva Product Media', 'keleva-woo-addons'); }
    public function get_icon(): string { return 'eicon-image'; }
    public function get_categories(): array { return ['keleva-woo']; }
    public function get_keywords(): array { return ['woocommerce', 'product', 'gallery', 'image', 'keleva']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Galerie produit', 'keleva-woo-addons')]);
        $this->add_control('image_size', [
            'label' => __('Taille des images', 'keleva-woo-addons'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'woocommerce_single',
            'options' => ['woocommerce_single' => __('Produit', 'keleva-woo-addons'), 'large' => __('Grande', 'keleva-woo-addons')],
        ]);
        $this->add_control('link_to_full', ['label' => __('Lier à l’image source', 'keleva-woo-addons'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->end_controls_section();
    }

    public static function render_product_media(WC_Product $product, string $image_size = 'woocommerce_single', bool $link_to_full = true): string {
        if (!in_array($image_size, ['woocommerce_single', 'large'], true)) $image_size = 'woocommerce_single';
        $image_id = absint($product->get_image_id());
        $ids = array_values(array_unique(array_filter(array_merge([$image_id], $product->get_gallery_image_ids()))));
        ob_start();
        if (!$ids) {
            echo wc_placeholder_img('woocommerce_single'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return (string) ob_get_clean();
        }
        $main_id = $image_id ?: $ids[0];
        $main_full = wp_get_attachment_image_url($main_id, 'full');
        $main_alt = (string) get_post_meta($main_id, '_wp_attachment_image_alt', true);
        $main_image = function_exists('keleva_woo_picture')
            ? keleva_woo_picture($main_id, $image_size, ['class' => 'wp-post-image', 'alt' => $main_alt ?: $product->get_name(), 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async'])
            : wp_get_attachment_image($main_id, $image_size, false, ['class' => 'wp-post-image', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'alt' => $main_alt ?: $product->get_name()]);
        $gallery_class = $image_id ? 'woocommerce-product-gallery--with-images' : 'woocommerce-product-gallery--without-images';
        echo '<div class="woocommerce-product-gallery keleva-product-gallery keleva-product-media ' . esc_attr($gallery_class) . ' images">';
        echo '<div class="woocommerce-product-gallery__wrapper"><div class="woocommerce-product-gallery__image keleva-product-gallery__image" data-keleva-gallery-main data-large_image="' . esc_url($main_full ?: '') . '">';
        echo '<a href="' . esc_url($main_full ?: '') . '"' . ($link_to_full ? '' : ' tabindex="-1" aria-hidden="true"') . ' aria-label="' . esc_attr__('Afficher l’image produit en grand', 'keleva-woo-addons') . '">' . $main_image . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div></div>';
        if (count($ids) > 1) {
            echo '<ul class="keleva-product-gallery__thumbs" aria-label="' . esc_attr__('Choisir une image produit', 'keleva-woo-addons') . '">';
            foreach ($ids as $id) {
                $full = wp_get_attachment_image_url($id, 'full');
                $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
                $avif = function_exists('keleva_woo_get_variant_url') ? keleva_woo_get_variant_url($id, 'full', 'AVIF') : '';
                $webp = function_exists('keleva_woo_get_variant_url') ? keleva_woo_get_variant_url($id, 'full', 'WEBP') : '';
                /* translators: %s: image alternative text or product name. */
                echo '<li><button type="button" class="keleva-product-gallery__thumb" data-keleva-gallery-image data-src="' . esc_url($full ?: '') . '" data-alt="' . esc_attr($alt ?: $product->get_name()) . '" data-avif="' . esc_url($avif ?: '') . '" data-webp="' . esc_url($webp ?: '') . '" aria-label="' . esc_attr(sprintf(__('Afficher l’image : %s', 'keleva-woo-addons'), $alt ?: $product->get_name())) . '" aria-pressed="' . ($id === $main_id ? 'true' : 'false') . '">';
                echo wp_get_attachment_image($id, 'woocommerce_gallery_thumbnail', false, ['loading' => 'lazy', 'decoding' => 'async', 'alt' => '']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</button></li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        return (string) ob_get_clean();
    }

    protected function render(): void {
        global $product;
        if (!$product instanceof WC_Product) { echo '<p class="keleva-widget-empty" role="status">' . esc_html__('À placer dans un template produit.', 'keleva-woo-addons') . '</p>'; return; }
        $settings = $this->get_settings_for_display();
        echo self::render_product_media($product, (string) ($settings['image_size'] ?? 'woocommerce_single'), 'yes' === ($settings['link_to_full'] ?? 'yes')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
