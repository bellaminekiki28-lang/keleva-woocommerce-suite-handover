<?php
defined('ABSPATH') || exit;
get_header();
?>
<main id="keleva-main" class="keleva-main" style="padding:80px 0;">
  <?php if (have_posts()) : while (have_posts()) : the_post(); the_content(); endwhile; endif; ?>
</main>
<?php get_footer(); ?>
