=== SZetemény – Sütikezelő ===
Contributors: szesuti
Tags: cookie, gdpr, consent, cookie consent, consent mode
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GDPR-kompatibilis sütikezelő valódi szkriptblokkolással, Google Consent Mode v2 támogatással, hozzájárulás-naplóval és többnyelvű felülettel.

== Description ==

A SZetemény nem csak megjeleníti a süti-bannert, hanem ténylegesen meg is akadályozza,
hogy a külső mérőkódok hozzájárulás nélkül lefussanak.

= Fő funkciók =

* **Valódi szkriptblokkolás** – a plugin az oldal kimenetét dolgozza fel, és a felismert
  külső szkripteket `type="text/plain"` típusra írja át, a `src` értékét pedig elrejti.
  Így a böngésző el sem kezdi letölteni őket.
* **35+ beépített szolgáltatás felismerése** – Google Analytics, Google Ads, Meta Pixel,
  Hotjar, Clarity, LinkedIn, TikTok, HubSpot, YouTube, Vimeo, Google Maps, élő chatek,
  Barion Pixel, Árukereső és társaik.
* **Google Consent Mode v2** – mind a hét jelzés (`ad_storage`, `ad_user_data`,
  `ad_personalization`, `analytics_storage`, `functionality_storage`,
  `personalization_storage`, `security_storage`), `wait_for_update`,
  `url_passthrough` és `ads_data_redaction` támogatással.
* **Hozzájárulás-napló** – saját adatbázistáblába, saltinggal képzett IP-lenyomattal (nem visszafejthető),
  CSV exporttal és automatikus törléssel a megőrzési idő után.
* **Többnyelvűség** – beépített magyar és angol szövegek, tetszőleges további nyelv,
  Polylang és WPML felismerés.
* **Geo-célzás** – csak EU/EGT-ben (vagy saját országlistán) jelenjen meg a banner,
  gyorsítótár-biztos módban.
* **Sütiszkenner** – a böngészőben végigjárja az egész oldalt (minden oldal, belső linkek,
  legörgetés), kigyűjti a sütiket, böngészős tárolókat, felismert szolgáltatásokat és
  ismeretlen külső kiszolgálókat, majd egy kattintással beteszi őket a sütilistába.
* **Sok beállítás** – elrendezés, pozíció, színek külön világos és sötét témához,
  animáció, gombstílus és -sorrend, saját CSS, ikonválasztó (saját képpel), élő előnézet.
* **Export/import** – egy JSON fájllal átvihető az összes beállítás másik oldalra.
* **Automatikus frissítés** – a GitHub-repó kiadásaiból (Release) a WordPress normál
  frissítési felületén jelzi és telepíti az új verziót. Belső tükörhöz JSON manifest is megadható.

= Shortcode-ok =

* `[szesuti_cookie_table]` – kategóriánkénti sütitáblázat a süti tájékoztató oldalhoz.
  Paraméterek: `category`, `show_desc`, `title_tag`.
* `[szesuti_settings_button]` – „Süti beállítások” gomb. Paraméterek: `text`, `class`, `style`.
* `[szesuti_consent_status]` – az aktuális döntés kiírása.

= Fejlesztőknek =

PHP:

`if ( function_exists( 'szesuti_has_consent' ) && szesuti_has_consent( 'marketing' ) ) { ... }`

JavaScript:

`SZESUTI.hasConsent( 'analytics' );`
`SZESUTI.showPreferences();`
`document.addEventListener( 'szesuti:change', function ( e ) { console.log( e.detail ); } );`

Szűrők: `szesuti_settings`, `szesuti_services`, `szesuti_known_cookies`,
`szesuti_blocker_rules`, `szesuti_js_config`, `szesuti_should_display`,
`szesuti_run_blocker`, `szesuti_current_language`, `szesuti_visitor_country`,
`szesuti_banner_template`, `szesuti_update_url`, `szesuti_update_repository`,
`szesuti_safe_hosts`, `szesuti_purge_page_cache`, `szesuti_purged_page_caches`.

Sablon felülírása a témában: `wp-content/themes/<téma>/szesuti/banner.php`

== Installation ==

1. Töltsd fel a `szesuti` mappát a `/wp-content/plugins/` könyvtárba,
   vagy telepítsd ZIP-ből a Bővítmények → Új hozzáadása → Bővítmény feltöltése menüpontban.
