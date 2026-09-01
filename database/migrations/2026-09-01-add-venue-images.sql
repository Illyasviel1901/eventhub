-- Migrare EventHub: galerie de imagini pentru locații
-- Pentru o bază nouă se recomandă database/schema.sql urmat de database/seed.sql.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS venue_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    image_data MEDIUMBLOB NULL,
    mime_type VARCHAR(100) NULL,
    alt_text VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,

    UNIQUE (venue_id, image_path),
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

INSERT INTO venue_images (venue_id, image_path, image_data, mime_type, alt_text, sort_order)
SELECT v.id, images.image_path, NULL, 'image/png', images.alt_text, images.sort_order
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
