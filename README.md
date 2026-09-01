# EventHub

Proiect universitar pentru disciplina Dezvoltarea Aplicațiilor Web.

EventHub este o aplicație web pentru o companie care oferă spre închiriere locații pentru evenimente.

## Tehnologii

- PHP simplu
- MySQL 8
- HTML, CSS și JavaScript
- PDO pentru accesul la baza de date
- Composer pentru PHPMailer
- Git și GitHub
- Railway pentru publicarea finală

## Stadiul actual

Etapa 16 — proiect pregătit pentru GitHub și Railway. Imaginea Docker de producție include PHP 8.4, Composer și extensiile necesare, ascultă pe portul Railway și a fost testată local cu Podman. Repository-ul are primul commit local; conectarea și publicarea efectivă necesită repository-ul GitHub și proiectul Railway ale proprietarului.

## Structură

- `index.php` — pagina principală și locațiile recomandate;
- `venues.php` — lista dinamică a locațiilor;
- `venue.php` — detaliile și disponibilitatea unei locații;
- `register.php` și `verify-email.php` — înregistrarea cu verificarea adresei prin cod;
- `login.php` — autentificarea utilizatorilor;
- `account.php` — pagina protejată a utilizatorului autentificat;
- `logout.php` — terminarea sigură a sesiunii prin cerere POST;
- `admin/index.php` — dashboard protejat, accesibil exclusiv rolului `ADMIN`;
- `admin/venues.php` — lista administrativă a locațiilor;
- `admin/venue-create.php` — adăugarea unei locații;
- `admin/venue-edit.php` — modificarea unei locații;
- `admin/venue-delete.php` — ștergerea protejată a unei locații;
- `reservation-create.php` — solicitarea unei locații de către un client `USER`;
- `my-reservations.php` — cererile utilizatorului autentificat;
- `account-edit.php` și `verify-email-change.php` — editarea profilului și verificarea noului email;
- `contact.php` — formular pentru utilizatorii autentificați, bazat pe numele și emailul profilului;
- `admin/users.php` — administrarea conturilor `USER`;
- `admin/reservations.php` — secțiunea „Cereri”, cu badge și acțiuni rapide de aprobare/respingere;
- `admin/reservation-status.php` — actualizarea sigură a statusului și notificarea clientului;
- `admin/analytics.php` — pagina „Statistici”, disponibilă numai administratorului;
- `admin/reports.php` — exporturi XLSX, import XLSX și raport PDF;
- `admin/export-venues.php` și `admin/export-reservations.php` — exporturile administrative;
- `admin/venue-import.php` — importul tranzacțional și validat al locațiilor;
- `admin/report-pdf.php` — raportul PDF generat din datele MySQL;
- `admin/reservation-create.php` — adăugarea unei solicitări pentru un client existent;
- `admin/reservation-edit.php` — editarea și schimbarea statusului;
- `admin/reservation-delete.php` — ștergerea protejată a unei solicitări;
- `admin/` — viitoarele pagini accesibile administratorului;
- `assets/` — CSS, JavaScript, imagini și elemente multimedia;
- `config/environment.php` — încărcarea variabilelor locale din `.env`;
- `config/database.php` — conexiunea PDO reutilizabilă;
- `config/session.php` — inițializarea sesiunii și configurarea cookie-ului;
- `includes/auth.php` — autentificare, CSRF, mesaje flash și terminarea sesiunii;
- `includes/analytics.php` — înregistrarea și agregarea accesărilor fără date personale;
- `includes/weather.php` și `weather-forecast.php` — prognoza pe data selectată și verificarea disponibilității;
- `includes/xlsx.php` — citirea și generarea fișierelor XLSX;
- `includes/pdf.php` — generarea raportului PDF;
- `assets/media/eventhub-prezentare.mp4` — materialul multimedia original;
- `robots.txt`, `sitemap.xml` și `sitemap.php` — controlul indexării și harta statică/dinamică a paginilor publice;
- `database/schema.sql` — tabele, relații, constrângeri și indexuri;
- `database/seed.sql` — contul ADMIN și cinci locații reale din București;
- `docs/browser-testing.md` — matricea testelor funcționale și de compatibilitate;
- `docs/deployment.md` — pașii GitHub, Railway, MySQL, SMTP și verificarea publică;
- `Dockerfile`, `railway.json` și `router.php` — mediul și pornirea aplicației în Railway;
- `includes/` — viitoarele componente și funcții PHP reutilizabile;
- `reports/` — viitoarele rapoarte generate;
- `.env.example` — model de configurare fără parolă;
- `.gitignore` — exclude secretele și fișierele generate.

