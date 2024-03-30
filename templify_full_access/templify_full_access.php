<?php
/**
 * Plugin Name:     Easy Digital Downloads - All Access
 * Plugin URI:      https://easydigitaldownloads.com/downloads/all-access/
 * Description:     Sell "All Access" memberships to your customers so they can download any product.
 * Version:         1.2.5
 * Requires PHP:    7.1
 * Requires at least: 5.4
 * Author:          Easy Digital Downloads
 * Author URI:      https://easydigitaldownloads.com
 * Text Domain:     edd-all-access
 *
 * @package         EDD\EddAllAccess
 * @author          Easy Digital Downloads
 * @copyright       Copyright (c) Easy Digital Downloads
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
define( 'EDD_ALL_ACCESS_VER', '1.2.5' );

// Plugin path.
define( 'EDD_ALL_ACCESS_DIR', plugin_dir_path( __FILE__ ) );

// Plugin URL.
define( 'EDD_ALL_ACCESS_URL', plugin_dir_url( __FILE__ ) );

// Plugin Root File.
define( 'EDD_ALL_ACCESS_FILE', __FILE__ );

if ( ! class_exists( 'EDD_All_Access' ) ) {

	/**
	 * Main EDD_All_Access class
	 *
	 * @since       1.0.0
	 */
	class EDD_All_Access {

		/**
		 * The one true EDD_All_Access
		 *
		 * @var         EDD_All_Access $instance The one true EDD_All_Access
		 * @since       1.0.0
		 */
		private static $instance;

		/**
		 * Holds the class containing the EDD Recurring Integration.
		 *
		 * @var EDD_All_Access_Recurring
		 */
		public static $edd_recurring;

		/**
		 * Holds the class containing the EDD Software Licensing Integration.
		 *
		 * @var EDD_All_Access_Software_Licensing
		 */
		public static $edd_software_licensing;

	

		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      object self::$instance The one true EDD_All_Access
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new EDD_All_Access();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->hooks();

				// Set up integrated plugins.
				self::$edd_recurring             = new EDD_All_Access_Recurring();
				self::$edd_software_licensing    = new EDD_All_Access_Software_Licensing();
			

				// Setup the Object Cache Helper.
				self::$instance->object_cache = new EDD\AllAccess\Helpers\ObjectCacheHelper();

			}

			return self::$instance;
		}

		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {

			// Include scripts.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/enqueue-scripts.php';

			// Include misc functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/misc-functions.php';

			// Upgrade Functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/upgrades.php';

			// Include receipt functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/receipts.php';

			// Include All Access status functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/all-access-status-functions.php';

			// Include Downloading functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/downloading-functions.php';

			// Include Download Form functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/download-form.php';

			// Include helper functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/helper-functions.php';

			// Include ajax callback functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/ajax-callbacks.php';

			// Include Customer Meta functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/admin/customers/customers.php';

			// Include All Access Single View page.
			require_once EDD_ALL_ACCESS_DIR . 'includes/admin/custom-pages/all-access-passes-page.php';

			// Include site-wide settings under downloads > settings > all-access.
			require_once EDD_ALL_ACCESS_DIR . 'includes/admin/settings/settings.php';

			// Include Post Meta options.
			require_once EDD_ALL_ACCESS_DIR . 'includes/admin/post-meta/all-access-metabox.php';
			require_once EDD_ALL_ACCESS_DIR . 'includes/admin/post-meta/prices-metabox.php';

			// Include Shortcodes.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/shortcodes.php';

			// EDD_All_Access_Pass object.
			require_once EDD_ALL_ACCESS_DIR . 'includes/class-edd-all-access-pass.php';

			// Tools added to EDD > Tools page.
			require_once EDD_ALL_ACCESS_DIR . 'includes/functions/tools.php';

			// Reports.
			require_once EDD_ALL_ACCESS_DIR . 'includes/admin/reports/reports.php';
			require_once EDD_ALL_ACCESS_DIR . 'includes/admin/reports/class-edd-aa-download-popularity-table.php';

			// Discount Code functions.
			require_once EDD_ALL_ACCESS_DIR . 'includes/admin/discount-codes/discount-codes.php';

			// Integration functions to make this work with EDD Recurring.
			require_once EDD_ALL_ACCESS_DIR . 'includes/integrations/plugin-recurring.php';

			// Integration with EDD Software Licensing.
			require_once EDD_ALL_ACCESS_DIR . 'includes/integrations/plugin-software-licenses.php';

	
	
		}

		/**
		 * Run action and filter hooks
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function hooks() {
			// Handle licensing.
			add_action( 'edd_extension_license_init', function( \EDD\Extensions\ExtensionRegistry $registry ) {
				$registry->addExtension( __FILE__, 'All Access', 1005380, EDD_ALL_ACCESS_VER );
			} );

			// Register a dashboard page so we can view single All Access pass data.
			add_action( 'admin_menu', array( $this, 'all_access_passes_view_page' ), 10 );
		}

		/**
		 * Register our All Access single view page
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function all_access_passes_view_page() {
			add_submenu_page(
				null,
				'This page is not shown in any menu',
				'This page is not shown in any menu',
				'view_shop_reports',
				'edd-all-access-pass',
				'edd_all_access_pass_page'
			);
		}

		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function load_textdomain() {
			// Set filter for language directory.
			$lang_dir = EDD_ALL_ACCESS_DIR . '/languages/';
			$lang_dir = apply_filters( 'edd_all_access_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter.
			$locale = apply_filters( 'plugin_locale', get_locale(), 'templify-full-access' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'templify-full-access', $locale );

			// Setup paths to current locale file.
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-all-access/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd_all_access/ folder.
				load_textdomain( 'templify-full-access', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd_all_access/languages/ folder.
				load_textdomain( 'templify-full-access', $mofile_local );
			} else {
				// Load the default language files.
				load_plugin_textdomain( 'templify-full-access', false, $lang_dir );
			}
		}
	}
} // End if class_exists check

/**
 * The main function responsible for returning the one true EDD_All_Access
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_All_Access The one true EDD_All_Access
 */
