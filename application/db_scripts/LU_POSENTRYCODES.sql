DROP TABLE IF EXISTS `LU_POSENTRYCODES`;

CREATE TABLE `LU_POSENTRYCODES` (
  `entrymodecode` varchar(8) NOT NULL,
  `pltrancode` varchar(2) NOT NULL,
  `datemodified` DATETIME DEFAULT current_timestamp
);

INSERT INTO `LU_POSENTRYCODES` VALUES ('0022','1',NOW());