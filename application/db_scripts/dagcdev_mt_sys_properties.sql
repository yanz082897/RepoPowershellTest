-- MySQL dump 10.13  Distrib 8.0.34, for Win64 (x86_64)
--
-- Host: dacawsgchostdev    Database: dagcdev
-- ------------------------------------------------------
-- Server version	8.0.33

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `mt_sys_properties`
--

DROP TABLE IF EXISTS `mt_sys_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mt_sys_properties` (
  `PROPERTY` varchar(320) NOT NULL,
  `VALUE` varchar(320) DEFAULT NULL,
  `FLAG` int DEFAULT NULL,
  `DESCRIPTION` varchar(320) DEFAULT NULL,
  PRIMARY KEY (`PROPERTY`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mt_sys_properties`
--

LOCK TABLES `mt_sys_properties` WRITE;
/*!40000 ALTER TABLE `mt_sys_properties` DISABLE KEYS */;
INSERT INTO `mt_sys_properties` VALUES ('AE_balInquiry','balance',1,'NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('AE_redemption','redeem',1,'NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('AE_reversal','reverse',1,'NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('AE_settlement','settlement',1,'NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('AE_voidRed','void',1,'NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('AE_voidRev','reversevoid',1,'NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('PINELABS_APIGATEWAY','http://127.0.0.1:8080/giftcard-apigateway-pinelabs/',1,'CSI Gateway. Should always end with a forward slash'),('PL_API_HTTP_TIMEOUT','15',1,'Allowance for PL response before sending a FORCED timeout reply'),('PL_DEFAULT_RC','98',1,'Default response code as requested by DAC 2023Oct26'),('PL_DEFAULT_RM','** No response from PL **',1,'Default response text'),('SIGNATURES','3739237d6d5c9120862241d37fda62a3849d120d7b2d2a815fd20c07c9d7d0b0',1,'SIGNATURES'),('PINELABS_APISERVER','https://AjF2m0R1nA8eaU2bN0BrCoU5h2I0-custuatdev.qwikcilver.com/QwikCilver/XnP/api/v3/',1,'CSI Gateway. Should always end with a forward slash'),('PL_balInquiry','gc/transactions',1,'Method: POST; NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('PL_redemption','gc/transactions',1,'Method: POST; NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('PL_reversal','gc/transactions/reverse',1,'Method: POST; NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('PL_settlement','batchclose',1,'Method: POST; NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('PL_voidRed','gc/transactions/cancel',1,'Method: POST; NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash'),('PL_voidRev','gc/transactions/reverse',1,'Method: POST; NOTE: Endpoint property VALUE field entries should: (1) NOT start with a forward slash (2) NOT contain an underscore (3) NOT end with a forward slash');
/*!40000 ALTER TABLE `mt_sys_properties` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-11-15 17:13:56
