# Checklist cerințe EventHub

| Cerință | Implementare | Status |
|---|---|---|
| PHP + MySQL | pagini PHP, `config/database.php`, `database/schema.sql` | Implementat |
| CRUD | `admin/venue-*.php`, administrarea solicitărilor și utilizatorilor | Implementat |
| Autentificare/înregistrare | `login.php`, `register.php`, `verify-email.php` | Implementat |
| Categorii de utilizatori | rolurile `USER`/`ADMIN`, `requireRole()` | Implementat |
| Pagini dinamice legate | catalog, detalii, cont, solicitări și administrare | Implementat |
| Raport non-HTML/CSV | `admin/report-pdf.php` | Implementat |
| Analytics | `page_visits`, `admin/analytics.php` | Implementat |
| Contact și email | `contact.php`, Brevo în `config/mailer.php` | Implementat |
| Informație externă modelată | Open-Meteo în `includes/weather.php` | Implementat |
| Import/export | XLSX în `includes/xlsx.php` și paginile `admin/` | Implementat |
| Multimedia | galerii foto interactive în `venue.php` | Implementat |
| SEO | meta, canonical, Open Graph, JSON-LD, robots, `sitemap.php` | Implementat |
| SQL Injection | prepared statements PDO | Implementat |
| XSS | funcția `e()` și conversii numerice | Implementat |
| CSRF/XSRF | `csrfToken()`/`isValidCsrfToken()` | Implementat |
| Form Spoofing | autorizare, rol/proprietar/status validate server-side | Implementat |
| HTTP Request Spoofing | metode HTTP și ID-uri validate | Implementat |
| Anti-automatizare | honeypot și cod email limitat | Implementat |
| Logout | `logout.php`, POST + CSRF + distrugerea sesiunii | Implementat |
| Testare browser | Firefox desktop/mobil; verificare Chromium consemnată în `browser-testing.md` | Parțial |
| GitHub public | https://github.com/Illyasviel1901/eventhub | Implementat |
| Hosting public | Railway, URL configurat în `APP_URL` | Implementat |
| Arhitectură/entități/procese | `architecture.md`, `database.md` | Implementat |

## Verificări finale manuale

Înainte de evaluare se testează pe URL-ul public:

1. loginul conturilor demo `USER` și `ADMIN`;
2. emailul de verificare Brevo și emailurile de aprobare/respingere;
3. galeriile și uploadul unei imagini de către ADMIN;
4. prognoza și blocarea unei date ocupate;
5. exporturile XLSX și raportul PDF;
6. interfața într-un browser Chromium/Chrome;
7. `robots.txt` și `sitemap.php` cu domeniul public.
