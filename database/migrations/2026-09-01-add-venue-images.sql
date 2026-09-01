-- Migrare EventHub: galerie de imagini pentru locații
-- Se rulează o singură dată pe baza existentă, apoi se rulează database/seed.sql.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS venue_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,

    UNIQUE (venue_id, image_path),
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

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
