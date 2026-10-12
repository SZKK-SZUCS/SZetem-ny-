# SZetemény – telepítés és bevezetés

Készítette: **Műszaki Fejlesztési és Üzemeltetési Igazgatóság** (Széchenyi István Egyetem)

Kész, telepíthető csomag: **`dist/szetemeny-1.2.1.zip`**

> **Az 1.0.0-s ZIP hibás volt, ne használd.** Linuxos tárhelyen nem lehetett sem
> aktiválni („A bővítmény fájl nem létezik”), sem törölni. Ha már feltetted, lásd a
> [0. pontot](#0-ha-korábban-az-100-t-telepítetted).

---

## 0. Ha korábban az 1.0.0-t telepítetted

A hibás csomag a szerveren hagyott egy mappát (`szesuti-1.0.0` vagy `szesuti` néven),
amit a WordPress **semmilyen módon nem tud törölni**: a „Törlés” sikert jelez, de nem
töröl semmit, a „Csere a feltöltöttre” pedig hibával leáll. Ne próbálkozz vele tovább.

**A) A takarító bővítménnyel, FTP nélkül (ajánlott, minden esetben működik)**

1. Bővítmények → Új hozzáadása → Bővítmény feltöltése → **`dist/szetemeny-takarito.zip`** → Telepítés most
2. Kattints az **Aktiválás** gombra.
3. A Bővítmények oldal tetején zöld üzenet jelenik meg:
   *„SZetemény takarító: Kész. Törölve: wp-content/plugins/szesuti-1.0.0 (36 fájl).”*
   A régi, hibás SZESÜTI eltűnik a listából. A takarító magától kikapcsol.
4. A takarítónál kattints a **Törlés** linkre – ez már rendesen törlődik.
5. Töltsd fel és kapcsold be a `dist/szetemeny-1.2.1.zip`-et (1. pont).

A takarító kizárólag a hibás csomag maradványát törli: egy `szesuti\szesuti.php` **nevű**
fájlról ismeri fel, ilyen szabályos pluginban nem létezik. Más pluginhoz nem nyúl.

*(Ha az 1.0.1 vagy újabb már fent van és aktív, a Bővítmények oldalon egy „Maradvány törlése” gomb
is megjelenik – az ugyanezt teszi. A takarító akkor is működik, ha az új verzió fel sem tölthető,
mert a hibás mappa `szesuti` néven foglalja a helyét.)*

**B) FTP-n vagy a tárhely fájlkezelőjében**

Töröld a `wp-content/plugins/szesuti-1.0.0` (vagy `szesuti`) mappát – azt, amelyikben
`szesuti\szesuti.php`-hoz hasonló nevű fájlokat látsz.

---

## 1. Telepítés egy oldalra

### A) ZIP-ből (ezt használd élesben)

1. WordPress admin → **Bővítmények → Új hozzáadása → Bővítmény feltöltése**
2. Válaszd ki a `dist/szetemeny-1.2.1.zip` fájlt → **Telepítés most**
3. **Bekapcsolás**

A ZIP gyökerében a `szesuti` mappa van, tehát közvetlenül feltölthető. A WordPress
a `wp-content/plugins/szesuti` mappába telepíti, a ZIP fájlnevétől függetlenül.

**Frissítésnél** (ha a `szesuti` már fent van): töltsd fel az új ZIP-et ugyanígy,
a WordPress felajánlja a **„Csere a feltöltöttre”** gombot. A beállítások megmaradnak.
Az 1.2.1-től erre csak egyszer van szükség, utána a frissítés magától érkezik (lásd 4. rész).

### B) FTP / SSH-n (fejlesztéshez)

Másold a `szesuti` mappát ide: `wp-content/plugins/szesuti`, majd kapcsold be a
Bővítmények menüben.

---

## 2. Első beállítás (kb. 10 perc)

A bal oldali menüben megjelenik a **SZetemény** menüpont.

