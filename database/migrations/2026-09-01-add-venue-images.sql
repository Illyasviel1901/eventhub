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