2. Kapcsold be a bővítményt a Bővítmények menüben.
3. Nyisd meg a SZetemény menüpontot, és add meg az adatkezelési tájékoztató linkjét.
4. Nézd át a Szkriptblokkolás fülön, hogy a megfelelő szolgáltatások vannak-e bekapcsolva.
5. Ha Google Analytics vagy Google Ads fut az oldalon, kapcsold be a Consent Mode-ot.

== Frequently Asked Questions ==

= Tényleg blokkolja a szkripteket, vagy csak megjelenít egy bannert? =

Ténylegesen blokkol. A plugin a teljes oldal HTML-kimenetét feldolgozza, és a felismert
szkripteket úgy írja át, hogy a böngésző ne futtassa őket. A `src` attribútum
`data-szesuti-src`-re kerül, így a letöltés sem indul el.

= Működik teljes oldalas gyorsítótárral (WP Rocket, LiteSpeed)? =

Igen. A blokkolás a HTML-be íródik bele, tehát a gyorsítótárazott oldal is blokkolva
marad – a feloldás a böngészőben történik. Két beállításra figyelj:
a geo-célzásnál használd a „böngészőből, gyorsítótár-biztos” módot, és hagyd kikapcsolva
a „Elrejtés keresőrobotok elől” opciót.

Mentéskor a plugin üríti a felismert gyorsítótár-bővítményeket (Nginx Helper, LiteSpeed,
WP Rocket stb.). Ha a szerveren bővítmény nélküli gyorsítótár fut (pl. nginx FastCGI cache
Nginx Helper nélkül, Varnish, Cloudflare), azt egy beállítás-módosítás után kézzel kell
üríteni, különben a látogatók a régi blokkolási listát kapják.

= Mi történik, ha a látogató visszavonja a hozzájárulását? =

A plugin alapértelmezetten újratölti az oldalt. Erre azért van szükség, mert a már
lefutott mérőkódokat utólag nem lehet leállítani.

= Tárol személyes adatot a napló? =

IP-címet nem. Az IP-ből saltinggal képzett SHA-256 lenyomatot képez, ami önmagában nem fejthető
vissza. Az URL és a böngészőazonosító mentése külön ki-be kapcsolható.

== Changelog ==

= 1.2.2 =
* A Tailwind CSS „Play CDN” (`cdn.tailwindcss.com`) felkerült a beépített biztonságos külső
  források közé. Az ezzel épített oldalakon (pl. aifuture.sze.hu) a szigorú mód eddig
  ismeretlen, marketing kategóriás forrásnak vette. Elfogadás előtt így az oldal teljes CSS-e
  hiányzott: szétesett fejléc, rossz betűtípusok és logók. A Tailwind nem rak le sütit és nem
  követ, csak a böngészőben állítja elő a CSS-t.
* Új: a beállítások vagy a háttéradatbázis mentésekor, a plugin be- és kikapcsolásakor és
  frissítés után a plugin üríti a felismert oldal-gyorsítótárakat: Nginx Helper (nginx FastCGI),
  LiteSpeed, WP Rocket, W3 Total Cache, WP Super Cache, WP Fastest Cache, Cache Enabler,
  SiteGround, Breeze, Hummingbird. A blokkolás és a kivételek a HTML-be íródnak, ezért eddig
  egy módosítás a gyorsítótárazott oldalakon csak a gyorsítótár lejárta után érvényesült.
  Ezért tűnt úgy, hogy egy frissen felvett kivétel „nem működik”. Mentés után az értesítés
  kiírja, mely gyorsítótárakat ürítette. Szerveroldali gyorsítótárat, amelyhez nincs WordPress-bővítmény
  (nginx FastCGI Nginx Helper nélkül, Varnish, Cloudflare), továbbra is kézzel kell
  üríteni. Kikapcsolható a `szesuti_purge_page_cache` szűrővel, saját gyorsítótár a
  `szesuti_purged_page_caches` szűrővel köthető be.

= 1.2.1 =
* A frissítések forrása mostantól a https://github.com/SZKK-SZUCS/SZetem-ny- repó (az SZKK-SZUCS
  szervezet alatt, az SZEducate mellett), a korábbi személyes LodThetherneon/SZEtemeny helyett.
  Így a kiadás nem egy személyes fiókhoz kötődik. Az 1.2.0 még a régi repóból frissül, ezért
  arra az 1.2.1-et egyszer kézzel kell feltölteni (vagy a régi repóban kiadni), utána az
  oldalak már az új repót figyelik.
