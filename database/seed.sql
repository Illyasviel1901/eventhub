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

INSERT INTO venue_images (venue_id, image_path, alt_text, sort_order)
SELECT v.id, images.image_path, images.alt_text, images.sort_order
FROM venues v
JOIN (
    SELECT 'Palatul Bragadiru' venue_name, 'assets/images/venues/palatul-bragadiru/sala-principala.svg' image_path, 'Ilustrație a sălii principale pentru evenimente la Palatul Bragadiru' alt_text, 1 sort_order
    UNION ALL SELECT 'Palatul Bragadiru', 'assets/images/venues/palatul-bragadiru/masa-festiva.svg', 'Ilustrație cu aranjament festiv pentru Palatul Bragadiru', 2
    UNION ALL SELECT 'Palatul Bragadiru', 'assets/images/venues/palatul-bragadiru/scena-eveniment.svg', 'Ilustrație a scenei pentru evenimente la Palatul Bragadiru', 3
    UNION ALL SELECT 'Palatul Bragadiru', 'assets/images/venues/palatul-bragadiru/terasa-gradina.svg', 'Ilustrație a terasei și grădinii pentru Palatul Bragadiru', 4
    UNION ALL SELECT 'JW Marriott Bucharest Grand Hotel', 'assets/images/venues/jw-marriott/sala-principala.svg', 'Ilustrație a unei săli principale la JW Marriott Bucharest Grand Hotel', 1
    UNION ALL SELECT 'JW Marriott Bucharest Grand Hotel', 'assets/images/venues/jw-marriott/masa-festiva.svg', 'Ilustrație cu aranjament festiv la JW Marriott Bucharest Grand Hotel', 2
    UNION ALL SELECT 'JW Marriott Bucharest Grand Hotel', 'assets/images/venues/jw-marriott/scena-eveniment.svg', 'Ilustrație a unei scene de eveniment la JW Marriott Bucharest Grand Hotel', 3
    UNION ALL SELECT 'JW Marriott Bucharest Grand Hotel', 'assets/images/venues/jw-marriott/terasa-gradina.svg', 'Ilustrație a unei terase pentru JW Marriott Bucharest Grand Hotel', 4
    UNION ALL SELECT 'InterContinental Athénée Palace Bucharest', 'assets/images/venues/athenee-palace/sala-principala.svg', 'Ilustrație a unei săli istorice la InterContinental Athénée Palace Bucharest', 1
    UNION ALL SELECT 'InterContinental Athénée Palace Bucharest', 'assets/images/venues/athenee-palace/masa-festiva.svg', 'Ilustrație cu aranjament festiv la InterContinental Athénée Palace Bucharest', 2
    UNION ALL SELECT 'InterContinental Athénée Palace Bucharest', 'assets/images/venues/athenee-palace/scena-eveniment.svg', 'Ilustrație a scenei la InterContinental Athénée Palace Bucharest', 3
    UNION ALL SELECT 'InterContinental Athénée Palace Bucharest', 'assets/images/venues/athenee-palace/terasa-gradina.svg', 'Ilustrație a terasei la InterContinental Athénée Palace Bucharest', 4
    UNION ALL SELECT 'Radisson Blu Hotel Bucharest', 'assets/images/venues/radisson-blu/sala-principala.svg', 'Ilustrație a unei săli moderne la Radisson Blu Hotel Bucharest', 1
    UNION ALL SELECT 'Radisson Blu Hotel Bucharest', 'assets/images/venues/radisson-blu/masa-festiva.svg', 'Ilustrație cu aranjament festiv la Radisson Blu Hotel Bucharest', 2
    UNION ALL SELECT 'Radisson Blu Hotel Bucharest', 'assets/images/venues/radisson-blu/scena-eveniment.svg', 'Ilustrație a scenei la Radisson Blu Hotel Bucharest', 3
    UNION ALL SELECT 'Radisson Blu Hotel Bucharest', 'assets/images/venues/radisson-blu/terasa-gradina.svg', 'Ilustrație a terasei la Radisson Blu Hotel Bucharest', 4
    UNION ALL SELECT 'Stejarii Country Club', 'assets/images/venues/stejarii-country-club/sala-principala.svg', 'Ilustrație a sălii principale la Stejarii Country Club', 1
    UNION ALL SELECT 'Stejarii Country Club', 'assets/images/venues/stejarii-country-club/masa-festiva.svg', 'Ilustrație cu aranjament festiv la Stejarii Country Club', 2
    UNION ALL SELECT 'Stejarii Country Club', 'assets/images/venues/stejarii-country-club/scena-eveniment.svg', 'Ilustrație a scenei pentru evenimente la Stejarii Country Club', 3
    UNION ALL SELECT 'Stejarii Country Club', 'assets/images/venues/stejarii-country-club/terasa-gradina.svg', 'Ilustrație a grădinii pentru evenimente la Stejarii Country Club', 4
) images ON images.venue_name = v.name
ON DUPLICATE KEY UPDATE
    alt_text = VALUES(alt_text),
    sort_order = VALUES(sort_order);
