-- Migrare EventHub: fotografii noi pentru locații și suport pentru upload ADMIN
-- Se rulează pe baza existentă după deploymentul codului care conține endpointul venue-image.php.
-- Nu modifică utilizatorii, rezervările sau datele locațiilor.

SET NAMES utf8mb4;

SET @add_image_data = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venue_images' AND COLUMN_NAME = 'image_data') = 0,
    'ALTER TABLE venue_images ADD COLUMN image_data MEDIUMBLOB NULL AFTER image_path',
    'SELECT 1'
);
PREPARE add_image_data_statement FROM @add_image_data;
EXECUTE add_image_data_statement;
DEALLOCATE PREPARE add_image_data_statement;

SET @add_mime_type = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venue_images' AND COLUMN_NAME = 'mime_type') = 0,
    'ALTER TABLE venue_images ADD COLUMN mime_type VARCHAR(100) NULL AFTER image_data',
    'SELECT 1'
);
PREPARE add_mime_type_statement FROM @add_mime_type;
EXECUTE add_mime_type_statement;
DEALLOCATE PREPARE add_mime_type_statement;

-- Elimină doar ilustrațiile statice vechi ale celor cinci locații standard.
-- Imaginile încărcate de administratori (image_data IS NOT NULL) sunt păstrate.
DELETE vi
FROM venue_images vi
JOIN venues v ON v.id = vi.venue_id
WHERE v.name IN (
    'Palatul Bragadiru',
    'JW Marriott Bucharest Grand Hotel',
    'InterContinental Athénée Palace Bucharest',
    'Radisson Blu Hotel Bucharest',
    'Stejarii Country Club'
)
AND vi.image_data IS NULL;

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
