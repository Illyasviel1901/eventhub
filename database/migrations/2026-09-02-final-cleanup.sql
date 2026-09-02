-- Curățare finală EventHub pentru baza Railway existentă.
-- Păstrează utilizatorii, locațiile, rezervările, mesajele, accesările și imaginile încărcate de ADMIN.

SET NAMES utf8mb4;

START TRANSACTION;

-- Elimină numai referințele către imagini statice care nu mai există în proiect.
DELETE FROM venue_images
WHERE image_data IS NULL
  AND image_path NOT IN (
    'assets/images/venues/palatul-bragadiru/sala-evenimente.png',
    'assets/images/venues/palatul-bragadiru/amenajare-interioara.png',
    'assets/images/venues/jw-marriott/sala-evenimente.png',
    'assets/images/venues/jw-marriott/amenajare-interioara.png',
    'assets/images/venues/athenee-palace/sala-evenimente.png',
    'assets/images/venues/athenee-palace/amenajare-interioara.png',
    'assets/images/venues/radisson-blu/sala-evenimente.png',
    'assets/images/venues/radisson-blu/amenajare-interioara.png',
    'assets/images/venues/stejarii-country-club/sala-evenimente.png',
    'assets/images/venues/stejarii-country-club/amenajare-interioara.png'
  );

-- MIME-ul este necesar doar pentru imaginile binare încărcate de ADMIN.
UPDATE venue_images
SET mime_type = NULL
WHERE image_data IS NULL;

COMMIT;
