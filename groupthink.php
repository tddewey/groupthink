<?php
/*
Plugin Name: Groupthink
Plugin URI: http://websitesthatdontsuck.com/groupthink
Description: Gives you a content type (or use an existing type) to do with what you will. Users can then vote yay or nay on each post and will be shown the next post in line. This repeats until the community comes to a consensus.
Version: 0.1
Author: Taylor
Author URI: http://websitesthatdontsuck.com
License: GPL

@todo: Create a way to determine what the last link is, if there are no more posts to vote on.
@todo: Flush rewrite rules on activation.

*/

// Include helper files
include __DIR__ . '/template-tags.php';

class groupthink {

	/**
	 * The only instance of the groupthink Object
	 * @var  groupthink
	 */
	private static $instance;

	/**
	 * The post type that we are yay-ing or nay-ing. This way existing post types can be used.
	 * filterable using `tdd_groupthink_post_type`
	 * @var string
	 */
	static $post_type;

	/**
	 * post meta key to use for number of meh votes
	 * @var
	 */
	public static $meh_count_key = '_tdd_groupthink_meh_count';

	/**
	 * post meta key to use for number of yay votes
	 * @var string
	 */
	public static $yay_count_key = '_tdd_groupthink_yay_count';

	/**
	 * post meta key to use for number of nay votes
	 * @var string
	 */
	public static $nay_count_key = '_tdd_groupthink_nay_count';

	/**
	 * Returns the main instance.
	 *
	 * @return  groupthink
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new groupthink;
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	/**
	 * A dummy constructor.
	 *
	 * @return  groupthink
	 */
	private function __construct() {
		/** This is a singleton class */
	}

	/**
	 * Set up WordPress hooks
	 */
	function setup_actions() {

		// Define the post_type
		self::$post_type = apply_filters( 'tdd_groupthink_post_type', 'groupthink' );

		// If the post_type wasn't filtered, we need to set up our groupthink post type
		if ( ! did_action( 'tdd_groupthink_post_type' ) ) $this->setup_post_type();

		// Register other hooks and filters
		$this->add_filters();
		$this->add_actions();
	}

	/**
	 * Calls to add_filter
	 */
	private function add_filters() {
		add_filter( 'template_include', array( $this, 'maybe_use_our_template' ) );
		add_filter( 'manage_edit-' . self::$post_type . '_columns', array( $this, 'setup_columns' ) );
		add_filter( 'manage_' . self::$post_type . '_posts_custom_column', array( $this, 'populate_columns' ), 10, 2 );
	}

	/**
	 * Calls to add_action
	 */
	private function add_actions() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings') );

		// Ajax handlers.
		$methods = array(
			'gt_vote_up',
			'gt_vote_down',
			'gt_vote_meh',
			'gt_next_page',
			'gt_results'
		);

