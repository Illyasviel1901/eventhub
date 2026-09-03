# EventHub

Aplicație web universitară pentru organizarea evenimentelor și solicitarea online a locațiilor din București.

- Cod sursă: https://github.com/Illyasviel1901/eventhub
- Aplicație publică: URL-ul configurat în variabila Railway `APP_URL`

## Tehnologii

- PHP 8.4, HTML5, CSS și JavaScript;
- MySQL 8 și PDO cu prepared statements;
- Brevo API prin HTTPS pentru emailuri;
- Open-Meteo pentru prognoza modelată în aplicație;
- Docker și Railway pentru publicare.

## Roluri

### USER

Poate crea și verifica un cont, edita numele/emailul, explora locațiile, trimite și anula solicitări `PENDING`, vedea propriile rezervări și trimite mesaje de contact.

### ADMIN

Poate gestiona locații, imagini, utilizatori `USER` și toate cererile; poate aproba, respinge sau șterge cereri, vedea statistici, importa/exporta XLSX și genera raportul PDF.

## Funcționalități

- înregistrare cu verificare prin cod email, autentificare, sesiuni și logout;
- catalog dinamic cu cinci locații și galerii foto interactive;
- upload persistent de imagini în MySQL la crearea unei locații;
- solicitări cu verificarea disponibilității și prognoză pentru următoarele șapte zile;
- emailuri tranzacționale prin Brevo la înregistrare și schimbarea statusului;
- CRUD locații și administrarea utilizatorilor/cererilor;
- statistici interne din `page_visits`;
- import/export XLSX și raport PDF;
- SEO: titluri, descrieri, canonical, Open Graph, JSON-LD, robots și sitemap dinamic;
- protecții PDO, XSS, CSRF/XSRF, Form Spoofing, HTTP Request Spoofing și honeypot.

## Structură principală

```text
admin/              pagini protejate pentru administrare
assets/             CSS, JavaScript și fotografii statice
config/             mediu, PDO, sesiune și Brevo
includes/           logică reutilizabilă
database/           schema și date inițiale
docs/               arhitectură, bază de date, testare și deploy
*.php                paginile și endpointurile publice
Dockerfile           imaginea Railway
railway.json         configurarea deploy-ului
```

## Instalare locală

1. Copiază `.env.example` în `.env` și completează conexiunea MySQL și, opțional, cheia Brevo.
2. Rulează `database/schema.sql`, apoi `database/seed.sql` pe baza selectată.
3. Pornește aplicația cu routerul folosit și în producție:

```bash
php -S 127.0.0.1:8000 router.php
```

4. Deschide `http://127.0.0.1:8000`.

Extensiile PHP necesare sunt: `pdo_mysql`, `dom` și `zip`.

## Configurare

Variabile locale pentru baza de date:

```text
APP_URL
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
```

În Railway se folosește `MYSQL_URL` în locul variabilelor `DB_*`.

Email:

```text
BREVO_API_KEY
MAIL_FROM
MAIL_FROM_NAME
COMPANY_EMAIL
```

Secretele se păstrează numai în `.env` local sau în Railway Variables.

## Baza de date

Schema finală are șase tabele:

- `users`;
- `venues`;
- `venue_images`;
- `reservations`;
- `contact_messages`;
- `page_visits`.

Pentru inițializarea bazei se rulează `schema.sql`, apoi `seed.sql`.

## Documentație

- `docs/architecture.md` — arhitectura, rolurile și procesele;
- `docs/database.md` — entități, coloane și relații;
- `docs/requirements-checklist.md` — maparea cerințelor profesorului;
- `docs/browser-testing.md` — testele funcționale și de browser;
- `docs/deployment.md` — GitHub, Railway și configurarea producției.

## Cont demonstrativ

Seedul creează contul `ADMIN` cu emailul `admin@eventhub.local`. Parola este stocată numai ca hash și se comunică separat evaluatorului, împreună cu un cont demonstrativ `USER`.
