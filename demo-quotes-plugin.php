<?php
/*
Plugin Name: Demo Quotes Plugin
Plugin URI: https://github.com/jrfnl/wp-plugin-best-practices-demo
Description: Demo plugin for WordPress Plugins Best Practices Tutorial
Version: 1.0
Author: Juliette Reinders Folmer
Author URI: http://adviesenzo.nl/
Text Domain: demo-quotes-plugin
Domain Path: /languages/
License: GPL v3

Copyright (C) 2013, Juliette Reinders Folmer - wp-best-practices@adviesenzo.nl

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/3.0/>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


/**
 * POTENTIAL ROAD MAP:
 *
 *
 */


if ( ! class_exists( 'Demo_Quotes_Plugin' ) ) {
	/**
	 * @package WordPress\Plugins\Demo_Quotes_Plugin
	 * @version 1.0
	 * @link https://github.com/jrfnl/wp-plugin-best-practices-demo WP Plugin Best Practices Demo
	 *
	 * @copyright 2013 Juliette Reinders Folmer
	 * @license http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Demo_Quotes_Plugin {


		/* *** DEFINE CLASS CONSTANTS *** */

		/**
		 * @const string	Plugin version number
		 * @usedby upgrade_options(), __construct()
		 */
		const VERSION = '0.9';

		/**
		 * @const string	Version in which the front-end styles where last changed
		 * @usedby	wp_enqueue_scripts()
		 */
		const STYLES_VERSION = '1.0';

		/**
		 * @const string	Version in which the front-end scripts where last changed
		 * @usedby	wp_enqueue_scripts()
		 */
		const SCRIPTS_VERSION = '1.0';

		/**
		 * @const string	Version in which the admin styles where last changed
		 * @usedby	admin_enqueue_scripts()
		 */
		const ADMIN_STYLES_VERSION = '1.0';

		/**
		 * @const string	Version in which the admin scripts where last changed
		 * @usedby	admin_enqueue_scripts()
		 */
		const ADMIN_SCRIPTS_VERSION = '1.0';


		/**
         * @const   string  Name of our shortcode
         */
		const SHORTCODE = 'demo_quote';





		/* *** DEFINE STATIC CLASS PROPERTIES *** */

		/**
		 * These static properties will be initialized - *before* class instantiation -
		 * by the static init() function
		 */

		/**
		 * @staticvar	string	$basename	Plugin Basename = 'dir/file.php'
		 */
		public static $basename;

		/**
		 * @staticvar	string	$name		Plugin name	  = dirname of the plugin
		 *									Also used as text domain for translation
		 */
		public static $name;

		/**
		 * @staticvar	string	$path		Full server path to the plugin directory, has trailing slash
		 */
		public static $path;

		/**
		 * @staticvar	string	$suffix		Suffix to use if scripts/styles are in debug mode
		 */
		public static $suffix;



		/* *** DEFINE CLASS PROPERTIES *** */


		/* *** Properties Holding Various Parts of the Class' State *** */

		/**
		 * @var object settings page class
		 */
		public $settings_page;




		/* *** PLUGIN INITIALIZATION METHODS *** */

		/**
		 * Object constructor for plugin
		 *
		 * @return Demo_Quotes_Plugin
		 */
		public function __construct() {

			spl_autoload_register( array( $this, 'auto_load' ) );

			/* Check if we have any upgrade actions to do */
			if ( ! isset( Demo_Quotes_Plugin_Option::$current['version'] ) || version_compare( self::VERSION, Demo_Quotes_Plugin_Option::$current['version'], '>' ) ) {
				add_action( 'init', array( $this, 'upgrade' ), 1 );
			}
			// Make sure that the upgrade actions are run on (re-)activation as well.
			// @todo check if this will really work
			add_action( 'demo_quotes_plugin_activate', array( $this, 'upgrade' ) );

			/* Register the plugin initialization actions */
			add_action( 'init', array( $this, 'init' ), 8 );
			add_action( 'admin_menu', array( $this, 'setup_options_page' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );

			/* Register the widget */
			add_action( 'widgets_init', array( $this, 'widgets_init' ) );

			/* Register the shortcode */
			add_shortcode( self::SHORTCODE, array( $this, 'do_shortcode' ) );
		}


		/**
		 * Set the static path and directory variables for this class
		 * Is called from the global space *before* instantiating the class to make
		 * sure the correct values are available to the object
		 *
		 * @return void
		 */
		public static function init_statics() {

			self::$basename = plugin_basename( __FILE__ );
			self::$name     = trim( dirname( self::$basename ) );
			self::$path     = plugin_dir_path( __FILE__ );
			self::$suffix   = ( ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min' );
		}


		/**
		 * Auto load our class files
		 *
		 * @param   string  $class  Class name
		 * @return    void
		 */
		public function auto_load( $class ) {
			static $classes = null;

			if ( $classes === null ) {
				$classes = array(
					'demo_quotes_plugin_cpt'			=> 'class-demo-quotes-plugin-cpt.php',
					'demo_quotes_plugin_option'			=> 'class-demo-quotes-manage-options.php',
					'demo_quotes_plugin_settings_page'	=> 'class-demo-quotes-plugin-settings-page.php',
					'demo_quotes_plugin_widget'			=> 'class-demo-quotes-plugin-widget.php',
					'demo_quotes_plugin_people_widget'	=> 'class-demo-quotes-plugin-people-widget.php',
				);
			}

			$cn = strtolower( $class );

			if ( isset( $classes[ $cn ] ) ) {
				include_once( self::$path . $classes[ $cn ] );
			}
		}



		/** ******************* ADMINISTRATIVE METHODS ******************* **/


		/**
		 * Add the actions for the front-end functionality
		 * Add actions which are needed for both front-end and back-end functionality
		 *
		 * @return void
		 */
		public function init() {

			/* Allow filtering of our plugin name */
			self::filter_statics();

			/* Load plugin text strings
			   @see http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way */
			load_plugin_textdomain( self::$name, false, self::$name . '/languages/' );

			/* Register the Quotes Custom Post Type and add any related action and filters */
			Demo_Quotes_Plugin_Cpt::init();

			/* Register our ajax actions for the widget */
			add_action( 'wp_ajax_demo_quotes_widget_next', array( $this, 'demo_quotes_widget_next' ) );
			add_action( 'wp_ajax_nopriv_demo_quotes_widget_next', array( $this, 'demo_quotes_widget_next' ) );

			/* Add js and css files */
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		}


		/**
		 * Allow filtering of the plugin name
		 * Mainly useful for non-standard directory setups
		 *
		 * @return void
		 */
		public static function filter_statics() {
			self::$name = apply_filters( 'demo_quotes_plugin_name', self::$name );
		}


		/**
		 * Add back-end functionality
		 *
		 * @return void
		 */
		public function admin_init() {
			/* Don't do anything if user does not have the required capability */
			if ( false === is_admin() /*|| false === current_user_can( Demo_Quotes_Plugin_Option::REQUIRED_CAP )*/ ) {
				return;
			}

			/* Add actions and filters for our custom post type */
			Demo_Quotes_Plugin_Cpt::admin_init();

			/* Add js and css files */
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}


		/**
		 * Register the options page for all users that have the required capability
		 *
		 * @return void
		 */
		public function setup_options_page() {

			/* Don't do anything if user does not have the required capability */
			if ( false === is_admin() || false === current_user_can( Demo_Quotes_Plugin_Option::REQUIRED_CAP ) ) {
				return;
			}
			$this->settings_page = new Demo_Quotes_Plugin_Settings_Page();
		}




		/**
		 * Register the Widget
		 *
		 * @see register_widget()
		 * @return void
		 */
		public function widgets_init() {
			register_widget( 'Demo_Quotes_Plugin_Widget' );
			register_widget( 'Demo_Quotes_Plugin_People_Widget' );
		}


		/**
		 * Register, but don't yet enqueue necessary javascript and css files for the front-end
		 *
		 * @return void
		 */
		public function wp_enqueue_scripts() {
			wp_register_style(
				self::$name . '-css', // id
				plugins_url( 'css/style' . self::$suffix . '.css', __FILE__ ), // url
				array(), // not used
				self::STYLES_VERSION, // version
				'all' // media
			);

			wp_register_script(
				self::$name . '-js', // id
				plugins_url( 'js/interaction' . self::$suffix . '.js', __FILE__ ), // url
				array( 'jquery', 'wp-ajax-response' ), // dependants
				self::SCRIPTS_VERSION, // version
				true // load in footer
			);
		}


		/**
		 * Conditionally add necessary javascript and css files for the back-end on the appropriate screens
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {

			$screen = get_current_screen();

			if ( property_exists( $screen, 'post_type' ) && $screen->post_type === Demo_Quotes_Plugin_Cpt::$post_type_name ) {
				wp_enqueue_style(
					self::$name . '-admin-css', // id
					plugins_url( 'css/admin-style' . self::$suffix . '.css', __FILE__ ), // url
					array(), // not used
					self::ADMIN_STYLES_VERSION, // version
					'all' // media
				);
			}

			/* Admin js for settings page only */
			if ( property_exists( $screen, 'base' ) && ( isset( $this->settings_page ) && $screen->base === $this->settings_page->hook ) ) {
				wp_enqueue_script(
					self::$name . '-admin-js', // id
					plugins_url( 'js/admin-interaction' . self::$suffix . '.js', __FILE__ ), // url
					array( 'jquery', 'jquery-ui-accordion' ), // dependants
					self::ADMIN_SCRIPTS_VERSION, // version
					true // load in footer
				);
			}
		}




		/**
		 * Function containing the helptext strings
		 *
		 * Of course in a real plugin, we'd have proper helpful texts here
		 *
		 * @static
		 * @param 	object	$screen		Screen object for the screen the user is on
		 * @param 	array	$tab		Help tab being requested
		 * @return  string  help text
		 */
		public static function get_helptext( $screen, $tab ) {

			switch ( $tab['id'] ) {
				case self::$name . '-main' :
					echo '
								<p>' . esc_html__( 'Here comes a helpful help text ;-)', self::$name ) . '</p>
								<p>' . esc_html__( 'And some more help.', self::$name ) . '</p>';
					break;

				case self::$name . '-add' :
					echo '
								<p>' . esc_html__( 'Some specific information about editing a quote', self::$name ) . '</p>
								<p>' . esc_html__( 'And some more help.', self::$name ) . '</p>';
					break;

				case self::$name . '-advanced' :
					echo '
								<p>' . esc_html__( 'Some information about advanced features if we create any.', self::$name ) . '</p>';
					break;

				case self::$name . '-extras' :
					echo '
								<p>' . esc_html__( 'And here we may say something on extra\'s we add to the post type', self::$name ) . '</p>';
					break;

				case self::$name . '-settings' :
					echo '
								<p>' . esc_html__( 'Some information on the effect of the settings', self::$name ) . '</p>';
					break;

				/*default:
					return false;*/
			}
		}




		/**
		 * Generate the links for the help sidebar
		 * Of course in a real plugin, we'd have proper links here
		 *
		 * @static
		 * @return	string
		 */
		public static function get_help_sidebar() {
			return '
				   <p><strong>' . /* TRANSLATORS: no need to translate - standard WP core translation will be used */ esc_html__( 'For more information:' ) . '</strong></p>
				   <p>
						<a href="http://wordpress.org/extend/plugins/" target="_blank">' . esc_html__( 'Official plugin page (if there would be one)', self::$name ) . '</a> |
						<a href="#" target="_blank">' . esc_html__( 'FAQ', self::$name ) . '</a> |
						<a href="#" target="_blank">' . esc_html__( 'Changelog', self::$name ) . '</a> |
						<a href="https://github.com/jrfnl/wp-plugin-best-practices-demo/issues" target="_blank">' . esc_html__( 'Report issues', self::$name ) . '</a>
					</p>
				   <p><a href="https://github.com/jrfnl/wp-plugin-best-practices-demo" target="_blank">' . esc_html__( 'Github repository', self::$name ) . '</a></p>
				   <p>' . sprintf( esc_html__( 'Created by %sAdvies en zo', self::$name ), '<a href="http://adviesenzo.nl/" target="_blank">' ) . '</a></p>
			';
		}



		/* *** PLUGIN ACTIVATION, UPGRADING AND DEACTIVATION *** */


		/**
		 * Plugin Activation routine
		 * @return void
		 */
		public static function activate() {
			/* Security check */
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$plugin = ( isset( $_REQUEST['plugin'] ) ? sanitize_text_field( $_REQUEST['plugin'] ) : '' );
			check_admin_referer( 'activate-plugin_' . $plugin );

			/* Register the Quotes Custom Post Type so WP knows how to adjust the rewrite rules */
			Demo_Quotes_Plugin_Cpt::register_post_type();
			Demo_Quotes_Plugin_Cpt::register_taxonomy();

			/* Make sure our post type and taxonomy slugs will be recognized */
			flush_rewrite_rules();

			/* Execute any extra actions registered */
			do_action( 'demo_quotes_plugin_activate' );
		}

		/**
		 * Plugin deactivation routine
		 * @return void
		 */
		public static function deactivate() {
			/* Security check */
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$plugin = ( isset( $_REQUEST['plugin'] ) ? sanitize_text_field( $_REQUEST['plugin'] ) : '' );
			check_admin_referer( 'deactivate-plugin_' . $plugin );

			/* Make sure our post type and taxonomy slugs will be removed */
			flush_rewrite_rules();

			/* Execute any extra actions registered */
			do_action( 'demo_quotes_plugin_deactivate' );
		}


		/**
		 * Function used when activating and/or upgrading the plugin
		 *
		 * Upgrades for any version of this plugin lower than x.x
		 * N.B.: Version nr has to be hard coded to be future-proof, i.e. facilitate
		 * upgrade routines for various versions
		 *
		 * - Initial activate: Save version number to option
		 * - v0.2 ensure post format is always set to 'quote'
		 * - v0.3 auto-set the post title and slug for our post type posts
		 *
		 * @todo - figure out if any special actions need to be run if multisite
		 * Probably not as this is run on init, so as soon as a page of another site in a multisite
		 * install is requested, the upgrade will run.
		 *
		 * @return void
		 */
		public function upgrade() {

			$options = Demo_Quotes_Plugin_Option::$current;

			/**
			 * Cpt post format upgrade for version 0.2
			 *
			 * Ensure all posts of our custom post type have the 'quote' post format
			 */
			if ( ! isset( $options['version'] ) || version_compare( $options['version'], '0.2', '<' ) ) {
				/* Get all posts of our custom post type which currently do not have the 'quote' post format */
				$args = array(
					'post_type'	=> Demo_Quotes_Plugin_Cpt::$post_type_name,
					'tax_query'	=> array(
						array(
							'taxonomy' => 'post_format',
							'field' => 'slug',
							'terms' => array( 'post-format-quote' ),
							'operator' => 'NOT IN',
						),
					),
					'nopaging'	=> true,
				);
				$query = new WP_Query( $args );

				/* Set the post format */
				while ( $query->have_posts() ) {
					$query->next_post();
					set_post_format( $query->post->ID, 'quote' );
				}
				wp_reset_postdata(); // Always restore original Post Data
				unset( $args, $query );
			}

			/**
			 * Cpt slug and title upgrade for version 0.3
			 *
			 * Ensure all posts of our custom post type posts have a title and a textual slug
			 */
			if ( ! isset( $options['version'] ) || version_compare( $options['version'], '0.3', '<' ) ) {
				/* Get all posts of our custom post type except for those with post status auto-draft,
				   inherit (=revision) or trash */
				/* Alternative way of getting the results for demonstration purposes */
				$sql    = $GLOBALS['wpdb']->prepare(
					'SELECT *
					FROM `' . $GLOBALS['wpdb']->posts . '`
					WHERE `post_type` = %s
					AND `post_status` NOT IN ( "auto-draft", "inherit", "trash" )
					',
					Demo_Quotes_Plugin_Cpt::$post_type_name
				);
				$result = $GLOBALS['wpdb']->get_results( $sql );

				/* Update the post title and post slug */
				if ( is_array( $result ) && $result !== array() ) {
					foreach ( $result as $row ) {
						Demo_Quotes_Plugin_Cpt::update_post_title_and_name( $row->ID, $row );
					}
					unset( $row );
				}
				unset( $sql, $result );
			}

			/**
			 * Custom taxonomies upgrade for version 0.5
			 *
			 * Ensure the rewrite rules are refreshed
			 */
			if ( ! isset( $options['version'] ) || version_compare( $options['version'], '0.5', '<' ) ) {
				/* Register the Quotes Custom Post Type so WP knows how to adjust the rewrite rules */
				Demo_Quotes_Plugin_Cpt::register_post_type();
				Demo_Quotes_Plugin_Cpt::register_taxonomy();
				flush_rewrite_rules();
			}

			/* Always update the version number */
			$options['version'] = self::VERSION;

			/* Update the settings and refresh our property */
			/* We'll be saving our options during the upgrade routine *before* the setting
			   is registered (and therefore the validation is registered), so make sure that the
			   options are validated anyway. */
			update_option( Demo_Quotes_Plugin_Option::NAME, $options );
		}





		/* *** HELPER METHODS *** */



		/* *** FRONT-END: DISPLAY METHODS *** */


		public function demo_quotes_widget_next() {
			/* Security check */
			check_ajax_referer(
				'demo-quotes-widget-next-nonce', // name of our nonce
				'dqpwNonce', // $REQUEST variable to look at
				true //die if check fails
			);

			$not = null;
			if ( isset( $_POST['currentQuote'] ) ) {
				$not = intval( $_POST['currentQuote'] );
			}

			$quote = self::get_random_quote( $not, false, 'array' );

			$response = new WP_Ajax_Response();

			$response->add(
				array(
					'what' 			=> 'quote',
					'action'		=> 'next_quote',
					'data'			=> '',
					'supplemental'	=> array(
						'quoteid' 		=> $quote['id'],
						'quote'			=> '<div class="dqpw-quote-wrapper">' . $quote['html'] . '</div>',
					),
				)
			);
			$response->send();

			exit;
		}


		/**
		 * Return random quote via shortcode
		 *
		 * @param	array	$args
		 * @return	mixed
		 */
		public function do_shortcode( $args ) {
			/* Filter received arguments and combine them with our defaults */
			/*$args = shortcode_atts(
				$this->shortcode_defaults, // the defaults
				$args, // the received shortcode arguments
				self::SHORTCODE // Shortcode name to be used by shortcode_args_{$shortcode} filter (WP 3.6+)
			);*/
			return self::get_random_quote();
		}


		/**
		 *
		 *
		 * @param	bool		$echo			(optional) Whether to echo the result, defaults to false
		 * @param	int|null	$not			(optional) Post id to exclude, defaults to none
		 * @param	string		$return_type	(optional) What to return:
		 *											'string' = html string
		 *											'array' = array consisting of:
		 *												'html' => html string,
		 *												 'id' => post id
		 *												 'post'	=> post object
		 *										Defaults to 'string'
		 * @return	mixed		false if no quotes found, null if echo = true, string/array if echo = false
		 */
		public static function get_random_quote( $not = null, $echo = false, $return_type = 'string' ) {

			// WP_Query arguments
			$args = array(
				'post_type'              => Demo_Quotes_Plugin_Cpt::$post_type_name,
				'post_status'            => 'publish',
				'posts_per_page'         => '1',
				'orderby'                => 'rand',
			);
			if ( isset( $not ) && filter_var( $not, FILTER_VALIDATE_INT ) !== false ) {
				$args['post__not_in'] = (array) $not;
			}

			/*if ( ID ) {
				// add id to query
			}
			else if ( person ||tag || most recent) {
				if ( person ) {
					// add to query
				}
				if ( tag ) {
					// add to query
				}
				if ( most recent ) {
					// add most recent to query
				}
			}
			else {
				// add random to query
			}*/

			// The Query
			$query = new WP_Query( $args );

			$html = '';
			if ( $query->post_count === 1 ) {
				$html .= '
			<div class="dqp-quote dqp-quote-' . esc_attr( $query->post->ID ) . '">
				<p>' . wp_kses_post( $query->post->post_content ) . '</p>
			</div>';
				$html .= self::get_quoted_by( $query->post->ID, false );

				if ( $echo === true ) {
					echo $html;
					wp_reset_postdata();
				}
				else {
					$return = null;
					if ( $return_type === 'array' ) {
						$return = array(
							'html'		=> $html,
							'id'		=> $query->post->ID,
							'object'	=> $query->post,
						);
					}
					else {
						$return = $html;
					}
					wp_reset_postdata();
					return $return;
				}
			}
			else {
				return false;
			}
		}


		/**
		 * Generate link to person quoted
		 *
		 * @param   int $post_id
		 * @param   bool $echo
		 * @return string
		 */
		public static function get_quoted_by( $post_id, $echo = false ) {

			$html  = '';
			$terms = wp_get_post_terms( $post_id, Demo_Quotes_Plugin_Cpt::$taxonomy_name );

			if ( is_array( $terms ) && $terms !== array() ) {
				$html .= '
				<div class="dqp-quote-by"><p>';

				foreach ( $terms as $term ) {
					$html .= '
					<a href="' . esc_url( get_term_link( $term ) ) . '" title="' . esc_attr( sprintf( esc_html__( 'View more quotes by %s', Demo_Quotes_Plugin::$name ), $term->name ) ) . '">' . esc_html( $term->name ) . '</a>';
				}
				$html .= '
				</p></div>';
			}

			if ( $echo === true ) {
				echo $html;
				return '';
			}
			else {
				return $html;
			}
		}
	} /* End of class */


	/* Instantiate our class */
	if ( ! defined( 'WP_INSTALLING' ) || WP_INSTALLING === false ) {
		add_action( 'plugins_loaded', 'demo_quotes_plugin_init' );
	}

	if ( ! function_exists( 'demo_quotes_plugin_init' ) ) {
		/**
		 * Initialize the class
		 *
		 * @return void
		 */
		function demo_quotes_plugin_init() {
			/* Initialize the static variables */
			Demo_Quotes_Plugin::init_statics();

			$GLOBALS['demo_quotes_plugin'] = new Demo_Quotes_Plugin();
		}
	}


	if ( ! function_exists( 'dqp_get_demo_quote' ) ) {
		/**
		 * Template tag
		 */
		function dqp_get_demo_quote( $args, $echo = false ) {
			$return = Demo_Quotes_Plugin::get_random_quote( $args );
			if ( $echo === true ) {
				echo $return;
				return '';
			}
			else {
				return $return;
			}
		}
	}

	/* Set up the (de-)activation actions */
	register_activation_hook( __FILE__, array( 'Demo_Quotes_Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Demo_Quotes_Plugin', 'deactivate' ) );

} /* End of class-exists wrapper */