| Fül | Mit csinálj |
|---|---|
| **Általános** | Add meg az **adatkezelési tájékoztató** linkjét. Ez a legfontosabb mező. |
| **Szkriptblokkolás** | A **szigorú mód** alapból be van kapcsolva (lásd 6. pont). Nézd át a fül alján a **„legutóbb visszatartott ismeretlen források”** listáját: ha valami az oldal működéséhez kell, vedd fel a kivételek közé. |
| **Consent Mode** | Csak akkor kapcsold be, ha GA4 / Google Ads / GTM fut az oldalon. |
| **Sütilista** | Indítsd el a **teljes szkennelést** (150 oldal kb. 2–3 perc), majd add hozzá a talált sütiket. Az ismeretlen sütiknek írj leírást – mentéskor ezek a Háttéradatbázisba is bekerülnek. |
| **Háttéradatbázis** (külön menüpont) | Ha van már más oldalról exportált háttéradatbázis, **importáld** – így ez az oldal is felismeri azokat a sütiket. |
| **Megjelenés** | Színek, elrendezés, ikon. Fent az **élő előnézet** mentés nélkül mutatja a változtatást (asztali és mobil nézet). |
| **Eszközök** | Nézd meg a **Rendszerállapot** listát – minden pipa legyen zöld. |

### Ellenőrzés élesítés előtt

1. Nyisd meg az oldalt **privát ablakban**.
2. Megjelenik a banner bal alul.
3. F12 → **Hálózat** fül → frissíts. **Nem indulhat kérés** a `google-analytics.com`,
   `googletagmanager.com`, `connect.facebook.net` – és szigorú módban semmilyen más
   idegen domain felé (kivéve a biztonságos könyvtár-CDN-eket, pl. `cdnjs.cloudflare.com`).
4. Kattints az **Összes elfogadása** gombra → most már indulniuk kell.
5. F12 → **Alkalmazás → Sütik** → ott kell lennie a `szesuti_consent` sütinek.

Ha Consent Mode-ot használsz, a konzolba írd be: `dataLayer`. Az első elemek közt
ott kell lennie egy `consent / default` hívásnak, elfogadás után pedig egy
`consent / update` hívásnak.

---

## 3. Kigurítás 100 oldalra

### Lépés 1 – mintaoldal

Állítsd be **egy** oldalon tökéletesre, majd:
**SZetemény → Eszközök → Exportálás → JSON letöltése**

A „naplózási salting is kerüljön bele” jelölőnégyzetet **hagyd üresen** – így minden
oldal saját saltingot kap, és a látogatók nem lesznek oldalak között összekapcsolhatók.

### Lépés 2 – a többi oldalon

Telepítés és bekapcsolás után:
**SZetemény → Eszközök → Importálás** → töltsd fel a JSON-t.

Hagyd bepipálva az **„oldal saját linkjei maradjanak meg”** opciót, így az
adatkezelési tájékoztató URL-je oldalanként helyes marad.

Régebbi verzióból (pl. 1.0.2) készült export is importálható: az azóta bejött új
beállítások (szigorú mód, futásidejű védelem) ilyenkor az alapértéküket kapják, vagyis
**bekapcsolva maradnak** – egy régi export nem gyengíti a védelmet.

### Lépés 2b – a Háttéradatbázis körbeadása

A **SZetemény → Háttéradatbázis** menüpontban gyűlik össze minden sütifajta, amit a plugin
ismer: a beépítettek, az adott oldalon leírással mentett sütik (automatikusan), és az
importáltak. A szkenner és a Sütilista innen ismeri fel a sütiket. Javasolt munkamenet:

1. Minden oldalon, ahol szkenneltél és leírtad az ismeretlen sütiket:
   **Háttéradatbázis → Exportálás → JSON letöltése**. A fájlba a saját és importált
   bejegyzések mellett az oldal teljes sütilistája is bekerül; az export doboz kiírja, hány
   süti lesz benne. A beépítetteket csak külön kérésre teszi bele – a másik oldal úgyis ismeri őket.
2. Egy „gyűjtő” oldalon importáld mindet egymás után. Ugyanaz a süti **nem kerül be kétszer**
   (kis- és nagybetűtől függetlenül); egy meglévő bejegyzésnek csak az üres mezői egészülnek
   ki (pl. hiányzó angol leírás), kivéve, ha bejelölöd a felülírást.
