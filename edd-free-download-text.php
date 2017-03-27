<?php
/*
Plugin Name: Easy Digital Downloads - Free Download Text
Plugin URI: https://wordpress.org/plugins/edd-free-download-text/
Description: Set the text for free download buttons
Version: 1.0.1
Author: Easy Digital Downloads
Author URI: https://easydigitaldownloads.com/
License: GPL-2.0+
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'EDD_Free_Download_Text' ) ) {

	final class EDD_Free_Download_Text {

		/**
		 * Holds the instance
		 *
		 * Ensures that only one instance of EDD Free Download Text exists in memory at any one
		 * time and it also prevents needing to define globals all over the place.
		 *
		 * TL;DR This is a static property property that holds the singleton instance.
		 *
		 * @var object
		 * @static
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * Plugin Version
		 */
		private $version = '1.0.1';

		/**
		 * Plugin Title
		 */
		public $title = 'EDD Free Download Text';

		/**
		 * Main Instance
		 *
		 * Ensures that only one instance exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0
		 *
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_Free_Download_Text ) ) {
				self::$instance = new EDD_Free_Download_Text;
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Constructor Function
		 *
		 * @since 1.0
		 * @access private
		 */
		private function __construct() {
			self::$instance = $this;
		}

		/**
		 * Reset the instance of the class
		 *
		 * @since 1.0
		 * @access public
		 * @static
		 */
		public static function reset() {
			self::$instance = null;
		}


		/**
		 * Setup the default hooks and actions
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function hooks() {
			// activation
			add_action( 'admin_init', array( $this, 'activation' ) );

			// plugin meta
			add_filter( 'plugin_row_meta', array( $this, 'plugin_meta' ), 10, 2 );
			
			// text domain
			add_action( 'after_setup_theme', array( $this, 'load_textdomain' ) );

			// free download text
			add_filter( 'edd_purchase_link_args', array( $this, 'free_download_text' ) );

			// settings
			add_filter( 'edd_settings_misc', array( $this, 'settings' ) );
		}


		/**
		 * Activation function fires when the plugin is activated.
		 *
		 * This function is fired when the activation hook is called by WordPress,
		 * it flushes the rewrite rules and disables the plugin if EDD isn't active
		 * and throws an error.
		 *
		 * @since 1.0
		 * @access public
		 *
		 * @return void
		 */
		public function activation() {
			if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
				// is this plugin active?
				if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
					// deactivate the plugin
			 		deactivate_plugins( plugin_basename( __FILE__ ) );
			 		// unset activation notice
			 		unset( $_GET[ 'activate' ] );
			 		// display notice
			 		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
				}

			}
			else {
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ), 10, 2 );
			}
		}

		/**
		 * Admin notices
		 *
		 * @since 1.0
		*/
		public function admin_notices() {
			$edd_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/easy-digital-downloads/easy-digital-downloads.php', false, false );

			if ( ! is_plugin_active('easy-digital-downloads/easy-digital-downloads.php') ) {
				echo '<div class="error"><p>' . sprintf( __( 'You must install %sEasy Digital Downloads%s to use %s.', 'edd-free-download-text' ), '<a href="http://easydigitaldownloads.com" title="Easy Digital Downloads" target="_blank">', '</a>', $this->title ) . '</p></div>';
			}

			if ( $edd_plugin_data['Version'] < '1.9' ) {
				echo '<div class="error"><p>' . sprintf( __( '%s requires Easy Digital Downloads Version 1.9 or greater. Please update Easy Digital Downloads.', 'edd-free-download-text' ), $this->title ) . '</p></div>';
			}
		}

		/**
		 * Loads the plugin language files
		 *
		 * @access public
		 * @since 1.0
		 * @return void
		 */
		public function load_textdomain() {
			// Set filter for plugin's languages directory
			$lang_dir = dirname( plugin_basename( plugin_dir_path( __FILE__ ) ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_free_download_text_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale        = apply_filters( 'plugin_locale',  get_locale(), 'edd-free-download-text' );
			$mofile        = sprintf( '%1$s-%2$s.mo', 'edd-free-download-text', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-free-download-text/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-free-download-text folder
				load_textdomain( 'edd-free-download-text', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-free-download-text/languages/ folder
				load_textdomain( 'edd-free-download-text', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'edd-free-download-text', false, $lang_dir );
			}
		}

		/**
		 * Easy Digital Downloads
		 * Change the text of a download button when the download is free
		*/
		public function free_download_text( $args ) {
			// Enter the text that should appear on the button when the download it's free
			$free_download_text = edd_get_option( 'edd_fdt_text', __( 'Free Download', 'edd-free-download-text' ) );

			$variable_pricing = edd_has_variable_prices( $args['download_id'] );

			if ( $args['price'] && $args['price'] !== 'no' && ! $variable_pricing ) {
				$price = edd_get_download_price( $args['download_id'] );

				if ( 0 == $price ) {
					$args['text'] = $free_download_text;
				}
			}

			return $args;
		}
	
		/**
		 * Settings
		*/
		public function settings( $settings ) {
		  $new_settings = array(
				array(
					'id'	=> 'edd_fdt_text',
					'name'	=> sprintf( __( 'Free %s Text', 'edd-free-download-text' ), edd_get_label_singular() ),
					'desc'	=> sprintf( __( 'Text that is shown for free %s', 'edd-free-download-text' ), edd_get_label_plural( true ) ),
					'type'	=> 'text',
					'std'	=> __( 'Free Download', 'edd-free-download-text' )
				),
			);

			return array_merge( $settings, $new_settings );
		}
		
		/**
		 * Plugin settings link
		 *
		 * @since 1.0
		*/
		public function settings_link( $links ) {
			$plugin_links = array(
				'<a href="' . admin_url( 'edit.php?post_type=download&page=edd-settings&tab=misc' ) . '">' . __( 'Settings', 'edd-free-download-text' ) . '</a>',
			);

			return array_merge( $plugin_links, $links );
		}

		/**
		 * Modify plugin metalinks
		 *
		 * @access      public
		 * @since       1.0.0
		 * @param       array $links The current links array
		 * @param       string $file A specific plugin table entry
		 * @return      array $links The modified links array
		 */
		public function plugin_meta( $links, $file ) {
		    if ( $file == plugin_basename( __FILE__ ) ) {
		        $plugins_link = array(
		            '<a title="'. __( 'View more plugins for Easy Digital Downloads by Sumobi', 'edd-free-download-text' ) .'" href="https://easydigitaldownloads.com/blog/author/andrewmunro/?ref=166" target="_blank">' . __( 'Author\'s EDD plugins', 'edd-free-download-text' ) . '</a>'
		        );

		        $links = array_merge( $links, $plugins_link );
		    }

		    return $links;
		}

	}
}

/**
 * Loads a single instance of EDD Free Download Text
 *
 * This follows the PHP singleton design pattern.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @example <?php $edd_free_download_text = edd_free_download_text(); ?>
 *
 * @since 1.0
 *
 * @see EDD_Free_Download_Text::get_instance()
 *
 * @return object Returns an instance of the EDD_Free_Download_Text class
 */
function edd_free_download_text() {
	return EDD_Free_Download_Text::get_instance();
}

/**
 * Loads plugin after all the others have loaded and have registered their hooks and filters
 *
 * @since 1.0
*/
add_action( 'plugins_loaded', 'edd_free_download_text', apply_filters( 'edd_free_download_text_action_priority', 10 ) );