* A bővítmény szerzője a Bővítmények listában: Kiss Zoltán – SZE Műszaki Fejlesztési és
  Üzemeltetési Igazgatóság, a link a https://munkatars.sze.hu/mfui-uzemeltetesi-referens
  oldalra visz (eddig a tesztoldalra mutatott).

= 1.2.0 =
* Új: automatikus frissítés a GitHub-repó kiadásaiból (Release), a beépített Plugin Update
  Checker 5.7 könyvtárral, ugyanúgy, mint az SZEducate-ben. Ha a repóban új Release jelenik meg,
  minden oldal Bővítmények listájában megjelenik a „Frissítés elérhető” jelzés, és egy
  kattintással, WP-CLI-vel vagy automatikus frissítéssel települ. Eddig 100 oldalon kézzel
  kellett ZIP-et feltölteni, vagy saját update.json-t kellett üzemeltetni.
* A frissítő csak a Release-hez csatolt `szetemeny-x.y.z.zip` mellékletet telepíti. A GitHub
  automatikus forrás-ZIP-je a teljes repó (a plugin egy almappában van). Ebből rossz szerkezetű,
  inaktív plugin lenne, ezért a frissítő ezt kifejezetten kizárja.
* A saját manifest URL (Eszközök fül) megmaradt. Ha ki van töltve, a GitHub helyett azt
  használja (pl. belső tükör), a formátum változatlan.
* Új, opcionális `SZESUTI_GITHUB_TOKEN` konstans a `wp-config.php`-ben, arra az esetre, ha egy
  szerverről sok oldal kérdez, és elfogyna a GitHub óránkénti kerete (60 kérés/IP). Egy
  ellenőrzés egyetlen API-kérés.
* Az ellenőrzés csak adminban, WP-cronban és WP-CLI-ben fut, a látogatói oldalakat nem lassítja.
  A `wp plugin update szesuti` mindig friss ellenőrzéssel indul, így tömeges frissítésnél nem
  kell kivárni a 12 órás ütemezést.
* Az 1.1.x-ről erre a verzióra még egyszer kézzel kell frissíteni (ZIP feltöltése). Ettől
  kezdve a frissítések maguktól érkeznek.

= 1.1.4 =
* Javítás: elfogadás után a beágyazott térkép vagy videó nem jelent meg, ha a téma az iframe-et
  abszolút pozícióval méretezi (16:9-es keret, `height:100%`, levágott My Maps fejléc). A helyőrző
  doboz az iframe körül maradt, 0 magasra zsugorodott és levágta. Mostantól elfogadáskor a
  helyőrző teljesen eltűnik, az iframe pedig visszakerül az eredeti helyére.
* Javítás: ilyen keretben a helyőrző elfogadás előtt a keret alá csúszott vagy kilógott belőle;
  most a keretet tölti ki, így az ikon és a gomb a beágyazás helyén látszik.
* A Google „Saját térképek” (My Maps, `google.com/maps/d/…`) beágyazását a plugin mostantól
  Google Maps-ként ismeri fel (Funkcionális kategória). Korábban szigorú módban ismeretlen
  forrásként a Marketing kategóriához kötötte.

= 1.1.3 =
* Javítás: a Háttéradatbázis exportja alapból csak a „saját és importált” bejegyzéseket
  tartalmazta, ami egy friss oldalon üres – a letöltött JSON-ban nem volt egyetlen süti sem.
  Mostantól az oldal sütilistája is bekerül, az export doboz pedig kiírja, hány süti lesz a fájlban.
* Import: egyetlen adatbázis-írás a végén (korábban bejegyzésenként egy – nagy fájlnál lassú volt).
* Import: a Jegyzettömbben elmentett (BOM-os) JSON fájl is beolvasható – a beállítás-importnál is.
* Import: az eredményüzenet külön jelzi a beépítettként már ismert és az érvénytelen bejegyzéseket;
  ha semmi nem változott, figyelmeztetés jelenik meg.
* Import felülírással: a beépítettekkel együtt exportált fájl nem másolja le feleslegesen a
  beépített sütiket saját bejegyzésnek (csak ha az adataik ténylegesen eltérnek).
* Export: más bővítmény kósza kimenete nem kerülhet a letöltött fájl elejére.