3. A gyűjtő oldalról exportáld az egészet, és ezt importáld az új oldalakra. Így egy új
   oldalon már az első szkennelés is felismeri azokat a sütiket, amiket máshol leírtál.

Import után az üzenet megmondja, hány süti lett új, hány egészült ki, és hány maradt ki,
mert beépítettként már ismert (ezeket a „felülírás” bejelölésével lehet saját változatra
cserélni). Ide a SZetemény **beállítás-exportja** is importálható – ilyenkor annak sütilistája kerül be.
A Sütilista fülön elég egy új süti nevét beírni: ha a Háttéradatbázis ismeri, a többi adata
magától kitöltődik.

### Lépés 3 – oldalspecifikus finomhangolás

Oldalanként csak ezt kell ellenőrizni:

- adatkezelési tájékoztató és süti tájékoztató linkje,
- GTM azonosító (ha Consent Mode-ot használsz),
- sütiszkenner lefuttatása (minden oldalon más külső kód futhat).

### Lépés 4 (opcionális) – deployból felülírás

Ha verziókezelve szeretnéd tartani a közös beállításokat, tegyél egy mu-plugint
minden oldalra:

```php
<?php
// wp-content/mu-plugins/szesuti-config.php
add_filter( 'szesuti_settings', function ( $s ) {
    $s['general']['privacy_url'] = home_url( '/adatkezelesi-tajekoztato/' );
    $s['appearance']['colors']['primary'] = '#0b5cd5';
    $s['log']['retention_days'] = 365;
    return $s;
} );
```

Ez felülírja az admin felületen beállítottat, és mentéskor **nem ég bele** az
adatbázisba – tehát bármikor visszavonható a mu-plugin törlésével.

---

## 4. Frissítés 100 oldalon egyszerre (GitHub Release)