## Baza de date

Schema conține tabelele:

- `users` — utilizatori cu rol `USER` sau `ADMIN`;
- `venues` — locațiile oferite spre închiriere;
- `venue_images` — imaginile galeriilor, în relație N:1 cu locațiile;
- `reservations` — cererile de rezervare și starea lor;
- `contact_messages` — mesajele formularului de contact;
- `page_visits` — pagina și momentul accesării, folosite pentru statisticile interne.

Scripturile SQL nu creează, nu șterg și nu redenumesc baza de date. Ele lucrează pe baza selectată de client.

## Configurare locală

1. Copiază `.env.example` ca `.env`.
2. Completează variabilele `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` și `DB_PASSWORD`.
3. Activează extensia PHP `pdo_mysql`.
4. Pentru o instalare nouă, importă întâi `database/schema.sql`, apoi `database/seed.sql`. Pentru o bază existentă fără galerii, rulează `database/migrations/2026-09-01-add-venue-images.sql`, apoi `database/seed.sql`.
5. Pornește aplicația din rădăcina proiectului:

```bash
php -S 127.0.0.1:8000
```

6. Deschide `http://127.0.0.1:8000` în browser.

Pentru containerul local existent, importul prin TCP se poate face astfel:

```bash
podman exec -i eventhub-mysql mysql -h 127.0.0.1 -P 3306 -u eventhub -p eventhub < database/schema.sql
podman exec -i eventhub-mysql mysql -h 127.0.0.1 -P 3306 -u eventhub -p eventhub < database/seed.sql
```

Parola nu trebuie scrisă în comenzile salvate sau în fișierele urmărite de Git.

## Gmail și codurile de verificare

Expeditorul și destinatarul companiei sunt configurate pentru `gomesjohn929@gmail.com`. Pentru livrarea reală, completează local `SMTP_PASSWORD` cu un App Password Google, nu cu parola obișnuită a contului. Fără acesta, înregistrarea și schimbarea emailului sunt oprite controlat, deoarece adresa nu poate fi verificată.

## Informații externe

În formularul de solicitare, alegerea unei date din intervalul azi–azi+7 zile declanșează preluarea prognozei zilnice Open-Meteo pentru localitatea locației. Răspunsul JSON este modelat în PHP și afișează temperaturile minimă/maximă, condițiile, probabilitatea de ploaie și vântul. Interfața folosește debounce de 300 ms, anularea cererii anterioare și verificarea secvenței, iar serverul folosește cache de 30 de minute și limitare între apelurile externe. Pentru datele de la ziua +8 încolo, chenarul meteo este eliminat. Prognoza apare și în „Rezervările mele” când data intră în fereastra de 7 zile.

## Statistici

Fiecare pagină HTML înregistrează o singură accesare în `page_visits`. Nu sunt salvate IP-ul, user-agentul, emailul sau ID-ul utilizatorului. Pagina protejată `admin/analytics.php`, afișată în meniu ca „Statistici”, prezintă totalul accesărilor, accesările din ziua curentă și ultimele 7 zile, paginile cele mai vizitate și activitatea recentă. PHP și sesiunea MySQL folosesc `Europe/Bucharest`, inclusiv conversia automată a orei de vară.

## XLSX și PDF

