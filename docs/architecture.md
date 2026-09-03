# Arhitectura EventHub

## Scop

EventHub prezintă locații pentru evenimente și gestionează solicitările clienților, deciziile administratorului, notificările email, statisticile și rapoartele.

## Componente

```text
Browser
  │
  ▼
PHP EventHub pe Railway
  ├── MySQL Railway: date persistente
  ├── Brevo API HTTPS: emailuri tranzacționale
  └── Open-Meteo API HTTPS: prognoză modelată
```

Aplicația folosește PHP simplu:

- paginile `.php` controlează requestul și construiesc răspunsul;
- `includes/` conține logica reutilizabilă;
- `config/` inițializează mediul, PDO, sesiunea și emailul;
- `assets/` conține prezentarea și imaginile statice;
- MySQL păstrează entitățile și imaginile încărcate de administrator.

## Roluri

### USER

- se înregistrează și verifică adresa email;
- se autentifică și își editează profilul;
- consultă locațiile și galeriile;
- solicită o locație disponibilă;
- vede doar propriile solicitări;
- anulează numai solicitări `PENDING` proprii;
- trimite formularul de contact.

### ADMIN

- accesează zona `/admin` după verificarea rolului pe server;
- gestionează locații și poate încărca imagini;
- creează/șterge conturi `USER`;
- creează, editează, aprobă, respinge și șterge solicitări;
- consultă statisticile;
- importă/exportă XLSX și generează PDF.

## Procese principale

### Înregistrare

```text
formular → validare + CSRF + honeypot → cod Brevo
→ verificare cod → INSERT users cu rol USER → sesiune
```

### Solicitare

```text
USER alege locația/data → verificare disponibilitate
→ prognoză Open-Meteo dacă data este în 7 zile
→ INSERT reservations cu status PENDING → email companie
```

### Decizie administrativă

```text
ADMIN apasă Aprobă/Respinge → rol + POST + CSRF
→ reverificare status/disponibilitate → UPDATE status
→ email către USER
```

### Imagini

Imaginile inițiale sunt fișiere statice și au căi în `venue_images`. Imaginile încărcate la crearea unei locații sunt validate și salvate în `image_data`; endpointul `venue-image.php` le servește cu MIME-ul verificat.

## Reutilizare

- `header.php`/`footer.php`: layout comun;
- `auth.php`: sesiune, roluri, CSRF, redirect și mesaje flash;
- modulele `*-management.php`: validare și operații PDO;
- `functions.php`: locații, galerii, formatare și escapare;
- `mailer.php`, `weather.php`, `analytics.php`, `xlsx.php`, `pdf.php`: servicii izolate.

## Securitate

- SQL Injection: prepared statements PDO;
- XSS: `e()`/`htmlspecialchars()` la afișare;
- CSRF/XSRF: token asociat sesiunii pentru operații de modificare;
- Form Spoofing: rolul, proprietarul și statusul sunt verificate pe server;
- HTTP Request Spoofing: metode HTTP și ID-uri validate;
- anti-bot: honeypot pe formularele fără privilegii;
- parole: `password_hash()` și `password_verify()`;
- secrete: `.env`/Railway Variables, excluse din Git.