Az 1.2.0-tól a plugin magától frissül a GitHub-repó kiadásaiból
(<https://github.com/SZKK-SZUCS/SZetem-ny-/releases>), ugyanúgy, mint az SZEducate.
A [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) könyvtár
a pluginba van csomagolva (`szesuti/vendor/plugin-update-checker`), nem kell hozzá semmit
beállítani.

> **Egyszeri lépés:** az 1.1.x-es oldalakon még nincs benne a frissítő, ezért ezekre az
> 1.2.1-et **egyszer kézzel** kell feltölteni („Csere a feltöltöttre”). Ettől kezdve minden
> további verzió magától érkezik.
>
> **Repóváltás (1.2.1):** az 1.2.0 még a régi repót (LodThetherneon/SZEtemeny) figyeli. Ott a
> v1.2.1 Release mellékletét az új repóra mutató ZIP-re cseréltük, így az 1.2.0-s oldalak ezt
> kapják meg, és utána már az új repóból frissülnek. Amelyik oldal a csere **előtt** kapta meg
> a régi 1.2.1-et, az a régi repónál ragadt (azonos verziószám, nem lát frissítést). Ezekre az
> 1.2.1-et egyszer kézzel kell feltölteni. Ellenőrzés: SZetemény → Beállítások → Eszközök fül, a frissítés
> forrása `SZKK-SZUCS/SZetem-ny-` legyen.

### Új verzió kiadása

1. Kód + changelog bejegyzés a `szesuti/readme.txt`-be (`= x.y.z =`).
2. Csomagolás:

   ```powershell
   .\build.ps1 -Version 1.2.1
   ```

   Ez elkészíti a `dist/szetemeny-1.2.1.zip`-et és mellé a `dist/szetemeny-1.2.1-release.md`-t
   (a changelog bejegyzés, ez lesz a Release leírása).
3. Commit és push a `main` ágra.
4. GitHubon: **Releases → Draft a new release**
   - **Tag:** `v1.2.1` (új tag, cél: `main`). A verziót a plugin a tagből olvassa ki, ezért
     egyezzen a `build.ps1 -Version` értékével.
   - **Leírás:** a `szetemeny-1.2.1-release.md` tartalma. A WordPress a „Részletek
     megtekintése” ablakban ezt mutatja változásnaplóként.
   - **Melléklet:** húzd be a `dist/szetemeny-1.2.1.zip`-et. **Pontosan ilyen nevű**
     (`szetemeny-x.y.z.zip`) melléklet kell, más ZIP-et a frissítő nem fogad el.
   - Ne jelöld „pre-release”-nek és ne hagyd piszkozatban, azokat a frissítő kihagyja.
   - **Publish release**.

Az oldalak 12 óránként néznek rá a GitHubra, ezért legfeljebb fél nap múlva jelenik meg a
**„Frissítés elérhető”** jelzés a Bővítmények oldalon. Azonnal is ellenőrizhető: a
Bővítmények listájában a SZetemény sorában **„Frissítés ellenőrzése”**, vagy
**SZetemény → Eszközök → Frissítés ellenőrzése most** (a Vezérlőpult → Frissítések oldal
percenként, a Bővítmények oldal óránként magától is ránéz). Ezután egy kattintással
frissíthető, vagy a plugin sorában bekapcsolható az **automatikus frissítés** is.

Kötegelve WP-CLI-vel: `wp plugin update szesuti`. Ez mindig friss ellenőrzéssel indul, tehát
a Release közzététele után azonnal végigfuttatható az összes oldalon.

### Mire figyel a frissítő

- **Csak a Release mellékletét telepíti.** A GitHub automatikus „Source code (zip)”
  csomagja a teljes repó, abban a plugin egy almappában van. Ebből a WordPress rossz
  szerkezetű, inaktív plugint csinálna (ilyen volt az 1.0.0-s hiba is), ezért a frissítő
  ezt kifejezetten kizárja. Ha a mellékletet elfelejted, egyszerűen nem jelez frissítést.
- **GitHub API-keret:** hitelesítés nélkül 60 kérés/óra **IP-címenként**. Egy ellenőrzés
  egy kérés, oldalanként 12 óránként egyszer, ez 100 oldalra is bőven elég. Ha mégis
  elfogy (sok oldal egy szerveren, sok kézi ellenőrzés), az Eszközök fül ezt kiírja. Ilyenkor
  egy csak olvasási jogú GitHub-tokent lehet megadni a `wp-config.php`-ben:

  ```php
  define( 'SZESUTI_GITHUB_TOKEN', 'github_pat_...' );
  ```

  Ugyanez kell akkor is, ha a repó egyszer privát lesz.
- Az ellenőrzés csak adminban, WP-cronban és WP-CLI-ben fut, a látogatói oldalakat nem lassítja.

### Belső tükör (opcionális)

Ha az oldalak nem érik el a GitHubot, a régi módszer is működik. Tegyél egy `update.json`
fájlt egy belső szerverre, és az URL-jét írd be: **Eszközök → Saját frissítési manifest URL**
(vagy mu-pluginból: `$s['advanced']['update_url'] = '...';`). Ha ki van töltve, a plugin a
GitHub helyett ezt használja.

```json
{
  "name": "SZetemény",
  "slug": "szesuti",
  "version": "1.2.1",
  "requires": "5.6",
  "tested": "6.7",
  "requires_php": "7.2",
  "homepage": "https://sajatszerver.hu/szesuti",
  "download_url": "https://sajatszerver.hu/szesuti/szetemeny-1.2.1.zip",
  "last_updated": "2026-10-10 10:00:00",
  "sections": {
    "description": "Sütikezelő.",
    "changelog": "<h4>1.2.1</h4><ul><li>Változás...</li></ul>"
  }
}
```

A `name`, a `version` és a `download_url` mező kötelező.

### Csomagolás

A `build.ps1` minden csomagolás után ellenőrzi a ZIP-et (perjeles útvonalak, minden a
`szesuti/` mappa alatt, benne van a frissítő), és ha hibás, törli és leáll. Mindig ezzel
csomagolj, **ne** a PowerShell `Compress-Archive` parancsával. Windows PowerShell 5.1
alatt az fordított perjellel ír, és az 1.0.0-s hiba is ebből lett.

---
## 5. Süti tájékoztató oldal

Hozz létre egy oldalt és tedd bele:

```
[szesuti_cookie_table]

Jelenlegi választásod: [szesuti_consent_status]

[szesuti_settings_button text="Süti beállítások módosítása"]
```

Ez automatikusan kategóriánként listázza a sütilistában szereplő sütiket, és
gombot ad a döntés módosítására.

---

## 6. Amire figyelj (ezek okozzák a legtöbb hibát)

**Szigorú mód (alapból BE) – mit enged át és mit nem**
A plugin nem csak a listájában szereplő 55 ismert szolgáltatást tartja vissza, hanem
**minden idegen domainről** érkező szkriptet, beágyazást (iframe), stíluslapot és
követőpixelt, amíg a látogató hozzá nem járul. A JavaScriptből később beszúrt elemeket,
valamint a közvetlen adatküldést (fetch, XHR, sendBeacon) a **futásidejű védelem** fogja el.

Átengedett, mert nem követ és sütit nem állít be:
- a saját domain és minden aldomainje (pl. `sze.hu` alatt `cdn.sze.hu` is),
- a beépített biztonságos könyvtár-CDN-ek (a lista a Szkriptblokkolás fülön látható),
- a tartalmi képek más domainről (csak az 1×1-es és rejtett követőpixelek tiltottak).

A visszatartott ismeretlen források a **Marketing** hozzájárulással oldódnak fel
(ez a Szkriptblokkolás fülön átállítható). A saját domainen futó mérőkódokat (pl. saját
Matomo) a fájlnevük alapján ismeri fel és blokkolja.

**A teljes sütiszkenner (Sütilista fül)**
A szkenner a te böngésződben, kis előnézeti keretekben végigjárja az oldalt: a kezdőlapot,
az összes oldalt, egy mintát a bejegyzésekből, és minden belső linket, amit talál (menü,
lábléc, tartalom). Minden oldalt legörget az aljáig, hogy a lustán betöltődő videók,
térképek és chatek is elinduljanak. Közben a blokkolás ki van kapcsolva – csak neked, egy
egyszeri biztonsági kóddal –, így azt látja, amit egy mindent elfogadó látogató.
- Kijelentkeztető, törlő, kosárba tevő linkeket és fájlokat soha nem nyit meg.
- A hasonló oldalakból (pl. /hirek/...) előbb csak 12-t, a többit a végén, ha a keretből futja.
- A végén törli a böngésződből az általa létrehozott sütiket.
- A mérőkódok közben tényleg lefutnak: a statisztikában megjelenhet néhány látogatásod.
- Ha a tárhely tiltja az oldal keretben való megnyitását (X-Frame-Options: DENY), használd a
  lenyitható „Gyors, szerveroldali szkennelés”-t.

**Ha a szigorú mód elront valamit** (pl. egy külső kereső- vagy chat-szolgáltatás nem
tölt be hozzájárulás nélkül): a Szkriptblokkolás fül alján a „legutóbb visszatartott
ismeretlen források” listájából vedd fel a domaint a **„Mindig engedélyezett
(kivételek)”** mezőbe. Csak olyat engedj át, ami biztosan nem követ.

**Szélsőséges megjelenési beállítások**
A banner úgy van felépítve, hogy bármilyen betűméret, szélesség, szövegmennyiség és
szín mellett minden gomb elérhető és kattintható maradjon: ha nem fér ki minden, a
szöveg görgethető, a gombok a helyükön maradnak. Az olvashatatlan színpárokat (pl. fehér
felirat fehér gombon) a plugin automatikusan olvashatóra cseréli, és a Megjelenés fülön
figyelmeztet rá.

**Saját CSS**
A banner stílusa védve van a téma felülírásai ellen, ezért a saját szabályaid elé
írd oda a `#szesuti-root`-ot, különben nem érvényesülnek:
`#szesuti-root .szesuti-banner { border-radius: 0; }`

**Gyorsítótár (WP Rocket, LiteSpeed, Super Cache)**
A blokkolás és a Consent Mode is gyorsítótárral együtt működik: a látogató döntését
mindig a böngésző olvassa ki a sütiből, a szerver nem írja bele az oldalba. De:
- a **geo-célzásnál** hagyd „böngészőből, gyorsítótár-biztos” módban,
- az **„Elrejtés keresőrobotok elől”** opciót hagyd **kikapcsolva** – különben a
  robotnak készült, banner nélküli változat kerülhet a cache-be.

**GTM és Consent Mode**
Ha Consent Mode-ot használsz, hagyd bekapcsolva a „Google mérőkódok kihagyása a
blokkolásból” opciót. A GTM ilyenkor betöltődik, de hozzájárulás nélkül nem ír
sütit. Ha teljesen blokkolod a GTM-et, a Consent Mode-nak nincs mit vezérelnie.

**reCAPTCHA és Google Fonts**
Mindkettő alapból **nincs** blokkolva, mert a blokkolásuk látványosan elrontja az
oldalt (nem küldhető el űrlap, elcsúszik a tipográfia). Csak tudatosan kapcsold be őket.

**Ami nem blokkolható automatikusan**
A PHP-ból, szerveroldalon meghívott külső API-kat (pl. egy plugin, ami a szerverről
küld adatot a Facebooknak) a HTML-alapú blokkoló nem látja. Ezeket kóddal kell
feltételhez kötni:

```php
if ( szesuti_has_consent( 'marketing' ) ) {
    // csak hozzájárulás után
}
```

---

## 7. Ha valami nem blokkolódik

1. **Eszközök → Hibakeresés** bekapcsolása.
2. Nyisd meg az oldal forrását (Ctrl+U), és görgess a `</body>` elé. Ott látod,
   hány elemet és milyen szolgáltatásokat blokkolt a plugin.
3. Ha a keresett szkript nincs köztük, keresd meg a forrásban a betöltési URL-jét,
   és vegyél fel rá **saját szabályt** a Szkriptblokkolás fülön. A minta egy egyszerű
   szövegrészlet, pl. `crm.pelda.hu/track.js`.

---

## 8. Fejlesztői felület

**PHP**

```php
szesuti_has_consent( 'analytics' );   // bool
szesuti_has_decision();               // bool – döntött-e már a látogató
```

**JavaScript**

```js
SZESUTI.hasConsent( 'marketing' );
SZESUTI.showPreferences();            // beállítások ablak megnyitása
SZESUTI.acceptAll();
SZESUTI.rejectAll();
SZESUTI.withdraw();                   // hozzájárulás visszavonása
SZESUTI.getConsent();                 // ['necessary','analytics']

document.addEventListener( 'szesuti:change', function ( e ) {
    console.log( e.detail.categories, e.detail.action );
} );
```

**Kézi megjelölés saját kódban**

```html
<script type="text/plain" data-szesuti="marketing"
        data-szesuti-src="https://pelda.hu/pixel.js"></script>

<script data-szesuti-ignore="1" src="soha-ne-blokkold.js"></script>
```

**Banner sablon felülírása a témában**

`wp-content/themes/<téma>/szesuti/banner.php`
(másold ki kiindulásnak a `szesuti/templates/banner.php` fájlt)

---

## 9. Mit NEM tartalmaz

Hogy ne érjen meglepetés:

- **Nincs IAB TCF támogatás.** Ha hirdetési hálózatokat (Google AdSense aukciós
  oldal, prebid) futtatsz, azokhoz TCF-regisztrált CMP kell. Saját tartalmú
  oldalakhoz és GA4/Ads mérőkódokhoz ez a plugin elég.
- **A sütiszkenner a harmadik fél sütijeit nem látja közvetlenül.** A saját domainen
  beállított sütiket (pl. `_ga`, `_fbp`, `_clck`) és a böngészős tárolókat valóban
  kiolvassa, a más domainen (pl. facebook.com) keletkezőket viszont a böngésző nem mutatja
  meg – ezekre a felismert szolgáltatások alapján következtet.
- **Jogi megfelelőség.** A plugin a technikai feltételeket teremti meg. A szövegek
  tartalmi helyességéért és az adatkezelési tájékoztatóért az oldal üzemeltetője felel.
