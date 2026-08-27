<?php
defined('ABSPATH') || exit;
get_header();

$products = keleva_woo_featured_products(9);
$hero_product = $products[0] ?? null;
$hero_image_id = (int) get_theme_mod('keleva_home_hero_image_id', 0);
if (!$hero_image_id) {
  $first_image_id = 0;
  foreach ($products as $candidate_product) {
    $candidate_image_id = (int) $candidate_product->get_image_id();
    if ($candidate_image_id <= 0) {
      continue;
    }
    $first_image_id = $first_image_id ?: $candidate_image_id;
    $candidate_metadata = wp_get_attachment_metadata($candidate_image_id);
    $candidate_width = (int) ($candidate_metadata['width'] ?? 0);
    $candidate_height = (int) ($candidate_metadata['height'] ?? 0);
    if ($candidate_width > 1 && $candidate_height > 1) {
      $hero_image_id = $candidate_image_id;
      break;
    }
  }
  // Preserve a valid attachment fallback if metadata is unavailable.
  $hero_image_id = $hero_image_id ?: $first_image_id;
}
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$categories = [
  ['label' => __('Assises', 'keleva-woo'), 'slug' => 'assises', 'count' => '03'],
  ['label' => __('Tables', 'keleva-woo'), 'slug' => 'tables', 'count' => '02'],
  ['label' => __('Rangements', 'keleva-woo'), 'slug' => 'rangements', 'count' => '02'],
  ['label' => __('Luminaires', 'keleva-woo'), 'slug' => 'luminaires', 'count' => '02'],
];
$copy = static fn (string $key, string $default): string => keleva_woo_home_copy($key, $default);
$benefits = [
  ['01', '⌁', 'Un panier qui suit', 'Le panier reste visible pendant la recherche. On ajuste, on compare, on continue — sans perdre la page en cours.'],
  ['02', '◌', 'Le détail au bon moment', 'Le quick view donne les informations utiles, les variantes et la quantité sans transformer chaque produit en nouvelle page.'],
  ['03', '◈', 'Une fin sans friction', 'Le checkout rassemble les coordonnées, la livraison et le paiement dans une séquence courte, lisible et mobile-first.'],
];
$faqs = [
  ['Pourquoi le quick view ?', 'Pour consulter les informations importantes et ajouter une variante sans quitter le catalogue. La page produit complète reste disponible pour les besoins plus approfondis.'],
  ['Quand la livraison est-elle offerte ?', 'Le rail panier affiche en direct la progression vers le seuil défini par la boutique. Le montant et les règles réels viennent de WooCommerce.'],
  ['Le checkout est-il adapté au mobile ?', 'Oui. Les champs sont regroupés en petites séquences, le résumé reste accessible et les contrôles sont conçus pour le pouce, avec une zone tactile confortable.'],
];
?>
<main id="keleva-main" class="keleva-main" tabindex="-1">
  <section class="velora-hero" aria-labelledby="keleva-hero-title">
    <div class="velora-hero__copy">
      <p class="velora-eyebrow"><span></span><?php esc_html_e('Mobilier contemporain, choisi avec intention', 'keleva-woo'); ?></p>
      <h1 id="keleva-hero-title"><?php esc_html_e('Composez votre intérieur.', 'keleva-woo'); ?><br><em><?php esc_html_e('Affirmez votre style.', 'keleva-woo'); ?></em></h1>
      <p class="velora-hero__description"><?php esc_html_e('Des pièces durables, des matières justes et des finitions à composer pour créer un intérieur qui vous ressemble.', 'keleva-woo'); ?></p>
      <div class="velora-intent" aria-label="<?php esc_attr_e('Commencer par une catégorie', 'keleva-woo'); ?>">
        <span><?php esc_html_e('Commencer par', 'keleva-woo'); ?></span>
        <div>
          <?php foreach (array_slice($categories, 0, 2) as $category) : $keleva_term = get_term_by('slug', $category['slug'], 'product_cat'); ?>
            <a href="<?php echo esc_url($keleva_term ? get_term_link($keleva_term) : $shop_url); ?>"><?php echo esc_html($category['label']); ?><b aria-hidden="true">→</b></a>
          <?php endforeach; ?>
          <?php $luminaires = get_term_by('slug', 'luminaires', 'product_cat'); ?><a href="<?php echo esc_url($luminaires ? get_term_link($luminaires) : $shop_url); ?>"><?php esc_html_e('Luminaires', 'keleva-woo'); ?><b aria-hidden="true">→</b></a>
        </div>
      </div>
      <div class="velora-hero__actions"><a class="velora-primary" href="#catalogue"><?php esc_html_e('Explorer la sélection', 'keleva-woo'); ?><b aria-hidden="true">→</b></a><a class="velora-quiet-link" href="#pourquoi"><?php esc_html_e('Pourquoi Velora', 'keleva-woo'); ?><b aria-hidden="true">⌄</b></a></div>
      <div class="velora-proof"><span>⌁ <?php esc_html_e('Expédition nette', 'keleva-woo'); ?></span><span>◈ <?php esc_html_e('Paiement protégé', 'keleva-woo'); ?></span></div>
      <p class="velora-status"><span class="site-brand__mark site-brand__mark--velora" aria-hidden="true">V</span><span><strong><?php esc_html_e('Votre sélection évolue.', 'keleva-woo'); ?></strong> <span data-keleva-cart-message><?php esc_html_e('0 pièce sélectionnée.', 'keleva-woo'); ?></span></span></p>
    </div>
    <figure class="velora-hero__visual">
      <?php if ($hero_image_id) : ?>
        <?php echo wp_kses_post(keleva_woo_picture($hero_image_id, 'full', ['fetchpriority' => 'high', 'loading' => 'eager', 'decoding' => 'async'])); ?>
      <?php else : ?>
        <div class="velora-hero__fallback" aria-hidden="true"></div>
      <?php endif; ?>
      <figcaption><span>01</span><strong><?php esc_html_e('Sélection', 'keleva-woo'); ?><br><?php esc_html_e('du moment', 'keleva-woo'); ?></strong><b aria-hidden="true">→</b></figcaption>
    </figure>
  </section>

  <section class="velora-shop" id="catalogue" aria-labelledby="keleva-catalog-title">
    <div class="velora-shop__catalog">
      <header class="velora-section-heading">
        <div><p class="velora-eyebrow"><?php esc_html_e('La collection, sans détour', 'keleva-woo'); ?></p><h2 id="keleva-catalog-title"><?php esc_html_e('Des pièces qui trouvent', 'keleva-woo'); ?><br><em><?php esc_html_e('leur place.', 'keleva-woo'); ?></em></h2></div>
        <p><?php esc_html_e('Assises, tables et objets de lumière : des formes calmes, des matières qui durent.', 'keleva-woo'); ?></p>
      </header>
      <div class="velora-toolbar"><nav class="velora-category-list" aria-label="<?php esc_attr_e('Catégories du catalogue', 'keleva-woo'); ?>"><a href="#catalogue" aria-current="page"><?php esc_html_e('Tout', 'keleva-woo'); ?><small>08</small></a><?php foreach ($categories as $category) : $keleva_term = get_term_by('slug', $category['slug'], 'product_cat'); if ($keleva_term) : ?><a href="<?php echo esc_url(get_term_link($keleva_term)); ?>"><?php echo esc_html($category['label']); ?><small><?php echo esc_html($category['count']); ?></small></a><?php endif; endforeach; ?></nav><a class="velora-sort-link" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Trier : pertinence', 'keleva-woo'); ?> <b aria-hidden="true">⌄</b></a></div>
      <?php /* translators: %d: number of products in the selection. */ ?><p class="velora-result-line"><?php echo esc_html(sprintf(_n('%d pièce dans la sélection', '%d pièces dans la sélection', count($products), 'keleva-woo'), count($products))); ?></p>
      <ul class="keleva-product-grid velora-product-grid">
        <?php foreach ($products as $product) : $GLOBALS['product'] = $product; wc_get_template_part('content', 'product'); endforeach; wp_reset_postdata(); ?>
      </ul>
    </div>
    <aside class="velora-cart-rail keleva-side-cart" aria-label="<?php esc_attr_e('Panier persistant', 'keleva-woo'); ?>">
      <p class="velora-eyebrow"><?php esc_html_e('Votre sélection', 'keleva-woo'); ?></p><h2><?php esc_html_e('Le panier', 'keleva-woo'); ?></h2><span class="velora-cart-rail__count"><b data-keleva-cart-count>0</b> <?php esc_html_e('article(s)', 'keleva-woo'); ?></span>
      <div class="velora-cart-rail__progress"><p data-keleva-cart-message><?php esc_html_e('Votre panier est prêt à accueillir une bonne idée.', 'keleva-woo'); ?></p><span><i data-velora-cart-progress></i></span></div>
      <div class="velora-cart-rail__lines" data-velora-cart-lines><div class="velora-cart-rail__empty">▢<p><?php esc_html_e('Votre panier est prêt à accueillir une bonne idée.', 'keleva-woo'); ?></p></div></div>
      <div class="velora-cart-rail__summary"><p><span><?php esc_html_e('Sous-total', 'keleva-woo'); ?></span><b data-velora-cart-subtotal>—</b></p><p><span><?php esc_html_e('Livraison', 'keleva-woo'); ?></span><b data-velora-cart-delivery><?php esc_html_e('Calculée au checkout', 'keleva-woo'); ?></b></p><p><span><?php esc_html_e('Total estimé', 'keleva-woo'); ?></span><b data-velora-cart-total>—</b></p></div>
      <?php if (function_exists('wc_get_cart_url')) : ?><a class="velora-primary velora-cart-rail__cta" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Passer au checkout', 'keleva-woo'); ?><b aria-hidden="true">→</b></a><?php endif; ?>
      <p class="velora-cart-rail__secure">◈ <?php esc_html_e('Checkout en une seule séquence, optimisé mobile.', 'keleva-woo'); ?></p>
    </aside>
  </section>

  <section class="velora-benefits" id="pourquoi" aria-labelledby="keleva-why-title"><div class="velora-benefits__heading"><p class="velora-eyebrow"><?php echo esc_html($copy('benefits_eyebrow', 'Une autre façon de vendre')); ?></p><h2 id="keleva-why-title"><?php echo esc_html($copy('benefits_title', 'Chaque détail est là')); ?><br><em><?php echo esc_html($copy('benefits_emphasis', 'pour alléger le choix.')); ?></em></h2></div><div class="velora-benefit-grid"><?php foreach ($benefits as [$number, $symbol, $benefit_title, $description]) : ?><article><b><?php echo esc_html($number); ?></b><span aria-hidden="true"><?php echo esc_html($symbol); ?></span><h3><?php echo esc_html($benefit_title); ?></h3><p><?php echo esc_html($description); ?></p></article><?php endforeach; ?></div></section>

  <section class="velora-faq" id="questions" aria-labelledby="keleva-faq-title"><div><p class="velora-eyebrow"><?php echo esc_html($copy('faq_eyebrow', 'Questions fréquentes')); ?></p><h2 id="keleva-faq-title"><?php echo esc_html($copy('faq_title', 'Tout ce qu’il faut')); ?><br><em><?php echo esc_html($copy('faq_emphasis', 'avant de décider.')); ?></em></h2></div><div class="velora-faq__list"><?php foreach ($faqs as $index => [$question, $answer]) : ?><details<?php echo $index === 0 ? ' open' : ''; ?>><summary><?php echo esc_html($question); ?><b aria-hidden="true">+</b></summary><p><?php echo esc_html($answer); ?></p></details><?php endforeach; ?></div></section>
</main>
<?php get_footer(); ?>
