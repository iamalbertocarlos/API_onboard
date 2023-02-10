-- BEWARE! 
-- This script may override your currently selected database.
-- Make sure you're on an empty database before running it to ensure your data doesn't get lost.
DROP TABLE IF EXISTS TEMPORARY_TOKENS;
DROP TABLE IF EXISTS ROUTES;
DROP TABLE IF EXISTS DRIVERS;
DROP TABLE IF EXISTS USERS;
DROP EVENT IF EXISTS auto_remove_temporary_tokens;


-- ###################################################################################################
-- ------TABLES                                                                                      #
-- ###################################################################################################

CREATE TABLE USERS(
    userId INTEGER AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    surnames VARCHAR(100),
    password VARCHAR(100),
    nationalId VARCHAR(20),
    socSecNum VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(50) UNIQUE,
    role VARCHAR(10),
    accessToken VARCHAR(100)
);
CREATE TABLE ROUTES(
    routeId INTEGER AUTO_INCREMENT PRIMARY KEY,
    driverId INTEGER,
    managerId INTEGER,
    totalKm FLOAT,
    currentMapUrl VARCHAR(2048),
    originalMapUrl VARCHAR(2048),
    progress VARCHAR(30),
    vehiclePlate VARCHAR(20),
    date DATE,
    puntOrigen VARCHAR(100),
    puntFinal VARCHAR(100),
    CONSTRAINT routes_fk_driverId FOREIGN KEY (driverId) REFERENCES USERS(userId),
    CONSTRAINT routes_fk_managerId FOREIGN KEY (managerId) REFERENCES USERS(userId)
);
CREATE TABLE DRIVERS(
    userId INTEGER,
    managerId INTEGER,
    defaultVehiclePlate VARCHAR(20),
    PRIMARY KEY (userId),
    CONSTRAINT drivers_fk_userId FOREIGN KEY (userId) REFERENCES USERS(userId),
    CONSTRAINT drivers_fk_managerId FOREIGN KEY (managerId) REFERENCES USERS(userId)
);
CREATE TABLE TEMPORARY_TOKENS(
    tokenId INTEGER AUTO_INCREMENT PRIMARY KEY,
    tokenValue VARCHAR(100)
);

-- ###################################################################################################
-- ------TRIGGERS                                                                                    #
-- ###################################################################################################

-- AUTO REMOVE TEMPORARY_TOKENS TRIGGER EVERY HOUR
CREATE EVENT auto_remove_temporary_tokens
    ON SCHEDULE
        EVERY 1 HOUR
    DO
        DELETE FROM TEMPORARY_TOKENS;

-- ###################################################################################################
-- ------INSERTS                                                                                     #
-- ###################################################################################################

-- ADMINISTRATOR USER INSERT
INSERT INTO USERS (userId, name, surnames, password, nationalId, socSecNum, phone, email, role)
    VALUES(1, "admin", "-", "1234", "admin", "-", "phone", "1@1.1", "admin");


INSERT INTO ROUTES (routeId, driverId, managerId, totalKm, currentMapUrl, originalMapUrl, progress, vehiclePlate, date, puntOrigen, puntFinal)
    VALUES(3, 2, "1", "12321", "ñadfjdñfj.com", "lñjfaljf.com", "12", "dlsk", "2022-02-10", "halaMadrí", "cya");

