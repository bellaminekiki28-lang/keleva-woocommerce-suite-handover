<?php
defined('ABSPATH') || exit;
get_header('shop');
?>
<main id="keleva-main" class="keleva-main velora-single-page" tabindex="-1">
  <?php if (keleva_woo_render_layout('single_product')) : ?>
  <?php else : ?>
    <p class="velora-single-page__back"><a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>">← <?php esc_html_e('Revenir à la sélection', 'keleva-woo'); ?></a></p>
    <?php while (have_posts()) : the_post(); wc_get_template_part('content', 'single-product'); endwhile; ?>
  <?php endif; ?>
</main>
<?php get_footer('shop'); ?>
