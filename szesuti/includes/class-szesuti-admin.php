<?php
/**
 * Admin felület: menü, mentés, eszközök, fülek.
 *
 * @package SZESUTI
 */

defined( 'ABSPATH' ) || exit;

class SZESUTI_Admin {

	const CAPABILITY = 'manage_options';

	/** @var SZESUTI_Settings */
	private $settings;

	/** @var SZESUTI_I18n */
	private $i18n;

	/** @var SZESUTI_Scanner */
	private $scanner;

	/** @var SZESUTI_Log */
	private $log;

	/** @var SZESUTI_Library|null */
	private $library;

	/** @var SZESUTI_Admin_Library|null A Háttéradatbázis oldal. */
	private $library_page = null;

	/** @var array Fülek. */
	private $tabs = array();

	/** @var array Fül => érintett beállítás-szekciók. */
	private $tab_sections = array(
		'general'    => array( 'general', 'geo' ),
		'appearance' => array( 'appearance' ),
		'texts'      => array( 'texts', 'languages', 'default_language' ),
		'categories' => array( 'categories' ),
		'blocker'    => array( 'blocker' ),
		'gcm'        => array( 'gcm' ),
		'cookies'    => array( 'cookies' ),
		'log'        => array( 'log' ),
		'tools'      => array( 'advanced' ),
	);

