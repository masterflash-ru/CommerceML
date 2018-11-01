-- MySQL dump 10.13  Distrib 5.6.41, for FreeBSD11.1 (i386)
--
-- Host: localhost    Database: gaz_krasnodar
-- ------------------------------------------------------
-- Server version	5.6.41

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `import_1c_brend`
--

DROP TABLE IF EXISTS `import_1c_brend`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_1c_brend` (
  `id1c` char(127) DEFAULT NULL,
  `name` char(255) DEFAULT NULL,
  `url` char(127) DEFAULT NULL,
  KEY `id1c` (`id1c`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='производители';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_1c_brend`
--

LOCK TABLES `import_1c_brend` WRITE;
/*!40000 ALTER TABLE `import_1c_brend` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_1c_brend` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_1c_category`
--

DROP TABLE IF EXISTS `import_1c_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_1c_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subid` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `name` char(255) DEFAULT NULL,
  `id1c` char(127) DEFAULT NULL,
  `flag_change` int(11) DEFAULT NULL COMMENT '1 - если были изменения',
  `url` char(127) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subid` (`subid`,`level`),
  KEY `name` (`name`),
  KEY `change` (`flag_change`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='категории';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_1c_category`
--

LOCK TABLES `import_1c_category` WRITE;
/*!40000 ALTER TABLE `import_1c_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_1c_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_1c_file`
--

DROP TABLE IF EXISTS `import_1c_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_1c_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_1c_tovar` char(127) DEFAULT NULL COMMENT 'ID товара в терминах 1C',
  `file` char(255) DEFAULT NULL COMMENT 'сам файл+ путь',
  `weight` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_1c_tovar` (`import_1c_tovar`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='файлы к товару';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_1c_file`
--

LOCK TABLES `import_1c_file` WRITE;
/*!40000 ALTER TABLE `import_1c_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_1c_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_1c_price`
--

DROP TABLE IF EXISTS `import_1c_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_1c_price` (
  `id1c` char(127) NOT NULL COMMENT 'ID товара в 1С',
  `import_1c_price_type` char(127) DEFAULT NULL COMMENT 'ID типа прайса в 1С',
  `currency` char(3) DEFAULT NULL,
  `price` decimal(11,2) DEFAULT NULL COMMENT 'сама цена'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='сами прайсы';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_1c_price`
--

LOCK TABLES `import_1c_price` WRITE;
/*!40000 ALTER TABLE `import_1c_price` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_1c_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_1c_price_type`
--

DROP TABLE IF EXISTS `import_1c_price_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_1c_price_type` (
  `id1c` char(127) NOT NULL COMMENT 'ID 1С прайса',
  `type` char(255) DEFAULT NULL COMMENT 'Имя цены',
  `currency` char(20) DEFAULT NULL COMMENT 'Валюта',
  `flag_change` int(11) DEFAULT NULL COMMENT '1-новая, 2-изменение'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='типы прайсов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_1c_price_type`
--

LOCK TABLES `import_1c_price_type` WRITE;
/*!40000 ALTER TABLE `import_1c_price_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_1c_price_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_1c_sklad`
--

DROP TABLE IF EXISTS `import_1c_sklad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_1c_sklad` (
  `id1c` char(127) DEFAULT NULL COMMENT 'ID товара в 1С',
  `import_1c_sklad_type` char(127) DEFAULT NULL COMMENT 'ID типа склада в 1С',
  `quantity` int(11) DEFAULT NULL COMMENT 'остаток'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='остатки на складах';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_1c_sklad`
--

LOCK TABLES `import_1c_sklad` WRITE;
/*!40000 ALTER TABLE `import_1c_sklad` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_1c_sklad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_1c_sklad_type`
--

DROP TABLE IF EXISTS `import_1c_sklad_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_1c_sklad_type` (
  `id1c` char(127) NOT NULL COMMENT 'ID 1С прайса',
  `type` char(255) DEFAULT NULL COMMENT 'Имя цены',
  `flag_change` int(11) DEFAULT NULL COMMENT '1-новая, 2-изменение'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='типы складов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_1c_sklad_type`
--

LOCK TABLES `import_1c_sklad_type` WRITE;
/*!40000 ALTER TABLE `import_1c_sklad_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_1c_sklad_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_1c_tovar`
--

DROP TABLE IF EXISTS `import_1c_tovar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_1c_tovar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_1c_category` int(11) DEFAULT NULL,
  `import_1c_brend` char(127) DEFAULT NULL COMMENT 'ID 1C производителя',
  `id1c` char(127) DEFAULT NULL,
  `name` char(255) DEFAULT NULL,
  `sku` char(127) DEFAULT NULL,
  `unit` char(100) DEFAULT NULL COMMENT 'имя бренда как есть',
  `description` text,
  `quantity` int(11) DEFAULT NULL COMMENT 'остаток',
  `category` char(127) DEFAULT NULL,
  `requisites_print` text COMMENT 'Наименование для печати',
  `url` char(127) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_1c_category` (`import_1c_category`),
  KEY `1c` (`id1c`),
  KEY `import_1c_brend` (`import_1c_brend`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='сам каталог';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_1c_tovar`
--

LOCK TABLES `import_1c_tovar` WRITE;
/*!40000 ALTER TABLE `import_1c_tovar` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_1c_tovar` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-11-01 18:56:34