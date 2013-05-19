<?php
/**
 * To customize: take this file and copy it into your theme.
 * If you modify this file, it will be overwritten when the plugin is overwritten
 */
get_header(); ?>

<h1><?php the_title(); ?></h1>
<?php the_content(); ?>

<?php tdd_groupthink_results(); ?>
<?php tdd_groupthink_yay_button(); ?>
<?php tdd_groupthink_meh_button(); ?>
<?php tdd_groupthink_nay_button(); ?>
<?php tdd_groupthink_next_link(); ?>
<?php get_footer(); ?>