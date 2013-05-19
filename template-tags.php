<?php
/**
 * Contains template tags to be used in front end development
 * namespace is tdd_groupthink (Taylor's initials + groupthink)
 * All of these must be used in a loop
 */

/**
 * Render an HTML button (that we hook into with JS) for a yay vote
 * Must be used in a loop
 *
 * @param bool $echo set to false to return content instead
 *
 * @todo add the text as a parameter in these button functions, as well as a filter.
 *
 * @return mixed html if $echo is set to false; boolean true if $echo is set to true
 */
function tdd_groupthink_yay_button( $echo = true ) {

	if ( get_groupthink()->has_current_user_voted_on_post( get_the_ID() ) ) {
		$disabled = 'disabled="disabled"';
	} else {
		$disabled = '';
	}

	ob_start();
	?>
	<button class="tdd-groupthink-yay groupthink-button" data-postid="<?php the_ID(); ?>" <?php echo $disabled; ?>><?php echo apply_filters( 'tdd_groupthink_yay_button_text', 'Yay' ); ?></button>
	<?php
	if ( $echo ) {
		echo ob_get_clean();
		return true;
	} else {
		return ob_get_clean();
	}
}

/**
 * Render an HTML button (that we'll hook into with JS) for a nay vote
 * Must be used in a loop
 *
 * @param bool $echo set to false to return content instead
 *
 * @return mixed html if $echo is set to false; boolean true if $echo is set to true
 */
function tdd_groupthink_nay_button( $echo = true ) {

	if ( get_groupthink()->has_current_user_voted_on_post( get_the_ID() ) ) {
		$disabled = 'disabled="disabled"';
	} else {
		$disabled = '';
	}

	ob_start();
	?>
	<button class="tdd-groupthink-nay groupthink-button" data-postid="<?php the_ID(); ?>" <?php echo $disabled; ?>><?php echo apply_filters( 'tdd_groupthink_nay_button_text', 'Nay' ); ?></button>
	<?php
	if ( $echo ) {
		echo ob_get_clean();
		return true;
	} else {
		return ob_get_clean();
	}
}

/**
 * Render an HTML button (that we'll hook into with JS) for a meh non-vote or skip
 * must be used in a loop
 *
 * @param bool $echo set to false to return content instead
 *
 * @return mixed html if $echo is set to false; boolean true if $echo is set to true
 */
function tdd_groupthink_meh_button( $echo = true ) {

	if ( get_groupthink()->has_current_user_voted_on_post( get_the_ID() ) ) {
		$disabled = 'disabled="disabled"';
	} else {
		$disabled = '';
	}

	ob_start();
	?>
	<button class="tdd-groupthink-meh groupthink-button" data-postid="<?php the_ID(); ?>" <?php echo $disabled; ?>><?php echo apply_filters( 'tdd_groupthink_meh_button_text', 'Meh' ); ?></button>
	<?php
	if ( $echo ) {
		echo ob_get_clean();
		return true;
	} else {
		return ob_get_clean();
	}
}

/**
 * Render a hidden button for navigating to the next page. Becomes a live (and non-hidden) link after someone votes
 *
 * @param bool $echo set to false to return content instead
 *
 * @return mixed html if $echo is set to false; boolean true if $echo is set to true
 */
function tdd_groupthink_next_link( $echo = true ) {

	// If someone has already voted on this, show the 'next' button right away.
	if ( get_groupthink()->has_current_user_voted_on_post( get_the_ID() ) ) {
		$url   = get_groupthink()->next_page( explode( ',', $_COOKIE['groupthink'] ) );
		$style = '';
	} else {
		$url   = '#';
		$style = 'display:none';
	}

	ob_start();
	?>
	<a href="<?php echo $url; ?>" style="<?php echo $style; ?>" class="tdd-groupthink-next groupthink-button"><?php echo apply_filters( 'tdd_groupthink_next_text', 'Next' ); ?></a>
	<?php
	if ( $echo ) {
		echo ob_get_clean();
		return true;
	} else {
		return ob_get_clean();
	}
}

/**
 * Show the results for the post
 * must be used in a loop
 * @uses Yay_Or_Nay::get_rating
 *
 * @param bool $echo set false to return content instead
 *
 * @return mixed html if $echo is set to false; boolean true if $echo is set to true
 */
function tdd_groupthink_results( $echo = true ) {

	// If someone has already voted on this, we can show the results right away
	if ( get_groupthink()->has_current_user_voted_on_post( get_the_ID() ) ) {
		$results = get_groupthink()->get_results_div( get_the_ID() );
	} else {
		$results = "<!-- results are injected here with ajax/javascript once you've voted, so stop peeking;-) -->";
	}


	ob_start();
	?>
	<div class="tdd-groupthink-results" data-postid="<?php the_ID(); ?>">
			<?php echo $results; ?>
	</div>
	<?php
	if ( $echo ) {
		echo ob_get_clean();
		return true;
	} else {
		return ob_get_clean();
	}
}