# Baza de date EventHub

## Relații

```text
users 1 ───── N reservations N ───── 1 venues
                                      │
                                      1
                                      │
                                      N
                                 venue_images

contact_messages   (independent)
page_visits         (independent)
```

## `users`

| Coloană | Rol |
|---|---|
| `id` | cheie primară |
| `name` | numele profilului |
| `email` | email unic și login |
| `password` | hashul parolei |
| `role` | `USER` sau `ADMIN` |

## `venues`

| Coloană | Rol |
|---|---|
| `id` | cheie primară |
| `name` | denumire unică |
| `description` | prezentare |
| `address` | adresă și localitate pentru meteo |
| `capacity` | limita participanților |

## `venue_images`

| Coloană | Rol |
|---|---|
| `id` | cheie primară |
| `venue_id` | locația asociată |
| `image_path` | cale statică sau URL-ul endpointului BLOB |
| `image_data` | conținut binar pentru uploadurile ADMIN; `NULL` pentru fișiere statice |
| `mime_type` | MIME pentru BLOB; `NULL` pentru fișiere statice |
| `alt_text` | descriere accesibilă și SEO |
| `sort_order` | ordinea în galerie |

La ștergerea locației, imaginile sunt șterse prin `ON DELETE CASCADE`.

## `reservations`

| Coloană | Rol |
|---|---|
| `id` | cheie primară |
| `user_id` | clientul |
| `venue_id` | locația |
| `event_date` | data evenimentului |
| `event_name` | denumirea evenimentului |
| `attendees_count` | numărul participanților |
| `details` | cerințele clientului |
| `status` | `PENDING`, `APPROVED` sau `REJECTED` |

Ștergerea unui client elimină solicitările lui (`CASCADE`). O locație cu solicitări nu poate fi ștearsă (`RESTRICT`). O locație/data este ocupată numai de o solicitare `APPROVED`.

## `contact_messages`

Păstrează `name`, `email`, `subject` și `message` pentru mesajele trimise companiei. Numele și emailul sunt preluate din profilul autentificat.

## `page_visits`

Păstrează `page` și `visited_at` pentru statisticile interne. Nu se stochează IP, user-agent sau identificator de utilizator.

## Fișiere SQL

- `database/schema.sql`: schema completă pentru instalări noi;
- `database/seed.sql`: cont ADMIN, cinci locații și fotografiile statice.
