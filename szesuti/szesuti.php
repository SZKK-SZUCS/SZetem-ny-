<?php
/**
 * Plugin Name:       SZetemény – Sütikezelő
 * Plugin URI:        https://adatvedelemteszt.sze.hu
 * Description:       Teljes körű, GDPR/ePrivacy-kompatibilis sütikezelő: automatikus szkriptblokkolás, Google Consent Mode v2, hozzájárulás-napló, többnyelvűség, geo-célzás, sütiszkenner és részletes megjelenési beállítások.
 * Version:           1.2.1
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Kiss Zoltán – SZE Műszaki Fejlesztési és Üzemeltetési Igazgatóság
 * Author URI:        https://munkatars.sze.hu/mfui-uzemeltetesi-referens
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       szesuti
 * Domain Path:       /languages
 *
 * @package SZESUTI
 */

defined( 'ABSPATH' ) || exit;

define( 'SZESUTI_VERSION', '1.2.1' );
define( 'SZESUTI_FILE', __FILE__ );
define( 'SZESUTI_DIR', plugin_dir_path( __FILE__ ) );
define( 'SZESUTI_URL', plugin_dir_url( __FILE__ ) );
define( 'SZESUTI_BASENAME', plugin_basename( __FILE__ ) );
define( 'SZESUTI_OPTION', 'szesuti_settings' );
define( 'SZESUTI_SLUG', 'szesuti' );

require_once SZESUTI_DIR . 'includes/class-szesuti-settings.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-icons.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-colors.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-i18n.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-cookie-db.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-library.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-geo.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-consent.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-log.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-blocker.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-gcm.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-frontend.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-shortcodes.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-rest.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-scanner.php';
require_once SZESUTI_DIR . 'includes/class-szesuti-updater.php';

if ( is_admin() ) {
	require_once SZESUTI_DIR . 'includes/class-szesuti-admin.php';
	require_once SZESUTI_DIR . 'includes/class-szesuti-admin-fields.php';
	require_once SZESUTI_DIR . 'includes/class-szesuti-admin-log.php';
	require_once SZESUTI_DIR . 'includes/class-szesuti-admin-library.php';
	require_once SZESUTI_DIR . 'includes/class-szesuti-wipe.php';
	require_once SZESUTI_DIR . 'includes/class-szesuti-cleanup.php';
}

/**
 * Fő plugin osztály – a komponensek betöltése és összekötése.
 */
final class SZESUTI_Plugin {

	/** @var SZESUTI_Plugin|null */
	private static $instance = null;

	/** @var SZESUTI_Settings */
	public $settings;

	/** @var SZESUTI_I18n */
	public $i18n;

	/** @var SZESUTI_Consent */
	public $consent;

	/** @var SZESUTI_Geo */
	public $geo;

	/** @var SZESUTI_Log */
	public $log;

	/** @var SZESUTI_Blocker */
	public $blocker;

	/** @var SZESUTI_GCM */
	public $gcm;

	/** @var SZESUTI_Frontend */
	public $frontend;

	/** @var SZESUTI_Scanner */
	public $scanner;

	/** @var SZESUTI_Library */
	public $library;

	/** @var SZESUTI_Updater */
	public $updater;

	public static function instance() {
		if ( null === self::$instance ) {
			// Előbb beállítjuk a példányt, csak utána építjük fel a komponenseket.
			// Így ha bármelyik komponens a szesuti() függvényt hívja, nem kerülünk
			// végtelen rekurzióba.
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** A komponensek felépítése és bekötése. */
	private function boot() {
		$this->settings = new SZESUTI_Settings();
		$this->i18n     = new SZESUTI_I18n( $this->settings );
		$this->geo      = new SZESUTI_Geo( $this->settings );
		$this->consent  = new SZESUTI_Consent( $this->settings );
		$this->log      = new SZESUTI_Log( $this->settings );
		$this->blocker  = new SZESUTI_Blocker( $this->settings );
		$this->gcm      = new SZESUTI_GCM( $this->settings, $this->consent );
		$this->frontend = new SZESUTI_Frontend( $this->settings, $this->i18n, $this->consent, $this->geo );
		$this->scanner  = new SZESUTI_Scanner( $this->settings );
		$this->library  = new SZESUTI_Library();

		new SZESUTI_Shortcodes( $this->settings, $this->i18n );
		new SZESUTI_Rest( $this->settings, $this->log, $this->geo );
		$this->updater = new SZESUTI_Updater( $this->settings );

		if ( is_admin() ) {
			new SZESUTI_Admin( $this->settings, $this->i18n, $this->scanner, $this->log, $this->library );
			new SZESUTI_Cleanup();
		}

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );
		add_action( 'szesuti_daily_cleanup', array( $this->log, 'cleanup' ) );
		add_filter( 'plugin_action_links_' . SZESUTI_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Adatbázis és ütemezés ellenőrzése.
	 *
	 * Erre azért van szükség, mert multisite hálózaton az aktiválási hook
	 * csak egyszer fut le, így az új aloldalakon egyébként soha nem jönne
	 * létre a naplótábla.
	 */
	public function maybe_upgrade() {
		$installed = get_option( 'szesuti_db_version', '' );

		if ( SZESUTI_VERSION === $installed ) {
			return;
		}

		$this->settings->install_defaults();
		SZESUTI_Log::create_table();

		if ( ! wp_next_scheduled( 'szesuti_daily_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'szesuti_daily_cleanup' );
		}

		update_option( 'szesuti_db_version', SZESUTI_VERSION, false );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'szesuti', false, dirname( SZESUTI_BASENAME ) . '/languages' );
	}

	public function action_links( $links ) {
		$url = admin_url( 'admin.php?page=szesuti' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Beállítások', 'szesuti' ) . '</a>' );
		return $links;
	}

	/** Aktiváláskor fut. */
	public static function activate() {
		require_once SZESUTI_DIR . 'includes/class-szesuti-settings.php';
		require_once SZESUTI_DIR . 'includes/class-szesuti-log.php';

		$settings = new SZESUTI_Settings();
		$settings->install_defaults();

		SZESUTI_Log::create_table();

		if ( ! wp_next_scheduled( 'szesuti_daily_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'szesuti_daily_cleanup' );
		}

		update_option( 'szesuti_activated_at', time(), false );
		update_option( 'szesuti_db_version', SZESUTI_VERSION, false );
		set_transient( 'szesuti_just_activated', 1, 60 );
	}

	/** Deaktiváláskor fut. */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'szesuti_daily_cleanup' );
	}
}

register_activation_hook( __FILE__, array( 'SZESUTI_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SZESUTI_Plugin', 'deactivate' ) );

/**
 * Globális elérés: szesuti()->settings->get(...)
 *
 * @return SZESUTI_Plugin
 */
function szesuti() {
	return SZESUTI_Plugin::instance();
}

szesuti();

/**
 * Publikus segédfüggvény témákhoz / más pluginekhez.
 *
 * @param string $category Kategória slug (pl. analytics).
 * @return bool
 */
function szesuti_has_consent( $category ) {
	return szesuti()->consent->has_consent( $category );
}

/**
 * Van-e már bármilyen döntés a látogatótól?
 *
 * @return bool
 */
function szesuti_has_decision() {
	return szesuti()->consent->has_decision();
}
