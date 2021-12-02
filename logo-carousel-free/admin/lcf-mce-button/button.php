<?php
/**
 * SP Tiny MCE Shortcode
 *
 * @package logo-carousel-free
 */

// Make sure we don't expose any info if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SPLC_MCE_Shortcode_list' ) ) {
	define( 'SPLC_TMCE_URL', plugin_dir_url( __FILE__ ) );
	/**
	 * Tiny MCE Shortcode list
	 */
	class SPLC_MCE_Shortcode_list {
		/**
		 * The single instance of the class.
		 *
		 * @var self
		 * @since 3.0
		 */
		private static $_instance = null;

		/**
		 * Allows for accessing single instance of class.
		 *
		 * @return SPLC_MCE_Shortcode_list
		 */
		public static function getInstance() {
			if ( ! self::$_instance ) {
				self::$_instance = new SPLC_MCE_Shortcode_list();
			}

			return self::$_instance;
		}
		/**
		 * Constructor
		 *
		 * @since 0.1
		 */
		public function __construct() {
			add_action( 'wp_ajax_cpt_list', array( $this, 'list_ajax' ) );
			add_action( 'admin_footer', array( $this, 'cpt_list' ) );
			add_action( 'admin_head', array( $this, 'mce_button' ) );
		}

		/**
		 *  Hooks your functions into the correct filters.
		 *
		 * @return void
		 */
		public function mce_button() {
			$capability = apply_filters( 'splogocarousel_chosen_ajax_capability', 'manage_options' );
			// check user permissions.
			if ( ! current_user_can( $capability ) ) {
				return;
			}
			// check if WYSIWYG is enabled.
			if ( 'true' == get_user_option( 'rich_editing' ) ) {
				add_filter( 'mce_external_plugins', array( $this, 'add_mce_plugin' ) );
				add_filter( 'mce_buttons', array( $this, 'register_mce_button' ) );
			}
		}

		/**
		 * Script for our mce button.
		 *
		 * @param  array $plugin_array plugin array .
		 * @return array
		 */
		public function add_mce_plugin( $plugin_array ) {
			$plugin_array['sp_mce_button'] = SPLC_TMCE_URL . 'sp-mce.js';
			return $plugin_array;
		}

		/**
		 * Register our button in the editor.
		 *
		 * @param  mixed $buttons buttons.
		 * @return statement
		 */
		public function register_mce_button( $buttons ) {
			array_push( $buttons, 'sp_mce_button' );
			return $buttons;
		}

		/**
		 * Function to fetch cpt posts list
		 *
		 * @param  mixed $post_type post type.
		 * @since  1.7
		 * @return void
		 */
		public function posts( $post_type ) {
			global $wpdb;
			$cpt_type        = $post_type;
			$cpt_post_status = 'publish';
			$cpt             = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title
					FROM $wpdb->posts
					WHERE $wpdb->posts.post_type = %s
					AND $wpdb->posts.post_status = %s
					ORDER BY ID DESC",
					$cpt_type,
					$cpt_post_status
				)
			);

			$list = array();

			foreach ( $cpt as $post ) {
				$selected  = '';
				$post_id   = $post->ID;
				$post_name = $post->post_title;
				$list[]    = array(
					'text'  => $post_name,
					'value' => $post_id,
				);
			}

			wp_send_json( $list );
		}

		/**
		 * Function to fetch buttons
		 *
		 * @since  1.6
		 * @return string
		 */
		public function list_ajax() {
			// check for nonce.
			check_ajax_referer( 'sp-mce-nonce', 'security' );
			$posts = $this->posts( 'sp_lc_shortcodes' ); // change 'sp_lc_shortcodes' to 'post' if you need posts list
			return $posts;
		}

		/**
		 * Function to output button list ajax script
		 *
		 * @since  1.6
		 * @return void
		 */
		public function cpt_list() {
			// create nonce.
			global $current_screen;
			$current_screen->post_type;
			if ( 'post' === $current_screen || 'page' === $current_screen ) {
				$nonce = wp_create_nonce( 'sp-mce-nonce' );
				?>
				<script type="text/javascript">
					jQuery( document ).ready( function( $ ) {
						var data = {
							'action'	: 'cpt_list',	// wp ajax action.
							'security'	: '<?php echo esc_attr( $nonce ); ?>' // nonce value created earlier.
						};
						// Fire ajax
						jQuery.post( ajaxurl, data, function( response ) {
							// if nonce fails then not authorized else settings saved
							if( response === '-1' ){
								// do nothing
								console.log('error');
							} else {
								if (typeof(tinyMCE) != 'undefined') {
									if (tinyMCE.activeEditor != null) {
										tinyMCE.activeEditor.settings.spShortcodeList = response;
									}
								}
							}
						});
					});
				</script>
				<?php
			}
		}
	} // Mce Class
	SPLC_MCE_Shortcode_list::getInstance();
}
