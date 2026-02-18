-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: ho-cad118    Database: edi
-- ------------------------------------------------------
-- Server version	8.0.34

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
-- Table structure for table `mlfund_payroll_new`
--

DROP TABLE IF EXISTS `mlfund_payroll_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mlfund_payroll_new` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_date` date DEFAULT NULL,
  `mainzone` varchar(45) DEFAULT NULL,
  `zone` varchar(45) DEFAULT NULL,
  `region_code` varchar(45) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `employee_id_no` varchar(100) DEFAULT NULL,
  `employee_name` varchar(150) DEFAULT NULL,
  `mlcomaker_operation_amount` decimal(10,2) DEFAULT NULL,
  `mlcomaker_support_amount` decimal(10,2) DEFAULT NULL,
  `mljewelry_operation_amount` decimal(10,2) DEFAULT NULL,
  `mljewelry_support_amount` decimal(10,2) DEFAULT NULL,
  `mlopi_operation_amount` decimal(10,2) DEFAULT NULL,
  `mlopi_support_amount` decimal(10,2) DEFAULT NULL,
  `mlpcl_operation_amount` decimal(10,2) DEFAULT NULL,
  `mlpcl_support_amount` decimal(10,2) DEFAULT NULL,
  `mlregular_operation_amount` decimal(10,2) DEFAULT NULL,
  `mlregular_support_amount` decimal(10,2) DEFAULT NULL,
  `ml_fund_amount` decimal(10,2) DEFAULT NULL,
  `extension_file_type` varchar(45) DEFAULT NULL,
  `excel_format_type` varchar(45) DEFAULT NULL,
  `source_file` varchar(45) DEFAULT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_date` datetime DEFAULT NULL,
  `post_edi` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`) /*!80000 INVISIBLE */,
  KEY `index_all` (`payroll_date`,`employee_id_no`,`mainzone`,`zone`,`region_code`,`post_edi`,`excel_format_type`,`source_file`)
) ENGINE=InnoDB AUTO_INCREMENT=69558 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mlfund_payroll_new`
--

LOCK TABLES `mlfund_payroll_new` WRITE;
/*!40000 ALTER TABLE `mlfund_payroll_new` DISABLE KEYS */;
/*!40000 ALTER TABLE `mlfund_payroll_new` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-18 14:54:57
