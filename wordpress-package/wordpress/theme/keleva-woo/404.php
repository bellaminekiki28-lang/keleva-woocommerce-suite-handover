<?php
defined('ABSPATH') || exit;
get_header();
?>
<main id="keleva-main" class="keleva-main velora-empty-page" tabindex="-1">
  <section class="velora-empty-state" aria-labelledby="keleva-404-title">
    <p class="velora-eyebrow"><?php esc_html_e('Navigation apaisée', 'keleva-woo'); ?></p>
    <h1 id="keleva-404-title"><?php esc_html_e('Cette page a quitté l’atelier.', 'keleva-woo'); ?></h1>
    <p><?php esc_html_e('La sélection reste accessible : reprenez simplement par le catalogue, sans message technique ni détour inutile.', 'keleva-woo'); ?></p>
    <a class="velora-primary" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>"><?php esc_html_e('Voir la sélection', 'keleva-woo'); ?><span aria-hidden="true">→</span></a>
  </section>
</main>
<?php get_footer(); ?>
