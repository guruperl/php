DROP PROCEDURE IF EXISTS `proc_peter_login`;
DROP TABLE IF EXISTS `cars`;

CREATE TABLE IF NOT EXISTS `cars` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Trademark` varchar(255) DEFAULT NULL,
  `Model` varchar(255) DEFAULT NULL,
  `HP` int DEFAULT NULL,
  `Liter` decimal(4,1) DEFAULT NULL,
  `Cyl` int DEFAULT NULL,
  `TransmissSpeedCount` int DEFAULT NULL,
  `TransmissAutomatic` enum('Yes','No') DEFAULT 'Yes',
  `MPG_City` int DEFAULT NULL,
  `MPG_Highway` int DEFAULT NULL,
  `Category` varchar(255) DEFAULT NULL,
  `Description` text,
  `Hyperlink` varchar(255) DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `PictureName` varchar(255) DEFAULT NULL,
  `PictureSize` int DEFAULT NULL,
  `PictureType` varchar(255) DEFAULT NULL,
  `PictureWidth` int DEFAULT NULL,
  `PictureHeight` int DEFAULT NULL,
  `Color` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `cars`
  (`ID`, `Trademark`, `Model`, `HP`, `Liter`, `Cyl`, `TransmissSpeedCount`, `TransmissAutomatic`, `MPG_City`, `MPG_Highway`, `Category`, `Description`, `Hyperlink`, `Price`, `PictureName`, `PictureSize`, `PictureType`, `PictureWidth`, `PictureHeight`, `Color`)
VALUES
  (1, 'Toyota', 'Camry', 203, 2.5, 4, 8, 'Yes', 28, 39, 'Sedan', 'Sample car row used by the Genelet project app.', 'https://www.toyota.com/camry/', 26420.00, NULL, NULL, NULL, NULL, NULL, 'Silver'),
  (2, 'Honda', 'Accord', 192, 1.5, 4, 10, 'Yes', 29, 37, 'Sedan', 'Second sample car row.', 'https://automobiles.honda.com/accord-sedan', 27295.00, NULL, NULL, NULL, NULL, NULL, 'Blue');

INSERT INTO `admin` (`adminid`, `login`, `passwd`, `status`, `created`)
VALUES ('SUPPORT', 'admin', SHA1(CONCAT('admin', 'KZ2k8M]B')), 'Yes', NOW())
ON DUPLICATE KEY UPDATE
  `adminid` = VALUES(`adminid`),
  `passwd` = VALUES(`passwd`),
  `status` = VALUES(`status`);

DELIMITER //
CREATE PROCEDURE `proc_peter_login`(
  IN i_login varchar(255),
  IN i_passwd varchar(255),
  IN i_ip int unsigned,
  OUT a_id varchar(255),
  OUT a_login varchar(255)
)
BEGIN
  SELECT `adminid`, `login`
    INTO a_id, a_login
    FROM `admin`
   WHERE `status` = 'Yes'
     AND `login` = i_login
     AND `passwd` = SHA1(CONCAT(i_login, i_passwd))
   LIMIT 1;
END//
DELIMITER ;