		foreach ( $methods as $method ) {
			add_action( 'wp_ajax_nopriv_' . $method, array( $this, 'ajax_handler' ) );
			add_action( 'wp_ajax_' . $method, array( $this, 'ajax_handler' ) );
		}

	}

	/**
	 * Enqueue front end scripts
	 */
	public function enqueue_scripts() {
		if ( is_singular( self::$post_type ) )
			wp_enqueue_script( 'tdd-groupthink', plugin_dir_url( __FILE__ ) . 'js/groupthink.js', array( 'jquery' ), '0.1', true );
		wp_localize_script( 'tdd-groupthink', 'groupthink', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'groupthink-actions' )
		) );
	}

	/**
	 * Add a settings page under the post type menu
	 */
	public function add_settings_page() {
		add_submenu_page( 'edit.php?post_type='. self::$post_type, 'Groupthink Settings', 'Settings', 'manage_options', 'groupthink-settings', array( $this, 'render_settings_page'));
	}

	/**
	 * Register settings
	 */
	public function register_settings(){
		register_setting( 'groupthink_options', 'groupthink_options', array( $this, 'validate_options' ));
		add_settings_section( 'groupthink', '', '__return_false', 'groupthink' );
		add_settings_field( 'gt_ending_url', 'URL to direct to after all posts have been voted on', array( $this, 'url_field'), 'groupthink', 'groupthink' );
	}

	/**
	 * Validate options before saving
	 */
	public function validate_options($input) {

		$sanitized_input['gt_ending_url'] = esc_url_raw($input['gt_ending_url']);

		return $sanitized_input;
	}

	/**
	 * Render the URL field
	 */
	public function url_field() {
		$options = get_option( 'groupthink_options' );
		echo "<input id='plugin_text_string' name='groupthink_options[gt_ending_url]' size='40' type='text' value='{$options['gt_ending_url']}' />";
	}

	/**
	 * Render the settings page
	 */
	public function render_settings_page() {
		?>
			<div class="wrap">
				<?php screen_icon(); ?> <h2><?php echo get_admin_page_title(); ?></h2>
				<?php settings_errors(); ?>
				<form action="options.php" method="post">
					<?php settings_fields( 'groupthink_options' ); ?>
					<?php do_settings_sections( 'groupthink' ); ?>

					<?php submit_button(); ?>

				</form>

			</div>
	<?php
	}

	/**
	 * Set up the groupthink post type
	 */
	private function setup_post_type() {

		$labels = array(
			'name'               => _x( 'Groupthink', 'groupthink' ),
			'singular_name'      => _x( 'Groupthink', 'groupthink' ),
			'add_new'            => _x( 'Add New', 'groupthink' ),
			'add_new_item'       => _x( 'Add New Groupthink Post', 'groupthink' ),
			'edit_item'          => _x( 'Edit Groupthink Post', 'groupthink' ),
			'new_item'           => _x( 'New Groupthink Post', 'groupthink' ),
			'view_item'          => _x( 'View Groupthink Post', 'groupthink' ),
			'search_items'       => _x( 'Search Groupthink Post', 'groupthink' ),
			'not_found'          => _x( 'No Groupthink Posts found', 'groupthink' ),
			'not_found_in_trash' => _x( 'Nothing found in the trash', 'groupthink' ),
			'parent_item_colon'  => _x( 'Parent:', 'groupthink' ),
			'menu_name'          => _x( 'Groupthink', 'groupthink' ),
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => 'A custom post type that will then be voted on until the community comes to a consensus',
			'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post'
		);

		register_post_type( 'groupthink', $args );

	}

	/**
	 * An abstraction for incrementing vote and voter counts by one.
	 *
	 * @param $post_id integer
	 * @param $type    string the type of value to set, can be 'yay', 'nay', or 'voter'
	 *
	 * @return bool|integer false on failure, the new count on success
	 */
	private function increment_vote_count( $post_id, $type ) {

		switch ( $type ) {
			case( 'yay' ):
				$key = self::$yay_count_key;
				break;
			case( 'nay' ):
				$key = self::$nay_count_key;
				break;
			case( 'meh' ):
				$key = self::$meh_count_key;
				break;
			default:
				$key = false;
		}

		if ( ! $key ) return false;

		// Get current value
		$value = get_post_meta( absint( $post_id ), $key, true );

		// Add one
		$bool = update_post_meta( absint( $post_id ), $key, $value + 1, $value );

		// return the boolean if false, otherwise the updated count
		if ( ! $bool ) {
			return false;
		} else {
			return $value + 1;
		}
	}

	/**
	 * Sets up some custom columns by filtering column slugs and names
	 *
	 * @param $columns
	 */
	function setup_columns( $columns ) {
		$columns['yay']   = 'Yay votes';
		$columns['nay']   = 'Nay votes';
		$columns['meh']   = 'Meh votes';
		$columns['total'] = 'Total votes';

		unset( $columns['date'] );

		return $columns;
	}

	function populate_columns( $column, $post_id ) {
		$filter_these = array( 'yay', 'nay', 'meh', 'total' );

		if ( in_array( $column, $filter_these ) ) {
			// set up some data
			$yay   = get_post_meta( $post_id, self::$yay_count_key, true );
			$nay   = get_post_meta( $post_id, self::$nay_count_key, true );
			$meh   = get_post_meta( $post_id, self::$meh_count_key, true );
			$total = $yay + $nay + $meh;


			if ( 'yay' === $column )
				echo $yay;

			if ( 'nay' === $column )
				echo $nay;

			if ( 'meh' === $column )
				echo $meh;

			if ( 'total' === $column )
				echo $total;


		}
	}

	/**
	 * Gets the last URL to redirect users to if there are no posts left to edit
	 */
	public function get_ending_url() {
		$options = get_option( 'groupthink_options' );
		$url = esc_url( $options['gt_ending_url'] );

		if ( $url ) {
			return $url;
		} else {
			return home_url();
		}
	}

	/**
	 * Adds a vote (a yay) to the $post_id.
	 *
	 * @param $post_id integer
	 *
	 * @return bool|integer false if there was a problem, the new 'yay' count on success.
	 */
	public function add_yay_vote( $post_id ) {
		return $this->increment_vote_count( $post_id, 'yay' );
	}

	/**
	 * Adds a vote (a nay) to the $post_id.
	 *
	 * @param $post_id integer
	 *
	 * @return bool|integer false if there was a problem, the new 'nay' count on success
	 */
	public function add_nay_vote( $post_id ) {
		return $this->increment_vote_count( $post_id, 'nay' );
	}

	/**
	 * Adds a meh vote to the $post_id.
	 *
	 * @param $post_id integer
	 *
	 * @return bool|integer false if there was a problem, the new 'meh' count on success
	 */
	public function add_meh_vote( $post_id ) {
		return $this->increment_vote_count( $post_id, 'meh' );
	}

	/**
	 * How many voters. This includes all yays, nays, and meh.
	 *
	 * @param $post_id integer
	 *
	 * @return integer
	 */
	public function get_voters_count( $post_id ) {
		$yays = get_post_meta( $post_id, self::$yay_count_key, true );
		$nays = get_post_meta( $post_id, self::$nay_count_key, true );
		$mehs = get_post_meta( $post_id, self::$meh_count_key, true );

		$count = $yays + $nays + $mehs;

		if ( ! $count )
			return false;
		else
			return absint( $count );
	}

	/**
	 * Gets the next page, but excludes an optional array of Ids
	 *
	 * @param $ids_to_exclude array of integers
	 *
	 * @return string ?
	 */
	public function next_page( $ids_to_exclude = array() ) {

		$next = new WP_Query( array(
			'post_type'      => self::$post_type,
			'posts_per_page' => 1,
			'post__not_in'   => $ids_to_exclude
		) );

		if ( $next->have_posts() ) {
			return get_permalink( $next->post->ID );
		} else {
			return $this->get_ending_url();
		}

	}

	/**
	 * Gets some HTML that shows the responses to a particular post ID
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	public function get_results_div( $post_id ) {
		$yay = get_post_meta( $post_id, self::$yay_count_key, true );
		$nay = get_post_meta( $post_id, self::$nay_count_key, true );
		$meh = get_post_meta( $post_id, self::$meh_count_key, true );

		ob_start();
		?>
		<table>
			<tr>
				<th><?php echo apply_filters( 'tdd_groupthink_yay_button_text', 'yay' ); ?> votes</th>
				<td><?php echo $yay; ?></td>
			</tr>
			<tr>
				<th><?php echo apply_filters( 'tdd_groupthink_nay_button_text', 'nay' ); ?> votes</th>
				<td><?php echo $nay; ?></td>
			</tr>
			<tr>
				<th><?php echo apply_filters( 'tdd_groupthink_meh_button_text', 'meh' ); ?> votes</th>
				<td><?php echo $meh; ?></td>
			</tr>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handles the ajax response to either update vote counts or get counts
	 * @return string json response
	 */
	public function ajax_handler() {

		// Check that the ajax request is valid by checking the nonce
		if ( ! check_ajax_referer( 'groupthink-actions', null, false ) ) {
			//$this->_send_response( 'error', 'Sorry, you don\'t have proper permissions to do this' );
		}

		$action = isset( $_POST['action'] ) ? $_POST['action'] : '';
		$data   = isset( $_POST['data'] ) ? $_POST['data'] : '';

		if ( ! $action || ! $data )
			$this->_send_response( 'error', 'Not enough information was sent with the request. Sorry' );

		if ( 'gt_vote_up' === $action ) {
			$result = $this->add_yay_vote( $data['postID'] );
		} elseif ( 'gt_vote_down' === $action ) {
			$result = $this->add_nay_vote( $data['postID'] );
		} elseif ( 'gt_vote_meh' === $action ) {
			$result = $this->add_meh_vote( $data['postID'] );
		} elseif ( 'gt_next_page' === $action ) {
			// It's expected that the Ids come in as a comma separated string.
			$ids    = explode( ',', $data['existingIds'] );
			$result = $this->next_page( $ids );
		} elseif ( 'gt_results' === $action ) {
			$result = $this->get_results_div( $data['postID'] );
		}

		if ( ! isset( $result ) || empty( $result ) ) {
			$this->_send_response( 'error', 'There was a problem counting your vote. Try again later' );
		} else {
			$this->_send_response( 'success', $result );
		}

	}

	/**
	 * Sends a JSON response to the screen. Should be used in conjunction with ajax handlers
	 *
	 * @param $status string a string representing the overall status
	 * @param $msg    string explains the status message.
	 */
	private function _send_response( $status, $msg ) {
		echo json_encode( array(
			'status'     => $status,
			'status_msg' => $msg
		) );
		die();
	}

	/**
	 * Hooked into 'template_redirect' this function checks to see if the current theme has an archive view template.
	 * If not, it'll use ours. Allows themes to overload without editing plugin files. Only applies to our post type, if
	 * it's been filtered, we'll default to the theme's template hierarchy.
	 */
	public function maybe_use_our_template( $template ) {

		if ( did_action( 'tdd_groupthink_post_type' ) )
			return $template;

		// only need to worry about the single version of our post type
		if ( ! is_singular( self::$post_type ) )
			return $template;

		// use locate_template to search stylesheetpath and templatepath for only the post type specific version
		if ( $theme_template = locate_template( array( 'single-' . self::$post_type . '.php' ), false ) )
			return $theme_template;

		return plugin_dir_path( __FILE__ ) . 'single-' . self::$post_type . '.php';

	}

	/**
	 * Checks the cookies to see if the current user has voted on a given post ID.
	 * @param $post_id
	 * @return bool;
	 */
	public function has_current_user_voted_on_post( $post_id ){
		if ( ! isset( $_COOKIE['groupthink'] ) )
			return false;

		$ids = $_COOKIE['groupthink'];
		$ids = explode( ',', $ids );

		if ( empty( $ids ) )
			return false;

		if ( in_array( $post_id, $ids ) ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Gets the main groupthink Instance
 *
 * Calling this function places into motion the main functions of the class, but can also be utilized to get properties
 * and run methods of the class.
 *
 * @return groupthink
 */
function get_groupthink() {
	return groupthink::get_instance();
}

add_action( 'init', 'get_groupthink' );