	public function __construct( SZESUTI_Settings $settings, SZESUTI_I18n $i18n, SZESUTI_Scanner $scanner, SZESUTI_Log $log, ?SZESUTI_Library $library = null ) {
		$this->settings = $settings;
		$this->i18n     = $i18n;
		$this->scanner  = $scanner;
		$this->log      = $log;
		$this->library  = $library;

		if ( $library && class_exists( 'SZESUTI_Admin_Library' ) ) {
			$this->library_page = new SZESUTI_Admin_Library( $settings, $library, $i18n, $this );
		}

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'wp_ajax_szesuti_preview', array( $this, 'ajax_preview' ) );
		add_action( 'wp_ajax_szesuti_scan_start', array( $this, 'ajax_scan_start' ) );
		add_action( 'wp_ajax_szesuti_scan_http', array( $this, 'ajax_scan_http' ) );
		add_action( 'wp_ajax_szesuti_scan_finish', array( $this, 'ajax_scan_finish' ) );
	}

	/**
	 * A fülek (név => címke). Csak az első használatkor készül el: a WordPress 6.7+
	 * nem engedi a fordítások betöltését az `init` előtt, a konstruktor pedig már a
	 * plugin betöltésekor lefut – ott `__()` hívás hibanapló-bejegyzést okozna.
	 *
	 * @return array
	 */
	private function tabs() {
		if ( empty( $this->tabs ) ) {
			$this->tabs = array(
				'general'    => __( 'Általános', 'szesuti' ),
				'appearance' => __( 'Megjelenés', 'szesuti' ),
				'texts'      => __( 'Szövegek', 'szesuti' ),
				'categories' => __( 'Kategóriák', 'szesuti' ),
				'blocker'    => __( 'Szkriptblokkolás', 'szesuti' ),
				'gcm'        => __( 'Consent Mode', 'szesuti' ),
				'cookies'    => __( 'Sütilista', 'szesuti' ),
				'log'        => __( 'Naplózás', 'szesuti' ),
				'tools'      => __( 'Eszközök', 'szesuti' ),
			);
		}
		return $this->tabs;
	}

	/** Menüpontok. */
	public function menu() {
		add_menu_page(
			__( 'SZetemény – Sütikezelő', 'szesuti' ),
			__( 'SZetemény', 'szesuti' ),
			self::CAPABILITY,
			'szesuti',
			array( $this, 'render_page' ),
			'dashicons-privacy',
			76
		);

		add_submenu_page(
			'szesuti',
			__( 'Beállítások', 'szesuti' ),
			__( 'Beállítások', 'szesuti' ),
			self::CAPABILITY,
			'szesuti',
			array( $this, 'render_page' )
		);

		if ( $this->library_page ) {
			add_submenu_page(
				'szesuti',
				__( 'Háttéradatbázis', 'szesuti' ),
				__( 'Háttéradatbázis', 'szesuti' ),
				self::CAPABILITY,
				SZESUTI_Admin_Library::PAGE,
				array( $this, 'render_library_page' )
			);
		}

		add_submenu_page(
			'szesuti',
			__( 'Hozzájárulás-napló', 'szesuti' ),
			__( 'Hozzájárulás-napló', 'szesuti' ),
			self::CAPABILITY,
			'szesuti-log',
			array( $this, 'render_log_page' )
		);
	}

	/** Admin eszközök betöltése. */
	public function enqueue( $hook ) {
		if ( false === strpos( (string) $hook, 'szesuti' ) ) {
			return;
		}

		wp_enqueue_style( 'szesuti-admin', SZESUTI_URL . 'assets/css/szesuti-admin.css', array(), SZESUTI_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'szesuti-admin', SZESUTI_URL . 'assets/js/szesuti-admin.js', array(), SZESUTI_VERSION, true );

		wp_localize_script(
			'szesuti-admin',
			'SZESUTI_ADMIN',
			array(
				'confirmRemove' => __( 'Biztosan törlöd ezt a sort?', 'szesuti' ),
				'confirmReset'  => __( 'Biztosan visszaállítasz MINDEN beállítást a gyári alapértékre? Ez nem vonható vissza.', 'szesuti' ),
				'confirmPurge'  => __( 'Biztosan törlöd a teljes hozzájárulás-naplót? Ez nem vonható vissza.', 'szesuti' ),
				'confirmLibDelete' => __( 'Biztosan törlöd a kijelölt sütiket a háttéradatbázisból? (A beépítettek nem törlődnek.)', 'szesuti' ),
				'confirmWipe'   => __( 'Utolsó figyelmeztetés: MINDEN törlődik – a hozzájárulás-napló, a háttéradatbázis saját sütijei, a sütilista, a szkennelési eredmények és minden beállítás. Ez nem vonható vissza. Folytatod?', 'szesuti' ),
				'libFilled'     => __( 'Kitöltve a háttéradatbázisból', 'szesuti' ),
				'libCount'      => __( '%1$d / %2$d süti', 'szesuti' ),
				'mediaTitle'    => __( 'Ikon kiválasztása vagy feltöltése', 'szesuti' ),
				'mediaButton'   => __( 'Ezt használom', 'szesuti' ),
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'previewNonce'  => wp_create_nonce( 'szesuti_preview' ),
				'tab'           => $this->current_tab(),
			)
		);

		// A Sütilista fülön a név beírásakor a háttéradatbázisból kitöltjük a többi mezőt.
		if ( 'cookies' === $this->current_tab() && class_exists( 'SZESUTI_Library' ) ) {
			$known = array();
			foreach ( SZESUTI_Library::all() as $entry ) {
				$known[] = array(
					'm' => $entry['match'],
					'p' => $entry['provider'],
					'c' => $entry['category'],
					'd' => $entry['duration'],
					't' => $entry['type'],
					'h' => $entry['desc_hu'],
					'e' => $entry['desc_en'],
				);
			}
			wp_add_inline_script( 'szesuti-admin', 'window.SZESUTI_LIBRARY = ' . wp_json_encode( $known ) . ';', 'before' );
		}

		// A teljes (böngészős) sütiszkenner csak a Sütilista fülön kell.
		if ( 'cookies' === $this->current_tab() ) {
			wp_enqueue_script( 'szesuti-scanner', SZESUTI_URL . 'assets/js/szesuti-scanner.js', array(), SZESUTI_VERSION, true );
			wp_localize_script(
				'szesuti-scanner',
				'SZESUTI_SCANNER',
				array(
					'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'szesuti_scan_ajax' ),
					'resultUrl' => add_query_arg(
						array(
							'page'        => 'szesuti',
							'tab'         => 'cookies',
							'szesuti_msg' => 'scanned',
						),
						admin_url( 'admin.php' )
					) . '#szesuti-scan-result',
					'i18n'      => array(
						'starting'   => __( 'Indítás…', 'szesuti' ),
						'progress'   => __( '%1$d / %2$d oldal', 'szesuti' ),
						'found'      => __( 'Sütik és tárolók: %1$d · Felismert szolgáltatások: %2$d · Egyéb külső kiszolgálók: %3$d', 'szesuti' ),
						'saving'     => __( 'Eredmény mentése…', 'szesuti' ),
						'stopping'   => __( 'Leállítás, a már bejárt oldalak eredménye mentésre kerül…', 'szesuti' ),
						'failed'     => __( 'A szkennelés nem sikerült: %s', 'szesuti' ),
						'frameError' => __( 'nem olvasható keretben (más címre irányít, vagy a tárhely X-Frame-Options / CSP beállítása tiltja)', 'szesuti' ),
						'timeout'    => __( 'nem töltött be időben', 'szesuti' ),
						'noScanMode' => __( 'A szkennelő mód nem kapcsolt be ezen az oldalon (pl. más domainen van a bejelentkezés), ezért a blokkolt kódok itt nem futottak le.', 'szesuti' ),
						'headers'    => __( 'A szerver ellenőrzi a JavaScriptből nem látható (HttpOnly) sütiket…', 'szesuti' ),
						'httpProbe'  => __( 'Szerveroldali fejléc-ellenőrzés kihagyva (a szerver nem érte el önmagát):', 'szesuti' ),
						'leave'      => __( 'A szkennelés még fut. Biztosan elhagyod az oldalt?', 'szesuti' ),
						'noPages'    => __( 'nem található bejárható oldal. A webhely címe (Beállítások → Általános) eltérhet az admin felület címétől – a szkennelést a webhely saját címén megnyitott adminból indítsd.', 'szesuti' ),
					),
				)
			);
		}
	}

	/**
	 * Mentés után az oldal ugyanott folytatódjon, ahol a mentés gombot megnyomtuk.
	 * Az első kirajzolásig elrejtjük a tartalmat, hogy ne látszódjon ugrás.
	 */
	public function admin_head() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, 'szesuti' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification -- csak megjelenítés.
		if ( empty( $_GET['szesuti_msg'] ) || 'saved' !== $_GET['szesuti_msg'] ) {
			return;
		}
		?>
		<style>html.szesuti-restoring #wpbody{visibility:hidden}</style>
		<script>
		try {
			if ( window.sessionStorage && sessionStorage.getItem( 'szesuti_scroll' ) ) {
				document.documentElement.className += ' szesuti-restoring';
				// Biztonsági háló: ha a lábléc szkriptje valamiért nem fut le, ne maradjon üres az oldal.
				setTimeout( function () { document.documentElement.classList.remove( 'szesuti-restoring' ); }, 1500 );
			}
		} catch ( e ) {}
		</script>
		<?php
	}

	/** Aktiválás utáni értesítés. */
	public function notices() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		if ( get_transient( 'szesuti_just_activated' ) ) {
			delete_transient( 'szesuti_just_activated' );
			printf(
				'<div class="notice notice-success is-dismissible"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
				esc_html__( 'A SZetemény aktiválva.', 'szesuti' ),
				esc_html__( 'Add meg az adatkezelési tájékoztató linkjét, és nézd át a blokkolási szabályokat.', 'szesuti' ),
				esc_url( admin_url( 'admin.php?page=szesuti' ) ),
				esc_html__( 'Beállítások megnyitása', 'szesuti' )
			);
		}
	}

	/* ---------------------------------------------------------------------
	 * Műveletek
	 * ------------------------------------------------------------------ */

	/** POST műveletek kezelése. */
	public function handle_actions() {
		if ( empty( $_POST['szesuti_action'] ) && empty( $_GET['szesuti_action'] ) ) {
			return;
		}

		// Az admin_init az admin-ajax.php kéréseknél is lefut. Az élő előnézet a teljes
		// űrlapot küldi (benne a szesuti_action=save mezővel) – ez nem vezethet mentéshez.
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$action = isset( $_POST['szesuti_action'] )
			? sanitize_key( wp_unslash( $_POST['szesuti_action'] ) )
			: sanitize_key( wp_unslash( $_GET['szesuti_action'] ) );

		switch ( $action ) {
			case 'save':
				$this->action_save();
				break;
			case 'export':
				$this->action_export();
				break;
			case 'import':
				$this->action_import();
				break;
			case 'reset':
				$this->action_reset();
				break;
			case 'wipe':
				$this->action_wipe();
				break;
			case 'scan':
				$this->action_scan();
				break;
			case 'add_scanned':
				$this->action_add_scanned();
				break;
			case 'check_update':
				$this->action_check_update();
				break;
			case 'export_log':
				$this->action_export_log();
				break;
			case 'purge_log':
				$this->action_purge_log();
				break;
		}
	}

	/** Beállítások mentése (fülenként). */
	private function action_save() {
		check_admin_referer( 'szesuti_save', 'szesuti_nonce' );

		$tab = isset( $_POST['szesuti_tab'] ) ? sanitize_key( wp_unslash( $_POST['szesuti_tab'] ) ) : 'general';
		if ( ! isset( $this->tab_sections[ $tab ] ) ) {
			$tab = 'general';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- a sanitize() végzi a tisztítást.
		$raw = isset( $_POST['szesuti'] ) ? wp_unslash( $_POST['szesuti'] ) : array();

		$this->settings->save( $this->merge_tab( $tab, (array) $raw ) );

		$this->redirect( array( 'tab' => $tab, 'szesuti_msg' => 'saved' ) );
	}

	/**
	 * Egy fül beküldött értékeinek összefésülése az eltárolt beállításokkal, tisztítva.
	 *
	 * A mentés és az élő előnézet is ezt használja, így az előnézet pontosan azt
	 * mutatja, ami mentés után a látogatói oldalon lesz.
	 *
	 * @param string $tab Fül.
	 * @param array  $raw Beküldött `szesuti[...]` adatok.
	 * @return array Tisztított, teljes beállítástömb.
	 */
	private function merge_tab( $tab, array $raw ) {
		// Szándékosan a szűretlen, eltárolt értékekből indulunk: ha valaki a
		// `szesuti_settings` szűrővel írja felül a beállításokat, azok ne
		// égjenek bele az adatbázisba egy mentéskor.
		$current  = $this->settings->stored();
		$defaults = $this->settings->defaults();

		// Csak az aktuális fülhöz tartozó szekciókat cseréljük ki. Ez fontos:
		// a be nem küldött jelölőnégyzetek így válnak 0 értékűvé, a többi fül
		// beállításai viszont érintetlenek maradnak.
		foreach ( $this->tab_sections[ $tab ] as $section ) {
			if ( isset( $raw[ $section ] ) ) {
				$current[ $section ] = $raw[ $section ];
			} elseif ( in_array( $section, array( 'categories', 'cookies', 'languages' ), true ) ) {
				$current[ $section ] = array();
			} elseif ( isset( $defaults[ $section ] ) ) {
				$current[ $section ] = $defaults[ $section ];
			}
		}

		return $this->settings->sanitize( $current );
	}

	/** Élő előnézet: a még nem mentett űrlapból egy önálló HTML oldalt ad vissza. */
	public function ajax_preview() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( '', 403 );
		}

		check_ajax_referer( 'szesuti_preview', 'nonce' );

		$tab = isset( $_POST['szesuti_tab'] ) ? sanitize_key( wp_unslash( $_POST['szesuti_tab'] ) ) : 'appearance';
		if ( ! isset( $this->tab_sections[ $tab ] ) ) {
			$tab = 'appearance';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- a sanitize() végzi a tisztítást.
		$raw  = isset( $_POST['szesuti'] ) ? (array) wp_unslash( $_POST['szesuti'] ) : array();
		$view = isset( $_POST['view'] ) ? sanitize_key( wp_unslash( $_POST['view'] ) ) : 'banner';
		$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';

		// Ideiglenes beállítás-objektum: semmi nem kerül mentésre.
		$preview = new SZESUTI_Settings();
		$preview->override( $this->merge_tab( $tab, $raw ) );

		$i18n = new SZESUTI_I18n( $preview );
		$i18n->force_language( $lang );

		$frontend = new SZESUTI_Frontend( $preview, $i18n, new SZESUTI_Consent( $preview ), new SZESUTI_Geo( $preview ) );

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		echo $frontend->preview_document( $view ); // phpcs:ignore WordPress.Security.EscapeOutput -- a sablon escapel.
		exit;
	}

	/** Beállítások exportálása JSON fájlba. */
	private function action_export() {
		check_admin_referer( 'szesuti_tools', 'szesuti_nonce' );

		$include_secrets = ! empty( $_POST['include_secrets'] );
		$json            = $this->settings->export_json( $include_secrets );

		$host     = wp_parse_url( home_url(), PHP_URL_HOST );
		$filename = 'szesuti-' . sanitize_file_name( $host ? $host : 'beallitasok' ) . '-' . gmdate( 'Ymd-Hi' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	/** Beállítások importálása. */
	private function action_import() {
		check_admin_referer( 'szesuti_tools', 'szesuti_nonce' );

		$json = '';

		if ( ! empty( $_FILES['szesuti_import_file']['tmp_name'] ) ) {
			$file = $_FILES['szesuti_import_file'];
			$tmp  = $file['tmp_name'];

			// Csak valódi, ezen a kérésen feltöltött fájlt olvasunk be.
			if ( ! empty( $file['error'] ) || ! is_uploaded_file( $tmp ) || (int) $file['size'] > 2 * MB_IN_BYTES ) {
				$this->redirect( array( 'tab' => 'tools', 'szesuti_msg' => 'import_error' ) );
			}

			$json = (string) file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		} elseif ( ! empty( $_POST['szesuti_import_text'] ) ) {
			$json = (string) wp_unslash( $_POST['szesuti_import_text'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		if ( '' === trim( $json ) ) {
			$this->redirect( array( 'tab' => 'tools', 'szesuti_msg' => 'import_empty' ) );
		}

		$result = $this->settings->import_json(
			$json,
			array(
				'keep_urls'  => ! empty( $_POST['keep_urls'] ),
				'keep_salt'  => true,
				'keep_texts' => ! empty( $_POST['keep_texts'] ),
			)
		);

		if ( is_wp_error( $result ) ) {
			set_transient( 'szesuti_import_error', $result->get_error_message(), 60 );
			$this->redirect( array( 'tab' => 'tools', 'szesuti_msg' => 'import_error' ) );
		}

		$this->redirect( array( 'tab' => 'tools', 'szesuti_msg' => 'imported' ) );
	}

	/** Gyári alapértékek visszaállítása. */
	private function action_reset() {
		check_admin_referer( 'szesuti_tools', 'szesuti_nonce' );

		$defaults                = $this->settings->defaults();
		$defaults['log']['salt'] = (string) $this->settings->get( 'log.salt', wp_generate_password( 32, false, false ) );
		$defaults['cookies']     = SZESUTI_Cookie_DB::starter_list();

		$this->settings->save( $defaults );
		$this->redirect( array( 'tab' => 'tools', 'szesuti_msg' => 'reset' ) );
	}

	/** Teljes törlés (wipe): napló, háttéradatbázis, szkennelési adatok, beállítások. */
	private function action_wipe() {
		check_admin_referer( 'szesuti_wipe', 'szesuti_nonce' );

		$typed = isset( $_POST['szesuti_wipe_confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['szesuti_wipe_confirm'] ) ) : '';
		if ( ! SZESUTI_Wipe::confirmed( $typed ) ) {
			$this->redirect( array( 'tab' => 'tools', 'szesuti_msg' => 'wipe_unconfirmed' ) );
		}

		$result = SZESUTI_Wipe::run( $this->settings, $this->log, ! empty( $_POST['szesuti_wipe_reconsent'] ) );

		$this->redirect(
			array(
				'tab'         => 'tools',
				'szesuti_msg' => 'wiped',
				'count'       => $result['log'],
				'lib'         => $result['library'],
				'pv'          => rawurlencode( $result['policy'] ),
			)
		);
	}

	/** Sütiszkennelés indítása. */
	private function action_scan() {
		check_admin_referer( 'szesuti_scan', 'szesuti_nonce' );

		$urls = array();
		if ( ! empty( $_POST['szesuti_scan_urls'] ) ) {
			$raw  = (string) wp_unslash( $_POST['szesuti_scan_urls'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$urls = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) );
		}

		$this->scanner->scan( $urls );
		$this->redirect( array( 'tab' => 'cookies', 'szesuti_msg' => 'scanned' ) );
	}

	/** A szkennelt sütik hozzáadása a listához. */
	private function action_add_scanned() {
		check_admin_referer( 'szesuti_scan', 'szesuti_nonce' );

		$selected = isset( $_POST['szesuti_scanned'] ) ? (array) wp_unslash( $_POST['szesuti_scanned'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$scan     = $this->scanner->last_scan();

		if ( empty( $selected ) || ! $scan ) {
			$this->redirect( array( 'tab' => 'cookies', 'szesuti_msg' => 'nothing_added' ) );
		}

		$cookies  = (array) $this->settings->get( 'cookies', array() );
		$existing = array();
		foreach ( $cookies as $cookie ) {
			$existing[ strtolower( $cookie['name'] ) ] = true;
		}

		$added = 0;
		foreach ( $scan['cookies'] as $cookie ) {
			if ( ! in_array( $cookie['name'], $selected, true ) ) {
				continue;
			}
			if ( isset( $existing[ strtolower( $cookie['name'] ) ] ) ) {
				continue;
			}
			$cookies[] = SZESUTI_Cookie_DB::to_row( $cookie );
			$existing[ strtolower( $cookie['name'] ) ] = true;
			$added++;
		}

		// A szűretlen, eltárolt beállításokból indulunk, hogy egy `szesuti_settings`
		// szűrő felülírásai ne égjenek bele az adatbázisba.
		$all            = $this->settings->stored();
		$all['cookies'] = $cookies;
		$this->settings->save( $this->settings->sanitize( $all ) );

		$this->redirect( array( 'tab' => 'cookies', 'szesuti_msg' => 'added', 'count' => $added ) );
	}

	/* ---------------------------------------------------------------------
	 * Teljes (böngészős) sütiszkenner – AJAX végpontok
	 * ------------------------------------------------------------------ */

	/** Közös jogosultság- és biztonságikód-ellenőrzés. */
	private function scan_ajax_guard() {
		if ( ! current_user_can( self::CAPABILITY ) || ! check_ajax_referer( 'szesuti_scan_ajax', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Nincs jogosultság, vagy lejárt a munkamenet – töltsd újra az oldalt.', 'szesuti' ) ), 403 );
		}
	}

	/** Indulás: a bejárandó oldalak és a szkenner beállításai. */
	public function ajax_scan_start() {
		$this->scan_ajax_guard();
		$limit = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 150; // phpcs:ignore WordPress.Security.NonceVerification
		wp_send_json_success( $this->scanner->crawl_config( $limit ) );
	}

	/** Szerveroldali fejléc-vizsgálat néhány URL-re (HttpOnly sütik). */
	public function ajax_scan_http() {
		$this->scan_ajax_guard();
		$urls = isset( $_POST['urls'] ) ? array_map( 'esc_url_raw', (array) wp_unslash( $_POST['urls'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		wp_send_json_success( $this->scanner->http_probe( $urls ) );
	}

	/** A bejárás eredményének mentése. */
	public function ajax_scan_finish() {
		$this->scan_ajax_guard();
		$raw  = isset( $_POST['result'] ) ? (string) wp_unslash( $_POST['result'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Hibás szkennelési adat.', 'szesuti' ) ), 400 );
		}
		$result = $this->scanner->save_browser_result( $data );
		wp_send_json_success(
			array(
				'pages'   => count( $result['urls'] ),
				'cookies' => count( $result['cookies'] ),
			)
		);
	}

	/** Frissítés kézi ellenőrzése. */
	private function action_check_update() {
		check_admin_referer( 'szesuti_tools', 'szesuti_nonce' );

		$version = szesuti()->updater->check_now();

		if ( is_wp_error( $version ) ) {
			set_transient( 'szesuti_update_error', $version->get_error_message(), 60 );
			$this->redirect( array( 'tab' => 'tools', 'szesuti_msg' => 'update_error' ) );
		}

		set_transient( 'szesuti_update_version', $version, 60 );
		$this->redirect( array( 'tab' => 'tools', 'szesuti_msg' => 'update_checked' ) );
	}

	/** Napló CSV export. */
	private function action_export_log() {
		check_admin_referer( 'szesuti_log', 'szesuti_nonce' );

		$this->log->export_csv(
			array(
				'action' => isset( $_REQUEST['filter_action'] ) ? sanitize_key( wp_unslash( $_REQUEST['filter_action'] ) ) : '',
				'search' => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
				'from'   => isset( $_REQUEST['from'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['from'] ) ) : '',
				'to'     => isset( $_REQUEST['to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['to'] ) ) : '',
			)
		);
		exit;
	}

	/** Teljes napló törlése. */
	private function action_purge_log() {
		check_admin_referer( 'szesuti_log', 'szesuti_nonce' );

		$this->log->truncate();

		wp_safe_redirect( add_query_arg( array( 'page' => 'szesuti-log', 'szesuti_msg' => 'log_purged' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Átirányítás a beállítások oldalra.
	 *
	 * @param array $args Query paraméterek.
	 */
	private function redirect( array $args ) {
		$args['page'] = 'szesuti';
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Megjelenítés
	 * ------------------------------------------------------------------ */

	/** Az aktuális fül. */
	private function current_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
		return isset( $this->tabs()[ $tab ] ) ? $tab : 'general';
	}

	/** A beállítások oldal. */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$tab         = $this->current_tab();
		$s           = $this->settings->all();
		$has_preview = in_array( $tab, array( 'general', 'appearance', 'texts', 'categories' ), true );

		echo '<div class="wrap szesuti-wrap' . ( $has_preview ? ' szesuti-wrap--wide' : '' ) . '">';

		// A WordPress minden értesítést ez alá a jelölő alá mozgat. Ha nincs ilyen,
		// a fejlécünk címe alá tenné őket, ami mentéskor az egész oldalt megrántotta.
		echo '<hr class="wp-header-end">';
		$this->render_messages();

		$this->render_header();
		$this->render_tabs( $tab );

		echo '<div class="szesuti-panel">';

		if ( $has_preview ) {
			$this->render_preview( $s );
		}

		// Az eszközök fül saját űrlapokat használ.
		if ( 'tools' === $tab ) {
			$this->tab_tools( $s );
		} elseif ( 'cookies' === $tab ) {
			$this->tab_cookies( $s );
		} else {
			echo '<form method="post" action="" class="szesuti-settings-form">';
			wp_nonce_field( 'szesuti_save', 'szesuti_nonce' );
			echo '<input type="hidden" name="szesuti_action" value="save" />';
			printf( '<input type="hidden" name="szesuti_tab" value="%s" />', esc_attr( $tab ) );

			$method = 'tab_' . $tab;
			if ( method_exists( $this, $method ) ) {
				$this->$method( $s );
			}

			echo '<p class="szesuti-submit">';
			submit_button( __( 'Beállítások mentése', 'szesuti' ), 'primary large', 'submit', false );
			echo '</p>';
			echo '</form>';
		}

		echo '</div></div>';
	}

	/** Oldalfejléc (a Háttéradatbázis oldal is ezt használja). */
	public function render_header() {
		?>
		<div class="szesuti-header">
			<div class="szesuti-header__brand">
				<a class="szesuti-header__org" href="https://adatvedelemteszt.sze.hu" target="_blank" rel="noopener noreferrer">
					<img src="<?php echo esc_url( SZESUTI_URL . 'assets/img/sze-logo.png' ); ?>" width="181" height="240" alt="<?php esc_attr_e( 'Széchenyi István Egyetem', 'szesuti' ); ?>" />
				</a>
				<span class="szesuti-header__sep" aria-hidden="true"></span>
				<div class="szesuti-header__text">
					<h1>SZetemény</h1>
					<p><?php esc_html_e( 'GDPR-kompatibilis sütikezelő és szkriptblokkoló', 'szesuti' ); ?></p>
					<p class="szesuti-header__author">
						<?php
						printf(
							/* translators: %s: a készítő szervezeti egység neve */
							esc_html__( 'Készítette: %s', 'szesuti' ),
							'<strong>' . esc_html__( 'Műszaki Fejlesztési és Üzemeltetési Igazgatóság', 'szesuti' ) . '</strong>'
						);
						?>
						<span aria-hidden="true">·</span>
						<?php esc_html_e( 'Széchenyi István Egyetem', 'szesuti' ); ?>
					</p>
				</div>
			</div>
			<div class="szesuti-header__meta">
				<span class="szesuti-badge"><?php echo esc_html( 'v' . SZESUTI_VERSION ); ?></span>
				<?php if ( $this->settings->get( 'general.enabled', 1 ) ) : ?>
					<span class="szesuti-badge szesuti-badge--on"><?php esc_html_e( 'Aktív', 'szesuti' ); ?></span>
				<?php else : ?>
					<span class="szesuti-badge szesuti-badge--off"><?php esc_html_e( 'Kikapcsolva', 'szesuti' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * A háttéradatbázis-import eredménye. Ha semmi nem változott, figyelmeztetés –
	 * különben úgy tűnik, mintha az import nem működne.
	 *
	 * @return array { típus, szöveg }
	 */
	private function lib_import_message() {
		// phpcs:disable WordPress.Security.NonceVerification
		$n = array();
		foreach ( array( 'a', 'f', 'k', 'b', 'i' ) as $key ) {
			$n[ $key ] = isset( $_GET[ $key ] ) ? (int) $_GET[ $key ] : 0;
		}
		// phpcs:enable

		$text = sprintf(
			/* translators: 1: új, 2: kiegészített, 3: már meglévő darabszám */
			__( 'Importálva: %1$d új, %2$d kiegészítve, %3$d már megvolt.', 'szesuti' ),
			$n['a'],
			$n['f'],
			$n['k']
		);
		if ( $n['b'] ) {
			/* translators: %d: darabszám */
			$text .= ' ' . sprintf( __( '%d beépített süti kimaradt, mert a SZetemény már ismeri (felülíráshoz jelöld be a „felülírás” lehetőséget).', 'szesuti' ), $n['b'] );
		}
		if ( $n['i'] ) {
			/* translators: %d: darabszám */
			$text .= ' ' . sprintf( __( '%d bejegyzés érvénytelen névvel kimaradt.', 'szesuti' ), $n['i'] );
		}
		if ( 0 === $n['a'] + $n['f'] + $n['k'] + $n['b'] + $n['i'] ) {
			return array( 'warning', __( 'A fájlban nem volt egyetlen süti sem.', 'szesuti' ) );
		}

		return array( $n['a'] + $n['f'] ? 'success' : 'warning', $text );
	}

	/** Visszajelző üzenetek (a Háttéradatbázis oldal is ezt használja). */
	public function render_messages() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( empty( $_GET['szesuti_msg'] ) ) {
			return;
		}
		$msg   = sanitize_key( wp_unslash( $_GET['szesuti_msg'] ) );
		$count = isset( $_GET['count'] ) ? (int) $_GET['count'] : 0;
		// phpcs:enable

		// phpcs:disable WordPress.Security.NonceVerification
		$wiped = sprintf(
			/* translators: 1: törölt naplóbejegyzések, 2: törölt háttéradatbázis-bejegyzések */
			__( 'Minden törölve: %1$d naplóbejegyzés és %2$d saját süti a háttéradatbázisból. A beállítások gyári alapértéken, új saltinggal.', 'szesuti' ),
			$count,
			isset( $_GET['lib'] ) ? (int) $_GET['lib'] : 0
		);
		$wiped_pv = isset( $_GET['pv'] ) ? sanitize_text_field( wp_unslash( $_GET['pv'] ) ) : '';
		// phpcs:enable
		if ( '' !== $wiped_pv ) {
			/* translators: %s: új szabályzatverzió */
			$wiped .= ' ' . sprintf( __( 'A látogatók újra döntenek (szabályzatverzió: %s).', 'szesuti' ), $wiped_pv );
		}

		$map = array(
			'saved'          => array( 'success', __( 'A beállítások elmentve.', 'szesuti' ) ),
			'wiped'          => array( 'success', $wiped ),
			'wipe_unconfirmed' => array( 'warning', __( 'Nem történt törlés: a megerősítéshez a felületen látható szót kell pontosan, kis- és nagybetű szerint beírni.', 'szesuti' ) ),
			'imported'       => array( 'success', __( 'A beállítások importálva.', 'szesuti' ) ),
			'reset'          => array( 'success', __( 'Minden beállítás visszaállt a gyári alapértékre.', 'szesuti' ) ),
			'scanned'        => array( 'success', __( 'A szkennelés lefutott.', 'szesuti' ) ),
			'added'          => array( 'success', sprintf( _n( '%d süti hozzáadva a listához.', '%d süti hozzáadva a listához.', $count, 'szesuti' ), $count ) ),
			'nothing_added'  => array( 'warning', __( 'Nem választottál ki egyetlen sütit sem.', 'szesuti' ) ),
			'import_empty'   => array( 'warning', __( 'Nem adtál meg importálandó tartalmat.', 'szesuti' ) ),
			'update_checked' => array( 'success', __( 'A frissítés ellenőrizve.', 'szesuti' ) ),
			'lib_saved'      => array( 'success', __( 'A süti mentve a háttéradatbázisba.', 'szesuti' ) ),
			'lib_invalid'    => array( 'warning', __( 'Érvénytelen sütinév. Szóköz, pontosvessző, idézőjel és egyenlőségjel nem szerepelhet benne.', 'szesuti' ) ),
			'lib_deleted'    => array( 'success', sprintf( _n( '%d süti törölve a háttéradatbázisból.', '%d süti törölve a háttéradatbázisból.', $count, 'szesuti' ), $count ) ),
			'lib_listed'     => array( 'success', sprintf( _n( '%d süti felvéve az oldal sütilistájába.', '%d süti felvéve az oldal sütilistájába.', $count, 'szesuti' ), $count ) ),
			'lib_learned'    => array( 'success', sprintf( _n( '%d süti átvéve a sütilistából.', '%d süti átvéve a sütilistából.', $count, 'szesuti' ), $count ) ),
			'lib_imported'   => $this->lib_import_message(),
		);

		if ( 'import_error' === $msg ) {
			$detail = get_transient( 'szesuti_import_error' );
			delete_transient( 'szesuti_import_error' );
			printf(
				'<div class="notice notice-error"><p>%s %s</p></div>',
				esc_html__( 'Az importálás nem sikerült.', 'szesuti' ),
				$detail ? esc_html( $detail ) : ''
			);
			return;
		}

		if ( 'update_error' === $msg ) {
			$detail = get_transient( 'szesuti_update_error' );
			delete_transient( 'szesuti_update_error' );
			printf(
				'<div class="notice notice-error"><p>%s %s</p></div>',
				esc_html__( 'A frissítés ellenőrzése nem sikerült.', 'szesuti' ),
				$detail ? esc_html( $detail ) : ''
			);
			return;
		}

		if ( ! isset( $map[ $msg ] ) ) {
			return;
		}

		$purged = SZESUTI_Cache::notice_text();
		if ( '' !== $purged ) {
			$map[ $msg ][1] .= ' ' . $purged;
		}

		// A sikerüzenet lebegő értesítés: nem tolja le a tartalmat, és magától eltűnik.
		// A teljes törlés összegzése kivétel: azt el kell tudni olvasni.
		if ( 'success' === $map[ $msg ][0] && 'wiped' !== $msg ) {
			printf(
				'<div class="szesuti-toast" role="status" aria-live="polite"><span class="szesuti-toast__icon" aria-hidden="true">✓</span>%s</div>',
				esc_html( $map[ $msg ][1] )
			);
			return;
		}

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $map[ $msg ][0] ),
			esc_html( $map[ $msg ][1] )
		);
	}

	/**
	 * Élő előnézet panel a beállítások fölött.
	 *
	 * @param array $s Beállítások.
	 */
	private function render_preview( $s ) {
		$langs   = (array) $s['languages'];
		$default = (string) $s['default_language'];
		?>
		<div class="szesuti-preview" data-szesuti-preview data-lang="<?php echo esc_attr( $default ); ?>">
			<div class="szesuti-preview__bar">
				<strong class="szesuti-preview__title"><?php esc_html_e( 'Élő előnézet', 'szesuti' ); ?></strong>

				<div class="szesuti-seg" role="group" aria-label="<?php esc_attr_e( 'Nézet', 'szesuti' ); ?>">
					<button type="button" class="is-active" data-preview-view="banner"><?php esc_html_e( 'Banner', 'szesuti' ); ?></button>
					<button type="button" data-preview-view="prefs"><?php esc_html_e( 'Beállítások ablak', 'szesuti' ); ?></button>
					<button type="button" data-preview-view="reopen"><?php esc_html_e( 'Lebegő gomb', 'szesuti' ); ?></button>
				</div>

				<div class="szesuti-seg" role="group" aria-label="<?php esc_attr_e( 'Eszköz', 'szesuti' ); ?>">
					<button type="button" class="is-active" data-preview-device="desktop"><?php esc_html_e( 'Asztali', 'szesuti' ); ?></button>
					<button type="button" data-preview-device="mobile"><?php esc_html_e( 'Mobil', 'szesuti' ); ?></button>
				</div>

				<?php if ( count( $langs ) > 1 ) : ?>
					<label class="szesuti-preview__lang">
						<span><?php esc_html_e( 'Nyelv', 'szesuti' ); ?></span>
						<select data-preview-lang>
							<?php foreach ( $langs as $lang ) : ?>
								<option value="<?php echo esc_attr( $lang ); ?>" <?php selected( $lang, $default ); ?>><?php echo esc_html( strtoupper( $lang ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>

				<span class="szesuti-preview__status" data-preview-status aria-live="polite"></span>

				<button type="button" class="button button-small szesuti-preview__toggle" data-preview-toggle aria-expanded="true">
					<span class="dashicons dashicons-hidden" aria-hidden="true"></span><?php esc_html_e( 'Előnézet elrejtése', 'szesuti' ); ?>
				</button>
			</div>

			<div class="szesuti-preview__stage" data-preview-stage>
				<iframe
					class="szesuti-preview__frame"
					title="<?php esc_attr_e( 'A banner előnézete', 'szesuti' ); ?>"
					sandbox="allow-scripts"
					loading="eager"
				></iframe>
			</div>

			<div
				class="szesuti-preview__resize"
				data-preview-resize
				role="separator"
				aria-orientation="horizontal"
				tabindex="0"
				aria-label="<?php esc_attr_e( 'Az előnézet magassága', 'szesuti' ); ?>"
				title="<?php esc_attr_e( 'Húzd a méretezéshez · Dupla kattintás: alapméret', 'szesuti' ); ?>"
			><span aria-hidden="true"></span></div>

			<p class="szesuti-preview__note">
				<?php esc_html_e( 'Az előnézet a még nem mentett változtatásokat is mutatja. A gombok kipróbálhatók – a döntés itt nem kerül mentésre.', 'szesuti' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Fülek kirajzolása.
	 *
	 * @param string $current Aktuális fül.
	 */
	private function render_tabs( $current ) {
		echo '<nav class="szesuti-tabs">';
		foreach ( $this->tabs() as $key => $label ) {
			printf(
				'<a href="%1$s" class="szesuti-tab%2$s">%3$s</a>',
				esc_url( add_query_arg( array( 'page' => 'szesuti', 'tab' => $key ), admin_url( 'admin.php' ) ) ),
				$key === $current ? ' is-active' : '',
				esc_html( $label )
			);
		}
		echo '</nav>';
	}

	/** A Háttéradatbázis oldal. */
	public function render_library_page() {
		if ( ! current_user_can( self::CAPABILITY ) || ! $this->library_page ) {
			return;
		}
		$this->library_page->render();
	}

	/** A napló oldal. */
	public function render_log_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$page = new SZESUTI_Admin_Log( $this->settings, $this->log );
		$page->render();
	}

	/* ---------------------------------------------------------------------
	 * A fülek tartalma külön fájlokban is olvasható méretűek maradnak
	 * ------------------------------------------------------------------ */

	/**
	 * Általános fül.
	 *
	 * @param array $s Beállítások.
	 */
	private function tab_general( $s ) {
		require SZESUTI_DIR . 'includes/admin/tab-general.php';
	}

	/**
	 * Megjelenés fül.
	 *
	 * @param array $s Beállítások.
	 */
	private function tab_appearance( $s ) {
		require SZESUTI_DIR . 'includes/admin/tab-appearance.php';
	}

	/**
	 * Szövegek fül.
	 *
	 * @param array $s Beállítások.
	 */
	private function tab_texts( $s ) {
		$i18n = $this->i18n;
		require SZESUTI_DIR . 'includes/admin/tab-texts.php';
	}

	/**
	 * Kategóriák fül.
	 *
	 * @param array $s Beállítások.
	 */
	private function tab_categories( $s ) {
		$i18n = $this->i18n;
		require SZESUTI_DIR . 'includes/admin/tab-categories.php';
	}

	/**
	 * Blokkolás fül.
	 *
	 * @param array $s Beállítások.
	 */
	private function tab_blocker( $s ) {
		require SZESUTI_DIR . 'includes/admin/tab-blocker.php';
	}

	/**
	 * Consent Mode fül.
	 *
	 * @param array $s Beállítások.
	 */
	private function tab_gcm( $s ) {
		require SZESUTI_DIR . 'includes/admin/tab-gcm.php';
	}

	/**
	 * Sütilista fül.
	 *
	 * @param array $s Beállítások.
	 */
	private function tab_cookies( $s ) {
		$scanner = $this->scanner;
		require SZESUTI_DIR . 'includes/admin/tab-cookies.php';
	}

	/**
	 * Naplózás fül.
	 *
	 * @param array $s Beállítások.
	 */
	private function tab_log( $s ) {
		$log = $this->log;
		require SZESUTI_DIR . 'includes/admin/tab-log.php';
	}

	/**
	 * Eszközök fül.
	 *
	 * @param array $s Beállítások.
	 */
	private function tab_tools( $s ) {
		$log = $this->log;
		require SZESUTI_DIR . 'includes/admin/tab-tools.php';
	}

	/**
	 * Kategóriaválasztó lehetőségek.
	 *
	 * @param bool $with_empty Legyen-e üres elem.
	 * @return array
	 */
	public function category_options( $with_empty = false ) {
		$options = $with_empty ? array( '' => __( '— nincs (mindig engedélyezett) —', 'szesuti' ) ) : array();

		foreach ( $this->settings->active_categories() as $cat ) {
			$meta                      = $this->i18n->category( $cat['slug'], $this->settings->get( 'default_language', 'hu' ) );
			$options[ $cat['slug'] ]   = $meta['name'] . ' (' . $cat['slug'] . ')';
		}

		return $options;
	}
}
