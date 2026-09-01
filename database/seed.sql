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
