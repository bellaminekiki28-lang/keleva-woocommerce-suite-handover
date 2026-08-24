<?php
defined('ABSPATH') || exit;

$cart_count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="keleva-page-progress" aria-hidden="true"></div>
<a class="screen-reader-text" href="#catalogue"><?php esc_html_e('Aller au catalogue', 'keleva-woo'); ?></a>
<header class="site-header keleva-header">
  <div class="site-header__inner keleva-header__inner">
    <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>" rel="home" aria-label="<?php esc_attr_e('Velora, revenir en haut', 'keleva-woo'); ?>">
      <span class="site-brand__mark site-brand__mark--velora" aria-hidden="true">V</span><span>velora<span class="site-brand__dot">.</span></span>
    </a>
    <nav class="site-navigation keleva-menu" aria-label="<?php esc_attr_e('Navigation principale', 'keleva-woo'); ?>">
      <?php
      wp_nav_menu([
          'theme_location' => 'primary',
          'container' => '',
          'fallback_cb' => static function () use ($shop_url): void {
              echo '<ul><li><a href="' . esc_url($shop_url) . '">' . esc_html__('La sélection', 'keleva-woo') . '</a></li><li><a href="' . esc_url(home_url('/#pourquoi')) . '">' . esc_html__('Notre méthode', 'keleva-woo') . '</a></li><li><a href="' . esc_url(home_url('/#questions')) . '">' . esc_html__('Questions', 'keleva-woo') . '</a></li></ul>';
          },
      ]);
      ?>
    </nav>
    <div class="site-actions">
      <form class="site-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" data-keleva-live-search>
        <span aria-hidden="true">⌕</span><label class="screen-reader-text" for="keleva-product-search"><?php esc_html_e('Rechercher dans la boutique', 'keleva-woo'); ?></label>
        <input id="keleva-product-search" type="search" role="combobox" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Rechercher', 'keleva-woo'); ?>" autocomplete="off" aria-autocomplete="list" aria-haspopup="listbox" aria-expanded="false" aria-controls="keleva-live-search-results">
        <input type="hidden" name="post_type" value="product">
        <div id="keleva-live-search-results" class="keleva-live-search-results" role="listbox" aria-label="<?php esc_attr_e('Résultats de recherche', 'keleva-woo'); ?>" hidden></div>
      </form>
      <?php if (function_exists('wc_get_cart_url')) : ?>
        <a class="site-cart keleva-cart-summary" href="<?php echo esc_url(wc_get_cart_url()); ?>" data-keleva-cart-summary data-keleva-cart-trigger aria-haspopup="dialog" aria-controls="keleva-cart-drawer">
          <span aria-hidden="true">▢</span><span class="site-cart__label"><?php esc_html_e('Panier', 'keleva-woo'); ?></span><b data-keleva-cart-count data-velora-header-cart-count><?php echo esc_html(str_pad((string) $cart_count, 2, '0', STR_PAD_LEFT)); ?></b>
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>
<?php if (function_exists('wc_get_cart_url')) : ?>
  <aside id="keleva-cart-drawer" class="keleva-cart-drawer" aria-labelledby="keleva-cart-drawer-title" aria-hidden="true" hidden>
    <div class="keleva-cart-drawer__backdrop" data-keleva-cart-close></div>
    <section class="keleva-cart-drawer__panel" role="dialog" aria-modal="true" tabindex="-1">
      <header class="keleva-cart-drawer__head"><div><span class="velora-eyebrow"><?php esc_html_e('Votre sélection', 'keleva-woo'); ?></span><h2 id="keleva-cart-drawer-title"><?php esc_html_e('Panier', 'keleva-woo'); ?></h2></div><button type="button" class="keleva-cart-drawer__close" data-keleva-cart-close aria-label="<?php esc_attr_e('Fermer le panier', 'keleva-woo'); ?>">×</button></header>
      <p class="keleva-cart-drawer__message" data-keleva-cart-message aria-live="polite"><?php esc_html_e('Votre panier est prêt à accueillir une bonne idée.', 'keleva-woo'); ?></p>
      <div class="keleva-cart-drawer__lines" data-velora-cart-lines></div>
      <section class="keleva-cart-drawer__cross-sells" data-keleva-cart-cross-sells aria-live="polite" hidden></section>
      <footer class="keleva-cart-drawer__footer"><div><span><?php esc_html_e('Sous-total', 'keleva-woo'); ?></span><strong data-velora-cart-subtotal>—</strong></div><p><?php esc_html_e('Livraison calculée au checkout.', 'keleva-woo'); ?></p><div class="keleva-cart-drawer__actions"><a class="velora-quiet-link" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Voir le panier', 'keleva-woo'); ?></a><a class="velora-primary" href="<?php echo esc_url(wc_get_checkout_url()); ?>"><?php esc_html_e('Commander', 'keleva-woo'); ?> <span aria-hidden="true">→</span></a></div></footer>
    </section>
  </aside>
<?php endif; ?>
