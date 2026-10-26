# SZetemény – sütikezelő WordPresshez

GDPR/ePrivacy-kompatibilis sütikezelő (CMP) WordPresshez, a Széchenyi István Egyetem
saját oldalaira. Fizetős CMP-k (Cookiebot, Complianz Pro stb.) kiváltására készült, kb.
100 oldalon való egyszerre üzemeltetésre.

Készítette: **Műszaki Fejlesztési és Üzemeltetési Igazgatóság** (Széchenyi István Egyetem)

- Verzió: **1.2.2** · WordPress 5.6+ · PHP 7.2+
- Telepítési és bevezetési útmutató: **[TELEPITES.md](TELEPITES.md)**
- Változásnapló: [szesuti/readme.txt](szesuti/readme.txt) (Changelog szakasz)

> A látható név **SZetemény**, a belső azonosítók viszont szándékosan `szesuti`-k
> maradtak (mappa, opciók, shortcode-ok, `szesuti_consent` süti, CSS-osztályok). Ha ezeket
> átneveznénk, a frissítés eltörné a már telepített oldalakat.

---

## Mit csinál?

A plugin nem csak kirak egy bannert, hanem **ténylegesen megakadályozza**, hogy a
mérőkódok, beágyazások és követőpixelek hozzájárulás nélkül lefussanak. Ezen felül:

| Funkció | Röviden |
|---|---|
| Szkriptblokkolás | Szerveroldali HTML-átírás és futásidejű JS-őr. **Szigorú mód alapból BE**: minden ismeretlen idegen domain blokkolva. |
| 35+ felismert szolgáltatás | GA4, Google Ads, GTM, Meta Pixel, Hotjar, Clarity, LinkedIn, TikTok, PixelYourSite, YouTube, Vimeo, Maps, élő chatek stb. |
| Google Consent Mode v2 | Mind a hét jelzés. Alapból ki van kapcsolva, oldalanként kapcsolható be. |
| Hozzájárulás-napló | Saját táblában, saltinggal képzett IP-lenyomattal, CSV exporttal és automatikus lejárattal. |
| Sütiszkenner | Böngészőben végigjárja az egész oldalt, és a talált sütiket felveszi a sütilistába. |
| Háttéradatbázis | Az összes ismert sütifajta. Oldalak között exporttal/importtal körbeadható. |
| Többnyelvűség, geo | HU/EN beépítve, WPML/Polylang, EU/EGT-célzás gyorsítótár-biztos módban. |
| Tömeges üzemeltetés | JSON beállítás-export/import, `szesuti_settings` szűrő mu-pluginból, automatikus frissítés a GitHub Release-ekből (Plugin Update Checker). |

---

## Hogyan működik?

### 1. Egy oldalbetöltés útja

```
Böngésző kérés
   │
   ▼
PHP (WordPress)
   ├─ SZESUTI_GCM        → <head> legelején: gtag('consent','default', …)   [ha be van kapcsolva]
   ├─ SZESUTI_Blocker    → <head> legelején: szesuti-guard.js inline (futásidejű őr)
   ├─ SZESUTI_Blocker    → a teljes HTML kimenet pufferelve, a blokkolandó elemek átírva:
   │                        <script src=…>  →  <script type="text/plain" data-szesuti-src=… data-szesuti="marketing">
   │                        <iframe src=…>  →  helyőrző + data-szesuti-src
   └─ SZESUTI_Frontend   → banner HTML (templates/banner.php) + szesuti.js + CSS
   │
   ▼
Böngésző
   ├─ szesuti-guard.js   → elfogja a később, JS-ből beszúrt elemeket és kéréseket
   │                        (createElement/src, innerHTML, document.write, new Image,
   │                         fetch, XHR, sendBeacon)
   └─ szesuti.js         → kiolvassa a szesuti_consent sütit
                           ├─ nincs döntés  → banner
                           └─ van döntés    → az engedélyezett kategóriák elemeit feloldja
                                              (type és src visszaállítása), consent update
```

A látogató döntését **mindig a böngésző olvassa ki a sütiből**, a szerver nem írja bele
az oldalba. Ezért működik teljes oldalas gyorsítótárral (WP Rocket, LiteSpeed) is: a
gyorsítótárban mindig a blokkolt változat van, és a feloldás a böngészőben történik.

### 2. A blokkolási döntés (`SZESUTI_Blocker::classify()`)

Minden külső forrásra (szkript, iframe, stíluslap, pixel) ugyanez a sorrend fut – a szerveren
PHP-ban, a böngészőben a `szesuti-guard.js`-ben azonos logikával:

1. saját fájl vagy kézzel engedélyezett kivétel → **átmegy**
2. ismert, bekapcsolt szolgáltatás → **blokkolva**, a saját kategóriájával
3. ismert, de tudatosan kikapcsolt szolgáltatás (pl. reCAPTCHA, Google Fonts) → átmegy
4. saját domain és aldomainjei → átmegy
5. beépített biztonságos CDN (cdnjs, jsDelivr stb.) → átmegy
6. **minden más idegen forrás → szigorú módban blokkolva** (alapból a Marketing hozzájárulással oldódik fel)

