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
-- Table structure for table `operation_deduction`
--

DROP TABLE IF EXISTS `operation_deduction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operation_deduction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `operation_date` date DEFAULT NULL,
  `mainzone` varchar(45) DEFAULT NULL,
  `zone` varchar(45) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `region_code` varchar(45) DEFAULT NULL,
  `bos_code` int DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `income_tax` decimal(10,2) DEFAULT NULL,
  `gl_code_income_tax` int DEFAULT NULL,
  `sss_contribution` decimal(10,2) DEFAULT NULL,
  `gl_code_sss_contribution` int DEFAULT NULL,
  `sss_loan` decimal(10,2) DEFAULT NULL,
  `gl_code_sss_loan` int DEFAULT NULL,
  `pagibig_contribution` decimal(10,2) DEFAULT NULL,
  `gl_code_pagibig_contribution` int DEFAULT NULL,
  `pagibig_loan` decimal(10,2) DEFAULT NULL,
  `gl_code_pagibig_loan` int DEFAULT NULL,
  `philhealth` decimal(10,2) DEFAULT NULL,
  `gl_code_philhealth` int DEFAULT NULL,
  `coated` decimal(10,2) DEFAULT NULL,
  `gl_code_coated` int DEFAULT NULL,
  `hmo` decimal(10,2) DEFAULT NULL,
  `gl_code_hmo` int DEFAULT NULL,
  `canteen` decimal(10,2) DEFAULT NULL,
  `gl_code_canteen` int DEFAULT NULL,
  `deduction_one` decimal(10,2) DEFAULT NULL,
  `gl_code_deduction_one` int DEFAULT NULL,
  `deduction_two` decimal(10,2) DEFAULT NULL,
  `gl_code_deduction_two` int DEFAULT NULL,
  `ml_fund` decimal(10,2) DEFAULT NULL,
  `gl_code_ml_fund` int DEFAULT NULL,
  `opec` decimal(10,2) DEFAULT NULL,
  `gl_code_opec` int DEFAULT NULL,
  `over_appraisal` decimal(10,2) DEFAULT NULL,
  `gl_code_over_appraisal` int DEFAULT NULL,
  `vpo_collection` decimal(10,2) DEFAULT NULL,
  `gl_code_vpo_collection` int DEFAULT NULL,
  `installment_account` decimal(10,2) DEFAULT NULL,
  `gl_code_installment_account` int DEFAULT NULL,
  `ticket` decimal(10,2) DEFAULT NULL,
  `gl_code_ticket` int DEFAULT NULL,
  `mobile_bill` decimal(10,2) DEFAULT NULL,
  `gl_code_mobile_bill` int DEFAULT NULL,
  `sako` decimal(10,2) DEFAULT NULL,
  `gl_code_sako` int DEFAULT NULL,
  `sako_savings` decimal(10,2) DEFAULT NULL,
  `gl_code_sako_savings` int DEFAULT NULL,
  `sheet_name` varchar(100) DEFAULT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_date` datetime DEFAULT NULL,
  `post_edi` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operation_deduction`
--

LOCK TABLES `operation_deduction` WRITE;
/*!40000 ALTER TABLE `operation_deduction` DISABLE KEYS */;
/*!40000 ALTER TABLE `operation_deduction` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-18 14:54:58
