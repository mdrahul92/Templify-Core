<?php


if ( ! defined( 'EDD_SL_PLUGIN_DIR' ) ) {
	define( 'EDD_SL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'EDD_SL_PLUGIN_URL' ) ) {
	define( 'EDD_SL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'EDD_SL_PLUGIN_FILE' ) ) {
	define( 'EDD_SL_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'EDD_SL_VERSION' ) ) {
	define( 'EDD_SL_VERSION', '3.8.11' );
}

require_once EDD_SL_PLUGIN_DIR . 'includes/classes/class-sl-requirements.php';

/**
 * Class EDD_SL_Requirements_Check
 *
 * @since 3.8
 */
final class EDD_SL_Requirements_Check {

	/**
	 * Plugin file
	 *
	 * @var string
	 * @since 3.8
	 */
	private $file;

	/**
	 * Plugin basename
	 *
	 * @var string
	 * @since 3.8
	 */
	private $plugin_base;

	/**
	 * Platform versions required to load Software Licensing.
	 *
	 * @var array[]
	 * @since 3.8
	 */
	private $current_requirements = array(
		'php'                    => array(
			'minimum' => '7.1',
			'name'    => 'PHP',
			'local'   => true,
		),
		'wp'                     => array(
			'minimum' => '5.4',
			'name'    => 'WordPress',
			'local'   => true,
		),
		'easy-digital-downloads' => array(
			'minimum' => '2.11',
			'name'    => 'Easy Digital Downloads',
			'local'   => true,
		),
	);

	/**
	 * @var EDD_SL_Requirements
	 */
	private $requirements;

	/**
	 * EDD_SL_Requirements_Check constructor.
	 *
	 * @param string $plugin_file
	 */
	public function __construct( $plugin_file ) {
		$this->file         = $plugin_file;
		$this->plugin_base  = plugin_basename( $this->file );
		$this->requirements = new EDD_SL_Requirements( $this->current_requirements );
	}

	/**
	 * Loads the plugin if requirements have been met, otherwise
	 * displays "plugin not fully active" UI and exists.
	 *
	 * @since 3.8
	 */
	public function maybe_load() {
		$this->requirements->met() ? $this->load() : "";
	}

	/**
	 * Loads Software Licensing
	 *
	 * @since 3.8
	 */
	private function load() {
		if ( ! class_exists( 'EDD_Software_Licensing' ) ) {
			require_once EDD_SL_PLUGIN_DIR . 'includes/classes/class-edd-software-licensing.php';
		}

		$this->maybe_install();

		// Get Software Licensing running.
		edd_software_licensing();
	}

	/**
	 * Installs Software Licensing if needed.
	 *
	 * @since 3.8
	 */
	private function maybe_install() {
		if ( ! function_exists( 'edd_sl_install' ) ) {
			require_once EDD_SL_PLUGIN_DIR . 'includes/install.php';
		}

		if ( get_option( 'edd_sl_run_install' ) ) {
			// Install Software Licensing.
			edd_sl_install();

			// Delete this option so we don't run the install again.
			delete_option( 'edd_sl_run_install' );
		}
	}

	


}

/**
 * Run the requirements check.
 *
 * This needs to be delayed until `plugins_loaded`, otherwise we won't be able to detect
 * EDD install/version.
 */
add_action( 'init', function() {
	$requirements_checker = new EDD_SL_Requirements_Check( EDD_SL_PLUGIN_FILE );
	$requirements_checker->maybe_load();
} );


