<?php
defined('ABSPATH') || exit;

do_action('woocommerce_cart_is_empty');
?>
<section class="velora-empty-state velora-empty-state--cart" aria-labelledby="keleva-empty-cart-title">
  <p class="velora-eyebrow"><?php esc_html_e('Votre sélection', 'keleva-woo'); ?></p>
  <h2 id="keleva-empty-cart-title"><?php esc_html_e('Le panier est encore léger.', 'keleva-woo'); ?></h2>
  <p><?php esc_html_e('Parcourez la sélection, ouvrez un quick view et gardez vos choix à portée de main dans le tiroir panier.', 'keleva-woo'); ?></p>
  <a class="velora-primary" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Découvrir la sélection', 'keleva-woo'); ?><span aria-hidden="true">→</span></a>
</section>