function edd_all_access() {
	return EDD_All_Access::instance();
}

require_once dirname( __FILE__ ) . '/vendor/autoload.php';
\EDD\ExtensionUtils\v1\ExtensionLoader::loadOrQuit( __FILE__, 'edd_all_access', array(
	'php'                    => '7.1',
	'easy-digital-downloads' => '3.0',
	'wp'                     => '5.4',
) );

/**
 * Admin notice used if EDD is not updated to 2.8 or later.
 *
 * @deprecated 1.2 In favour of ExtensionLoader class.
 *
 * @since       1.0.0
 */
function edd_all_access_edd_too_old_notice() {
	_deprecated_function( __FUNCTION__, '1.2' );
	?>
	<div class="notice notice-error">
	<p><?php echo esc_html( __( 'EDD All Access: Your version of Easy Digital Downloads must be updated to version 2.8 or later to use the All Access extension', 'templify-full-access' ) ); ?></p>
	</div>
	<?php
}

/**
 * Upon fresh activation, this function fires and prevents all previous upgrade routines from running as they are not needed on fresh installs.
 *
 * @since       1.0.0
 */
function edd_all_access_install() {

	$current_version = get_option( 'edd_all_access_version' );

	if ( ! $current_version ) {

		if ( defined( 'EDD_PLUGIN_DIR' ) ) {

			require_once EDD_PLUGIN_DIR . 'includes/admin/upgrades/upgrade-functions.php';

			// When new upgrade routines are added, mark them as complete on fresh install.
			$upgrade_routines = array(
				'aa_v1_reorganize_customer_meta',
				'aa_fix_utc_timezones',
			);

			foreach ( $upgrade_routines as $upgrade ) {
				edd_set_upgrade_complete( $upgrade );
			}
		}
	}

	add_option( 'edd_all_access_version', EDD_ALL_ACCESS_VER, '', false );

}
register_activation_hook( __FILE__, 'edd_all_access_install' );
