<?php
defined('ABSPATH') || exit;

get_header();
$location = function_exists('is_cart') && is_cart() ? 'cart' : (function_exists('is_checkout') && is_checkout() ? 'checkout' : '');
$keleva_page_title = $location === 'cart' ? __('Votre sélection', 'keleva-woo') : ($location === 'checkout' ? __('Finaliser simplement.', 'keleva-woo') : '');
?>
<main id="keleva-main" class="keleva-main<?php echo $location ? ' velora-' . esc_attr($location) . '-page' : ''; ?>" tabindex="-1">
  <?php if ($location) : ?><header class="velora-flow-heading"><p class="velora-eyebrow"><?php echo $location === 'cart' ? esc_html__('Le panier, sans rupture', 'keleva-woo') : esc_html__('Une étape à la fois', 'keleva-woo'); ?></p><h1><?php echo esc_html($keleva_page_title); ?></h1><p><?php echo $location === 'cart' ? esc_html__('Ajustez votre sélection puis poursuivez lorsque tout est juste.', 'keleva-woo') : esc_html__('Coordonnées, livraison et paiement : une séquence simple, lisible et confidentielle.', 'keleva-woo'); ?></p></header><?php endif; ?>
  <?php if ($location && keleva_woo_render_layout($location)) : ?>
  <?php else : ?>
    <?php if (have_posts()) : while (have_posts()) : the_post(); the_content(); endwhile; endif; ?>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
