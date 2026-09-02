-- Date inițiale EventHub
-- Scriptul poate fi rulat din nou fără să dubleze înregistrările.
-- Parola contului ADMIN este stocată exclusiv ca hash generat cu password_hash().

SET NAMES utf8mb4;

INSERT INTO users (name, email, password, role)
VALUES (
    'Administrator EventHub',
    'admin@eventhub.local',
    '$2y$12$Os.dpo6H3RkBSgufkzympOeiJFIf5AD5bvzmpKCuH5Ez7a6kHO8w.',
    'ADMIN'
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    role = VALUES(role);

INSERT INTO venues (name, description, address, capacity)
VALUES
    ('Palatul Bragadiru', 'Palat istoric cu saloane ample, potrivit pentru nunți, gale și recepții elegante.', 'Calea Rahovei 147-153, București', 400),
    ('JW Marriott Bucharest Grand Hotel', 'Hotel de cinci stele cu săli modulare pentru nunți, conferințe și evenimente corporate.', 'Calea 13 Septembrie 90, București', 800),
    ('InterContinental Athénée Palace Bucharest', 'Hotel emblematic în centrul Capitalei, cu saloane istorice pentru recepții și evenimente premium.', 'Strada Episcopiei 1-3, București', 300),
    ('Radisson Blu Hotel Bucharest', 'Spațiu central cu săli moderne pentru conferințe, gale, lansări și evenimente private.', 'Calea Victoriei 63-81, București', 500),
    ('Stejarii Country Club', 'Complex premium în nordul Bucureștiului, potrivit pentru ceremonii, recepții și evenimente în aer liber.', 'Strada Jandarmeriei 14A, București', 250)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    address = VALUES(address),
    capacity = VALUES(capacity);

-- Elimină referințele statice vechi; imaginile încărcate de ADMIN sunt păstrate.
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

INSERT INTO venue_images (venue_id, image_path, image_data, mime_type, alt_text, sort_order)
SELECT v.id, images.image_path, NULL, NULL, images.alt_text, images.sort_order
FROM venues v
JOIN (
    SELECT 'Palatul Bragadiru' venue_name, 'assets/images/venues/palatul-bragadiru/sala-evenimente.png' image_path, 'Sala de evenimente a Palatului Bragadiru din București' alt_text, 1 sort_order
    UNION ALL SELECT 'Palatul Bragadiru', 'assets/images/venues/palatul-bragadiru/amenajare-interioara.png', 'Amenajare interioară pentru evenimente la Palatul Bragadiru', 2
    UNION ALL SELECT 'JW Marriott Bucharest Grand Hotel', 'assets/images/venues/jw-marriott/sala-evenimente.png', 'Sala de evenimente a JW Marriott Bucharest Grand Hotel', 1
    UNION ALL SELECT 'JW Marriott Bucharest Grand Hotel', 'assets/images/venues/jw-marriott/amenajare-interioara.png', 'Amenajare interioară la JW Marriott Bucharest Grand Hotel', 2
    UNION ALL SELECT 'InterContinental Athénée Palace Bucharest', 'assets/images/venues/athenee-palace/sala-evenimente.png', 'Sala de evenimente a InterContinental Athénée Palace Bucharest', 1
    UNION ALL SELECT 'InterContinental Athénée Palace Bucharest', 'assets/images/venues/athenee-palace/amenajare-interioara.png', 'Amenajare interioară la InterContinental Athénée Palace Bucharest', 2
    UNION ALL SELECT 'Radisson Blu Hotel Bucharest', 'assets/images/venues/radisson-blu/sala-evenimente.png', 'Sala de evenimente a Radisson Blu Hotel Bucharest', 1
    UNION ALL SELECT 'Radisson Blu Hotel Bucharest', 'assets/images/venues/radisson-blu/amenajare-interioara.png', 'Amenajare interioară la Radisson Blu Hotel Bucharest', 2
    UNION ALL SELECT 'Stejarii Country Club', 'assets/images/venues/stejarii-country-club/sala-evenimente.png', 'Sala de evenimente a Stejarii Country Club', 1
    UNION ALL SELECT 'Stejarii Country Club', 'assets/images/venues/stejarii-country-club/amenajare-interioara.png', 'Amenajare interioară la Stejarii Country Club', 2
) images ON images.venue_name = v.name
ON DUPLICATE KEY UPDATE
    mime_type = VALUES(mime_type),
    alt_text = VALUES(alt_text),
    sort_order = VALUES(sort_order);