= 1.1.2 =
* Javítás: a PixelYourSite bővítmény sütijei (`pys_*`, `last_pys_*`) eddig hozzájárulás nélkül
  is lefutottak. A bővítmény a saját szkriptjeit a weboldal domainjéről (első féltől) tölti,
  és nem szerepelt a felismert szolgáltatások között, a szigorú mód „ismeretlen forrás” szűrője
  pedig csak a külső kiszolgálókra vonatkozik – így mindig átcsúszott. Mostantól a
  PixelYourSite felismert (marketing) szolgáltatás: elutasításkor nem tölt be, elfogadáskor
  ugyanúgy feloldódik, mint a többi mérőkód.
* A sütilista kiegészült a PixelYourSite sütijeivel (`pys_*`, `last_pys_*`).

= 1.1.1 =
* A Beállítások, a Háttéradatbázis és a Naplózás oldal teljes szélességű (korábban egy
  fix maximális szélességre volt korlátozva).
* Wipe: a megerősítő szó nem másolható ki a felületről, és az űrlapba nem illeszthető be
  (beillesztés, húzd-és-ejtsd és jobb kattintásos beillesztés is tiltva) – tényleg végig
  be kell gépelni, kis- és nagybetű pontosan.

= 1.1.0 =
* Új: Teljes törlés (wipe) az Eszközök fülön. Egy mozdulattal mindent töröl, és a plugint a
  friss telepítés utáni állapotba hozza: a hozzájárulás-naplót, a Háttéradatbázis saját és
  importált sütijeit, a szkennelési eredményeket, és minden beállítást gyári alapértékre állít
  (a sütilista a kezdő listára, új saltinggal). Véletlenül nem indítható: a gomb csak a WIPE
  szó beírása után él, és még egyszer rákérdez. Alapból a látogatókat is újra döntésre kéri
  (a szabályzat verziója eggyel nő), mert a napló törlése után a korábbi hozzájárulásokra
  nincs bizonyíték.
* A Sütiszkenner leírása mellé kép került (asztali nézetben).
* Javítás: a szkenner „Leállítás és mentés” gombja szkennelésen kívül is látszott.

= 1.0.7 =
* Javítás (valós E2E teszt alapján): szigorú módban a Microsoft Clarity csak a marketing
  elfogadásával indult el, a „csak statisztika” hozzájárulás nem volt elég. A tag a tényleges
  kódot a scripts.clarity.ms-ről tölti be, ezt a blokkoló ismeretlen (marketing) kiszolgálónak
  vette. Most a Clarity (*.clarity.ms) és a Hotjar mérési kiszolgálói (*.hotjar.io,
  insights.hotjar.com) is a statisztikához tartoznak. A cégek honlapjára mutató linkek
  (www.hotjar.com, clarity.microsoft.com) nem okoznak téves találatot a szkennerben.
* A szkenner a Hotjar mérési végpontját (metrics.hotjar.io) már nem sorolja az ismeretlen
  kiszolgálók közé.

= 1.0.6 =
* Javítás (valós E2E teszt alapján): a szkenner szerveroldali része a plugin saját
  szkriptjeiben lévő szolgáltatás-mintákat is „felismerte”, így az oldalon nem létező
  szolgáltatásokat és sütiket jelzett (pl. Barion, Criteo, Google Ads). Ez a teljes és a
  gyors szkennelést is érintette.
* Javítás: WordPress 6.7+ alatt a hibanaplóba minden admin-kérésnél figyelmeztetés került
  a túl korai fordítás-betöltés miatt.
* A szkenner az ismeretlen külső kiszolgálók közül kihagyja a felismert szolgáltatásokét.
* 18 új ismert süti valós mérés alapján (TikTok, LinkedIn, YouTube, Vimeo, Spotify,
  reCAPTCHA, X, Microsoft), leírással.

= 1.0.5 =
* Új menüpont: Háttéradatbázis. Itt gyűlik össze minden ismert sütifajta minden adatával
  (beépített, az oldalon leírással mentett – automatikusan –, és importált). Kereshető,
  szűrhető, szerkeszthető; exportálható és importálható, ugyanaz a süti nem kerül be kétszer.
  A szkenner és a Sütilista innen ismeri fel a sütiket; a Sütilistán a név beírásakor a
  többi adat magától kitöltődik.
* Az élő előnézet magassága húzással (vagy a nyilakkal) állítható, alsó és felső korláttal;
  asztali és mobil nézetre külön megjegyzi. Összecsukva az „Előnézet megjelenítése” gomb
  görgetéskor is fent marad.
* A „só” megnevezés helyett mindenhol „salting”.

