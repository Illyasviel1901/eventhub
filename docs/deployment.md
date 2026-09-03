# Publicare EventHub pe Railway

## GitHub

Repository public:

```text
https://github.com/Illyasviel1901/eventhub
```

Railway este conectat la ramura `main` și redeployează după fiecare push.

## Servicii Railway

Proiectul conține:

- serviciul web EventHub construit din `Dockerfile`;
- serviciul MySQL;
- referința privată `MYSQL_URL` din serviciul web către MySQL.

## Variabilele serviciului web

```text
APP_URL=https://DOMENIUL-PUBLIC
MYSQL_URL=<referință Railway către MySQL.MYSQL_URL>
BREVO_API_KEY=<secret Brevo>
MAIL_FROM=gomesjohn929@gmail.com
MAIL_FROM_NAME=EventHub
COMPANY_EMAIL=gomesjohn929@gmail.com
```

Nu se adaugă variabile `DB_*` în Railway când există `MYSQL_URL`. Nu se publică `.env`, cheia Brevo sau credențialele MySQL.

## Inițializarea bazei

Pentru o bază nouă:

1. rulează `database/schema.sql`;
2. rulează `database/seed.sql`.

## Verificare după deploy

- `/` și `/venues.php` afișează locațiile și fotografiile;
- `/register.php` trimite codul prin Brevo;
- loginurile `USER` și `ADMIN` funcționează;
- solicitările pot fi create și procesate;
- `/robots.txt` și `/sitemap.php` folosesc `APP_URL` public;
- exporturile XLSX și raportul PDF se descarcă;
- statisticile înregistrează accesări.

## Actualizări

```bash
git add .
git commit -m "Descriere"
git push origin main
```

Modificările structurii bazei se aplică separat prin SQLTools; un push nu execută SQL automat.
