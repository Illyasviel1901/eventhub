# Testare funcțională și browsere

## Mediu automatizat

- Arch Linux / Hyprland;
- PHP cu MySQL în Podman;
- Mozilla Firefox 154.0.1;
- Deno pentru verificarea JavaScript.

## Teste efectuate

| Zonă | Rezultat |
|---|---|
| Pagini publice și resurse | HTTP 200 |
| Locație inexistentă | HTTP 404 |
| Login și cont USER | OK |
| Login și dashboard ADMIN | OK |
| Separarea rolurilor | HTTP 403 pentru acces nepermis |
| Solicitare, anulare, aprobare, respingere și ștergere | OK |
| Prognoză și verificarea disponibilității | OK |
| Galerie, miniaturi, săgeți și tastatură | OK |
| Upload imagine ADMIN și servire BLOB | OK |
| XLSX import/export și PDF | OK |
| JavaScript | verificare sintactică OK |
| PHP | verificare sintactică OK |

Firefox a randat aplicația la:

- desktop: 1440 × 1000;
- mobil: 390 × 844.

## Compatibilitate

Sunt folosite HTML5, CSS Grid/Flexbox, media queries, `fetch`, `AbortController`, `URL`, `addEventListener`, `textContent` și formulare standard.

## Verificare manuală Chromium

Înainte de evaluare se verifică în Chrome/Chromium:

1. pagina principală, catalogul și galeria;
2. meniul la lățimi desktop și mobil;
3. autentificarea `USER`/`ADMIN`;
4. prognoza la schimbarea datei;
5. confirmările de anulare, aprobare, respingere și ștergere;
6. tabelele administrative;
7. uploadul de imagini și descărcările XLSX/PDF.