= 1.0.4 =
* Teljes sütiszkenner: a böngészőben végigjárja az egész oldalt (minden oldal, bejegyzések,
  archívumok, a menük és a tartalom belső linkjei), minden oldalt legörget, és a JavaScriptből
  beállított sütiket, a böngészős tárolókat és az ismeretlen külső kiszolgálókat is megtalálja.
  Élő előnézet és folyamatjelző; 150 oldal kb. 2–3 perc. A régi szerveroldali mód megmaradt.
* A Beállítások gomb színei (háttér, szöveg, keret) külön állíthatók; üresen hagyva a
  korábbi, automatikus megjelenés marad.
* Admin: szélesebb felület, a fejlécből kikerült a süti ikon, az előnézet gombjának ikonja
  egy vonalban a szöveggel; az élő előnézet az alacsony keretben is az asztali megjelenést mutatja.

= 1.0.3 =
* Szigorú blokkolás (alapból bekapcsolva): minden ismeretlen idegen domainről érkező
  szkript, beágyazás, stíluslap és követőpixel visszatartva a hozzájárulásig. A saját
  domain, az aldomainek és a biztonságos könyvtár-CDN-ek kivételek.
* Futásidejű védelem: a JavaScriptből később beszúrt szkripteket, iframe-eket, képeket,
  valamint a fetch / XHR / sendBeacon adatküldést is visszatartja.
* 20 új felismert szolgáltatás (pl. Microsoft Ads, TikTok, Pinterest, LinkedIn, Taboola,
  Gemius, Cloudflare Web Analytics, Jetpack Stats, Zendesk, Tidio, Calendly).
* Consent Mode: a korábbi döntést a böngésző olvassa ki a sütiből, így oldal-gyorsítótár
  mellett sem kerülhet ki más látogató „elfogadott” állapota. Szigorú módban nincs
  GTM noscript-iframe.
* Minden gomb elérhető marad bármilyen megjelenési beállításnál (betűméret, szélesség,
  hosszú szövegek, kis képernyő); az olvashatatlan színpárokat automatikusan javítja,
  és az adminban figyelmeztet rá. Üres gombfelirat helyett alapértelmezett szöveg jelenik meg.
* Admin: SZE logó és készítő a fejlécben; a fontos kapcsolók kiemelve, bekapcsolva pirosak;
  az élő előnézet elrejtése valóban eltünteti az előnézetet.
* Import: régebbi verzió exportja nem kapcsolja ki az újabb védelmi beállításokat.
* Készítő: Műszaki Fejlesztési és Üzemeltetési Igazgatóság.

= 1.0.2 =
* Új név: SZetemény (a belső azonosítók – mappa, shortcode-ok, süti neve – változatlanok,
  így a frissítés a meglévő beállításokat megtartja).
* Témabiztos megjelenés: a téma gomb-, címsor-, táblázat- és linkstílusai (pl. Elementor
  globális gombstílusa) már nem írják felül a bannert és a beállítások ablakot.
* Élő előnézet a beállítások fölött: banner, beállítások ablak, lebegő gomb; asztali és
  mobil nézet; mentés nélkül mutatja a változtatásokat.
* Ikonválasztó: beépített ikonok, pixeles süti, vagy saját kép a médiatárból / feltöltve.
  A lebegő gomb ikonja is választható.
* Új, rendezett teljes szélességű sáv: balra a szöveg, jobbra egy sorban a gombok.
* Szebb csúszkák az adminban.
* Javítás: mentés után az oldal nem ugrik a tetejére, és nem rándul meg; a sikerüzenet
  lebegő értesítésként jelenik meg.

= 1.0.1 =
* Javítás: az 1.0.0-s ZIP fordított perjellel tárolta az útvonalakat, ezért Linuxos
  tárhelyen nem lehetett aktiválni („A bővítmény fájl nem létezik”) és törölni sem.
  Az új ZIP szabályos. A régi, beragadt telepítés maradványát az 1.0.1 felismeri,
  és egy gombbal törölhetővé teszi.
* Javítás: a mérőkódot akkor is blokkolja, ha a kódban szerepel a „szesuti” szó.
* Javítás: a [szesuti_settings_button] gomb a banneren kívül is működik.
* Javítás: a süti tájékoztató oldal elrendezése (gomb, táblázat, sütinevek tördelése).
* Javítás: a lebegő gomb nem látszik a nyitott beállítások ablak alatt.

= 1.0.0 =
* Első kiadás.