A saját domainen futó mérőkódokat (pl. saját Matomo, PixelYourSite) fájlnévminta alapján
ismeri fel. Ami szerveroldalon, PHP-ból hív külső API-t, azt a HTML-alapú blokkoló nem
látja – arra a `szesuti_has_consent()` függvény való.

### 3. Hozzájárulás

- A döntést a `szesuti_consent` süti tárolja (név, élettartam, domain állítható): az
  elfogadott kategóriák (`cats`), a szabályzat verziója (`pv`), az időbélyeg (`t`), egy
  véletlen döntésazonosító (`id`) és a művelet (`act`).
- Ha a **szabályzat verziója** nő, minden látogató újra dönt.
- A döntést a böngésző a `POST /wp-json/szesuti/v1/consent` végpontra küldi, ez írja a
  naplótáblát (`{prefix}szesuti_consent_log`). A végpont nyilvános, ezért szigorú
  validáció és sebességkorlát védi.
- Visszavonáskor az oldal alapból újratölt, mert a már lefutott mérőkódot nem lehet leállítani.

### 4. Sütilista, szkenner, Háttéradatbázis

```
      Sütiszkenner ──talál──▶ Sütilista (oldalanként, a beállításokban) ──▶ [szesuti_cookie_table]
           │                        │  mentéskor a leírással ellátott,
           │ felismerés             │  nem beépített sütik bekerülnek ▼
           ▼                        ▼
      Háttéradatbázis = beépített sütik (SZESUTI_Cookie_DB)
                      + saját / importált bejegyzések (opció: szesuti_cookie_library)
                                    ▲
                     export / import JSON más oldalakról
```

- A **szkenner** az admin böngészőjében, kis keretekben nyitja meg az oldal lapjait
  „szkennelő módban”, amelyben nincs blokkolás. Ez csak bejelentkezett adminnak, egyszeri
  kóddal működik. Minden lapot legörget, követi a belső linkeket, a végén pedig törli
  az általa létrehozott sütiket. Van gyors, szerveroldali módja is (`Set-Cookie` fejlécek alapján).
- A **Háttéradatbázis** egy név csak egyszer szerepelhet benne (kis- és nagybetűtől
  függetlenül), a `*` bármilyen karaktersort helyettesít (`_ga_*`). A saját bejegyzés
  felülírja az azonos nevű beépítettet.
- **Export:** a saját és importált bejegyzések, valamint az oldal teljes sütilistája;
  kérésre a beépítettek is.
- **Import:** a háttéradatbázis-exportot, a beállítás-exportot (annak sütilistáját) és egy
  sima bejegyzéstömböt is elfogad. Alapból csak kiegészít (új nevet felvesz, meglévőnél csak
  az üres mezőket tölti ki). Felülírás módban a meglévő adatokat cseréli.

### 5. Beállítások és tömeges üzemeltetés

- Minden beállítás egyetlen opcióban van: `szesuti_settings` (szekciók: `general`, `geo`,
  `appearance`, `categories`, `languages`/`texts`, `blocker`, `gcm`, `cookies` (a sütilista),
  `log`, `advanced`).
- **Beállítás-export/import** (Eszközök fül): egy mintaoldalról a többire. Régebbi export
  importjakor a hiányzó új kapcsolók az alapértéküket kapják, így egy régi export nem
  gyengíti a védelmet.
- **`szesuti_settings` szűrő** mu-pluginból: kódból felülírható bármi, és ez nem ég bele az
  adatbázisba (lásd TELEPITES.md, 3. rész, 4. lépés).
- **Frissítés:** `SZESUTI_Updater` a beépített [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)
  (v5, `szesuti/vendor/`) segítségével a repó GitHub Release-eit figyeli. Új Release esetén a
  WordPress normál „Frissítés elérhető” felületén jelenik meg. Csak a Release-hez csatolt
  `szetemeny-x.y.z.zip`-et telepíti, a GitHub forrás-ZIP-jét nem (abban a plugin almappában
  van). Belső tükörhöz JSON manifest URL is megadható. Részletek: TELEPITES.md, 4. rész.

### 6. Eltávolítás és teljes törlés

- **Wipe** (Eszközök fül): minden adatot töröl (naplót, háttéradatbázist, szkennelési
  eredményeket), és gyári állapotba teszi a beállításokat. Csak a megerősítő szó begépelése után indul.
- **Törlés** a Bővítmények oldalon: az `uninstall.php` minden opciót, átmeneti adatot és
  a naplótáblát eltávolítja.

---

## Könyvtárszerkezet

