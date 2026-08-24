<?php defined('ABSPATH') || exit; ?>
<footer class="site-footer keleva-footer">
  <div class="site-footer__brand">
    <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="site-brand__mark site-brand__mark--velora" aria-hidden="true">V</span><span>velora<span class="site-brand__dot">.</span></span></a>
    <p><?php esc_html_e('Choisir moins. Choisir mieux.', 'keleva-woo'); ?></p>
  </div>
  <nav class="site-footer__links" aria-label="<?php esc_attr_e('Navigation de pied de page', 'keleva-woo'); ?>">
    <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>"><?php esc_html_e('Catalogue', 'keleva-woo'); ?></a>
    <a href="<?php echo esc_url(home_url('/#pourquoi')); ?>"><?php esc_html_e('Notre méthode', 'keleva-woo'); ?></a>
    <a href="<?php echo esc_url(home_url('/#questions')); ?>"><?php esc_html_e('Aide', 'keleva-woo'); ?></a>
  </nav>
  <span class="site-footer__note"><?php esc_html_e('Objets choisis avec intention.', 'keleva-woo'); ?></span>
</footer>
<?php wp_footer(); ?>
</body>
</html>