Exporturile XLSX sunt arhive OOXML valide și includ locațiile sau cererile. Importul acceptă maximum 2 MB și 500 de locații, validează arhiva, dimensiunea decomprimată, antetele și fiecare rând înainte de a porni o tranzacție. Antetele necesare sunt `Nume`, `Descriere`, `Adresa`, `Capacitate`; locațiile cu nume existent sunt actualizate. Raportul PDF conține sinteza aplicației, distribuția statusurilor, locațiile cele mai solicitate și cererile existente.

## Multimedia și SEO

Fiecare locație include o galerie multimedia cu patru ilustrații SVG originale, stocate local și asociate prin tabelul `venue_images`. Galeria are imagine principală, miniaturi, navigare cu săgeți și tastatură, texte alternative descriptive și `loading="lazy"` pentru miniaturi. Ilustrațiile sunt generate pentru proiect și sunt marcate transparent ca nefiind fotografii oficiale ale locațiilor reale.

Paginile publice au titluri și descrieri specifice, URL canonical, Open Graph, Twitter Card și imagine socială 1200×630. Pagina principală publică date structurate `Organization`, catalogul publică `CollectionPage`/`ItemList`, iar fiecare locație publică `EventVenue` și `PostalAddress`. Paginile de cont, autentificare și administrare primesc `noindex, nofollow`. `robots.txt` exclude zonele private, iar `sitemap.php` generează dinamic URL-urile celor cinci locații; `sitemap.xml` este instantaneul local și trebuie regenerat cu URL-ul public la publicarea pe Railway.

## Securitatea cerută

- **SQL Injection:** toate valorile provenite din request sunt transmise prin parametri PDO în prepared statements. Interogările executate cu `query()` sunt constante interne, fără date ale utilizatorului.
- **XSS:** funcția comună `e()` folosește `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`; textele din formulare, sesiune, API și baza de date sunt escapate la afișare. Valorile numerice sunt convertite explicit la `int`.
- **CSRF/XSRF:** toate formularele care modifică date includ tokenul sesiunii generat cu `random_bytes()` și validat cu `hash_equals()` înaintea operației.
- **HTTP Request Spoofing:** endpointurile verifică metoda GET/POST, validează ID-urile și returnează 405, 403 sau 404 când cererea nu respectă contractul.
- **Form Spoofing și roluri:** `requireAuthentication()`/`requireRole()` protejează server-side acțiunile; operațiile ADMIN nu se bazează pe ascunderea butoanelor. Formularul public de rezervare ignoră `user_id`, `status` și `venue_id` falsificate și folosește utilizatorul din sesiune, statusul `PENDING` și locația validată din URL.
- **Anti-automatizare:** autentificarea, înregistrarea și verificarea publică a emailului folosesc un câmp honeypot verificat server-side. Codul email are suplimentar expirare de 10 minute și maximum 5 încercări. Formularul de contact păstrează honeypotul, deși necesită autentificare în fluxul actual.

## Testare în browser

Firefox 154.0.1 a fost rulat efectiv în mod headless pentru pagina principală desktop (1440×1000) și catalogul mobil (390×844). Au fost retestate paginile publice, loginurile USER/ADMIN, separarea rolurilor, formularul de rezervare, paginile administrative și fișierele XLSX/PDF. JavaScriptul a trecut `deno check`. Matricea completă și verificarea manuală rămasă pentru Chromium sunt în `docs/browser-testing.md`.

## Railway

Proiectul are o imagine Docker verificată local cu PHP 8.4, Composer, `pdo_mysql`, `zip`, `dom` și `mbstring`. Serverul folosește variabila `PORT`, iar `router.php` generează dinamic `robots.txt` pe baza `APP_URL`. Conexiunea acceptă `MYSQL_URL` oferit de Railway; variabilele individuale `DB_*`, dacă sunt definite, au prioritate. Fișierele `.env` reale nu se publică în Git. Instrucțiunile complete sunt în `docs/deployment.md`.

## Cont demonstrativ

Seedul creează utilizatorul ADMIN `admin@eventhub.local`. Parola demonstrativă se comunică separat și în baza de date este păstrată numai sub formă de hash.