```
szesuti/                         ← maga a plugin (ez kerül a ZIP-be)
  szesuti.php                    ← belépési pont, SZESUTI_Plugin: komponensek összekötése
  uninstall.php
  readme.txt                     ← WP-formátumú leírás + Changelog
  includes/
    class-szesuti-settings.php   ← alapértékek, sanitize, mentés, export/import
    class-szesuti-blocker.php    ← HTML-átírás + az őr konfigurációja (classify)
    class-szesuti-cookie-db.php  ← beépített szolgáltatás- és sütiadatbázis
    class-szesuti-library.php    ← Háttéradatbázis (saját + importált sütik)
    class-szesuti-scanner.php    ← sütiszkenner (szerveroldali rész)
    class-szesuti-frontend.php   ← banner, eszközök, dinamikus CSS
    class-szesuti-gcm.php        ← Google Consent Mode v2
    class-szesuti-consent.php    ← hozzájárulás kiolvasása PHP-ban
    class-szesuti-log.php        ← hozzájárulás-napló (tábla, takarítás)
    class-szesuti-rest.php       ← /szesuti/v1/consent és /geo
    class-szesuti-geo.php, -i18n.php, -colors.php, -icons.php, -shortcodes.php
    class-szesuti-updater.php    ← frissítés GitHub Release-ből (PUC)
    class-szesuti-wipe.php, -cleanup.php
    class-szesuti-admin*.php     ← admin menü, mentés, eszközök, Háttéradatbázis, napló
    admin/tab-*.php              ← az admin fülek tartalma
  assets/js/
    szesuti-guard.js             ← futásidejű őr (inline, a <head> legelején)
    szesuti.js                   ← banner és feloldás
    szesuti-scanner.js           ← böngészős szkenner
    szesuti-admin.js             ← admin felület
  assets/css/, assets/img/
  templates/banner.php           ← témából felülírható: <téma>/szesuti/banner.php
  vendor/plugin-update-checker/  ← Plugin Update Checker 5.7 (MIT, külső könyvtár, ne módosítsd)
takarito/szesuti-takarito/       ← külön bővítmény a hibás 1.0.0-s csomag maradványához
build.ps1                        ← telepíthető ZIP készítése a dist/ mappába
TELEPITES.md                     ← telepítés, bevezetés 100 oldalra, hibakeresés
```

---

## Fejlesztés

### Csomagolás

```powershell
.\build.ps1                  # dist\szetemeny-<verzió>.zip
.\build.ps1 -Version 1.2.0   # a verziószámot is átírja (plugin fejléc, konstans, Stable tag)
```

Mellé elkészül a `dist\szetemeny-<verzió>-release.md` is (a changelog bejegyzés). Kiadás:
commit + push, majd GitHubon új Release `v<verzió>` taggel, ezzel a leírással és a ZIP-pel
mellékletként. Ettől frissül a többi oldal (TELEPITES.md, 4. rész).

A `build.ps1` ellenőrzi, hogy a ZIP-ben minden útvonal perjeles és a `szesuti/` mappa
alatt van. **Ne használd a `Compress-Archive`-ot**: Windows PowerShell 5.1 alatt fordított
perjellel csomagol, így a plugin Linuxos tárhelyen aktiválhatatlan és törölhetetlen lesz
(ebből lett az 1.0.0-s hiba). A `dist/` mappa nincs verziókezelve, a ZIP mindig újraépíthető.

### Ellenőrzés kiadás előtt

1. PHP-lint 7.2-vel és 8.x-szel (a plugin PHP 7.2-t ígér).
2. Kipróbálás egy helyi WordPressen. A frontend-változtatásokat olyan témával is nézd meg,
   amelyik agresszíven felülírja a gombstílusokat (Elementor-szerű témák).
3. **Linuxon is** telepítsd az új ZIP-et (Dockerben). A Windows elfedi a csomagolási hibákat.
4. Frissítési próba: régi verzió → új ZIP „Csere a feltöltöttre”, és a beállítások megmaradnak-e.

### Szabályok, amelyekre új beállításnál figyelni kell

- Kerüljön bele a JSON export/importba, és legyen alapértéke a `defaults()`-ban, hogy a
  régi exportok importja ne kapcsolja ki.
- Felülírható legyen a `szesuti_settings` szűrővel.
- Gondold végig, hogyan viselkedik teljes oldalas gyorsítótár mellett: a látogatóra
  vonatkozó döntést ne a szerver írja a HTML-be.
- Kényelmi kompromisszumnál az alapértelmezés mindig a **szigorúbb** irány.
- Saját frontend CSS mindig `#szesuti-root` előtaggal (a téma felülírásai ellen).

### Fejlesztői felület

```php
szesuti_has_consent( 'analytics' );  // bool
szesuti_has_decision();              // bool
```

```js
SZESUTI.hasConsent( 'marketing' );
SZESUTI.showPreferences();
document.addEventListener( 'szesuti:change', e => console.log( e.detail ) );
```

Szűrők: `szesuti_settings`, `szesuti_services`, `szesuti_known_cookies`,
`szesuti_blocker_rules`, `szesuti_safe_hosts`, `szesuti_js_config`, `szesuti_should_display`,
`szesuti_run_blocker`, `szesuti_current_language`, `szesuti_visitor_country`,
`szesuti_banner_template`, `szesuti_update_url`, `szesuti_update_repository`.

Kézi megjelölés: `<script type="text/plain" data-szesuti="marketing" data-szesuti-src="…">`,
kivétel: `data-szesuti-ignore="1"`.

---

## Licenc

GPL-2.0-or-later
