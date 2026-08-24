<?php
defined('ABSPATH') || exit;
get_header('shop');

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
?>
<main id="keleva-main" class="keleva-main velora-listing">
  <?php if (keleva_woo_render_layout('shop_archive')) : ?>
  <?php else : ?>
    <?php do_action('woocommerce_before_main_content'); ?>
    <section class="velora-shop" aria-labelledby="keleva-shop-title">
      <div class="velora-shop__catalog">
        <header class="velora-section-heading velora-listing__heading">
          <div><p class="velora-eyebrow"><?php esc_html_e('Le catalogue, sans détour', 'keleva-woo'); ?></p><h1 id="keleva-shop-title"><?php woocommerce_page_title(); ?></h1></div>
          <p><?php esc_html_e('Choisissez, ajustez, continuez : les détails et le panier restent à portée de main.', 'keleva-woo'); ?></p>
        </header>
        <?php if (woocommerce_product_loop()) : ?>
          <div class="velora-toolbar"><nav class="velora-category-list" aria-label="<?php esc_attr_e('Catégories du catalogue', 'keleva-woo'); ?>"><a href="<?php echo esc_url($shop_url); ?>"<?php echo is_shop() ? ' aria-current="page"' : ''; ?>><?php esc_html_e('Tout', 'keleva-woo'); ?></a><?php foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]) as $category) : ?><a href="<?php echo esc_url(get_term_link($category)); ?>"<?php echo is_product_category($category->slug) ? ' aria-current="page"' : ''; ?>><?php echo esc_html($category->name); ?></a><?php endforeach; ?></nav><?php do_action('woocommerce_before_shop_loop'); ?></div>
          <ul class="keleva-product-grid velora-product-grid">
            <?php while (have_posts()) : the_post(); wc_get_template_part('content', 'product'); endwhile; ?>
          </ul>
          <?php do_action('woocommerce_after_shop_loop'); ?>
        <?php else : ?>
          <section class="velora-empty-state" aria-labelledby="keleva-no-products-title">
            <p class="velora-eyebrow"><?php esc_html_e('La sélection évolue', 'keleva-woo'); ?></p>
            <h2 id="keleva-no-products-title"><?php esc_html_e('Aucun objet ne correspond encore.', 'keleva-woo'); ?></h2>
            <p><?php esc_html_e('Essayez une autre recherche ou revenez à toute la sélection pour retrouver les pièces disponibles.', 'keleva-woo'); ?></p>
            <a class="velora-primary" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Voir toute la sélection', 'keleva-woo'); ?><span aria-hidden="true">→</span></a>
          </section>
        <?php endif; ?>
      </div>
      <aside class="velora-cart-rail keleva-side-cart" aria-label="<?php esc_attr_e('Panier persistant', 'keleva-woo'); ?>">
        <p class="velora-eyebrow"><?php esc_html_e('Votre sélection', 'keleva-woo'); ?></p><h2><?php esc_html_e('Le panier', 'keleva-woo'); ?></h2><span class="velora-cart-rail__count"><b data-keleva-cart-count>0</b> <?php esc_html_e('article(s)', 'keleva-woo'); ?></span>
        <div class="velora-cart-rail__progress"><p data-keleva-cart-message><?php esc_html_e('Votre sélection vous attend.', 'keleva-woo'); ?></p><span><i></i></span></div>
        <div class="velora-cart-rail__summary"><p><span><?php esc_html_e('Livraison', 'keleva-woo'); ?></span><b><?php esc_html_e('Au checkout', 'keleva-woo'); ?></b></p><p><span><?php esc_html_e('Total estimé', 'keleva-woo'); ?></span><b><?php esc_html_e('Selon panier', 'keleva-woo'); ?></b></p></div>
        <?php if (function_exists('wc_get_cart_url')) : ?><a class="velora-primary velora-cart-rail__cta" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Passer au checkout', 'keleva-woo'); ?><b aria-hidden="true">→</b></a><?php endif; ?>
      </aside>
    </section>
    <?php do_action('woocommerce_after_main_content'); ?>
  <?php endif; ?>
</main>
<?php get_footer('shop'); ?>
