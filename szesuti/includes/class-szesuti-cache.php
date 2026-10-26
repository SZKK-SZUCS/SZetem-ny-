<?php
/**
 * Oldal-gyorsítótár ürítése.
 *
 * A blokkolás a HTML-be íródik, a guard beállításai (kivételek, biztonságos hosztok)
 * is. Ha egy teljes oldalas gyorsítótár (nginx FastCGI, WP Rocket, LiteSpeed…) a régi
 * HTML-t adja ki, egy beállítás-módosítás csak a gyorsítótár lejárta után érvényesül.
 * Ezért mentéskor, frissítéskor, be- és kikapcsoláskor a felismert gyorsítótárakat
 * ürítjük. Az objektum-gyorsítótárat (Redis, Memcached) nem, abban nincs HTML.
 *
 * @package SZESUTI
 */

defined( 'ABSPATH' ) || exit;

class SZESUTI_Cache {

	/** Az ürítés eredménye ebben a tranzienseben jut el a mentés utáni üzenetig. */
	const NOTICE_TRANSIENT = 'szesuti_cache_purged_';

	/** @var bool Egy kérésen belül csak egyszer ürítünk (az import több opciót is ír). */
	private static $done = false;

	/** Bekötés: beállítás- és háttéradatbázis-mentés, plugin-frissítés. */
	public static function init() {
		add_action( 'add_option_' . SZESUTI_OPTION, array( __CLASS__, 'purge_all' ), 99, 0 );
		add_action( 'update_option_' . SZESUTI_OPTION, array( __CLASS__, 'purge_all' ), 99, 0 );
		add_action( 'add_option_szesuti_cookie_library', array( __CLASS__, 'purge_all' ), 99, 0 );
		add_action( 'update_option_szesuti_cookie_library', array( __CLASS__, 'purge_all' ), 99, 0 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'on_upgrade' ), 99, 2 );
	}

	/**
	 * Frissítés után: ha köztük volt ez a plugin, ürítünk.
	 *
	 * @param WP_Upgrader $upgrader Frissítő.
	 * @param array       $options  Részletek.
	 */
	public static function on_upgrade( $upgrader, $options ) {
		if ( empty( $options['type'] ) || 'plugin' !== $options['type'] || empty( $options['plugins'] ) ) {
			return;
		}
		if ( in_array( SZESUTI_BASENAME, (array) $options['plugins'], true ) ) {
			self::purge_all();
		}
	}

	/**
	 * Minden felismert oldal-gyorsítótár ürítése.
	 *
	 * @return string[] A kiürített gyorsítótárak neve.
	 */
	public static function purge_all() {
		if ( self::$done ) {
			return array();
		}
		self::$done = true;

		/**
		 * Kikapcsolható, ha az oldalon az ürítés túl költséges.
		 *
		 * @param bool $enabled Ürítsen-e.
		 */
		if ( ! apply_filters( 'szesuti_purge_page_cache', true ) ) {
			return array();
		}

		$purged = array();

		// Nginx Helper (nginx FastCGI / Redis page cache).
		if ( has_action( 'rt_nginx_helper_purge_all' ) ) {
			do_action( 'rt_nginx_helper_purge_all' );
			$purged[] = 'Nginx Helper';
		}
		// LiteSpeed Cache.
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
			$purged[] = 'LiteSpeed Cache';
		}
		// WP Rocket.
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
			$purged[] = 'WP Rocket';
		}
		// W3 Total Cache.
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
			$purged[] = 'W3 Total Cache';
		}
		// WP Super Cache.
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
			$purged[] = 'WP Super Cache';
		}
		// WP Fastest Cache.
		if ( has_action( 'wpfc_clear_all_cache' ) ) {
			do_action( 'wpfc_clear_all_cache' );
			$purged[] = 'WP Fastest Cache';
		}
		// Cache Enabler.
		if ( has_action( 'cache_enabler_clear_complete_cache' ) ) {
			do_action( 'cache_enabler_clear_complete_cache' );
			$purged[] = 'Cache Enabler';
		}
		// SiteGround Speed Optimizer.
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
			$purged[] = 'SiteGround Optimizer';
		}
		// Breeze.
		if ( has_action( 'breeze_clear_all_cache' ) ) {
			do_action( 'breeze_clear_all_cache' );
			$purged[] = 'Breeze';
		}
		// Hummingbird.
		if ( has_action( 'wphb_clear_page_cache' ) ) {
			do_action( 'wphb_clear_page_cache' );
			$purged[] = 'Hummingbird';
		}

		/**
		 * Saját gyorsítótár ürítése (pl. Varnish, CDN). A visszaadott névlista a mentés
		 * utáni üzenetben jelenik meg.
		 *
		 * @param string[] $purged Az eddig kiürített gyorsítótárak.
		 */
		$purged = (array) apply_filters( 'szesuti_purged_page_caches', $purged );

		$user = get_current_user_id();
		if ( $user && $purged ) {
			set_transient( self::NOTICE_TRANSIENT . $user, array_values( $purged ), 5 * MINUTE_IN_SECONDS );
		}

		return $purged;
	}

	/**
	 * A mentés utáni üzenet kiegészítése (egyszer olvasható).
	 *
	 * @return string Üres, ha nem volt ürítés.
	 */
	public static function notice_text() {
		$user = get_current_user_id();
		if ( ! $user ) {
			return '';
		}
		$purged = get_transient( self::NOTICE_TRANSIENT . $user );
		if ( ! $purged ) {
			return '';
		}
		delete_transient( self::NOTICE_TRANSIENT . $user );

		/* translators: %s: gyorsítótárak felsorolása */
		return sprintf( __( 'Oldal-gyorsítótár ürítve: %s.', 'szesuti' ), implode( ', ', (array) $purged ) );
	}
}
