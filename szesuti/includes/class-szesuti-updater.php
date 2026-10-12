<?php
/**
 * Frissítéskezelő – Plugin Update Checker (PUC v5) alapon.
 *
 * Alapból a GitHub-repó Release-eiből frissít, ugyanúgy, mint az SZEducate: ha a
 * repóban új Release jelenik meg, a WordPress a megszokott „Frissítés elérhető”
 * jelzést adja, és egy kattintással (vagy `wp plugin update szesuti`-val, vagy
 * automatikus frissítéssel) települ. Így nem kell 100 oldalon ZIP-et feltölteni.
 *
 * A repó gyökere NEM maga a plugin (az a szesuti/ almappában van), ezért a GitHub
 * automatikus forrás-ZIP-je nem telepíthető: a plugin egy szinttel lejjebb kerülne,
 * és inaktív lenne. Emiatt csak a Release-hez csatolt szetemeny-x.y.z.zip-et
 * fogadjuk el (a build.ps1 kimenete), a tag- és ágalapú visszalépést kikapcsoljuk.
 *
 * Ha az Eszközök fülön (advanced.update_url) vagy a szesuti_update_url szűrővel
 * JSON manifest URL van megadva, a GitHub helyett azt használja (pl. belső tükör).
 * A manifest formátuma a PUC JSON formátuma, ami megegyezik a korábbi update.json-nal.
 *
 * Opcionális konstans a wp-config.php-ben: SZESUTI_GITHUB_TOKEN (csak olvasási jogú
 * token). Akkor kell, ha a repó privát lesz, vagy egy szerverről annyi oldal
 * kérdez, hogy elfogy a GitHub hitelesítés nélküli keretének (60 kérés/óra/IP).
 *
 * @package SZESUTI
 */

defined( 'ABSPATH' ) || exit;

class SZESUTI_Updater {

	/** A hivatalos repó. */
	const REPOSITORY = 'https://github.com/SZKK-SZUCS/SZetem-ny-/';

	/** Csak ilyen nevű Release-mellékletből frissít (a build.ps1 kimenete). */
	const ASSET_REGEX = '/^szetemeny-\d+\.\d+\.\d+\.zip$/';

	/** @var SZESUTI_Settings */
	private $settings;

	/** @var object|null A PUC példány (Vcs\PluginUpdateChecker vagy Plugin\UpdateChecker). */
	private $checker = null;

