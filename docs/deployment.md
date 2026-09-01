# Publicare EventHub — GitHub și Railway

Acest document descrie etapa 16. Nu se copiază `.env` în GitHub sau Railway.

## 1. GitHub

1. Creează în GitHub un repository public gol, de exemplu `eventhub`.
2. Nu solicita generarea unui README, `.gitignore` sau license, deoarece proiectul le conține deja.
3. În terminalul proiectului adaugă remote-ul afișat de GitHub:

```bash
git remote add origin https://github.com/UTILIZATOR/eventhub.git
git push -u origin main
```

Dacă folosești SSH:

```bash
git remote add origin git@github.com:UTILIZATOR/eventhub.git
git push -u origin main
```

GitHub poate solicita autentificarea în browser, un Personal Access Token sau o cheie SSH. Parola normală a contului GitHub nu se folosește pentru push prin HTTPS.

## 2. Proiectul Railway

1. În Railway selectează **New Project**.
2. Alege **Deploy from GitHub repo** și repository-ul `eventhub`.
3. Railway detectează `railway.json` și `Dockerfile`.
4. Adaugă în același proiect un serviciu **MySQL**.
5. În serviciul aplicației, deschide **Variables** și adaugă o referință la `MYSQL_URL` din serviciul MySQL. Folosește opțiunea Railway de tip **Add Reference**, fără copierea manuală a parolei în cod.

Imaginea include PHP 8.4, Composer și extensiile necesare:

- `pdo_mysql`;
- `zip`;
- `dom`;
- `mbstring`.

Serverul ascultă automat pe variabila Railway `PORT`.

## 3. Variabilele aplicației

Configurează în serviciul web:

```text
APP_ENV=production
APP_URL=https://DOMENIUL-RAILWAY
MYSQL_URL=<referință la MYSQL_URL din serviciul MySQL>
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=gomesjohn929@gmail.com
SMTP_PASSWORD=<App Password Google, introdus ca secret>
SMTP_ENCRYPTION=tls
MAIL_FROM=gomesjohn929@gmail.com
MAIL_FROM_NAME=EventHub
COMPANY_EMAIL=gomesjohn929@gmail.com
```

Nu configura simultan `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` și `DB_PASSWORD` dacă folosești `MYSQL_URL`, deoarece variabilele `DB_*` au prioritate în aplicație.

După ce Railway generează domeniul public, actualizează `APP_URL` cu URL-ul HTTPS complet, fără slash final. Această valoare este folosită pentru canonical, Open Graph, sitemap și `robots.txt` dinamic.

## 4. Inițializarea bazei de producție

Baza Railway pornește goală. Rulează o singură dată, în această ordine:

1. `database/schema.sql`;
2. `database/seed.sql`.

Poți folosi SQLTools cu datele publice de conectare afișate de serviciul Railway MySQL. Nu folosi hostul privat Railway din afara platformei.

Alternativ, folosește interfața/terminalul de bază de date pus la dispoziție de Railway.

Scripturile nu conțin `CREATE DATABASE`, `DROP DATABASE` sau `USE`; lucrează în baza selectată. `schema.sql` folosește `CREATE TABLE IF NOT EXISTS`, iar seedul este idempotent pentru datele unice.

Nu importa baza locală completă dacă nu dorești să publici conturile, mesajele, accesările și cererile locale. Pentru mediul demonstrativ sunt suficiente schema și seedul.

## 5. Domeniul public

În serviciul web Railway:

1. deschide **Settings / Networking**;
2. generează un domeniu Railway;
3. copiază URL-ul HTTPS în `APP_URL`;
4. așteaptă redeploy-ul produs de schimbarea variabilei.

Verifică:

```text
/
/venues.php
/login.php
/register.php
/robots.txt
/sitemap.php
```

`robots.txt` este generat dinamic de `router.php`, astfel încât linia sitemap folosește `APP_URL` din producție.

## 6. Checklist după publicare

- pagina principală răspunde prin HTTPS;
- cele cinci locații sunt afișate;
- înregistrarea trimite codul Gmail;
- loginul USER funcționează;
- loginul ADMIN funcționează;
- o cerere poate fi creată;
- administratorul o poate aproba sau respinge;
- clientul primește emailul de status;
- prognoza Open-Meteo se actualizează;
- exportul XLSX se descarcă;
- raportul PDF se descarcă;
- `robots.txt` și `sitemap.php` conțin domeniul public;
- pagina Statistici înregistrează accesări;
- testul vizual este repetat în Firefox și într-un browser Chromium.

## 7. Actualizări ulterioare

După modificări locale testate:

```bash
git add .
git commit -m "Descrierea modificării"
git push
```

Railway va detecta commitul nou și va redeploya serviciul. Modificările structurii MySQL trebuie aplicate separat printr-un script de migrare; un push Git nu modifică automat baza existentă.

## 8. Secrete

Nu se publică:

- `.env`;
- parola MySQL;
- `MYSQL_URL`;
- App Password-ul Gmail;
- cookie-uri sau sesiuni;
- exporturi cu date reale.

Dacă un secret ajunge accidental într-un commit public, acesta trebuie revocat și înlocuit; simpla ștergere într-un commit ulterior nu îl elimină din istoricul Git.
