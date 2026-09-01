# Testare browsere și funcțională — etapa 15

Data verificării: 1 septembrie 2026

## Mediu

- Sistem: Arch Linux / Hyprland
- PHP: serverul integrat PHP
- Bază de date: MySQL în Podman, conexiune TCP
- Browser disponibil: Mozilla Firefox 154.0.1
- Verificare JavaScript: Deno 2.9.6
- Chromium/Chrome/Edge: nu sunt instalate în mediul curent

## Teste Firefox reale

Firefox a fost pornit în mod headless și a randat pagini reale servite de aplicația PHP:

| Scenariu | Dimensiune | Rezultat |
|---|---:|---|
| Pagina principală desktop | 1440 × 1000 | OK |
| Catalog locații mobil | 390 × 844 | OK |

Au fost încărcate HTML-ul, CSS-ul, JavaScriptul și resursele multimedia. Capturile temporare au avut dimensiunile solicitate și nu au fost detectate erori JavaScript, erori PHP fatale sau erori de sintaxă.

## Teste funcționale automate

| Zonă | Verificare | Rezultat |
|---|---|---|
| Public | Acasă, locații, detalii, login, register | HTTP 200 |
| Erori | Locație inexistentă | HTTP 404 |
| Resurse | CSS, JavaScript, video MP4, robots și sitemap | HTTP 200 |
| USER | Login și cont | OK |
| USER | Rezervările mele | OK |
| USER | Formular solicitare și integrare meteo | OK |
| USER | Acces direct la administrare | HTTP 403 |
| ADMIN | Login și dashboard | OK |
| ADMIN | Cereri, locații, utilizatori, statistici, rapoarte | HTTP 200 |
| Export | XLSX locații | Fișier XLSX valid |
| Raport | PDF administrativ | Fișier PDF valid |
| JavaScript | Analiză sintactică Deno | OK |
| PHP | Sintaxa tuturor fișierelor proiectului | OK |

## Regresie prognoză pentru cereri respinse

A fost creată temporar o cerere `REJECTED` cu data în intervalul meteo de șapte zile.

S-a verificat că:

- cererea apare în „Rezervările mele”;
- statusul „Respinsă” este afișat;
- nu este apelată funcția Open-Meteo pentru cererea respinsă;
- chenarul „Prognoză Open-Meteo” nu este generat în HTML.

Contul și cererea temporare au fost eliminate după test.

## Compatibilitate folosită în cod

Interfața folosește standarde suportate de browserele moderne:

- HTML5 semantic și `<video>`;
- CSS Grid și Flexbox;
- media queries responsive;
- `fetch`, `AbortController` și `URL`;
- `addEventListener`;
- `textContent` și `replaceChildren` pentru conținut dinamic sigur;
- formulare HTML standard, fără componente dependente de un singur browser.

## Verificare manuală rămasă

Cerința oficială solicită funcționarea corespunzătoare indiferent de browser. Firefox a fost testat efectiv. Deoarece Chromium/Chrome/Edge nu este instalat în mediul curent, înaintea prezentării sau după publicarea pe Railway trebuie executată această verificare manuală într-un browser Chromium:

1. pagina principală și redarea video;
2. meniul la 390 px și 1440 px;
3. login USER și ADMIN;
4. selectarea datei și actualizarea meteo;
5. confirmările de deconectare, aprobare, respingere și ștergere;
6. tabelele administrative și derularea lor pe mobil;
7. descărcarea XLSX și PDF.

Nu a fost instalat un browser suplimentar doar pentru rularea testelor.