	public function __construct( SZESUTI_Settings $settings ) {
		$this->settings = $settings;

		// Frissíteni csak adminban, cronban és WP-CLI-ből lehet; a látogatói oldalt nem terheljük.
		if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			$this->boot();
		}
	}

	/** A PUC betöltése és beállítása. */
	private function boot() {
		require_once SZESUTI_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

		$manifest = $this->manifest_url();

		try {
			$this->checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
				'' !== $manifest ? $manifest : $this->repository(),
				SZESUTI_FILE,
				SZESUTI_SLUG
			);
		} catch ( Exception $e ) {
			// Hibás repó URL a szűrőből: inkább ne legyen frissítés, mint hogy az admin elhasaljon.
			$this->checker = null;
			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI', false ) ) {
			// A PUC WP-CLI alatt csak 12 óránként néz rá a forrásra. Tömeges frissítésnél
			// (`wp plugin update szesuti` 100 oldalon) ne kelljen kivárni.
			WP_CLI::add_hook( 'before_invoke:plugin update', array( $this, 'cli_check' ) );
		}

		if ( '' !== $manifest ) {
			return;
		}

		$this->checker->setBranch( 'main' );

		$api = $this->checker->getVcsApi();
		// A konstans a PUC verziózott névterében van; a get_class() azt a verziót adja,
		// amelyiket a gyár ténylegesen választott (ha egy másik plugin újabb PUC-ot hoz).
		$api->enableReleaseAssets( self::ASSET_REGEX, constant( get_class( $api ) . '::REQUIRE_RELEASE_ASSETS' ) );

		add_filter( $this->checker->getUniqueName( 'vcs_update_detection_strategies' ), array( $this, 'only_releases' ) );
		add_filter( 'pre_http_request', array( $this, 'skip_repo_file_requests' ), 10, 3 );

		if ( defined( 'SZESUTI_GITHUB_TOKEN' ) && SZESUTI_GITHUB_TOKEN ) {
			$this->checker->setAuthentication( (string) SZESUTI_GITHUB_TOKEN );
		}
	}

	/** Azonnali ellenőrzés a `wp plugin update` előtt. */
	public function cli_check() {
		if ( null !== $this->checker ) {
			$this->checker->checkForUpdates();
		}
	}

	/**
	 * A beállított JSON manifest URL (üres = GitHub Release-ek).
	 *
	 * @return string
	 */
	private function manifest_url() {
		/**
		 * A frissítési manifest URL-je kóddal is megadható (pl. mu-pluginból).
		 * Üres érték esetén a plugin a GitHub Release-ekből frissít.
		 *
		 * @param string $url Manifest URL.
		 */
		return trim( (string) apply_filters( 'szesuti_update_url', (string) $this->settings->get( 'advanced.update_url', '' ) ) );
	}

	/**
	 * A GitHub-repó URL-je.
	 *
	 * @return string
	 */
	private function repository() {
		/**
		 * A frissítés forrásául szolgáló GitHub-repó (pl. fork esetén).
		 *
		 * @param string $url Repó URL.
		 */
		return (string) apply_filters( 'szesuti_update_repository', self::REPOSITORY );
	}

	/**
	 * Csak a legutóbbi Release jöhet szóba, a tag és az ág nem.
	 *
	 * A tag/ág forrás-ZIP-je a teljes repó, amiben a plugin almappában van – ha a
	 * Release-ből hiányzik a ZIP, inkább ne legyen frissítés, mint rossz telepítés.
	 *
	 * @param array $strategies PUC stratégiák.
	 * @return array
	 */
	public function only_releases( $strategies ) {
		return array_intersect_key( (array) $strategies, array( 'latest_release' => true ) );
	}

	/**
	 * A repó gyökeréből kért fájlok (szesuti.php, readme.txt) lekérésének kihagyása.
	 *
	 * A PUC minden ellenőrzésnél a repó gyökerében keresi a fő plugin fájlt és a
	 * readme.txt-t. Nálunk ezek a szesuti/ almappában vannak, a válasz mindig 404,
	 * vagyis csak a GitHub API óránkénti, IP-nkénti keretét fogyasztanák. Ha egy
	 * szerveren sok oldal fut, ez számít. A verziót a Release tagje, a változásnaplót
	 * a Release leírása adja.
	 *
	 * @param false|array|WP_Error $pre  Korábbi rövidzár.
	 * @param array                $args Kérés argumentumai.
	 * @param string               $url  Kért URL.
	 * @return false|array|WP_Error
	 */
	public function skip_repo_file_requests( $pre, $args, $url ) {
		if ( false !== $pre || ! $this->is_skipped_url( $url ) ) {
			return $pre;
		}

		return array(
			'headers'  => array(),
			'body'     => '',
			'response' => array(
				'code'    => 404,
				'message' => 'Not Found',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * A kihagyott (repógyökérből fájlt kérő) API-hívás-e?
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	private function is_skipped_url( $url ) {
		$path = trim( (string) wp_parse_url( $this->repository(), PHP_URL_PATH ), '/' );
		if ( '' === $path ) {
			return false;
		}

		return 0 === strpos( (string) $url, 'https://api.github.com/repos/' . $path . '/contents/' );
	}

	/**
	 * A frissítés forrása, az admin felületen való kiíráshoz.
	 *
	 * @return string
	 */
	public function source_label() {
		$manifest = $this->manifest_url();
		if ( '' !== $manifest ) {
			/* translators: %s: manifest URL */
			return sprintf( __( 'JSON manifest: %s', 'szesuti' ), $manifest );
		}

		$path = trim( (string) wp_parse_url( $this->repository(), PHP_URL_PATH ), '/' );
		/* translators: %s: GitHub repó (felhasználó/név) */
		return sprintf( __( 'GitHub Release-ek: %s', 'szesuti' ), $path );
	}

	/**
	 * Azonnali ellenőrzés (az Eszközök fül gombja).
	 *
	 * @return string|WP_Error A legfrissebb elérhető verzió, vagy hiba.
	 */
	public function check_now() {
		if ( null === $this->checker ) {
			return new WP_Error( 'szesuti_update_off', __( 'A frissítéskezelő nem töltődött be (hibás repó URL?).', 'szesuti' ) );
		}

		$this->checker->checkForUpdates();

		// A getUpdate() csak az újabb verziót adja vissza; a tárolt állapotban az is
		// benne van, ha a legfrissebb kiadás megegyezik a telepítettel.
		$remote = $this->checker->getUpdateState()->getUpdate();
		if ( is_object( $remote ) && ! empty( $remote->version ) ) {
			return (string) $remote->version;
		}

		return new WP_Error( 'szesuti_update_failed', $this->describe_errors() );
	}

	/**
	 * A sikertelen ellenőrzés oka, emberi nyelven.
	 *
	 * @return string
	 */
	private function describe_errors() {
		$details    = array();
		$rate_limit = false;

		foreach ( $this->checker->getLastRequestApiErrors() as $item ) {
			if ( isset( $item['url'] ) && $this->is_skipped_url( $item['url'] ) ) {
				continue;
			}
			if ( isset( $item['httpResponse'] ) && is_array( $item['httpResponse'] ) ) {
				$code = (int) wp_remote_retrieve_response_code( $item['httpResponse'] );
				if ( 403 === $code || 429 === $code ) {
					$rate_limit = true;
				}
			}
			if ( isset( $item['error'] ) && is_wp_error( $item['error'] ) ) {
				$details[] = $item['error']->get_error_message();
			}
		}

		if ( $rate_limit ) {
			$message = __( 'A GitHub elutasította a kérést, valószínűleg elfogyott az óránkénti keret (60 kérés/IP). Próbáld később, vagy adj meg tokent a SZESUTI_GITHUB_TOKEN konstanssal.', 'szesuti' );
		} elseif ( '' !== $this->manifest_url() ) {
			$message = __( 'A manifest nem érhető el, vagy hiányzik belőle a „name”, a „version” vagy a „download_url” mező.', 'szesuti' );
		} else {
			$message = __( 'Nem található olyan kiadás (Release), amelyhez szetemeny-x.y.z.zip melléklet tartozik, vagy a GitHub nem érhető el.', 'szesuti' );
		}

		if ( $details ) {
			$message .= ' (' . implode( ' · ', array_unique( $details ) ) . ')';
		}

		return $message;
	}
}
