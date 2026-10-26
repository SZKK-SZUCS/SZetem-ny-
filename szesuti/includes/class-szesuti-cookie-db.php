<?php
/**
 * Beépített szolgáltatás- és sütiadatbázis.
 *
 * Itt él egy helyen minden, amit a plugin „tud” a külső szolgáltatásokról:
 * - milyen URL/kód minta alapján ismerhető fel a szkript vagy beágyazás,
 * - melyik kategóriába tartozik alapból,
 * - milyen sütiket helyez el.
 *
 * @package SZESUTI
 */

defined( 'ABSPATH' ) || exit;

class SZESUTI_Cookie_DB {

	/**
	 * Felismerhető szolgáltatások a szkriptblokkoláshoz.
	 *
	 * A `patterns` elemei egyszerű részstring-egyezések a script src-ben vagy
	 * az inline kód tartalmában (kis-nagybetű független).
	 *
	 * @return array
	 */
	public static function services() {
		$services = array(

			/* ---------------- Statisztika ---------------- */
			'google_analytics' => array(
				'label'    => 'Google Analytics (GA4 / Universal)',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'script',
				'patterns' => array(
					// A teljes domain: így a mérési végpontok (collect, g/collect) felé
					// indított beacon/fetch/XHR hívások is fennakadnak.
					'google-analytics.com',
					'analytics.google.com',
					'googletagmanager.com/gtag/js',
					'ga-audiences',
					'gtag(\'config\'',
					'gtag("config"',
					'ga(\'create\'',
					'_gaq.push',
				),
			),
			'google_tag_manager' => array(
				'label'    => 'Google Tag Manager',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'script',
				'patterns' => array(
					'googletagmanager.com/gtm.js',
					'googletagmanager.com/ns.html',
					'(window,document,\'script\',\'dataLayer\'',
				),
				'note'     => 'Ha a Google Consent Mode be van kapcsolva, a GTM-et NE blokkold – helyette a Consent Mode jelzések vezérlik.',
			),
			'hotjar' => array(
				'label'    => 'Hotjar',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'script',
				// A kliens API (insights.hotjar.com) és a mérési végpontok (metrics/vc/
				// content.hotjar.io) is a Hotjarhoz tartoznak – különben szigorú módban
				// „ismeretlenként” a marketinghez kerülnének. A www.hotjar.com (a cég honlapja,
				// pl. egy tájékoztató linkje) szándékosan nincs benne: az nem mérés.
				'patterns' => array( 'static.hotjar.com', 'script.hotjar.com', 'insights.hotjar.com', '.hotjar.io', '_hjSettings' ),
			),
			'clarity' => array(
				'label'    => 'Microsoft Clarity',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'script',
				// A tag (www.clarity.ms/tag/…) a valódi kódot a scripts.clarity.ms-ről tölti be,
				// és a *.clarity.ms/collect címre küld – mindez a Clarityhez tartozik. (A cég
				// honlapja clarity.microsoft.com, arra ez nem illeszkedik.)
				'patterns' => array( 'clarity.ms/tag', 'www.clarity.ms', '.clarity.ms' ),
			),
			'matomo' => array(
				'label'    => 'Matomo / Piwik',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'script',
				'patterns' => array( 'matomo.js', 'piwik.js', 'matomo.php', 'piwik.php', '_paq.push' ),
			),
			'yandex_metrica' => array(
				'label'    => 'Yandex Metrica',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'script',
				'patterns' => array( 'mc.yandex.ru/metrika' ),
			),
			'plausible' => array(
				'label'    => 'Plausible Analytics',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'script',
				'patterns' => array( 'plausible.io/js' ),
			),

			/* ---------------- Marketing ---------------- */
			'facebook_pixel' => array(
				'label'    => 'Meta (Facebook) Pixel',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'connect.facebook.net', 'fbevents.js', 'fbq(\'init\'', 'fbq("init"', 'facebook.com/tr' ),
			),
			'google_ads' => array(
				'label'    => 'Google Ads / Remarketing',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'googleadservices.com', 'googlesyndication.com', 'doubleclick.net', 'google.com/ads', 'conversion.js', 'conversion_async.js' ),
			),
			'linkedin' => array(
				'label'    => 'LinkedIn Insight Tag',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'snap.licdn.com', '_linkedin_partner_id' ),
			),
			'tiktok' => array(
				'label'    => 'TikTok Pixel',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'analytics.tiktok.com' ),
			),
			'twitter_ads' => array(
				'label'    => 'X (Twitter) Ads',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'static.ads-twitter.com', 'platform.twitter.com/oct.js' ),
			),
			'pinterest' => array(
				'label'    => 'Pinterest Tag',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 's.pinimg.com/ct' ),
			),
			'hubspot' => array(
				'label'    => 'HubSpot',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'js.hs-scripts.com', 'js.hsforms.net', 'js.hs-analytics.net', 'js.usemessages.com' ),
			),
			'mailchimp' => array(
				'label'    => 'Mailchimp',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'chimpstatic.com', 'list-manage.com' ),
			),
			'klaviyo' => array(
				'label'    => 'Klaviyo',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'static.klaviyo.com', 'static-tracking.klaviyo.com' ),
			),
			'criteo' => array(
				'label'    => 'Criteo',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'static.criteo.net' ),
			),
			'arukereso' => array(
				'label'    => 'Árukereső / Megbízható Bolt',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'arukereso.hu', 'aruhaz.arukereso' ),
			),
			'barion' => array(
				'label'    => 'Barion Pixel',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'pixel.barion.com', 'barion-pixel' ),
			),
			'pixelyoursite' => array(
				'label'    => 'PixelYourSite',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				// A PixelYourSite a saját domainről (wp-content/plugins/pixelyoursite/...)
				// tölti a szkriptjeit, ezért host alapján sosem esne bele a szigorú mód
				// "ismeretlen külső forrás" hálójába – csak explicit mintaillesztéssel
				// blokkolható. A pysOptions a beépített PYS_JS_Extra inline konfigja.
				'patterns' => array( '/plugins/pixelyoursite/', 'pysOptions' ),
			),

			/* ---------------- Funkcionális / beágyazások ---------------- */
			'youtube' => array(
				'label'    => 'YouTube beágyazás',
				'group'    => 'embed',
				'category' => 'marketing',
				'type'     => 'iframe',
				'patterns' => array( 'youtube.com/embed', 'youtube-nocookie.com/embed', 'youtu.be/' ),
				'provider' => 'YouTube',
			),
			'vimeo' => array(
				'label'    => 'Vimeo beágyazás',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'iframe',
				'patterns' => array( 'player.vimeo.com' ),
				'provider' => 'Vimeo',
			),
			'google_maps' => array(
				'label'    => 'Google Maps beágyazás',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'both',
				// A google.com/maps/d/… a „Saját térképek” (My Maps) beágyazása.
				'patterns' => array( 'google.com/maps/embed', 'google.com/maps/d/embed', 'google.com/maps/d/u/', 'maps.googleapis.com', 'maps.google.com/maps' ),
				'provider' => 'Google Maps',
			),
			'google_fonts' => array(
				'label'    => 'Google Fonts (külső betűtípus)',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'script',
				'patterns' => array( 'fonts.googleapis.com', 'fonts.gstatic.com' ),
				'default_off' => true,
				'note'        => 'Alapból kikapcsolva: a betűtípus blokkolása látványosan elrontja az oldalt. Csak akkor kapcsold be, ha helyi betűtípusra váltottál.',
			),
			'facebook_embed' => array(
				'label'    => 'Facebook beágyazás / SDK',
				'group'    => 'embed',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'facebook.com/plugins', 'connect.facebook.net/en_US/sdk.js', 'connect.facebook.net/hu_HU/sdk.js' ),
				'provider' => 'Facebook',
			),
			'instagram_embed' => array(
				'label'    => 'Instagram beágyazás',
				'group'    => 'embed',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'instagram.com/embed', 'platform.instagram.com' ),
				'provider' => 'Instagram',
			),
			'spotify' => array(
				'label'    => 'Spotify beágyazás',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'iframe',
				'patterns' => array( 'open.spotify.com/embed' ),
				'provider' => 'Spotify',
			),
			'soundcloud' => array(
				'label'    => 'SoundCloud beágyazás',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'iframe',
				'patterns' => array( 'w.soundcloud.com/player' ),
				'provider' => 'SoundCloud',
			),
			'disqus' => array(
				'label'    => 'Disqus hozzászólások',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'both',
				'patterns' => array( 'disqus.com/embed.js', '.disqus.com' ),
				'provider' => 'Disqus',
			),
			'recaptcha' => array(
				'label'       => 'Google reCAPTCHA',
				'group'       => 'embed',
				'category'    => 'functional',
				'type'        => 'script',
				'patterns'    => array( 'google.com/recaptcha', 'gstatic.com/recaptcha' ),
				'default_off' => true,
				'note'        => 'Alapból kikapcsolva: blokkolás esetén az űrlapok nem küldhetők el. Sok esetben a reCAPTCHA jogos érdek / szükséges kategória.',
			),

			/* ---------------- Chat / support ---------------- */
			'tawk' => array(
				'label'    => 'Tawk.to chat',
				'group'    => 'chat',
				'category' => 'functional',
				'type'     => 'script',
				'patterns' => array( 'embed.tawk.to' ),
			),
			'smartsupp' => array(
				'label'    => 'Smartsupp chat',
				'group'    => 'chat',
				'category' => 'functional',
				'type'     => 'script',
				'patterns' => array( 'smartsuppchat.com', 'smartsupp.com' ),
			),
			'intercom' => array(
				'label'    => 'Intercom',
				'group'    => 'chat',
				'category' => 'functional',
				'type'     => 'script',
				'patterns' => array( 'widget.intercom.io', 'js.intercomcdn.com' ),
			),
			'crisp' => array(
				'label'    => 'Crisp chat',
				'group'    => 'chat',
				'category' => 'functional',
				'type'     => 'script',
				'patterns' => array( 'client.crisp.chat' ),
			),
			'livechat' => array(
				'label'    => 'LiveChat',
				'group'    => 'chat',
				'category' => 'functional',
				'type'     => 'script',
				'patterns' => array( 'cdn.livechatinc.com' ),
			),
			'messenger' => array(
				'label'    => 'Facebook Messenger chat',
				'group'    => 'chat',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'connect.facebook.net/en_US/sdk/xfbml.customerchat.js', 'customerchat.js' ),
			),
			'zendesk' => array(
				'label'    => 'Zendesk chat',
				'group'    => 'chat',
				'category' => 'functional',
				'type'     => 'script',
				'patterns' => array( 'static.zdassets.com', 'zopim.com' ),
			),
			'tidio' => array(
				'label'    => 'Tidio chat',
				'group'    => 'chat',
				'category' => 'functional',
				'type'     => 'script',
				'patterns' => array( 'code.tidio.co' ),
			),

			/* ---------------- 1.0.3-ban felvett szolgáltatások ---------------- */
			'bing_uet' => array(
				'label'    => 'Microsoft Advertising (Bing UET)',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'bat.bing.com', 'bat.r.msn.com' ),
			),
			'twitter_widgets' => array(
				'label'    => 'X (Twitter) beágyazás / widget',
				'group'    => 'embed',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'platform.twitter.com', 'syndication.twitter.com', 'platform.x.com' ),
				'provider' => 'X (Twitter)',
			),
			'tiktok_embed' => array(
				'label'    => 'TikTok beágyazás',
				'group'    => 'embed',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'tiktok.com/embed' ),
				'provider' => 'TikTok',
			),
			'pinterest_widgets' => array(
				'label'    => 'Pinterest widget',
				'group'    => 'embed',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'assets.pinterest.com', 'ct.pinterest.com' ),
				'provider' => 'Pinterest',
			),
			'linkedin_embed' => array(
				'label'    => 'LinkedIn beágyazás',
				'group'    => 'embed',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'linkedin.com/embed', 'px.ads.linkedin.com', 'platform.linkedin.com' ),
				'provider' => 'LinkedIn',
			),
			'google_embeds' => array(
				'label'    => 'Google Űrlapok, Naptár, Dokumentumok, Drive beágyazás',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'iframe',
				'patterns' => array( 'docs.google.com', 'calendar.google.com/calendar/embed', 'drive.google.com/file', 'forms.gle' ),
				'provider' => 'Google',
			),
			'openstreetmap' => array(
				'label'    => 'OpenStreetMap beágyazás',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'iframe',
				'patterns' => array( 'openstreetmap.org/export/embed' ),
				'provider' => 'OpenStreetMap',
			),
			'calendly' => array(
				'label'    => 'Calendly időpontfoglaló',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'both',
				'patterns' => array( 'assets.calendly.com', 'calendly.com/' ),
				'provider' => 'Calendly',
			),
			'typeform' => array(
				'label'    => 'Typeform',
				'group'    => 'embed',
				'category' => 'functional',
				'type'     => 'both',
				'patterns' => array( 'embed.typeform.com', '.typeform.com/to/' ),
				'provider' => 'Typeform',
			),
			'dailymotion' => array(
				'label'    => 'Dailymotion beágyazás',
				'group'    => 'embed',
				'category' => 'marketing',
				'type'     => 'iframe',
				'patterns' => array( 'dailymotion.com/embed', 'geo.dailymotion.com' ),
				'provider' => 'Dailymotion',
			),
			'jetpack_stats' => array(
				'label'    => 'Jetpack Stats (WordPress.com)',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'both',
				'patterns' => array( 'stats.wp.com', 'pixel.wp.com' ),
			),
			'cloudflare_insights' => array(
				'label'    => 'Cloudflare Web Analytics',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'script',
				'patterns' => array( 'static.cloudflareinsights.com', 'cloudflareinsights.com/cdn-cgi/rum' ),
			),
			'gemius' => array(
				'label'    => 'Gemius (gemiusAudience)',
				'group'    => 'analytics',
				'category' => 'analytics',
				'type'     => 'both',
				'patterns' => array( 'gemius.pl', 'gemius.com', 'pp_gemius' ),
			),
			'sharing_widgets' => array(
				'label'    => 'Megosztó gombok (ShareThis, AddThis, AddToAny)',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'script',
				'patterns' => array( 'sharethis.com', 'addthis.com', 'static.addtoany.com' ),
			),
			'snapchat' => array(
				'label'    => 'Snapchat Pixel',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'sc-static.net', 'tr.snapchat.com' ),
			),
			'reddit' => array(
				'label'    => 'Reddit Pixel',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'redditstatic.com/ads', 'alb.reddit.com' ),
			),
			'taboola_outbrain' => array(
				'label'    => 'Taboola / Outbrain',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'cdn.taboola.com', 'trc.taboola.com', 'widgets.outbrain.com', 'amplify.outbrain.com' ),
			),
			'trustpilot' => array(
				'label'    => 'Trustpilot widget',
				'group'    => 'marketing',
				'category' => 'marketing',
				'type'     => 'both',
				'patterns' => array( 'widget.trustpilot.com' ),
				'provider' => 'Trustpilot',
			),
		);

		/**
		 * Beépített blokkolási szabályok bővítése.
		 *
		 * @param array $services Szolgáltatások.
		 */
		return apply_filters( 'szesuti_services', $services );
	}

	/**
	 * Beépített, biztonságos külső források: könyvtár-CDN-ek, betűtípus-szolgáltatók,
	 * fizetési és robotvédelmi szolgáltatások, amelyek nélkül egy oldal működésképtelen
	 * lehet, és nem követik a látogatót. Szigorú módban ezek NEM blokkolódnak.
	 *
	 * @return string[] Hosztnevek (az aldomainjeik is ide tartoznak).
	 */
	public static function safe_hosts() {
		$hosts = array(
			// Könyvtár-CDN-ek.
			'cdnjs.cloudflare.com',
			'cdn.jsdelivr.net',
			'unpkg.com',
			'code.jquery.com',
			'ajax.googleapis.com',
			'stackpath.bootstrapcdn.com',
			'maxcdn.bootstrapcdn.com',
			'cdn.datatables.net',
			// CSS-keretrendszer (a Tailwind Play CDN futás közben állítja elő az oldal CSS-ét).
			'cdn.tailwindcss.com',
			// Ikon- és betűtípus-szolgáltatók.
			'use.fontawesome.com',
			'kit.fontawesome.com',
			'ka-f.fontawesome.com',
			'ka-p.fontawesome.com',
			'use.typekit.net',
			// WordPress.com statikus fájlok (nem a Stats!).
			'c0.wp.com',
			's0.wp.com',
			's.w.org',
			// Fizetés.
			'js.stripe.com',
			'm.stripe.network',
			'hooks.stripe.com',
			'www.paypal.com',
			'www.paypalobjects.com',
			// Robotvédelem.
			'challenges.cloudflare.com',
			'hcaptcha.com',
		);

		/**
		 * A beépített biztonságos hosztlista módosítása.
		 *
		 * @param string[] $hosts Hosztok.
		 */
		return apply_filters( 'szesuti_safe_hosts', $hosts );
	}

	/**
	 * Szolgáltatáscsoportok címkéi az admin felülethez.
	 *
	 * @return array
	 */
	public static function service_groups() {
		return array(
			'analytics' => __( 'Statisztika és mérés', 'szesuti' ),
			'marketing' => __( 'Marketing és hirdetés', 'szesuti' ),
			'embed'     => __( 'Beágyazások és külső tartalom', 'szesuti' ),
			'chat'      => __( 'Chat és ügyfélszolgálat', 'szesuti' ),
		);
	}

	/**
	 * Az összes ismert süti a felismeréshez: előbb a háttéradatbázis saját és
	 * importált bejegyzései (ezek az erősebbek), utána a beépítettek.
	 *
	 * A `match` mező egy egyszerű minta, ahol a `*` bármit jelent.
	 *
	 * @return array
	 */
	public static function known_cookies() {
		$list = class_exists( 'SZESUTI_Library' )
			? array_merge( SZESUTI_Library::as_known(), self::builtin_cookies() )
			: self::builtin_cookies();

		/**
		 * Ismert sütik listájának bővítése.
		 *
		 * @param array $list Sütik.
		 */
		return apply_filters( 'szesuti_known_cookies', $list );
	}

	/**
	 * A pluginba épített ismert sütik.
	 *
	 * @return array
	 */
	public static function builtin_cookies() {
		return array(
			/* WordPress alap */
			array( 'match' => 'wordpress_logged_in_*', 'name' => 'wordpress_logged_in_*', 'provider' => 'WordPress', 'category' => 'necessary', 'duration' => 'Munkamenet / 14 nap', 'desc_hu' => 'A bejelentkezett felhasználó azonosítására szolgál.', 'desc_en' => 'Identifies the logged-in user.' ),
			array( 'match' => 'wordpress_sec_*', 'name' => 'wordpress_sec_*', 'provider' => 'WordPress', 'category' => 'necessary', 'duration' => 'Munkamenet / 14 nap', 'desc_hu' => 'Biztonsági azonosító a bejelentkezett munkamenethez.', 'desc_en' => 'Security token for the logged-in session.' ),
			array( 'match' => 'wp-settings-*', 'name' => 'wp-settings-*', 'provider' => 'WordPress', 'category' => 'functional', 'duration' => '1 év', 'desc_hu' => 'Az admin felület személyes nézetbeállításait tárolja.', 'desc_en' => 'Stores personal admin interface preferences.' ),
			array( 'match' => 'wp_lang', 'name' => 'wp_lang', 'provider' => 'WordPress', 'category' => 'functional', 'duration' => 'Munkamenet', 'desc_hu' => 'A kiválasztott nyelvet tárolja.', 'desc_en' => 'Stores the selected language.' ),
			array( 'match' => 'comment_author_*', 'name' => 'comment_author_*', 'provider' => 'WordPress', 'category' => 'functional', 'duration' => '1 év', 'desc_hu' => 'Megjegyzi a hozzászólásnál megadott nevet és e-mail-címet.', 'desc_en' => 'Remembers the name and e-mail used for comments.' ),
			array( 'match' => 'PHPSESSID', 'name' => 'PHPSESSID', 'provider' => 'A weboldal', 'category' => 'necessary', 'duration' => 'Munkamenet', 'desc_hu' => 'A szerveroldali munkamenet azonosítója.', 'desc_en' => 'Server side session identifier.' ),

			/* WooCommerce */
			array( 'match' => 'woocommerce_cart_hash', 'name' => 'woocommerce_cart_hash', 'provider' => 'WooCommerce', 'category' => 'necessary', 'duration' => 'Munkamenet', 'desc_hu' => 'A kosár tartalmának változását követi.', 'desc_en' => 'Tracks changes of the cart content.' ),
			array( 'match' => 'woocommerce_items_in_cart', 'name' => 'woocommerce_items_in_cart', 'provider' => 'WooCommerce', 'category' => 'necessary', 'duration' => 'Munkamenet', 'desc_hu' => 'Jelzi, hogy van-e termék a kosárban.', 'desc_en' => 'Indicates whether the cart has items.' ),
			array( 'match' => 'wp_woocommerce_session_*', 'name' => 'wp_woocommerce_session_*', 'provider' => 'WooCommerce', 'category' => 'necessary', 'duration' => '2 nap', 'desc_hu' => 'A vásárló munkamenetét és kosarát azonosítja.', 'desc_en' => 'Identifies the customer session and cart.' ),

			/* Google */
			array( 'match' => '_ga', 'name' => '_ga', 'provider' => 'Google Analytics', 'category' => 'analytics', 'duration' => '2 év', 'desc_hu' => 'Egyedi, névtelen azonosítót rendel a látogatóhoz a statisztikákhoz.', 'desc_en' => 'Assigns a unique anonymous ID to the visitor for statistics.' ),
			array( 'match' => '_ga_*', 'name' => '_ga_*', 'provider' => 'Google Analytics 4', 'category' => 'analytics', 'duration' => '2 év', 'desc_hu' => 'A GA4 adatfolyamhoz tartozó munkamenet-állapotot tárolja.', 'desc_en' => 'Stores session state for the GA4 data stream.' ),
			array( 'match' => '_gid', 'name' => '_gid', 'provider' => 'Google Analytics', 'category' => 'analytics', 'duration' => '24 óra', 'desc_hu' => 'Napi szintű, névtelen látogatóazonosító.', 'desc_en' => 'Daily anonymous visitor identifier.' ),
			array( 'match' => '_gat*', 'name' => '_gat*', 'provider' => 'Google Analytics', 'category' => 'analytics', 'duration' => '1 perc', 'desc_hu' => 'A kérések számát korlátozza (throttling).', 'desc_en' => 'Throttles the request rate.' ),
			array( 'match' => '_gcl_au', 'name' => '_gcl_au', 'provider' => 'Google Ads', 'category' => 'marketing', 'duration' => '90 nap', 'desc_hu' => 'A hirdetési konverziók mérésére szolgál.', 'desc_en' => 'Used for ad conversion measurement.' ),
			array( 'match' => 'IDE', 'name' => 'IDE', 'provider' => 'Google DoubleClick', 'category' => 'marketing', 'duration' => '1 év', 'desc_hu' => 'Hirdetések személyre szabása és hatékonyságmérés.', 'desc_en' => 'Ad personalisation and performance measurement.' ),
			array( 'match' => 'NID', 'name' => 'NID', 'provider' => 'Google', 'category' => 'marketing', 'duration' => '6 hónap', 'desc_hu' => 'A Google szolgáltatásainak testreszabására szolgál.', 'desc_en' => 'Used to personalise Google services.' ),
			array( 'match' => '1P_JAR', 'name' => '1P_JAR', 'provider' => 'Google', 'category' => 'marketing', 'duration' => '1 hónap', 'desc_hu' => 'Hirdetésmérés és statisztika.', 'desc_en' => 'Ad measurement and statistics.' ),

			/* Meta */
			array( 'match' => '_fbp', 'name' => '_fbp', 'provider' => 'Meta (Facebook)', 'category' => 'marketing', 'duration' => '3 hónap', 'desc_hu' => 'Hirdetések megjelenítése és konverziómérés a Facebookon.', 'desc_en' => 'Ad delivery and conversion measurement on Facebook.' ),
			array( 'match' => 'fr', 'name' => 'fr', 'provider' => 'Meta (Facebook)', 'category' => 'marketing', 'duration' => '3 hónap', 'desc_hu' => 'Célzott hirdetések megjelenítése.', 'desc_en' => 'Delivery of targeted advertising.' ),
			array( 'match' => '_fbc', 'name' => '_fbc', 'provider' => 'Meta (Facebook)', 'category' => 'marketing', 'duration' => '3 hónap', 'desc_hu' => 'A hirdetésre kattintás adatait tárolja.', 'desc_en' => 'Stores ad click data.' ),

			/* Egyéb */
			array( 'match' => '_hjSession*', 'name' => '_hjSession*', 'provider' => 'Hotjar', 'category' => 'analytics', 'duration' => '30 perc', 'desc_hu' => 'A Hotjar munkamenet-adatait tárolja.', 'desc_en' => 'Stores the Hotjar session data.' ),
			array( 'match' => '_hj*', 'name' => '_hj*', 'provider' => 'Hotjar', 'category' => 'analytics', 'duration' => '1 év', 'desc_hu' => 'Névtelen viselkedéselemzés (hőtérkép, felvétel).', 'desc_en' => 'Anonymous behaviour analytics (heatmaps, recordings).' ),
			array( 'match' => '_clck', 'name' => '_clck', 'provider' => 'Microsoft Clarity', 'category' => 'analytics', 'duration' => '1 év', 'desc_hu' => 'Névtelen látogatóazonosító a Clarity statisztikákhoz.', 'desc_en' => 'Anonymous visitor ID for Clarity analytics.' ),
			array( 'match' => '_clsk', 'name' => '_clsk', 'provider' => 'Microsoft Clarity', 'category' => 'analytics', 'duration' => '1 nap', 'desc_hu' => 'A Clarity munkamenet adatait tárolja.', 'desc_en' => 'Stores Clarity session data.' ),
			array( 'match' => 'li_gc', 'name' => 'li_gc', 'provider' => 'LinkedIn', 'category' => 'marketing', 'duration' => '6 hónap', 'desc_hu' => 'A LinkedIn süti-hozzájárulást tárolja.', 'desc_en' => 'Stores LinkedIn cookie consent.' ),
			array( 'match' => 'bcookie', 'name' => 'bcookie', 'provider' => 'LinkedIn', 'category' => 'marketing', 'duration' => '1 év', 'desc_hu' => 'Böngészőazonosító a LinkedIn szolgáltatásokhoz.', 'desc_en' => 'Browser identifier for LinkedIn services.' ),
			array( 'match' => 'VISITOR_INFO1_LIVE', 'name' => 'VISITOR_INFO1_LIVE', 'provider' => 'YouTube', 'category' => 'marketing', 'duration' => '6 hónap', 'desc_hu' => 'A YouTube-lejátszó sávszélességét és beállításait méri.', 'desc_en' => 'Measures YouTube player bandwidth and settings.' ),
			array( 'match' => 'YSC', 'name' => 'YSC', 'provider' => 'YouTube', 'category' => 'marketing', 'duration' => 'Munkamenet', 'desc_hu' => 'A megtekintett YouTube-videók statisztikáját tárolja.', 'desc_en' => 'Stores statistics of watched YouTube videos.' ),
			array( 'match' => 'CONSENT', 'name' => 'CONSENT', 'provider' => 'Google / YouTube', 'category' => 'marketing', 'duration' => '2 év', 'desc_hu' => 'A Google-szolgáltatások hozzájárulási állapotát tárolja.', 'desc_en' => 'Stores consent state for Google services.' ),
			array( 'match' => '__cf_bm', 'name' => '__cf_bm', 'provider' => 'Cloudflare', 'category' => 'necessary', 'duration' => '30 perc', 'desc_hu' => 'Botvédelem: megkülönbözteti az emberi látogatókat a robotoktól.', 'desc_en' => 'Bot protection: distinguishes humans from bots.' ),
			array( 'match' => 'cf_clearance', 'name' => 'cf_clearance', 'provider' => 'Cloudflare', 'category' => 'necessary', 'duration' => '1 év', 'desc_hu' => 'A Cloudflare biztonsági ellenőrzés eredményét tárolja.', 'desc_en' => 'Stores the result of the Cloudflare security check.' ),
			array( 'match' => 'barion_*', 'name' => 'barion_*', 'provider' => 'Barion', 'category' => 'marketing', 'duration' => '1 év', 'desc_hu' => 'A Barion csalásmegelőzési és mérési sütije.', 'desc_en' => 'Barion fraud prevention and measurement cookie.' ),
			array( 'match' => 'pys*', 'name' => 'pys_*', 'provider' => 'PixelYourSite', 'category' => 'marketing', 'duration' => 'Munkamenet / 1 év', 'desc_hu' => 'A PixelYourSite bővítmény forgalomforrás- és munkamenet-sütijei.', 'desc_en' => 'PixelYourSite traffic-source and session cookies.' ),
			array( 'match' => 'last_pys*', 'name' => 'last_pys_*', 'provider' => 'PixelYourSite', 'category' => 'marketing', 'duration' => '1 év', 'desc_hu' => 'A PixelYourSite bővítmény korábbi látogatásra vonatkozó sütijei.', 'desc_en' => 'PixelYourSite cookies referring to the previous visit.' ),
			/* Valós méréskor megfigyelt további sütik */
			array( 'match' => '_ttp', 'name' => '_ttp', 'provider' => 'TikTok', 'category' => 'marketing', 'duration' => '13 hónap', 'desc_hu' => 'A TikTok hirdetések mérésére és célzására szolgáló azonosító.', 'desc_en' => 'Identifier used to measure and target TikTok ads.' ),
			array( 'match' => 'tt_*', 'name' => 'tt_*', 'provider' => 'TikTok', 'category' => 'marketing', 'duration' => 'Munkamenet', 'type' => 'html', 'desc_hu' => 'A TikTok Pixel munkamenet-adatai a böngésző tárolójában.', 'desc_en' => 'TikTok Pixel session data in browser storage.' ),
			array( 'match' => 'lidc', 'name' => 'lidc', 'provider' => 'LinkedIn', 'category' => 'marketing', 'duration' => '1 nap', 'desc_hu' => 'A LinkedIn adatközpont-választását segíti (útválasztás).', 'desc_en' => 'Used by LinkedIn for data center routing.' ),
			array( 'match' => 'li_fat_id', 'name' => 'li_fat_id', 'provider' => 'LinkedIn', 'category' => 'marketing', 'duration' => '30 nap', 'desc_hu' => 'LinkedIn hirdetési konverziómérés.', 'desc_en' => 'LinkedIn ad conversion tracking.' ),
			array( 'match' => 'AnalyticsSyncHistory', 'name' => 'AnalyticsSyncHistory', 'provider' => 'LinkedIn', 'category' => 'marketing', 'duration' => '30 nap', 'desc_hu' => 'A LinkedIn mérési adatok szinkronizálásának ideje.', 'desc_en' => 'Time of LinkedIn analytics synchronisation.' ),
			array( 'match' => 'UserMatchHistory', 'name' => 'UserMatchHistory', 'provider' => 'LinkedIn', 'category' => 'marketing', 'duration' => '30 nap', 'desc_hu' => 'LinkedIn hirdetési azonosító-szinkronizálás.', 'desc_en' => 'LinkedIn ads ID syncing.' ),
			array( 'match' => 'VISITOR_PRIVACY_METADATA', 'name' => 'VISITOR_PRIVACY_METADATA', 'provider' => 'YouTube', 'category' => 'marketing', 'duration' => '6 hónap', 'desc_hu' => 'A YouTube a látogató adatvédelmi (hozzájárulási) állapotát tárolja.', 'desc_en' => 'Stores the visitor\'s privacy (consent) state for YouTube.' ),
			array( 'match' => '__Secure-YNID', 'name' => '__Secure-YNID', 'provider' => 'YouTube', 'category' => 'marketing', 'duration' => '6 hónap', 'desc_hu' => 'A YouTube biztonsági és visszaélés-megelőzési azonosítója.', 'desc_en' => 'YouTube security and abuse prevention identifier.' ),
			array( 'match' => '__Secure-ROLLOUT_TOKEN', 'name' => '__Secure-ROLLOUT_TOKEN', 'provider' => 'YouTube', 'category' => 'marketing', 'duration' => '6 hónap', 'desc_hu' => 'A YouTube-lejátszó funkcióinak fokozatos bevezetéséhez használt azonosító.', 'desc_en' => 'Used by YouTube to roll out player features.' ),
			array( 'match' => 'vuid', 'name' => 'vuid', 'provider' => 'Vimeo', 'category' => 'marketing', 'duration' => '2 év', 'desc_hu' => 'A Vimeo a lejátszóval kapcsolatos statisztikákhoz azonosítja a látogatót.', 'desc_en' => 'Identifies the visitor for Vimeo player statistics.' ),
			array( 'match' => 'sp_t', 'name' => 'sp_t', 'provider' => 'Spotify', 'category' => 'marketing', 'duration' => '1 év', 'desc_hu' => 'A Spotify beágyazott lejátszójának látogatóazonosítója.', 'desc_en' => 'Visitor identifier of the embedded Spotify player.' ),
			array( 'match' => 'sp_landing', 'name' => 'sp_landing', 'provider' => 'Spotify', 'category' => 'marketing', 'duration' => '1 nap', 'desc_hu' => 'A Spotify a belépési pontot tárolja.', 'desc_en' => 'Spotify stores the entry point of the visit.' ),
			array( 'match' => '_GRECAPTCHA', 'name' => '_GRECAPTCHA', 'provider' => 'Google reCAPTCHA', 'category' => 'functional', 'duration' => '6 hónap', 'desc_hu' => 'A reCAPTCHA robotszűrés kockázatelemzéséhez szükséges.', 'desc_en' => 'Required for reCAPTCHA bot-protection risk analysis.' ),
			array( 'match' => 'guest_id*', 'name' => 'guest_id*', 'provider' => 'X (Twitter)', 'category' => 'marketing', 'duration' => '13 hónap', 'desc_hu' => 'Az X (Twitter) beágyazások látogatóazonosítója.', 'desc_en' => 'Visitor identifier of X (Twitter) embeds.' ),
			array( 'match' => 'personalization_id', 'name' => 'personalization_id', 'provider' => 'X (Twitter)', 'category' => 'marketing', 'duration' => '13 hónap', 'desc_hu' => 'Az X (Twitter) személyre szabási és hirdetési azonosítója.', 'desc_en' => 'X (Twitter) personalisation and ads identifier.' ),
			array( 'match' => 'MUID', 'name' => 'MUID', 'provider' => 'Microsoft (Clarity, Bing)', 'category' => 'marketing', 'duration' => '1 év', 'desc_hu' => 'A Microsoft szolgáltatásainak egyedi látogatóazonosítója.', 'desc_en' => 'Unique visitor identifier for Microsoft services.' ),
			array( 'match' => 'CLID', 'name' => 'CLID', 'provider' => 'Microsoft Clarity', 'category' => 'analytics', 'duration' => '1 év', 'desc_hu' => 'A Clarity az első és a későbbi látogatások összekapcsolására használja.', 'desc_en' => 'Used by Clarity to link first and subsequent visits.' ),

			array( 'match' => 'szesuti_consent', 'name' => 'szesuti_consent', 'provider' => 'A weboldal (SZetemény)', 'category' => 'necessary', 'duration' => '6 hónap', 'desc_hu' => 'A süti-beállításaidat tárolja, hogy ne kelljen minden látogatáskor újra döntened.', 'desc_en' => 'Stores your cookie preferences so you do not have to decide on every visit.' ),
		);
	}

	/**
	 * A plugin telepítésekor beírt kezdő sütilista.
	 *
	 * @return array
	 */
	public static function starter_list() {
		$starter = array( 'wordpress_logged_in_*', 'wp-settings-*', 'PHPSESSID', 'comment_author_*' );
		$out     = array();

		foreach ( self::builtin_cookies() as $cookie ) {
			if ( in_array( $cookie['match'], $starter, true ) ) {
				$out[] = self::to_row( $cookie );
			}
		}

		$out[] = array(
			'name'     => 'szesuti_consent',
			'provider' => __( 'A weboldal (SZetemény)', 'szesuti' ),
			'category' => 'necessary',
			'duration' => __( '6 hónap', 'szesuti' ),
			'type'     => 'http',
			'desc_hu'  => 'A süti-beállításaidat tárolja, hogy ne kelljen minden látogatáskor újra döntened.',
			'desc_en'  => 'Stores your cookie preferences so you do not have to decide on every visit.',
		);

		return $out;
	}

	/**
	 * Ismert süti sor konvertálása a beállításokban tárolt formára.
	 *
	 * @param array $cookie Ismert süti.
	 * @return array
	 */
	public static function to_row( array $cookie ) {
		return array(
			'name'     => isset( $cookie['name'] ) ? $cookie['name'] : $cookie['match'],
			'provider' => isset( $cookie['provider'] ) ? $cookie['provider'] : '',
			'category' => isset( $cookie['category'] ) ? $cookie['category'] : 'necessary',
			'duration' => isset( $cookie['duration'] ) ? $cookie['duration'] : '',
			'type'     => isset( $cookie['type'] ) ? $cookie['type'] : 'http',
			'desc_hu'  => isset( $cookie['desc_hu'] ) ? $cookie['desc_hu'] : '',
			'desc_en'  => isset( $cookie['desc_en'] ) ? $cookie['desc_en'] : '',
		);
	}

	/**
	 * Egy süti nevéhez megkeresi az ismert leírást.
	 *
	 * @param string $name Süti neve.
	 * @return array|null
	 */
	public static function match_cookie( $name ) {
		$name = (string) $name;

		foreach ( self::known_cookies() as $cookie ) {
			$pattern = '/^' . str_replace( '\*', '.*', preg_quote( $cookie['match'], '/' ) ) . '$/i';
			if ( preg_match( $pattern, $name ) ) {
				$row         = self::to_row( $cookie );
				$row['name'] = $name;
				return $row;
			}
		}

		return null;
	}
}
