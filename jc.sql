-- MySQL dump 10.13  Distrib 8.0.37, for Linux (x86_64)
--
-- Host: localhost    Database: joycity
-- ------------------------------------------------------
-- Server version	8.0.37-0ubuntu0.22.04.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `app_option`
--

DROP TABLE IF EXISTS `app_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_option` (
  `id` int NOT NULL AUTO_INCREMENT,
  `updated_at` datetime DEFAULT NULL,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx-app_option-key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_option`
--

LOCK TABLES `app_option` WRITE;
/*!40000 ALTER TABLE `app_option` DISABLE KEYS */;
INSERT INTO `app_option` VALUES (1,NULL,'mpstats_token',NULL);
/*!40000 ALTER TABLE `app_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attachment`
--

DROP TABLE IF EXISTS `attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `size` int NOT NULL,
  `extension` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachment`
--

LOCK TABLES `attachment` WRITE;
/*!40000 ALTER TABLE `attachment` DISABLE KEYS */;
INSERT INTO `attachment` VALUES (1,'/attachments/1698737435_6937_7ed7475aa7714d3690a231d393fa87be.png',369736,'png','image/png'),(2,'/attachments/1698737546_3104_000a6bbe788494a9ef8c6cdd58c4ae3c.jpg',2636358,'jpg','image/jpeg'),(3,'/attachments/1698737559_2552_ca39e4fa01ed5fff34dadb135adf2800.jpg',3479570,'jpg','image/jpeg'),(4,'/attachments/1698740566_4952_d6eb58226fad3de6dc7fc39d71effde3.png',19878,'png','image/png'),(5,'/attachments/1698740609_5724_92c0dd513a5e60c06f42a7cf2bee895f.png',17318,'png','image/png'),(6,'/attachments/1698744159_5464_e8aa1fd0bec232a80bc1e73b2f95069a.jpg',180496,'jpg','image/jpeg'),(7,'/attachments/1698785577_3708_e8aa1fd0bec232a80bc1e73b2f95069a.jpg',180496,'jpg','image/jpeg'),(8,'/attachments/1698827071_4441_e8aa1fd0bec232a80bc1e73b2f95069a.jpg',180496,'jpg','image/jpeg'),(9,'/attachments/1698835065_5131_53f735745977646dde35b34fb575f533.jpg',5294742,'jpg','image/jpeg'),(10,'/attachments/1698847708_7919_9f2242dd0fa9c094cf827120d4d2a13c.jpg',5747092,'jpg','image/jpeg'),(11,'/attachments/1698848289_2525_e8aa1fd0bec232a80bc1e73b2f95069a.jpg',180496,'jpg','image/jpeg'),(12,'/attachments/1698848759_2057_9ed7afbd609a0d18dae61be901e97f1a.jpg',4805629,'jpg','image/jpeg'),(13,'/attachments/1699869132_6834_05d4dea53ca679017bcf8dda4ba92b39.jpg',4939681,'jpg','image/jpeg'),(14,'/attachments/1699896972_5166_6ee6b6ec948318a9899fe9afcff40c2f.jpg',4759331,'jpg','image/jpeg'),(15,'/attachments/1699972358_3993_85822e6c4108685d19cc9196ea9f40c3.jpg',3545211,'jpg','image/jpeg'),(16,'/attachments/1700143546_1174_369b73af3dc7cef4bac28d8e73e34021.jpg',4746883,'jpg','image/jpeg'),(17,'/attachments/1700146538_8270_51ad46457e37fac3099c1da1088f275c.jpg',6079407,'jpg','image/jpeg'),(18,'/attachments/1707591472_3497_89783496de5a4f52056e5eef018b7889.png',43703,'png','image/png'),(19,'/attachments/1707592216_4566_3e15794a9cdcbd41630f9a7d91cc3f3d.png',1060674,'png','image/png'),(20,'/attachments/1707592457_8866_6ed749f7e5cd2f9c1f1546bc203b7526.png',257971,'png','image/png'),(21,'/attachments/1707597640_4383_2ba980de3ed9e9f3102befd56d392536.png',3856592,'png','image/png'),(22,'/attachments/1707600004_5952_ad896a97fca35fa631bf4f03ef78ff4f.png',4164163,'png','image/png'),(23,'/attachments/1707601119_5414_54d6cff80b80c5e955fcc55d3e4ad9a2.png',2025573,'png','image/png'),(24,'/attachments/1707602026_3238_dcae28ea7841934b819004290d410a42.png',1085564,'png','image/png'),(25,'/attachments/1707602491_5926_3aeb2076b8fb83966a2f74569d666be4.png',299129,'png','image/png'),(26,'/attachments/1707604122_8202_1c3c4b498e52fa2429fb2dea85e4f4f3.png',574480,'png','image/png'),(27,'/attachments/1707606832_4506_10eb336934d0ea860a803aa2cb8e2a76.png',284769,'png','image/png'),(28,'/attachments/1707607352_8262_fd63b765dfd0ece529dfe906435e7840.png',182983,'png','image/png'),(29,'/attachments/1707607570_8162_3d80fc9e943cf7b430111f620bf9c9f4.png',844609,'png','image/png'),(30,'/attachments/1707608244_5492_f1dbdbc15712e2efc9f2533c447648ab.png',605762,'png','image/png'),(31,'/attachments/1707608776_7343_28ae51d8d0533c6f9b14d33d5a973b6b.png',486436,'png','image/png'),(32,'/attachments/1707608945_1905_940c5529f3cfac805db1632f49cb8f3b.png',5779742,'png','image/png'),(33,'/attachments/1707611337_6928_16825feddb6e8c6f9e00d4a2c48ddd17.png',202812,'png','image/png'),(34,'/attachments/1707611648_5126_60cf53173fddfa314ff8b245081baa87.png',385257,'png','image/png'),(35,'/attachments/1707612267_8196_e994e6cfa14162b29618122421d7b9be.png',2725830,'png','image/png'),(36,'/attachments/1707612730_4841_68a4e2d0be95f929e2573ab534e337ea.png',1327811,'png','image/png'),(37,'/attachments/1707613006_8986_6aabc70818ef6040ff4e525998ad2521.png',419264,'png','image/png'),(38,'/attachments/1707613335_6374_7bd0d18e35fa6d57d78b464e0e4d94f5.png',6877439,'png','image/png'),(39,'/attachments/1707613673_5622_3027d5a6fe63a3fd798bcfd17e7814c7.png',454476,'png','image/png'),(40,'/attachments/1707613761_3056_54d6cff80b80c5e955fcc55d3e4ad9a2.png',2025573,'png','image/png'),(41,'/attachments/1707767332_3851_f750db97ae4c668f999a901e55be0a51.png',306631,'png','image/png'),(42,'/attachments/1707767483_3235_3247c909cbcdac9fb827f53a26282e90.png',78567,'png','image/png'),(43,'/attachments/1707767681_4934_3f0f89e97136ee04223f889526c94d8d.png',267542,'png','image/png'),(44,'/attachments/1707767703_1243_fec6b25d6aa056b8b4be3dea057f65b1.png',93761,'png','image/png'),(45,'/attachments/1707767796_7364_87f778c50f0b5ef4042206d582421859.png',626545,'png','image/png'),(46,'/attachments/1707767812_2191_6a80905ce2b1672955bcde26bff5d036.png',190696,'png','image/png'),(47,'/attachments/1707767831_7404_edf71499fc94b0971568b8f503f6362f.png',88045,'png','image/png'),(48,'/attachments/1707767851_3777_aa6a91ce3b30c33b6d1d58a96b27c478.png',160767,'png','image/png'),(49,'/attachments/1707767863_1233_5f273f62c525af76886c5f94f05c17ed.png',56072,'png','image/png'),(50,'/attachments/1707767874_1770_58ec1cce95f95453889797a1f002b4de.png',27257,'png','image/png'),(51,'/attachments/1707767887_2458_dedabba54ac61044d58cc8cc697b9b96.png',215011,'png','image/png'),(52,'/attachments/1707767913_6586_a9dc29f658135fc1fb73bae67b1d110c.png',156429,'png','image/png'),(53,'/attachments/1707767932_7608_e2ebe64bceb20e8c7f635ee892b80777.png',151862,'png','image/png'),(54,'/attachments/1707767955_6700_c7379a936521f97f4cebad49a04686b2.png',128582,'png','image/png'),(55,'/attachments/1707767969_8316_9bca62178a1bdf1c4a4963d08a951570.png',78935,'png','image/png'),(56,'/attachments/1707767980_1535_effd42ed1b55884b2ded7a8584ca2288.png',82479,'png','image/png'),(57,'/attachments/1707767999_6806_0136f85ee31cbcde2ee10f0b8c20ec26.png',684739,'png','image/png'),(58,'/attachments/1707768016_4724_e61cfc79d2ccdc522060723a4387ea49.png',160003,'png','image/png'),(59,'/attachments/1707768029_3350_088141a6b4adab0f38867292203f5afc.png',88907,'png','image/png'),(60,'/attachments/1707768047_7075_43b0e3766dcd5551ac4a2856eb444670.png',115532,'png','image/png'),(61,'/attachments/1707768065_4890_3247c909cbcdac9fb827f53a26282e90.png',78567,'png','image/png'),(62,'/attachments/1707768710_5896_5af0cf528bb292cfea34dbf7e1610727.png',1024777,'png','image/png'),(63,'/attachments/1707768719_6861_ce2b65fc4827d5c59522eb230476d013.png',954879,'png','image/png'),(64,'/attachments/1707768788_2579_523ee8cfec80a4fe052db8689c6961c9.png',34637,'png','image/png'),(65,'/attachments/1707827275_2582_d6ba45b250b9cc0d26217b73357f11b4.png',754809,'png','image/jpeg'),(66,'/attachments/1707828026_4511_2cd040966b777b3f1b3a8a9d7491d08b.png',327681,'png','image/jpeg'),(67,'/attachments/1707838254_1624_0281aabd999d4dd8bd7ec1ff2c69485f.jpg',4291412,'jpg','image/jpeg'),(68,'/attachments/1707907512_2055_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(69,'/attachments/1707907518_5709_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(70,'/attachments/1707907538_1265_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(71,'/attachments/1707907550_4009_6eb2b15a71f9e317526d858502a8375d.jpg',458138,'jpg','image/jpeg'),(72,'/attachments/1707907755_5090_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(73,'/attachments/1707907755_2783_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(74,'/attachments/1707907755_4476_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(75,'/attachments/1707907755_1314_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(76,'/attachments/1707907755_4475_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(77,'/attachments/1707909460_8914_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(78,'/attachments/1707909502_8565_417c8052b1464a6666c12078bf544c09.jpg',120525,'jpg','image/jpeg'),(79,'/attachments/1707910169_5137_bf3f8150ac212be8ce052848fc332aca.jpg',1336777,'jpg','image/jpeg'),(80,'/attachments/1707911564_2627_73d0bbc9ce9a75bb2d81cf52b7954956.jpg',148800,'jpg','image/jpeg'),(81,'/attachments/1707944768_2980_471c1e3ec25ff18a6f0483c2bdbe571c.jpg',6272044,'jpg','image/jpeg'),(82,'/attachments/1707945086_1931_86f600d396f3e5ed214689653f0beb92.jpg',3370889,'jpg','image/jpeg'),(83,'/attachments/1707945164_8819_a18fd4aa3fcfc9ed220973d5a1243392.jpg',6706569,'jpg','image/jpeg'),(84,'/attachments/1707945247_4266_8e1e48686f4174fd56869cded9b7b906.jpg',5020265,'jpg','image/jpeg'),(85,'/attachments/1707998808_2028_7b079c6c04296a4fca0acda22b9a92ff.jpg',158852,'jpg','image/jpeg'),(86,'/attachments/1708005089_7463_105a2ca36ffbf62da48fac14b9971971.png',248369,'png','image/jpeg'),(87,'/attachments/1708277752_8361_2aaa5b254fd7902da420ec5761514453.jpg',7599839,'jpg','image/jpeg'),(88,'/attachments/1708285977_5671_95cf37eb561f86a0fd10eb704661c8d8.jpg',5032433,'jpg','image/jpeg');
/*!40000 ALTER TABLE `attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_delivery_offer`
--

DROP TABLE IF EXISTS `buyer_delivery_offer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_delivery_offer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `order_id` int NOT NULL,
  `buyer_id` int NOT NULL,
  `manager_id` int NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price_product` decimal(12,4) NOT NULL,
  `total_quantity` int NOT NULL,
  `total_packaging_quantity` int NOT NULL,
  `product_height` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `product_width` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `product_depth` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `product_weight` decimal(8,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_buyer_delivery_offer_order_id` (`order_id`),
  KEY `fk_buyer_delivery_offer_buyer_id` (`buyer_id`),
  KEY `fk_buyer_delivery_offer_manager_id` (`manager_id`),
  CONSTRAINT `fk_buyer_delivery_offer_buyer_id` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_buyer_delivery_offer_manager_id` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_buyer_delivery_offer_order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_delivery_offer`
--

LOCK TABLES `buyer_delivery_offer` WRITE;
/*!40000 ALTER TABLE `buyer_delivery_offer` DISABLE KEYS */;
/*!40000 ALTER TABLE `buyer_delivery_offer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyer_offer`
--

DROP TABLE IF EXISTS `buyer_offer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_offer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `order_id` int NOT NULL,
  `buyer_id` int NOT NULL,
  `status` int NOT NULL,
  `price_product` decimal(12,4) NOT NULL,
  `price_inspection` decimal(12,4) NOT NULL,
  `total_quantity` int NOT NULL,
  `product_height` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `product_width` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `product_depth` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `product_weight` decimal(8,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  KEY `fk_buyer_offer_order_request1_idx` (`order_id`),
  KEY `fk_buyer_offer_user1_idx` (`buyer_id`),
  CONSTRAINT `fk_buyer_offer_order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `fk_buyer_offer_user1` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyer_offer`
--

LOCK TABLES `buyer_offer` WRITE;
/*!40000 ALTER TABLE `buyer_offer` DISABLE KEYS */;
INSERT INTO `buyer_offer` VALUES (1,'2024-02-14 15:11:09',9,12,1,20000.0000,20.0000,4000,0.0000,0.0000,0.0000,0.0000),(2,'2024-02-14 15:11:52',6,12,1,500.0000,500.0000,25,0.0000,0.0000,0.0000,0.0000),(3,'2024-02-14 15:18:46',4,12,1,30.0000,10.0000,2000,0.0000,0.0000,0.0000,0.0000),(4,'2024-02-14 15:21:25',5,12,1,20.0000,2.0000,5000,0.0000,0.0000,0.0000,0.0000),(5,'2024-02-18 20:23:22',21,12,2,2.0000,3.0000,1,0.0000,0.0000,0.0000,0.0000),(6,'2024-02-18 20:23:39',20,12,0,2.0000,1.0000,3,0.0000,0.0000,0.0000,0.0000),(7,'2024-02-18 20:24:01',13,12,1,1.0000,1.0000,1,0.0000,0.0000,0.0000,0.0000),(8,'2024-02-18 20:25:52',18,12,2,20.0000,30.0000,20,0.0000,0.0000,0.0000,0.0000),(9,'2024-02-18 20:32:32',22,12,1,5.0000,1.0000,50,0.0000,0.0000,0.0000,0.0000),(10,'2024-02-18 22:46:35',14,12,0,1.0000,1.0000,1,0.0000,0.0000,0.0000,0.0000),(11,'2024-02-18 22:49:35',23,12,1,1.0000,2.0000,1,0.0000,0.0000,0.0000,0.0000);
/*!40000 ALTER TABLE `buyer_offer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `en_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ru_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zh_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `avatar_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_category_attachment1_idx` (`avatar_id`),
  CONSTRAINT `fk_category_attachment1` FOREIGN KEY (`avatar_id`) REFERENCES `attachment` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Man','Мужчинам','男人',1,4),(2,'Woman','Женщинам','妇女',1,5),(3,'Women','Женщинам','女性',0,64),(4,'Footwear','Обувь','鞋类',0,43),(5,'Children','Детям','儿童',0,44),(6,'Men','Мужчинам','男性',0,62),(7,'Home','Дом','家居',0,63),(8,'Beauty','Красота','美容',0,45),(9,'Accessories','Аксессуары','配饰',0,46),(10,'Electronics','Электроника','电子产品',0,47),(11,'Toys','Игрушки','玩具',0,48),(12,'Furniture','Мебель','家具',0,49),(13,'Adult Products','Товары для взрослых','成人用品',0,50),(14,'Groceries','Продукты','食品',0,51),(15,'Sports','Спорт','体育',0,52),(16,'Home Appliances','Бытовая техника','家用电器',0,53),(17,'Pet Supplies','Зоотовары','宠物用品',0,54),(18,'Car Accessories','Автотовары','汽车配件',0,55),(19,'Books','Книги','书籍',0,56),(20,'Home Repair','Для ремонта','家居维修',0,57),(21,'Garden and Cottage','Сад и дача','花园和别墅',0,58),(22,'Health','Здоровье','健康',0,59),(23,'Stationery','Канцтовары','文具',0,60),(24,'Jewelry','Ювелирные изделия','珠宝',0,61);
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat`
--

DROP TABLE IF EXISTS `chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `twilio_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `order_id` int DEFAULT NULL,
  `user_verification_request_id` int DEFAULT NULL,
  `is_archive` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fk_chat_user_verification_request_id` (`user_verification_request_id`),
  KEY `fk_chat_order_id` (`order_id`),
  CONSTRAINT `fk_chat_order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `fk_chat_user_verification_request_id` FOREIGN KEY (`user_verification_request_id`) REFERENCES `user_verification_request` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat`
--

LOCK TABLES `chat` WRITE;
/*!40000 ALTER TABLE `chat` DISABLE KEYS */;
INSERT INTO `chat` VALUES (1,'2024-02-13 11:16:44','CH7277f564ac544fff824db9c9b9ce0573','','client_manager','verification',NULL,1,0),(2,'2024-02-13 15:19:26','CH061082c0fc384cceb7e8c1f644c8474d','','client_manager','verification',NULL,2,0),(3,'2024-02-13 18:29:14','CH8f5b9e3ce68d4ac693a0c47282d6168d','','client_manager','verification',NULL,3,0),(4,'2024-02-13 18:30:54','CH9f7299398ffd498eb673c69cd53799d2','','client_manager','order',1,NULL,1),(5,'2024-02-13 23:21:03','CH67002b2ddcf14b34a68543652a2c633c','','client_manager','order',2,NULL,1),(6,'2024-02-13 23:24:17','CH2c70f9ef94c24200b4b08fe3d8c9bd5b','','client_manager','order',3,NULL,1),(7,'2024-02-14 00:00:00','CHef79db90c9784f65a90832636f735da4','','client_buyer','order',4,NULL,0),(8,'2024-02-14 00:00:04','CH46a323dc42054590947604859abf6f9b','','manager_buyer','order',4,NULL,0),(9,'2024-02-14 00:00:06','CH7e2f66127385448f9bc71701c33f278f','','client_manager','order',4,NULL,0),(10,'2024-02-14 11:14:54','CH31bde9be97da4b328f2da7ddd3a58d7a','','client_buyer','order',5,NULL,0),(11,'2024-02-14 11:14:57','CH5f3d8408c3c64eddaa3b937f017e881d','','manager_buyer','order',5,NULL,0),(12,'2024-02-14 11:15:00','CH9e599b50ba07432dbbd35a3589efabee','','client_manager','order',5,NULL,0),(13,'2024-02-14 11:16:50','CH1112061ef08a4931b943ccf83bc715d0','','client_buyer','order',6,NULL,0),(14,'2024-02-14 11:16:53','CHf01fb6c71d9e46649c7678f2bc1e0196','','manager_buyer','order',6,NULL,0),(15,'2024-02-14 11:16:56','CH9687ab9e67004328a25219e2e405ff0a','','client_manager','order',6,NULL,0),(16,'2024-02-14 11:51:46','CH7bb7e37a3a584d43b395973b3f329a7b','','client_manager','verification',NULL,4,0),(17,'2024-02-14 14:14:59','CH5eb9a46262154e1e9e9bb84a1532c7b5','','client_manager','verification',NULL,5,0),(18,'2024-02-14 14:29:29','CH1f37f5350e984a64a4578e73b88977b6','','client_manager','order',7,NULL,1),(19,'2024-02-14 14:52:44','CH986166213d5d48f9b5cd09bd12002937','','client_manager','order',8,NULL,1),(20,'2024-02-14 14:55:31','CHd208f4c0b2b2488b9620ec76e87cfae1','','client_buyer','order',9,NULL,0),(21,'2024-02-14 14:55:34','CH07ae007893744653b721de7f114bffff','','manager_buyer','order',9,NULL,0),(22,'2024-02-14 14:55:37','CH3dfdc51f03cf4af58da2df35cad71fac','','client_manager','order',9,NULL,0),(23,'2024-02-14 15:28:28','CH6b527a36b6c247e2906a939e22ce505a','','client_fulfilment','order',4,NULL,0),(24,'2024-02-14 15:28:31','CHb16c41e1e06c42e292e711d6717c6911','','manager_fulfilment','order',4,NULL,0),(25,'2024-02-14 17:12:48','CHe55a883e187747a4b7c2aab2e7d551e1','','client_manager','verification',NULL,6,0),(26,'2024-02-14 22:19:54','CH4e2d5160bcd0441a803609a638304fbc','','client_manager','verification',NULL,7,0),(27,'2024-02-15 00:09:23','CHfcb74b8de16d49c3a4ed08229854ef80','','client_fulfilment','order',6,NULL,0),(28,'2024-02-15 00:09:26','CHc28071ffadb54dccbc2c7880948e3a0b','','manager_fulfilment','order',6,NULL,0),(29,'2024-02-15 00:09:57','CH021237b76a3d49fcbfd3f90c8f877786','','client_fulfilment','order',5,NULL,0),(30,'2024-02-15 00:10:01','CHfd9c2b2f811745e18f72ca9574a671d8','','manager_fulfilment','order',5,NULL,0),(31,'2024-02-15 00:16:34','CH7b94ca76d9174b9582acab986c98f458','','client_manager','verification',NULL,8,0),(32,'2024-02-15 13:19:18','CH48dd7177224b49f998c1e3210ad3fd61','','client_manager','verification',NULL,9,0),(33,'2024-02-15 15:06:48','CHc70d614d5c7b4946ad766df6a03f7eeb','','client_manager','order',10,NULL,1),(34,'2024-02-16 00:30:19','CH1ea0eb4c01bf45cd98ef1d0f6453b156','','client_manager','verification',NULL,10,0),(35,'2024-02-16 00:40:53','CH9bcbf713cdd54fbc8b90d74a4743cd65','','client_manager','order',11,NULL,1),(36,'2024-02-16 20:08:06','CHfa67e7443ef4420bab22f3019fcf4e4e','','client_manager','order',12,NULL,1),(37,'2024-02-17 01:27:48','CH8e8fca9a154442098a08ade037e28262','','client_manager','verification',NULL,11,0),(38,'2024-02-18 17:39:33','CH3e19478a4bec4321a12d3e2d26d0f5be','','client_buyer','order',13,NULL,0),(39,'2024-02-18 17:39:37','CH41055fd266474463ab366e11c53620da','','manager_buyer','order',13,NULL,0),(40,'2024-02-18 17:39:40','CHef6774224f9c42b79f6ce8b941764e80','','client_manager','order',13,NULL,0),(41,'2024-02-18 17:40:12','CH4451e46a1abf4462b02a25a7a85b5731','','client_buyer','order',14,NULL,0),(42,'2024-02-18 17:40:14','CHaa5ebb55e715471286c21edbe814b125','','manager_buyer','order',14,NULL,0),(43,'2024-02-18 17:40:17','CH6c0324ee270743a9964aadacf15cd1e3','','client_manager','order',14,NULL,0),(44,'2024-02-18 17:40:34','CH60e35b6adf4241ea8575ce665756843a','','client_buyer','order',15,NULL,0),(45,'2024-02-18 17:40:37','CHabc10b9ac2ce44f48c13da0b2fdaa43a','','manager_buyer','order',15,NULL,0),(46,'2024-02-18 17:40:39','CH854d6ea02e114d6099f20bd2e4f174b5','','client_manager','order',15,NULL,0),(47,'2024-02-18 18:01:39','CH90b06f981e0542cc98318d60b91b1e66','','client_manager','verification',NULL,12,0),(48,'2024-02-18 18:03:18','CH35a20a2f1b794adeb77748acda71cd8d','','client_buyer','order',16,NULL,0),(49,'2024-02-18 18:03:21','CHb623ef003d70426db548327118dd6744','','manager_buyer','order',16,NULL,0),(50,'2024-02-18 18:03:24','CH6a5e4f18a7304eb0b30120027835c526','','client_manager','order',16,NULL,0),(51,'2024-02-18 18:53:39','CH17066ace63064817aa05c9665c90c3c4','','client_buyer','order',17,NULL,0),(52,'2024-02-18 18:53:41','CH072b3ac2b88541eb942fa5c0188b835b','','manager_buyer','order',17,NULL,0),(53,'2024-02-18 18:53:44','CH5ab00ca9d10b4f368c040a134a4a2556','','client_manager','order',17,NULL,0),(54,'2024-02-18 19:01:26','CH1dfab5d0bb1a4258b17907ab6bedec2c','','client_buyer','order',18,NULL,1),(55,'2024-02-18 19:01:29','CHaeaa44c3cf7d40f1aebd4251ee57ffe1','','manager_buyer','order',18,NULL,1),(56,'2024-02-18 19:01:32','CH6243921d350b4384a385e4ac1ebb2a43','','client_manager','order',18,NULL,1),(57,'2024-02-18 19:03:17','CH6fd65fc575cd4756b896d20615081009','','client_buyer','order',19,NULL,0),(58,'2024-02-18 19:03:20','CH42a0d222c3d34dd98f84e9614394f5a8','','manager_buyer','order',19,NULL,0),(59,'2024-02-18 19:03:22','CH4f2bd9dcf820457eb749824445272eb4','','client_manager','order',19,NULL,0),(60,'2024-02-18 19:07:53','CH25a546aa679c4fd98576c42c519db521','','client_buyer','order',20,NULL,0),(61,'2024-02-18 19:07:56','CH90dd90a8b66147728dcd60434c60f6f8','','manager_buyer','order',20,NULL,0),(62,'2024-02-18 19:07:59','CHc3cf020a98ed4d98a5809fc39e59b2be','','client_manager','order',20,NULL,0),(63,'2024-02-18 19:57:07','CHd05795156cd64a698fd963829c1f0172','','client_buyer','order',21,NULL,1),(64,'2024-02-18 19:57:10','CHf79c57ef9e304e5e8ad0b0178333822d','','manager_buyer','order',21,NULL,1),(65,'2024-02-18 19:57:13','CHad4fbde1932b4e0c8226463cdcc4fb7c','','client_manager','order',21,NULL,1),(66,'2024-02-18 20:30:30','CH4cd6e0c2dc7044ec8fc772dbb60e7799','','client_buyer','order',22,NULL,0),(67,'2024-02-18 20:30:33','CHbf43f1c40d5d4c6e8f74ba588cd433ea','','manager_buyer','order',22,NULL,0),(68,'2024-02-18 20:30:35','CH03fdcd5bdc8d4ed0990e1b2a9ae60be7','','client_manager','order',22,NULL,0),(69,'2024-02-18 20:34:55','CH47da8f92fbfd4da49b34427e4fc98334','','client_fulfilment','order',22,NULL,0),(70,'2024-02-18 20:34:58','CH9021506703574a428e30f503ca99f185','','manager_fulfilment','order',22,NULL,0),(71,'2024-02-18 22:41:34','CH17babb1b3c8e4a8ebf5b0725096d060a','','client_buyer','order',23,NULL,0),(72,'2024-02-18 22:41:37','CHd559d1c6beb94cd29d084209c8bcd920','','manager_buyer','order',23,NULL,0),(73,'2024-02-18 22:41:40','CH57fea4a77d5c4c309fbe50b0f831d689','','client_manager','order',23,NULL,0),(74,'2024-02-18 22:51:04','CH8d3bb4322ee746a299b1c6e2ac9d304b','','client_fulfilment','order',23,NULL,0),(75,'2024-02-18 22:51:07','CH4ca2bd70a65849d5bb8cde76d2e9e49d','','manager_fulfilment','order',23,NULL,0),(76,'2024-02-21 00:47:12','CH717ffa59e27846e4b3bef7decc27a78a','','client_manager','verification',NULL,13,0),(77,'2024-02-26 17:10:58','CH4de39dbb2fb74939b237bad6c8fb5b8f','','client_manager','verification',NULL,14,0),(78,'2024-02-27 22:04:54','CH64af145095be49df930114b8ca6c6978','','client_manager','verification',NULL,15,0),(79,'2024-04-09 11:33:23','CH08a7fbe5c6ec486e9df3474de67e049a','','client_manager','verification',NULL,16,0),(80,'2024-04-10 11:17:25','CHea6c616c02164a3b9e752eb4e9551bf6','','client_manager','verification',NULL,17,0),(81,'2024-07-11 19:34:50','CH47783f8fd3f44c1d918636c11e3f33d5','','client_manager','verification',NULL,18,0);
/*!40000 ALTER TABLE `chat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_translate`
--

DROP TABLE IF EXISTS `chat_translate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_translate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ru` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zh` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_key` (`message_key`),
  KEY `idx-chat_translate-message_key` (`message_key`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_translate`
--

LOCK TABLES `chat_translate` WRITE;
/*!40000 ALTER TABLE `chat_translate` DISABLE KEYS */;
INSERT INTO `chat_translate` VALUES (1,'CH7277f564ac544fff824db9c9b9ce0573_0','Добрый день. Отправьте фото чека с оплатой.','Добрый день. 发送付款收据的照片。','Good afternoon. Send a photo of the receipt with the payment.'),(2,'CH7277f564ac544fff824db9c9b9ce0573_2','Отлично. Одобрено','优秀。已批准。','Excellent. Approved.'),(3,'CH7277f564ac544fff824db9c9b9ce0573_5','Привет','你好','Hello'),(4,'CH7277f564ac544fff824db9c9b9ce0573_6','Привет Нарек, все ли у тебя хорошо?','你好，纳雷克，你还好吗？','Hello Narek, is everything good with you?'),(5,'CHef79db90c9784f65a90832636f735da4_0','Здравствуйте','你好','Hello'),(6,'CHd208f4c0b2b2488b9620ec76e87cfae1_0','Здравствуйте','你好','Hello'),(7,'CHd208f4c0b2b2488b9620ec76e87cfae1_1','Добрый день','早上好','Good day'),(8,'CHef79db90c9784f65a90832636f735da4_1','Добрый день','早上好','Good day'),(9,'CH7e2f66127385448f9bc71701c33f278f_0','Добрый день. Оплатите пожалуйста и пришлите чек!','Добрый день. Оплатите пожалуйста и пришлите чек!','Good day. Please pay and send a check!'),(10,'CH7e2f66127385448f9bc71701c33f278f_1','Хорошо, спасибо.','好的，谢谢。','Okay, thanks.'),(11,'CH7e2f66127385448f9bc71701c33f278f_2','Я заплатил. Это мой чек','我已经付款了。这是我的收据。','I’ve paid. This is my receipt'),(12,'CH7e2f66127385448f9bc71701c33f278f_4','Отлично. Одобрили сделку','很好。交易已获批准','Excellent. The deal has been approved'),(13,'CHd208f4c0b2b2488b9620ec76e87cfae1_3','.','.','.'),(14,'CH31bde9be97da4b328f2da7ddd3a58d7a_0','Здравствуйте','你好','Hello'),(15,'CH1112061ef08a4931b943ccf83bc715d0_0','Здравствуйте','你好','Hello'),(16,'CH5eb9a46262154e1e9e9bb84a1532c7b5_0','Добрый день!','你好！','Good day!'),(17,'CH8f5b9e3ce68d4ac693a0c47282d6168d_0','Привет','你好','Hello'),(18,'CH7bb7e37a3a584d43b395973b3f329a7b_0','Добрый день. Одобряю вам оплату.','您好。我批准您的付款。','Good day. I approve your payment.'),(19,'CHd208f4c0b2b2488b9620ec76e87cfae1_4','Ваш заказ в отправлен','您的订单已发送','Your order has been sent'),(20,'CHe55a883e187747a4b7c2aab2e7d551e1_0','Добрый день. Верификация одобрена для вас.','下午好。您的验证已通过。','Good afternoon. Verification has been approved for you.'),(21,'CH6b527a36b6c247e2906a939e22ce505a_0','Добрый день, скиньте ссылку на техническое задание','你好，请发送技术任务的链接。','Good day, please send a link to the technical task'),(22,'CHef79db90c9784f65a90832636f735da4_3','Ваш заказ на складе в Москве','您的订单在莫斯科的仓库里','Your order is at the warehouse in Moscow'),(23,'CH03fdcd5bdc8d4ed0990e1b2a9ae60be7_0','Байер вам скоро ответит','拜尔很快会回答你','Bayer will answer you soon'),(24,'CH03fdcd5bdc8d4ed0990e1b2a9ae60be7_1','Спасибо','谢谢','Thank you'),(25,'CH57fea4a77d5c4c309fbe50b0f831d689_1','Внесено предложение','提交提案','Proposal submitted'),(26,'CH57fea4a77d5c4c309fbe50b0f831d689_0','Одобрено байером','买家批准','Approved by the buyer'),(27,'CH57fea4a77d5c4c309fbe50b0f831d689_3','Хорошо','好','Good'),(28,'CH717ffa59e27846e4b3bef7decc27a78a_0','Привет','你好','Hello');
/*!40000 ALTER TABLE `chat_translate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_user`
--

DROP TABLE IF EXISTS `chat_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chat_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_chat_user_chat_id` (`chat_id`),
  KEY `fk_chat_user_user_id` (`user_id`),
  CONSTRAINT `fk_chat_user_chat_id` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`),
  CONSTRAINT `fk_chat_user_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_user`
--

LOCK TABLES `chat_user` WRITE;
/*!40000 ALTER TABLE `chat_user` DISABLE KEYS */;
INSERT INTO `chat_user` VALUES (1,1,2),(2,1,8),(3,2,8),(4,2,14),(5,3,6),(6,3,8),(7,4,6),(8,4,8),(9,5,8),(10,5,14),(11,6,8),(12,6,14),(13,7,2),(14,7,12),(15,8,2),(16,8,8),(17,9,2),(18,9,8),(19,10,12),(20,10,14),(21,11,8),(22,11,14),(23,12,8),(24,12,14),(25,13,12),(26,13,14),(27,14,8),(28,14,14),(29,15,8),(30,15,14),(31,16,8),(32,16,16),(33,17,8),(34,17,20),(35,18,8),(36,18,20),(37,19,8),(38,19,20),(39,20,12),(40,20,20),(41,21,8),(42,21,20),(43,22,8),(44,22,20),(45,23,2),(46,23,10),(47,24,8),(48,24,10),(49,25,8),(50,25,21),(51,26,8),(52,26,11),(53,27,7),(54,27,14),(55,28,7),(56,28,8),(57,29,7),(58,29,14),(59,30,7),(60,30,8),(61,31,8),(62,31,25),(63,32,8),(64,32,26),(65,33,8),(66,33,26),(67,34,8),(68,34,28),(69,35,8),(70,35,28),(71,36,8),(72,36,26),(73,37,8),(74,37,32),(75,38,12),(76,38,21),(77,39,8),(78,39,21),(79,40,8),(80,40,21),(81,41,12),(82,41,21),(83,42,8),(84,42,21),(85,43,8),(86,43,21),(87,44,12),(88,44,21),(89,45,8),(90,45,21),(91,46,8),(92,46,21),(93,47,4),(94,47,8),(95,48,4),(96,48,12),(97,49,4),(98,49,8),(99,50,4),(100,50,8),(101,51,12),(102,51,21),(103,52,21),(104,52,31),(105,53,21),(106,53,31),(107,54,12),(108,54,21),(109,55,8),(110,55,21),(111,56,8),(112,56,21),(113,57,12),(114,57,21),(115,58,8),(116,58,21),(117,59,8),(118,59,21),(119,60,12),(120,60,21),(121,61,21),(122,61,31),(123,62,21),(124,62,31),(125,63,12),(126,63,21),(127,64,8),(128,64,21),(129,65,8),(130,65,21),(131,66,12),(132,66,14),(133,67,14),(134,67,31),(135,68,14),(136,68,31),(137,69,13),(138,69,14),(139,70,13),(140,70,31),(141,71,12),(142,71,21),(143,72,21),(144,72,31),(145,73,21),(146,73,31),(147,74,13),(148,74,21),(149,75,13),(150,75,31),(151,76,31),(152,76,34),(153,77,8),(154,77,36),(155,78,8),(156,78,35),(157,79,8),(158,79,37),(159,80,8),(160,80,38),(161,81,31),(162,81,45);
/*!40000 ALTER TABLE `chat_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_point_address`
--

DROP TABLE IF EXISTS `delivery_point_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_point_address` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_delivery_point_id` int NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_user_id_delivery_point_address` (`user_id`),
  KEY `fk_delivery_point_address_type_delivery_point_id` (`type_delivery_point_id`),
  CONSTRAINT `fk_delivery_point_address_type_delivery_point_id` FOREIGN KEY (`type_delivery_point_id`) REFERENCES `type_delivery_point` (`id`),
  CONSTRAINT `fk_delivery_point_address_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_point_address`
--

LOCK TABLES `delivery_point_address` WRITE;
/*!40000 ALTER TABLE `delivery_point_address` DISABLE KEYS */;
INSERT INTO `delivery_point_address` VALUES (1,2,'проспект Дзержинского 127',0,5),(2,2,'Апаренки  ',0,7),(3,2,'Шипиловская 14',0,9),(4,2,'Кутузовское шоссе 45/7',0,10),(5,2,'yrttttgggy',0,13),(6,1,'г.Москва, склад мечты клиента 17/2',0,NULL),(7,2,'кутузовское шоссе 45',0,18),(8,2,'Нагатинская наб. 66-2',0,19),(9,2,'Ленина 11',0,22),(10,2,'Красная площадь 3',0,24),(11,2,'Пермская дом 1 стр 1',0,27),(12,2,'ул. 1905г',0,29);
/*!40000 ALTER TABLE `delivery_point_address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_buyer`
--

DROP TABLE IF EXISTS `feedback_buyer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_buyer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `buyer_id` int NOT NULL,
  `text` varchar(750) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rating` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_feedback_buyer_created_by` (`created_by`),
  KEY `fk_feedback_buyer_buyer_id` (`buyer_id`),
  CONSTRAINT `fk_feedback_buyer_buyer_id` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_feedback_buyer_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_buyer`
--

LOCK TABLES `feedback_buyer` WRITE;
/*!40000 ALTER TABLE `feedback_buyer` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_buyer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_buyer_link_attachment`
--

DROP TABLE IF EXISTS `feedback_buyer_link_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_buyer_link_attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `feedback_buyer_id` int NOT NULL,
  `attachment_id` int NOT NULL,
  `type` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_feedback_buyer_link_attachment_feedback_buyer_id` (`feedback_buyer_id`),
  KEY `fk_feedback_buyer_link_attachment_attachment_id` (`attachment_id`),
  CONSTRAINT `fk_feedback_buyer_link_attachment_attachment_id` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `fk_feedback_buyer_link_attachment_feedback_buyer_id` FOREIGN KEY (`feedback_buyer_id`) REFERENCES `feedback_buyer` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_buyer_link_attachment`
--

LOCK TABLES `feedback_buyer_link_attachment` WRITE;
/*!40000 ALTER TABLE `feedback_buyer_link_attachment` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_buyer_link_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_product`
--

DROP TABLE IF EXISTS `feedback_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_product` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `product_id` int NOT NULL,
  `text` varchar(750) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rating` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_feedback_product_created_by` (`created_by`),
  KEY `fk_feedback_product_product_id` (`product_id`),
  CONSTRAINT `fk_feedback_product_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_feedback_product_product_id` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_product`
--

LOCK TABLES `feedback_product` WRITE;
/*!40000 ALTER TABLE `feedback_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_product_link_attachment`
--

DROP TABLE IF EXISTS `feedback_product_link_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_product_link_attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `feedback_product_id` int NOT NULL,
  `attachment_id` int NOT NULL,
  `type` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_feedback_product_link_attachment_feedback_product_id` (`feedback_product_id`),
  KEY `fk_feedback_product_link_attachment_attachment_id` (`attachment_id`),
  CONSTRAINT `fk_feedback_product_link_attachment_attachment_id` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `fk_feedback_product_link_attachment_feedback_product_id` FOREIGN KEY (`feedback_product_id`) REFERENCES `feedback_product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_product_link_attachment`
--

LOCK TABLES `feedback_product_link_attachment` WRITE;
/*!40000 ALTER TABLE `feedback_product_link_attachment` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_product_link_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_user`
--

DROP TABLE IF EXISTS `feedback_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `text` varchar(750) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_feedback_user_created_by` (`created_by`),
  CONSTRAINT `fk_feedback_user_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_user`
--

LOCK TABLES `feedback_user` WRITE;
/*!40000 ALTER TABLE `feedback_user` DISABLE KEYS */;
INSERT INTO `feedback_user` VALUES (1,'2024-02-13 15:40:26',2,'Есть проблема с чатами','bug'),(2,'2024-02-14 13:49:15',19,'Добрый день! Пока тестируем ','bug'),(3,'2024-02-14 14:18:22',20,'Отььььбббббббббббббббб','proposal');
/*!40000 ALTER TABLE `feedback_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_user_link_attachment`
--

DROP TABLE IF EXISTS `feedback_user_link_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_user_link_attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `feedback_user_id` int NOT NULL,
  `attachment_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_feedback_user_link_attachment_feedback_user_id` (`feedback_user_id`),
  KEY `fk_feedback_user_link_attachment_attachment_id` (`attachment_id`),
  CONSTRAINT `fk_feedback_user_link_attachment_attachment_id` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `fk_feedback_user_link_attachment_feedback_user_id` FOREIGN KEY (`feedback_user_id`) REFERENCES `feedback_user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_user_link_attachment`
--

LOCK TABLES `feedback_user_link_attachment` WRITE;
/*!40000 ALTER TABLE `feedback_user_link_attachment` DISABLE KEYS */;
INSERT INTO `feedback_user_link_attachment` VALUES (1,1,66),(2,2,72),(3,2,73),(4,2,74),(5,2,75),(6,2,76),(7,3,78);
/*!40000 ALTER TABLE `feedback_user_link_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fulfillment_inspection_report`
--

DROP TABLE IF EXISTS `fulfillment_inspection_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fulfillment_inspection_report` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `order_id` int NOT NULL,
  `defects_count` int NOT NULL DEFAULT '0',
  `package_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_deep` tinyint NOT NULL DEFAULT '0',
  `fulfillment_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fk-fulfillment_inspection_report-order_id` (`order_id`),
  KEY `fk_fulfillment_inspection_report_user` (`fulfillment_id`),
  CONSTRAINT `fk-fulfillment_inspection_report-order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `fk_fulfillment_inspection_report_user` FOREIGN KEY (`fulfillment_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fulfillment_inspection_report`
--

LOCK TABLES `fulfillment_inspection_report` WRITE;
/*!40000 ALTER TABLE `fulfillment_inspection_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `fulfillment_inspection_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fulfillment_marketplace_transaction`
--

DROP TABLE IF EXISTS `fulfillment_marketplace_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fulfillment_marketplace_transaction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `fulfillment_id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_count` int NOT NULL,
  `amount` decimal(10,4) NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_fulfillment_marketplace_transaction_fulfillment` (`fulfillment_id`),
  KEY `fk_fulfillment_marketplace_transaction_order` (`order_id`),
  CONSTRAINT `fk_fulfillment_marketplace_transaction_fulfillment` FOREIGN KEY (`fulfillment_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_fulfillment_marketplace_transaction_order` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fulfillment_marketplace_transaction`
--

LOCK TABLES `fulfillment_marketplace_transaction` WRITE;
/*!40000 ALTER TABLE `fulfillment_marketplace_transaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `fulfillment_marketplace_transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fulfillment_offer`
--

DROP TABLE IF EXISTS `fulfillment_offer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fulfillment_offer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `order_id` int NOT NULL,
  `fulfillment_id` int NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `overall_price` decimal(10,4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fk_fulfillment_offer_order_request_idx` (`order_id`),
  KEY `fk_fulfillment_offer_user_idx` (`fulfillment_id`),
  CONSTRAINT `fk_fulfillment_offer_order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `fk_fulfillment_offer_user` FOREIGN KEY (`fulfillment_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fulfillment_offer`
--

LOCK TABLES `fulfillment_offer` WRITE;
/*!40000 ALTER TABLE `fulfillment_offer` DISABLE KEYS */;
INSERT INTO `fulfillment_offer` VALUES (1,'2024-02-18 20:40:46',22,13,'created',6500.0000),(2,'2024-02-18 23:09:25',23,13,'created',1000.0000);
/*!40000 ALTER TABLE `fulfillment_offer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fulfillment_packaging_labeling`
--

DROP TABLE IF EXISTS `fulfillment_packaging_labeling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fulfillment_packaging_labeling` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `order_id` int NOT NULL,
  `fulfillment_id` int NOT NULL,
  `shipped_product` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fk-fulfillment_packaging_labeling-order_id` (`order_id`),
  KEY `fk_fulfillment_packaging_labeling_user` (`fulfillment_id`),
  CONSTRAINT `fk-fulfillment_packaging_labeling-order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `fk_fulfillment_packaging_labeling_user` FOREIGN KEY (`fulfillment_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fulfillment_packaging_labeling`
--

LOCK TABLES `fulfillment_packaging_labeling` WRITE;
/*!40000 ALTER TABLE `fulfillment_packaging_labeling` DISABLE KEYS */;
/*!40000 ALTER TABLE `fulfillment_packaging_labeling` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fulfillment_stock_report`
--

DROP TABLE IF EXISTS `fulfillment_stock_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fulfillment_stock_report` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `order_id` int NOT NULL,
  `fulfillment_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fk-fulfillment_stock_report-order_id` (`order_id`),
  KEY `fk_fulfillment_stock_report` (`fulfillment_id`),
  CONSTRAINT `fk-fulfillment_stock_report-order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `fk_fulfillment_stock_report` FOREIGN KEY (`fulfillment_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fulfillment_stock_report`
--

LOCK TABLES `fulfillment_stock_report` WRITE;
/*!40000 ALTER TABLE `fulfillment_stock_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `fulfillment_stock_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fulfillment_stock_report_link_attachment`
--

DROP TABLE IF EXISTS `fulfillment_stock_report_link_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fulfillment_stock_report_link_attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fulfillment_stock_report_id` int NOT NULL,
  `attachment_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-fulfillment_stock_report_link_attachment-attachment_id` (`attachment_id`),
  KEY `idx-fulfillment_stock_report_link_attachment_stock_report` (`fulfillment_stock_report_id`),
  CONSTRAINT `fk-fulfillment_stock_report_link_attachment-attachment_id` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `fk-fulfillment_stock_report_link_attachment_stock_report` FOREIGN KEY (`fulfillment_stock_report_id`) REFERENCES `fulfillment_stock_report` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fulfillment_stock_report_link_attachment`
--

LOCK TABLES `fulfillment_stock_report_link_attachment` WRITE;
/*!40000 ALTER TABLE `fulfillment_stock_report_link_attachment` DISABLE KEYS */;
/*!40000 ALTER TABLE `fulfillment_stock_report_link_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration`
--

DROP TABLE IF EXISTS `migration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration` (
  `version` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apply_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration`
--

LOCK TABLES `migration` WRITE;
/*!40000 ALTER TABLE `migration` DISABLE KEYS */;
INSERT INTO `migration` VALUES ('m000000_000000_base',1707773083),('m230512_122808_init_joy_user_structucture',1707773083),('m230518_105700_init_joy_product',1707773083),('m230521_174222_add_confirm_email_column_to_user_table',1707773083),('m230528_120745_init_joy_request',1707773083),('m230529_184230_add_request_enabled_to_user_table',1707773083),('m230529_191447_init_user_request_enabled',1707773084),('m230617_083926_create_review_product_table',1707773084),('m230623_164153_remove_image_column_from_product_table',1707773084),('m230623_172625_create_product_attachment_table',1707773084),('m230623_172756_create_product_attachment_has_product_table',1707773085),('m230625_125219_create_request_attachment_tables',1707773085),('m230625_125309_request_attachment_has_order_request',1707773085),('m230704_150105_alter_product_table',1707773085),('m230705_093625_add_publication_date_to_review_product',1707773085),('m230706_104914_review_attachment_from_product',1707773086),('m230707_081840_add_rating_buyer_to_user_table',1707773086),('m230707_083555_create_review_buyer_tables',1707773087),('m230712_144119_add_required_fields_to_user_table',1707773088),('m230718_093515_change_phone_number_in_user_table',1707773088),('m230720_131320_alter_user_table',1707773089),('m230721_134230_change_column_type_request_attachment',1707773089),('m230721_135213_change_column_type_product_attachment',1707773089),('m230727_173605_add_profile_picture_to_user_table',1707773089),('m230730_150549_add_delete_in_to_user_table',1707773089),('m230801_104626_create_settings_table',1707773090),('m230802_135436_create_feedback_tables',1707773090),('m230803_074453_create_privacy_policy_table',1707773090),('m230803_153049_create_subcategory_table',1707773091),('m230803_173521_remove_category_id_from_product_table',1707773091),('m230803_174451_add_subcategory_id_category_id_to_product_table',1707773091),('m230808_170904_create_type_packaging_table',1707773092),('m230808_172721_create_type_destination_table',1707773092),('m230808_185156_create_type_delivery_table',1707773092),('m230808_203656_create_deep_inspection_table',1707773092),('m230808_215905_add_subcategory_category_to_order_request',1707773093),('m230813_232557_change_user_nickname_column',1707773093),('m230815_153725_change_product_table',1707773094),('m230815_173127_change_product_name_table',1707773094),('m230816_122715_change_price',1707773095),('m230818_200107_drop_type_packaging_table',1707773095),('m230818_200534_drop_type_destination_table',1707773096),('m230818_201058_drop_type_delivery_table',1707773096),('m230818_201503_drop_deep_inspection_table',1707773096),('m230822_220617_create_type_packaging_tables',1707773096),('m230822_221419_create_type_delivery_tables',1707773097),('m230824_120833_attachment_migration',1707773102),('m230825_141804_product_refactoring',1707773107),('m230828_171014_remove_profile_picture',1707773107),('m230828_172044_add_avatar_id_to_user_table',1707773108),('m230828_231718_change_user_role',1707773109),('m230829_092331_updating_editable_constants',1707773110),('m230829_112329_order_request_merge',1707773119),('m230830_143339_user_description',1707773120),('m230904_105926_linking_order_rate',1707773120),('m230904_160944_implementing_feedback_count',1707773120),('m230908_203902_add_reason_to_feedback_user_table',1707773120),('m230910_151417_add_is_deleted_to_order',1707773120),('m230911_155135_implementing_expected_order_price',1707773121),('m230912_095547_delete_column_type_feedback_user_link_attachment',1707773121),('m230914_145031_create_product_stock_report_table',1707773121),('m230914_150154_create_product_inspection_report_table',1707773121),('m230914_152745_create_product_stock_report_link_attachment_table',1707773122),('m230914_154307_create_order_tracking_table',1707773122),('m230917_201855_create_verification_request_table',1707773123),('m230918_093524_implementing_user_verification',1707773124),('m230919_122946_implementing_order_manager_id',1707773125),('m230920_140900_implementing_chats',1707773126),('m230924_212348_add_is_deleted_to_category_subcategory',1707773126),('m230926_124232_adding_manager_id_to_user_verification_request',1707773126),('m230926_152329_add_avatar_to_category',1707773126),('m231004_105617_implementing_one_to_one_link',1707773128),('m231010_171125_add_phone_country_code_to_user',1707773128),('m231011_102159_implementing_order_distribution',1707773128),('m231014_194013_create_app_option_table',1707773128),('m231018_140029_buyer_offer_improvements',1707773128),('m231030_201545_add_is_deleted_to_delivery_point_address',1707773128),('m231102_140050_add_created_at_rate',1707773128),('m231115_130356_implementing_notifications',1707773129),('m231205_145812_price_decimal_convertation',1707773134),('m231215_134858_add_high_workload_to_user_settings',1707773134),('m231218_084551_add_chat_transite_create',1707773134),('m231222_120445_add_fulfillment_id_to_order',1707773135),('m240107_013654_create_fulfillment_inspection_report_table',1707773136),('m240108_224753_create_fulfillment_packaging_labeling_tables',1707773138),('m240111_171450_add_user_id_to_delivery_point_address',1707773138),('m240115_182827_fulfillment_offer',1707773139),('m240116_144224_change_chat_translate_columns_type',1707773139),('m240118_111422_add_type_to_order_rate',1707773139),('m240121_182617_remove_unique_key_from_order_rate_table',1707773140),('m240127_004820_alter_delivery_point_address_user_id',1707773140),('m240130_162345_fulfillment_marketplace_transaction',1707773140),('m240204_152914_add_link_tz_column_to_order_table',1707773140),('m240205_150507_fulfillment_improvements',1707773141),('m240213_170529_delivery_price_calculation',1708543209),('m240217_144725_type_delivery_available_for_all_flag',1708543209),('m240218_203851_implementing_usd_rate',1708543209);
/*!40000 ALTER TABLE `migration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `user_id` int NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `entity_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `entity_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notification_user_id` (`user_id`),
  CONSTRAINT `fk_notification_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification`
--

LOCK TABLES `notification` WRITE;
/*!40000 ALTER TABLE `notification` DISABLE KEYS */;
INSERT INTO `notification` VALUES (1,'2024-02-13 11:16:47',8,1,'verification_created','verification',1),(2,'2024-02-13 15:19:29',8,0,'verification_created','verification',2),(3,'2024-02-13 18:29:17',8,1,'verification_created','verification',3),(4,'2024-02-13 18:33:07',6,0,'order_status_change','order',1),(5,'2024-02-13 18:33:07',5,0,'order_status_change','order',1),(6,'2024-02-13 23:23:16',14,0,'order_status_change','order',2),(7,'2024-02-13 23:23:16',7,0,'order_status_change','order',2),(8,'2024-02-13 23:26:31',14,0,'order_status_change','order',3),(9,'2024-02-13 23:26:32',7,0,'order_status_change','order',3),(10,'2024-02-14 00:00:00',2,1,'order_status_change','order',4),(11,'2024-02-14 00:00:00',12,1,'order_status_change','order',4),(12,'2024-02-14 00:00:00',10,1,'order_status_change','order',4),(13,'2024-02-14 11:14:54',14,1,'order_status_change','order',5),(14,'2024-02-14 11:14:54',12,1,'order_status_change','order',5),(15,'2024-02-14 11:14:54',7,0,'order_status_change','order',5),(16,'2024-02-14 11:16:50',14,1,'order_status_change','order',6),(17,'2024-02-14 11:16:50',12,1,'order_status_change','order',6),(18,'2024-02-14 11:16:50',7,0,'order_status_change','order',6),(19,'2024-02-14 11:51:48',8,0,'verification_created','verification',4),(20,'2024-02-14 14:15:03',8,0,'verification_created','verification',5),(21,'2024-02-14 14:31:41',20,0,'order_status_change','order',7),(22,'2024-02-14 14:31:42',5,0,'order_status_change','order',7),(23,'2024-02-14 14:54:57',20,1,'order_status_change','order',8),(24,'2024-02-14 14:55:31',20,1,'order_status_change','order',9),(25,'2024-02-14 14:55:31',12,1,'order_status_change','order',9),(26,'2024-02-14 15:11:09',20,1,'order_status_change','order',9),(27,'2024-02-14 15:11:09',12,1,'order_status_change','order',9),(28,'2024-02-14 15:11:52',14,1,'order_status_change','order',6),(29,'2024-02-14 15:11:52',12,1,'order_status_change','order',6),(30,'2024-02-14 15:11:52',7,0,'order_status_change','order',6),(31,'2024-02-14 15:18:46',2,1,'order_status_change','order',4),(32,'2024-02-14 15:18:46',12,1,'order_status_change','order',4),(33,'2024-02-14 15:18:46',10,1,'order_status_change','order',4),(34,'2024-02-14 15:20:31',2,1,'order_status_change','order',4),(35,'2024-02-14 15:20:31',12,1,'order_status_change','order',4),(36,'2024-02-14 15:20:31',10,1,'order_status_change','order',4),(37,'2024-02-14 15:20:31',8,0,'order_waiting_payment','order',4),(38,'2024-02-14 15:21:25',14,1,'order_status_change','order',5),(39,'2024-02-14 15:21:25',12,1,'order_status_change','order',5),(40,'2024-02-14 15:21:25',7,0,'order_status_change','order',5),(41,'2024-02-14 15:28:28',2,1,'order_status_change','order',4),(42,'2024-02-14 15:28:28',12,1,'order_status_change','order',4),(43,'2024-02-14 15:28:28',10,1,'order_status_change','order',4),(44,'2024-02-14 15:47:22',20,1,'order_status_change','order',9),(45,'2024-02-14 15:47:22',12,1,'order_status_change','order',9),(46,'2024-02-14 15:47:22',8,1,'order_waiting_payment','order',9),(47,'2024-02-14 17:12:51',8,0,'verification_created','verification',6),(48,'2024-02-14 22:19:57',8,0,'verification_created','verification',7),(49,'2024-02-15 00:02:08',14,1,'order_status_change','order',6),(50,'2024-02-15 00:02:08',12,1,'order_status_change','order',6),(51,'2024-02-15 00:02:08',7,0,'order_status_change','order',6),(52,'2024-02-15 00:02:08',8,1,'order_waiting_payment','order',6),(53,'2024-02-15 00:04:08',14,1,'order_status_change','order',5),(54,'2024-02-15 00:04:08',12,1,'order_status_change','order',5),(55,'2024-02-15 00:04:08',7,0,'order_status_change','order',5),(56,'2024-02-15 00:04:08',8,0,'order_waiting_payment','order',5),(57,'2024-02-15 00:06:08',2,1,'order_status_change','order',4),(58,'2024-02-15 00:06:08',12,1,'order_status_change','order',4),(59,'2024-02-15 00:06:08',10,1,'order_status_change','order',4),(60,'2024-02-15 00:06:27',2,1,'order_status_change','order',4),(61,'2024-02-15 00:06:27',12,1,'order_status_change','order',4),(62,'2024-02-15 00:06:27',10,1,'order_status_change','order',4),(63,'2024-02-15 00:06:35',2,1,'order_status_change','order',4),(64,'2024-02-15 00:06:35',12,1,'order_status_change','order',4),(65,'2024-02-15 00:06:35',10,1,'order_status_change','order',4),(66,'2024-02-15 00:09:10',20,0,'order_status_change','order',9),(67,'2024-02-15 00:09:10',12,1,'order_status_change','order',9),(68,'2024-02-15 00:09:23',14,1,'order_status_change','order',6),(69,'2024-02-15 00:09:23',12,1,'order_status_change','order',6),(70,'2024-02-15 00:09:23',7,0,'order_status_change','order',6),(71,'2024-02-15 00:09:57',14,1,'order_status_change','order',5),(72,'2024-02-15 00:09:57',12,1,'order_status_change','order',5),(73,'2024-02-15 00:09:57',7,0,'order_status_change','order',5),(74,'2024-02-15 00:11:26',20,0,'order_status_change','order',9),(75,'2024-02-15 00:11:26',12,1,'order_status_change','order',9),(76,'2024-02-15 00:11:40',20,0,'order_status_change','order',9),(77,'2024-02-15 00:11:40',12,1,'order_status_change','order',9),(78,'2024-02-15 00:11:44',20,0,'order_status_change','order',9),(79,'2024-02-15 00:11:44',12,1,'order_status_change','order',9),(80,'2024-02-15 00:12:44',14,1,'order_status_change','order',6),(81,'2024-02-15 00:12:44',12,1,'order_status_change','order',6),(82,'2024-02-15 00:12:44',7,0,'order_status_change','order',6),(83,'2024-02-15 00:12:57',14,1,'order_status_change','order',6),(84,'2024-02-15 00:12:57',12,1,'order_status_change','order',6),(85,'2024-02-15 00:12:57',7,0,'order_status_change','order',6),(86,'2024-02-15 00:13:02',14,1,'order_status_change','order',6),(87,'2024-02-15 00:13:02',12,1,'order_status_change','order',6),(88,'2024-02-15 00:13:02',7,0,'order_status_change','order',6),(89,'2024-02-15 00:14:07',14,1,'order_status_change','order',5),(90,'2024-02-15 00:14:07',12,1,'order_status_change','order',5),(91,'2024-02-15 00:14:07',7,0,'order_status_change','order',5),(92,'2024-02-15 00:14:17',14,1,'order_status_change','order',5),(93,'2024-02-15 00:14:17',12,1,'order_status_change','order',5),(94,'2024-02-15 00:14:17',7,0,'order_status_change','order',5),(95,'2024-02-15 00:14:24',14,1,'order_status_change','order',5),(96,'2024-02-15 00:14:24',12,1,'order_status_change','order',5),(97,'2024-02-15 00:14:24',7,0,'order_status_change','order',5),(98,'2024-02-15 00:16:37',8,0,'verification_created','verification',8),(99,'2024-02-15 13:19:21',8,0,'verification_created','verification',9),(100,'2024-02-15 15:09:01',26,0,'order_status_change','order',10),(101,'2024-02-15 15:09:01',7,0,'order_status_change','order',10),(102,'2024-02-16 00:30:22',8,0,'verification_created','verification',10),(103,'2024-02-16 00:43:06',28,0,'order_status_change','order',11),(104,'2024-02-16 15:54:39',20,0,'order_status_change','order',9),(105,'2024-02-16 15:54:39',12,1,'order_status_change','order',9),(106,'2024-02-16 20:11:21',26,0,'order_status_change','order',12),(107,'2024-02-16 20:11:21',7,0,'order_status_change','order',12),(108,'2024-02-17 01:27:51',8,1,'verification_created','verification',11),(109,'2024-02-18 17:39:33',21,1,'order_status_change','order',13),(110,'2024-02-18 17:39:33',12,1,'order_status_change','order',13),(111,'2024-02-18 17:40:12',21,0,'order_status_change','order',14),(112,'2024-02-18 17:40:12',12,1,'order_status_change','order',14),(113,'2024-02-18 17:40:34',21,0,'order_status_change','order',15),(114,'2024-02-18 17:40:34',12,1,'order_status_change','order',15),(115,'2024-02-18 18:01:42',8,0,'verification_created','verification',12),(116,'2024-02-18 18:03:18',4,0,'order_status_change','order',16),(117,'2024-02-18 18:03:18',12,1,'order_status_change','order',16),(118,'2024-02-18 18:53:39',21,0,'order_status_change','order',17),(119,'2024-02-18 18:53:39',12,1,'order_status_change','order',17),(120,'2024-02-18 19:01:26',21,0,'order_status_change','order',18),(121,'2024-02-18 19:01:26',12,1,'order_status_change','order',18),(122,'2024-02-18 19:01:26',13,0,'order_status_change','order',18),(123,'2024-02-18 19:03:17',21,1,'order_status_change','order',19),(124,'2024-02-18 19:03:17',12,1,'order_status_change','order',19),(125,'2024-02-18 19:07:53',21,1,'order_status_change','order',20),(126,'2024-02-18 19:07:53',12,1,'order_status_change','order',20),(127,'2024-02-18 19:57:07',21,0,'order_status_change','order',21),(128,'2024-02-18 19:57:07',12,1,'order_status_change','order',21),(129,'2024-02-18 20:23:22',21,0,'order_status_change','order',21),(130,'2024-02-18 20:23:22',12,1,'order_status_change','order',21),(131,'2024-02-18 20:23:39',21,1,'order_status_change','order',20),(132,'2024-02-18 20:23:39',12,1,'order_status_change','order',20),(133,'2024-02-18 20:24:01',21,1,'order_status_change','order',13),(134,'2024-02-18 20:24:01',12,1,'order_status_change','order',13),(135,'2024-02-18 20:25:52',21,0,'order_status_change','order',18),(136,'2024-02-18 20:25:52',12,1,'order_status_change','order',18),(137,'2024-02-18 20:25:52',13,0,'order_status_change','order',18),(138,'2024-02-18 20:26:04',21,0,'order_status_change','order',21),(139,'2024-02-18 20:26:10',21,0,'order_status_change','order',18),(140,'2024-02-18 20:26:10',13,0,'order_status_change','order',18),(141,'2024-02-18 20:30:30',14,1,'order_status_change','order',22),(142,'2024-02-18 20:30:30',12,1,'order_status_change','order',22),(143,'2024-02-18 20:30:30',13,1,'order_status_change','order',22),(144,'2024-02-18 20:32:32',14,1,'order_status_change','order',22),(145,'2024-02-18 20:32:32',12,1,'order_status_change','order',22),(146,'2024-02-18 20:32:32',13,1,'order_status_change','order',22),(147,'2024-02-18 20:34:23',14,1,'order_status_change','order',22),(148,'2024-02-18 20:34:23',12,1,'order_status_change','order',22),(149,'2024-02-18 20:34:23',13,1,'order_status_change','order',22),(150,'2024-02-18 20:34:23',31,1,'order_waiting_payment','order',22),(151,'2024-02-18 20:34:55',14,1,'order_status_change','order',22),(152,'2024-02-18 20:34:55',12,1,'order_status_change','order',22),(153,'2024-02-18 20:34:55',13,1,'order_status_change','order',22),(154,'2024-02-18 20:35:52',14,1,'order_status_change','order',22),(155,'2024-02-18 20:35:52',12,1,'order_status_change','order',22),(156,'2024-02-18 20:35:52',13,1,'order_status_change','order',22),(157,'2024-02-18 20:36:07',14,1,'order_status_change','order',22),(158,'2024-02-18 20:36:07',12,1,'order_status_change','order',22),(159,'2024-02-18 20:36:07',13,1,'order_status_change','order',22),(160,'2024-02-18 20:36:12',14,1,'order_status_change','order',22),(161,'2024-02-18 20:36:12',12,1,'order_status_change','order',22),(162,'2024-02-18 20:36:12',13,1,'order_status_change','order',22),(163,'2024-02-18 22:41:34',21,1,'order_status_change','order',23),(164,'2024-02-18 22:41:34',12,1,'order_status_change','order',23),(165,'2024-02-18 22:41:34',13,1,'order_status_change','order',23),(166,'2024-02-18 22:46:35',21,0,'order_status_change','order',14),(167,'2024-02-18 22:46:35',12,1,'order_status_change','order',14),(168,'2024-02-18 22:49:06',21,1,'order_status_change','order',13),(169,'2024-02-18 22:49:06',12,1,'order_status_change','order',13),(170,'2024-02-18 22:49:06',8,0,'order_waiting_payment','order',13),(171,'2024-02-18 22:49:35',21,1,'order_status_change','order',23),(172,'2024-02-18 22:49:35',12,1,'order_status_change','order',23),(173,'2024-02-18 22:49:35',13,1,'order_status_change','order',23),(174,'2024-02-18 22:50:42',21,1,'order_status_change','order',23),(175,'2024-02-18 22:50:42',12,1,'order_status_change','order',23),(176,'2024-02-18 22:50:42',13,1,'order_status_change','order',23),(177,'2024-02-18 22:50:42',31,1,'order_waiting_payment','order',23),(178,'2024-02-18 22:51:04',21,1,'order_status_change','order',23),(179,'2024-02-18 22:51:04',12,1,'order_status_change','order',23),(180,'2024-02-18 22:51:04',13,1,'order_status_change','order',23),(181,'2024-02-18 22:52:57',21,1,'order_status_change','order',23),(182,'2024-02-18 22:52:57',12,1,'order_status_change','order',23),(183,'2024-02-18 22:52:57',13,1,'order_status_change','order',23),(184,'2024-02-18 22:53:09',21,1,'order_status_change','order',23),(185,'2024-02-18 22:53:09',12,1,'order_status_change','order',23),(186,'2024-02-18 22:53:09',13,1,'order_status_change','order',23),(187,'2024-02-18 22:53:13',21,1,'order_status_change','order',23),(188,'2024-02-18 22:53:13',12,1,'order_status_change','order',23),(189,'2024-02-18 22:53:13',13,1,'order_status_change','order',23),(190,'2024-02-21 00:47:15',31,0,'verification_created','verification',13),(191,'2024-02-26 17:11:01',8,0,'verification_created','verification',14),(192,'2024-02-27 22:04:57',8,0,'verification_created','verification',15),(193,'2024-04-09 11:33:27',8,0,'verification_created','verification',16),(194,'2024-04-10 11:17:29',8,0,'verification_created','verification',17),(195,'2024-07-11 19:34:55',31,0,'verification_created','verification',18);
/*!40000 ALTER TABLE `notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_by` int NOT NULL,
  `buyer_id` int DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `fulfillment_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `product_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expected_quantity` int NOT NULL,
  `expected_price_per_item` decimal(10,4) NOT NULL,
  `expected_packaging_quantity` int NOT NULL DEFAULT '0',
  `subcategory_id` int NOT NULL,
  `type_packaging_id` int NOT NULL,
  `type_delivery_id` int NOT NULL,
  `type_delivery_point_id` int NOT NULL,
  `delivery_point_address_id` int NOT NULL,
  `price_product` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `price_inspection` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `price_packaging` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `price_fulfilment` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `price_delivery` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `total_quantity` int NOT NULL DEFAULT '0',
  `is_need_deep_inspection` tinyint(1) NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `link_tz` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `fk_order_product1_idx` (`product_id`),
  KEY `fk_order_user1_idx` (`created_by`),
  KEY `fk_order_user2_idx` (`buyer_id`),
  KEY `fk_order_subcategory_id` (`subcategory_id`),
  KEY `fk_order_type_packaging_id` (`type_packaging_id`),
  KEY `fk_order_type_delivery_id` (`type_delivery_id`),
  KEY `fk_order_type_delivery_point_id` (`type_delivery_point_id`),
  KEY `fk_order_delivery_point_address_id` (`delivery_point_address_id`),
  KEY `fk_order_manager_id` (`manager_id`),
  KEY `fk_order_user3_idx` (`fulfillment_id`),
  CONSTRAINT `fk_order_delivery_point_address_id` FOREIGN KEY (`delivery_point_address_id`) REFERENCES `delivery_point_address` (`id`),
  CONSTRAINT `fk_order_manager_id` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_order_product1` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`),
  CONSTRAINT `fk_order_subcategory_id` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategory` (`id`),
  CONSTRAINT `fk_order_type_delivery_id` FOREIGN KEY (`type_delivery_id`) REFERENCES `type_delivery` (`id`),
  CONSTRAINT `fk_order_type_delivery_point_id` FOREIGN KEY (`type_delivery_point_id`) REFERENCES `type_delivery_point` (`id`),
  CONSTRAINT `fk_order_type_packaging_id` FOREIGN KEY (`type_packaging_id`) REFERENCES `type_packaging` (`id`),
  CONSTRAINT `fk_order_user1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_order_user2` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_order_user3` FOREIGN KEY (`fulfillment_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order`
--

LOCK TABLES `order` WRITE;
/*!40000 ALTER TABLE `order` DISABLE KEYS */;
INSERT INTO `order` VALUES (1,'2024-02-13 18:30:54','cancelled_request',6,NULL,8,5,NULL,'Ромио','Пн гсорроррррролоопап',1000,13.7931,0,106,1,6,2,1,0.0000,0.0000,0.0000,0.0000,0.0000,0,0,0,NULL),(2,'2024-02-13 23:21:03','cancelled_request',14,NULL,8,7,NULL,'Крючки для полотенец ','Упаковать 200 единиц по 2 шт итого 100 ед',200,68.9655,0,79,2,1,2,2,0.0000,0.0000,0.0000,0.0000,0.0000,0,0,0,NULL),(3,'2024-02-13 23:24:17','cancelled_request',14,NULL,8,7,NULL,'Крючки для полотенец ','Упаковать 200 единиц по 2 шт итого 100 ед',200,68.9655,0,79,2,1,2,2,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(4,'2024-02-14 00:00:00','transferring_to_fulfillment',2,12,8,10,1,'Термопринтер','Термопринтер стационарный',2000,30.0000,0,151,2,7,2,4,30.0000,10.0000,20.0000,0.0000,0.0000,0,1,0,NULL),(5,'2024-02-14 11:14:54','transferring_to_fulfillment',14,12,8,7,1,'Термопринтер','Термопринтер стационарный',1000,30.0000,0,151,1,1,2,2,20.0000,2.0000,2.0000,0.0000,0.0000,0,1,0,'https://docs.google.com/file/d/1aflXm1TM9tMppwCD1wqMvaPVia187nDZ/edit?usp=docslist_api&filetype=msexcel'),(6,'2024-02-14 11:16:50','transferring_to_fulfillment',14,12,8,7,1,'Термопринтер','Термопринтер стационарный',1000,30.0000,0,151,1,1,2,2,500.0000,500.0000,1.0000,0.0000,0.0000,0,1,0,'https://docs.google.com/file/d/1aflXm1TM9tMppwCD1wqMvaPVia187nDZ/edit?usp=docslist_api&filetype=msexcel'),(7,'2024-02-14 14:29:29','cancelled_request',20,NULL,8,5,NULL,'Спортивные резинки ','Спортивные резинки 4 ',10000,13.7931,0,220,1,2,2,1,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(8,'2024-02-14 14:52:44','cancelled_request',20,NULL,8,NULL,NULL,'Спорт','Спорттолллщдльлллльььь',100000,6.8966,0,220,2,2,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(9,'2024-02-14 14:55:31','arrived_to_warehouse',20,12,8,NULL,1,'Термопринтер','Термопринтер стационарный',100,30.0000,0,151,1,2,1,6,20000.0000,20.0000,1666.0000,0.0000,0.0000,0,0,0,NULL),(10,'2024-02-15 15:06:48','cancelled_request',26,NULL,8,7,NULL,'Костюм унисекс ','Футер 3-х ника, с начесом. Цвет черный ',1000,68.9655,0,57,1,2,2,2,0.0000,0.0000,0.0000,0.0000,0.0000,0,0,0,NULL),(11,'2024-02-16 00:40:53','cancelled_request',28,NULL,8,NULL,NULL,'Платье ','Платье лапша , летнее, с завязками по бокам ',3000,20.6897,0,16,2,3,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(12,'2024-02-16 20:08:06','cancelled_request',26,NULL,8,7,NULL,'Костюм унисекс ','Футер 3-х ника, с начесом. Цвет черный ',1000,68.9655,0,57,1,2,2,2,0.0000,0.0000,0.0000,0.0000,0.0000,0,0,0,NULL),(13,'2024-02-18 17:39:33','buyer_offer_accepted',21,12,8,NULL,1,'Термопринтер','Термопринтер стационарный',100,30.0000,0,151,2,1,1,6,1.0000,1.0000,1.0000,0.0000,0.0000,0,1,0,NULL),(14,'2024-02-18 17:40:11','buyer_offer_created',21,12,8,NULL,1,'Термопринтер','Термопринтер стационарный',100,30.0000,0,151,2,1,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(15,'2024-02-18 17:40:34','buyer_assigned',21,12,8,NULL,1,'Термопринтер','Термопринтер стационарный',100,30.0000,0,151,2,1,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(16,'2024-02-18 18:03:18','buyer_assigned',4,12,8,NULL,1,'Термопринтер','Термопринтер стационарный',200,30.0000,0,151,2,1,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(17,'2024-02-18 18:53:38','buyer_assigned',21,12,31,NULL,1,'Термопринтер','Термопринтер стационарный',100,30.0000,0,151,2,1,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(18,'2024-02-18 19:01:26','cancelled_request',21,NULL,8,13,1,'Термопринтер','Термопринтер стационарный',100,30.0000,0,151,2,1,2,5,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(19,'2024-02-18 19:03:17','buyer_assigned',21,12,8,NULL,1,'Термопринтер','Термопринтер стационарный',1000,30.0000,0,151,2,1,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(20,'2024-02-18 19:07:53','buyer_offer_created',21,12,31,NULL,1,'Термопринтер','Термопринтер стационарный',111,30.0000,0,151,2,1,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(21,'2024-02-18 19:57:07','cancelled_request',21,NULL,8,NULL,1,'Термопринтер','Термопринтер стационарный',1000,30.0000,0,151,2,1,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(22,'2024-02-18 20:30:30','transferring_to_fulfillment',14,12,31,13,1,'Термопринтер','Термопринтер стационарный',5,30.0000,0,151,2,1,2,5,5.0000,1.0000,35.0000,0.0000,0.0000,0,1,0,'https://docs.google.com/file/d/1aflXm1TM9tMppwCD1wqMvaPVia187nDZ/edit?usp=docslist_api&filetype=msexcel'),(23,'2024-02-18 22:41:34','transferring_to_fulfillment',21,12,31,13,1,'Термопринтер','Термопринтер стационарный',100,30.0000,0,151,2,1,2,5,1.0000,2.0000,2.0000,0.0000,0.0000,0,1,0,'https://static-basket-02.wbbasket.ru/vol20/news/2024_02_16_Subject_up.xlsx'),(75,'2024-07-26 14:04:52','created',23,NULL,31,NULL,NULL,'Test Product','Test Description',10,240.0000,1,1,1,9,1,1,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(76,'2024-07-26 14:07:41','created',45,NULL,8,NULL,NULL,'Test Product','Test Description',10,240.0000,1,1,1,9,1,1,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(87,'2024-07-26 15:53:19','created',2,NULL,8,NULL,NULL,'Test Product','Test Description',10,234.8160,1,1,1,9,1,1,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(89,'2024-07-26 15:56:28','created',45,15,91,NULL,NULL,'Test item','Description of the world ',1000,100.0000,1,7,1,7,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,0,0,NULL),(92,'2024-07-26 16:50:09','created',95,NULL,31,NULL,NULL,'Тестовый товар','тестовое описание товара',10000,234816.0000,5000,7,2,7,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,0,0,NULL),(93,'2024-07-29 12:20:22','created',2,NULL,91,NULL,NULL,'Test Product','Test Description',10,234.8160,1,1,1,9,1,1,0.0000,0.0000,0.0000,0.0000,0.0000,0,1,0,NULL),(94,'2024-07-29 12:22:28','created',95,NULL,31,NULL,NULL,'тест 2','описание теста 2 тест',100,5870.4000,20,7,1,7,1,6,0.0000,0.0000,0.0000,0.0000,0.0000,0,0,0,NULL);
/*!40000 ALTER TABLE `order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_distribution`
--

DROP TABLE IF EXISTS `order_distribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_distribution` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `current_buyer_id` int NOT NULL,
  `requested_at` datetime NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `buyer_ids_list` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fk_order_distribution_order_id` (`order_id`),
  KEY `fk_order_distribution_current_buyer_id` (`current_buyer_id`),
  CONSTRAINT `fk_order_distribution_current_buyer_id` FOREIGN KEY (`current_buyer_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_order_distribution_order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_distribution`
--

LOCK TABLES `order_distribution` WRITE;
/*!40000 ALTER TABLE `order_distribution` DISABLE KEYS */;
INSERT INTO `order_distribution` VALUES (1,1,15,'2024-02-13 18:33:07','closed','12,15'),(2,2,15,'2024-02-13 23:23:16','closed','12,15'),(3,3,15,'2024-02-13 23:26:32','closed','12,15'),(4,4,12,'2024-02-14 00:00:00','accepted','12'),(5,5,12,'2024-02-14 11:14:54','accepted','12'),(6,6,12,'2024-02-14 11:16:50','accepted','12'),(7,7,15,'2024-02-14 14:31:42','closed','12,15'),(8,8,15,'2024-02-14 14:54:57','closed','12,15'),(9,9,12,'2024-02-14 14:55:31','accepted','12'),(10,10,15,'2024-02-15 15:09:02','closed','12,15'),(11,11,15,'2024-02-16 00:43:06','closed','12,15'),(12,12,30,'2024-02-16 20:11:21','closed','12,15,30'),(13,13,12,'2024-02-18 17:39:33','accepted','12'),(14,14,12,'2024-02-18 17:40:12','accepted','12'),(15,15,12,'2024-02-18 17:40:34','accepted','12'),(16,16,12,'2024-02-18 18:03:18','accepted','12'),(17,17,12,'2024-02-18 18:53:38','accepted','12'),(18,18,12,'2024-02-18 20:26:10','closed','12'),(19,19,12,'2024-02-18 19:03:17','accepted','12'),(20,20,12,'2024-02-18 19:07:53','accepted','12'),(21,21,12,'2024-02-18 20:26:04','closed','12'),(22,22,12,'2024-02-18 20:30:30','accepted','12'),(23,23,12,'2024-02-18 22:41:34','accepted','12'),(31,75,44,'2024-07-26 14:04:52','in_work','44,44'),(32,76,44,'2024-07-26 14:07:41','in_work','44,44'),(33,87,12,'2024-07-26 15:53:19','in_work','12,15,30,44,92'),(35,89,12,'2024-07-26 15:56:28','in_work','12,15,30,44,92'),(38,92,12,'2024-07-26 16:50:09','in_work','12,15,30,44,92'),(39,93,12,'2024-07-29 12:20:22','in_work','12,15,30,44,92'),(40,94,12,'2024-07-29 12:22:29','in_work','12,15,30,44,92');
/*!40000 ALTER TABLE `order_distribution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_link_attachment`
--

DROP TABLE IF EXISTS `order_link_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_link_attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `attachment_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_link_attachment_order_id` (`order_id`),
  KEY `fk_order_link_attachment_attachment_id` (`attachment_id`),
  CONSTRAINT `fk_order_link_attachment_attachment_id` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `fk_order_link_attachment_order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_link_attachment`
--

LOCK TABLES `order_link_attachment` WRITE;
/*!40000 ALTER TABLE `order_link_attachment` DISABLE KEYS */;
INSERT INTO `order_link_attachment` VALUES (1,1,67),(2,7,79),(3,8,80),(4,10,85),(5,12,85);
/*!40000 ALTER TABLE `order_link_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_rate`
--

DROP TABLE IF EXISTS `order_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_rate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `RUB` decimal(10,4) NOT NULL,
  `CNY` decimal(10,4) NOT NULL,
  `USD` decimal(10,4) NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_rate_order_id` (`order_id`),
  CONSTRAINT `fk_order_rate_order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_rate`
--

LOCK TABLES `order_rate` WRITE;
/*!40000 ALTER TABLE `order_rate` DISABLE KEYS */;
INSERT INTO `order_rate` VALUES (1,3,14.5000,1.0000,0.0000,'buyer_payment'),(2,1,14.5000,1.0000,0.0000,'buyer_payment'),(3,2,14.5000,1.0000,0.0000,'buyer_payment'),(4,4,14.5000,1.0000,0.0000,'buyer_payment'),(5,9,14.5000,1.0000,0.0000,'buyer_payment'),(6,11,14.5000,1.0000,0.0000,'buyer_payment');
/*!40000 ALTER TABLE `order_rate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_tracking`
--

DROP TABLE IF EXISTS `order_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_tracking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `order_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-order_tracking-order_id` (`order_id`),
  CONSTRAINT `fk-order_tracking-order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_tracking`
--

LOCK TABLES `order_tracking` WRITE;
/*!40000 ALTER TABLE `order_tracking` DISABLE KEYS */;
INSERT INTO `order_tracking` VALUES (1,'2024-02-14 15:28:33','awaiting',4),(2,'2024-02-15 00:06:08','in_bayer_warehouse',4),(3,'2024-02-15 00:06:35','item_sent',4),(4,'2024-02-15 00:09:10','awaiting',9),(5,'2024-02-15 00:09:29','awaiting',6),(6,'2024-02-15 00:10:04','awaiting',5),(7,'2024-02-15 00:11:26','in_bayer_warehouse',9),(8,'2024-02-15 00:11:44','item_sent',9),(9,'2024-02-15 00:12:44','in_bayer_warehouse',6),(10,'2024-02-15 00:13:02','item_sent',6),(11,'2024-02-15 00:14:07','in_bayer_warehouse',5),(12,'2024-02-15 00:14:24','item_sent',5),(13,'2024-02-18 20:35:00','awaiting',22),(14,'2024-02-18 20:35:52','in_bayer_warehouse',22),(15,'2024-02-18 20:36:12','item_sent',22),(16,'2024-02-18 22:51:09','awaiting',23),(17,'2024-02-18 22:52:57','in_bayer_warehouse',23),(18,'2024-02-18 22:53:13','item_sent',23);
/*!40000 ALTER TABLE `order_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packaging_report_link_attachment`
--

DROP TABLE IF EXISTS `packaging_report_link_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packaging_report_link_attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `packaging_report_id` int NOT NULL,
  `attachment_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk-packaging_report_link_attachment-attachment_id` (`attachment_id`),
  KEY `idx-packaging_report_link_attachment-packaging_report_id` (`packaging_report_id`),
  CONSTRAINT `fk-packaging_report_link_attachment-attachment_id` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `fk-packaging_report_link_attachment-packaging_report_id` FOREIGN KEY (`packaging_report_id`) REFERENCES `fulfillment_packaging_labeling` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packaging_report_link_attachment`
--

LOCK TABLES `packaging_report_link_attachment` WRITE;
/*!40000 ALTER TABLE `packaging_report_link_attachment` DISABLE KEYS */;
/*!40000 ALTER TABLE `packaging_report_link_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `privacy_policy`
--

DROP TABLE IF EXISTS `privacy_policy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `privacy_policy` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` varchar(1055) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privacy_policy`
--

LOCK TABLES `privacy_policy` WRITE;
/*!40000 ALTER TABLE `privacy_policy` DISABLE KEYS */;
/*!40000 ALTER TABLE `privacy_policy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rating` decimal(10,2) NOT NULL DEFAULT '0.00',
  `feedback_count` int NOT NULL DEFAULT '0',
  `buyer_id` int NOT NULL,
  `subcategory_id` int NOT NULL,
  `range_1_min` int NOT NULL DEFAULT '1',
  `range_1_max` int NOT NULL,
  `range_1_price` decimal(10,4) NOT NULL,
  `range_2_min` int DEFAULT NULL,
  `range_2_max` int DEFAULT NULL,
  `range_2_price` decimal(10,4) DEFAULT NULL,
  `range_3_min` int DEFAULT NULL,
  `range_3_max` int DEFAULT NULL,
  `range_3_price` decimal(10,4) DEFAULT NULL,
  `range_4_min` int DEFAULT NULL,
  `range_4_max` int DEFAULT NULL,
  `range_4_price` decimal(10,4) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `product_height` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `product_width` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `product_depth` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `product_weight` decimal(8,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  KEY `fk_product_user1_idx` (`buyer_id`),
  KEY `fk_product_subcategory1_idx` (`subcategory_id`),
  CONSTRAINT `fk_product_subcategory1` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategory` (`id`),
  CONSTRAINT `fk_product_user1` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,'Термопринтер','Термопринтер стационарный',0.00,0,12,151,12,1000,30.0000,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.0000,0.0000,0.0000,0.0000);
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_inspection_report`
--

DROP TABLE IF EXISTS `product_inspection_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_inspection_report` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `order_id` int NOT NULL,
  `defects_count` int NOT NULL DEFAULT '0',
  `package_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_deep` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx-product_inspection_report-order_id` (`order_id`),
  CONSTRAINT `fk-product_inspection_report-order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_inspection_report`
--

LOCK TABLES `product_inspection_report` WRITE;
/*!40000 ALTER TABLE `product_inspection_report` DISABLE KEYS */;
INSERT INTO `product_inspection_report` VALUES (1,'2024-02-15 00:06:27',4,1,'good',1),(2,'2024-02-15 00:11:39',9,1,'normal',0),(3,'2024-02-15 00:12:57',6,1,'normal',1),(4,'2024-02-15 00:14:17',5,5,'bad',1),(5,'2024-02-18 20:36:07',22,1,'good',1),(6,'2024-02-18 22:53:09',23,1,'good',1);
/*!40000 ALTER TABLE `product_inspection_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_link_attachment`
--

DROP TABLE IF EXISTS `product_link_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_link_attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attachment_id` int NOT NULL,
  `product_id` int NOT NULL,
  `type` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_product_link_attachment_attachment_id` (`attachment_id`),
  KEY `fk_product_link_attachment_product_id` (`product_id`),
  CONSTRAINT `fk_product_link_attachment_attachment_id` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `fk_product_link_attachment_product_id` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_link_attachment`
--

LOCK TABLES `product_link_attachment` WRITE;
/*!40000 ALTER TABLE `product_link_attachment` DISABLE KEYS */;
INSERT INTO `product_link_attachment` VALUES (1,65,1,0);
/*!40000 ALTER TABLE `product_link_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_stock_report`
--

DROP TABLE IF EXISTS `product_stock_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_stock_report` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `order_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-product_stock_report-order_id` (`order_id`),
  CONSTRAINT `fk-product_stock_report-order_id` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_stock_report`
--

LOCK TABLES `product_stock_report` WRITE;
/*!40000 ALTER TABLE `product_stock_report` DISABLE KEYS */;
INSERT INTO `product_stock_report` VALUES (1,'2024-02-15 00:06:08',4),(2,'2024-02-15 00:11:26',9),(3,'2024-02-15 00:12:44',6),(4,'2024-02-15 00:14:07',5),(5,'2024-02-18 20:35:52',22),(6,'2024-02-18 22:52:57',23);
/*!40000 ALTER TABLE `product_stock_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_stock_report_link_attachment`
--

DROP TABLE IF EXISTS `product_stock_report_link_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_stock_report_link_attachment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_stock_report` int NOT NULL,
  `attachment_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx-product_stock_report_link_attachment-product_stock_report` (`product_stock_report`),
  KEY `idx-product_stock_report_link_attachment-attachment_id` (`attachment_id`),
  CONSTRAINT `fk-product_stock_report_link_attachment-attachment_id` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  CONSTRAINT `fk-product_stock_report_link_attachment-product_stock_report` FOREIGN KEY (`product_stock_report`) REFERENCES `product_stock_report` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_stock_report_link_attachment`
--

LOCK TABLES `product_stock_report_link_attachment` WRITE;
/*!40000 ALTER TABLE `product_stock_report_link_attachment` DISABLE KEYS */;
INSERT INTO `product_stock_report_link_attachment` VALUES (1,1,81),(2,2,82),(3,3,83),(4,4,84),(5,5,87),(6,6,88);
/*!40000 ALTER TABLE `product_stock_report_link_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate`
--

DROP TABLE IF EXISTS `rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `RUB` decimal(10,4) NOT NULL,
  `CNY` decimal(10,4) NOT NULL,
  `USD` decimal(10,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate`
--

LOCK TABLES `rate` WRITE;
/*!40000 ALTER TABLE `rate` DISABLE KEYS */;
INSERT INTO `rate` VALUES (1,14.5000,1.0000,0.0000,'2023-12-26 10:44:43'),(2,13.2500,1.0000,7.2000,'2024-02-26 14:12:11'),(3,13.2500,1.0000,7.2604,'2024-02-26 14:46:02'),(4,0.0830,1.0000,7.2600,'2024-07-15 11:54:11'),(5,12.1100,1.0000,7.2600,'2024-07-15 11:54:38'),(6,2.0000,1.0000,4.0000,'2024-07-16 16:50:52'),(7,1.0000,12.0000,22.0000,'2024-07-23 15:49:47'),(8,1.0000,11.7408,86.1000,'2024-07-26 15:51:15');
/*!40000 ALTER TABLE `rate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcategory`
--

DROP TABLE IF EXISTS `subcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subcategory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `en_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ru_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zh_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_subcategory_category1_idx` (`category_id`),
  CONSTRAINT `fk_subcategory_category1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=383 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcategory`
--

LOCK TABLES `subcategory` WRITE;
/*!40000 ALTER TABLE `subcategory` DISABLE KEYS */;
INSERT INTO `subcategory` VALUES (1,'Blouses and shirts','Блузки и рубашки','衬衫和衬衫',2,0),(2,'Trousers','Брюки','长裤',2,0),(3,'Outerwear','Верхняя одежда','外衣；外衣',2,0),(4,'Jeans','Джинсы','牛仔裤',1,1),(5,'Trousers','Брюки','长裤',1,1),(6,'Outerwear','Верхняя одежда','外衣；外衣',1,0),(7,'Blouses and Shirts','Блузки и рубашки','衬衫和衬衫',3,0),(8,'Trousers','Брюки','裤子',3,0),(9,'Outerwear','Верхняя одежда','外套',3,0),(10,'Sweaters, Turtlenecks, and Cardigans','Джемперы, водолазки и кардиганы','毛衣、高领毛衣和开襟衫',3,0),(11,'Jeans','Джинсы','牛仔裤',3,0),(12,'Jumpsuits','Комбинезоны','连身裤',3,0),(13,'Suits','Костюмы','西装',3,0),(14,'Longsleeves','Лонгсливы','长袖',3,0),(15,'Blazers, Vests, and Jackets','Пиджаки, жилеты и жакеты','西装外套、马甲和夹克',3,0),(16,'Dresses and Sundresses','Платья и сарафаны','连衣裙和吊带裙',3,0),(17,'Sweatshirts and Hoodies','Толстовки, свитшоты и худи','运动衫、卫衣和连帽衫',3,0),(18,'Tunics','Туники','长款上衣',3,0),(19,'T-shirts and Tops','Футболки и топы','T恤和上衣',3,0),(20,'Robes','Халаты','浴袍',3,0),(21,'Shorts','Шорты','短裤',3,0),(22,'Skirts','Юбки','裙子',3,0),(23,'Lingerie','Белье','内衣',3,0),(24,'Plus Size','Большие размеры','大尺码',3,0),(25,'Maternity Wear','Будущие мамы','孕妇装',3,0),(26,'Tall','Для высоких','高个子',3,0),(27,'Petite','Для невысоких','矮个子',3,0),(28,'Home Wear','Одежда для дома','家居服',3,0),(29,'Office','Офис','办公室',3,0),(30,'Beachwear','Пляжная мода','海滩装',3,0),(31,'Religious','Религиозная','宗教',3,0),(32,'Wedding','Свадьба','婚礼',3,0),(33,'Workwear and PPE','Спецодежда и СИЗы','专业工装和个人防护装备',3,0),(34,'Women\'s Gifts','Подарки женщинам','女士礼品',3,0),(35,'Kids','Детская','儿童',4,0),(36,'Newborn','Для новорожденных','新生儿',4,0),(37,'Women\'s','Женская','女性',4,0),(38,'Men\'s','Мужская','男性',4,0),(39,'Orthopedic Shoes','Ортопедическая обувь','矫形鞋',4,0),(40,'Shoe Accessories','Аксессуары для обуви','鞋配件',4,0),(41,'Boys','Для мальчиков','男孩',5,0),(42,'Girls','Для девочек','女孩',5,0),(43,'Newborn','Для новорожденных','新生儿',5,0),(44,'Kids Electronics','Детская электроника','儿童电子产品',5,0),(45,'Building Blocks','Конструкторы','搭积木',5,0),(46,'Kids\' Transport','Детский транспорт','儿童交通工具',5,0),(47,'Baby Food','Детское питание','婴儿食品',5,0),(48,'Religious','Религиозная одежда','宗教服装',5,0),(49,'Baby Essentials','Товары для малыша','婴儿用品',5,0),(50,'Diapers','Подгузники','尿布',5,0),(51,'Kids\' Gifts','Подарки детям','儿童礼品',5,0),(52,'Trousers','Брюки','裤子',6,0),(53,'Outerwear','Верхняя одежда','外套',6,0),(54,'Sweaters, Turtlenecks, and Cardigans','Джемперы, водолазки и кардиганы','毛衣、高领毛衣和开襟衫',6,0),(55,'Jeans','Джинсы','牛仔裤',6,0),(56,'Jumpsuits and Overalls','Комбинезоны и полукомбинезоны','连身裤和工装',6,0),(57,'Suits','Костюмы','西装',6,0),(58,'Longsleeves','Лонгсливы','长袖',6,0),(59,'Tank Tops','Майки','背心',6,0),(60,'Blazers, Vests, and Jackets','Пиджаки, жилеты и жакеты','西装外套、马甲和夹克',6,0),(61,'Pajamas','Пижамы','睡衣',6,0),(62,'Shirts','Рубашки','衬衫',6,0),(63,'Sweatshirts and Hoodies','Толстовки, свитшоты и худи','运动',6,0),(64,'T-shirts','Футболки','T恤',6,0),(65,'Polo Shirts','Футболки-поло','POLO衫',6,0),(66,'Robes','Халаты','浴袍',6,0),(67,'Shorts','Шорты','短裤',6,0),(68,'Lingerie','Белье','内衣',6,0),(69,'Plus Size','Большие размеры','大尺码',6,0),(70,'Tall','Для высоких','高个子',6,0),(71,'Petite','Для невысоких','矮个子',6,0),(72,'Home Wear','Одежда для дома','家居服',6,0),(73,'Office','Офис','办公室',6,0),(74,'Beachwear','Пляжная мода','海滩装',6,0),(75,'Religious','Религиозная','宗教',6,0),(76,'Wedding','Свадьба','婚礼',6,0),(77,'Workwear and PPE','Спецодежда и СИЗы','专业工装和个人防护装备',6,0),(78,'Men\'s Gifts','Подарки мужчинам ','男士礼品',6,0),(79,'Bathroom','Ванная','浴室',7,0),(80,'Kitchen','Кухня','厨房',7,0),(81,'Interior Items','Предметы интерьера','室内装饰品',7,0),(82,'Bedroom','Спальня','卧室',7,0),(83,'Living Room','Гостиная','客厅',7,0),(84,'Kids\' Room','Детская','儿童房',7,0),(85,'Leisure and Creativity','Досуг и творчество','休闲和创意',7,0),(86,'Party Supplies','Все для праздника','聚会用品',7,0),(87,'Mirrors','Зеркала','镜子',7,0),(88,'Rugs','Коврики','地毯',7,0),(89,'Brackets','Кронштейны','支架',7,0),(90,'Lighting','Освещение','灯饰',7,0),(91,'Smoking Accessories','Для курения','吸烟用品',7,0),(92,'Outdoor Leisure','Отдых на природе','户外休闲',7,0),(93,'Home Fragrance','Парфюмерия для дома','家居香氛',7,0),(94,'Hallway','Прихожая','玄关',7,0),(95,'Religion and Esoterics','Религия, эзотерика','宗教和神秘',7,0),(96,'Souvenirs','Сувенирная продукция','纪念品',7,0),(97,'Household Goods','Хозяйственные товары','家居用品',7,0),(98,'Storage','Хранение вещей','收纳',7,0),(99,'Flowers, Vases and Pots','Цветы, вазы и кашпо','花卉、花瓶和盆栽',7,0),(100,'Curtains','Шторы','窗帘',7,0),(101,'Accessories','Аксессуары','饰品',8,0),(102,'Hair','Волосы','头发',8,0),(103,'Pharmacy Cosmetics','Аптечная косметика','药妆',8,0),(104,'Kids\' Decorative Cosmetics','Детская декоративная косметика','儿童装饰性化妆品',8,0),(105,'Tanning','Для загара','晒黑',8,0),(106,'For Moms and Babies','Для мам и малышей','给妈妈和宝宝',8,0),(107,'Israeli Cosmetics','Израильская косметика','以色列化妆品',8,0),(108,'Hairdressing Tools','Инструменты для парикмахеров','美发工具',8,0),(109,'Korean Brands','Корейские бренды','韩国品牌',8,0),(110,'Beauty Devices and Accessories','Косметические аппараты и аксессуары','化妆工具和配件',8,0),(111,'Crimean Cosmetics','Крымская косметика','克里米亚化妆品',8,0),(112,'Makeup','Макияж','化妆',8,0),(113,'Men\'s Line','Мужская линия','男士系列',8,0),(114,'Care Sets','Наборы для ухода','护理套装',8,0),(115,'Nails','Ногти','指甲',8,0),(116,'Organic Cosmetics','Органическая косметика','有机化妆品',8,0),(117,'Perfume','Парфюмерия','香水',8,0),(118,'Gift Sets','Подарочные наборы','礼品套装',8,0),(119,'Professional Cosmetics','Профессиональная косметика','专业化妆品',8,0),(120,'Personal Hygiene Products','Средства личной гигиены','个人卫生用品',8,0),(121,'Oral Hygiene','Гигиена полости рта','口腔卫生',8,0),(122,'Skin Care','Уход за кожей','护肤',8,0),(123,'Hair Accessories','Аксессуары для волос','头饰',9,0),(124,'Clothing Accessories','Аксессуары для одежды','服饰配饰',9,0),(125,'Jewelry','Бижутерия','珠宝首饰',9,0),(126,'Fans','Веера','折扇',9,0),(127,'Ties and Bowties','Галстуки и бабочки','领带和蝴蝶结',9,0),(128,'Headwear','Головные уборы','头饰',9,0),(129,'Mirrors','Зеркальца','小镜子',9,0),(130,'Umbrellas','Зонты','雨伞',9,0),(131,'Wallets and Cardholders','Кошельки и кредитницы','钱包和卡包',9,0),(132,'Sleep Masks','Маски для сна','眼罩',9,0),(133,'Handkerchiefs','Носовые платки','手帕',9,0),(134,'Glasses and Cases','Очки и футляры','眼镜和眼镜盒',9,0),(135,'Gloves and Mittens','Перчатки и варежки','手套和连指手套',9,0),(136,'Scarves and Shawls','Платки и шарфы','围巾和披肩',9,0),(137,'Religious','Религиозные','宗教',9,0),(138,'Belts and Waistbands','Ремни и пояса','皮带和腰带',9,0),(139,'Bags and Backpacks','Сумки и рюкзаки','手提包和背包',9,0),(140,'Watches and Straps','Часы и ремешки','手表和表带',9,0),(141,'Luggage and Baggage Protection','Чемоданы и защита багажа','行李箱和行李包防护',9,0),(142,'Car Electronics and Navigation','Автоэлектроника и навигация','汽车电子产品和导航',10,0),(143,'Headsets and Headphones','Гарнитуры и наушники','耳机和头戴式耳机',10,0),(144,'Kids Electronics','Детская электроника','儿童电子产品',10,0),(145,'Game Consoles and Games','Игровые консоли и игры','游戏机和游戏',10,0),(146,'Cables and Chargers','Кабели и зарядные устройства','电缆和充电器',10,0),(147,'Music and Video','Музыка и видео','音乐和视频',10,0),(148,'Laptops and Computers','Ноутбуки и компьютеры','笔记本电脑和计算机',10,0),(149,'Office Equipment','Офисная техника','办公设备',10,0),(150,'Entertainment and Gadgets','Развлечения и гаджеты','娱乐和小工具',10,0),(151,'Networking Equipment','Сетевое оборудование','网络设备',10,0),(152,'Security Systems','Системы безопасности','安全系统',10,0),(153,'Smartphones and Phones','Смартфоны и телефоны','智能手机和电话',10,0),(154,'Smartwatches and Bracelets','Смарт-часы и браслеты','智能手表和手环',10,0),(155,'Solar Power Stations and Components','Солнечные электростанции и комплектующие','太阳能发电站和组件',10,0),(156,'TV, Audio, Photo, and Video Equipment','ТВ, Аудио, Фото, Видео техника','电视、音频、照片和视频设备',10,0),(157,'Commercial Equipment','Торговое оборудование','商业设备',10,0),(158,'Smart Home','Умный дом','智能家居',10,0),(159,'Electric Transport and Accessories','Электротранспорт и аксессуары','电动交通工具和配件',10,0),(160,'Stress Relievers','Антистресс','减压玩具',11,0),(161,'For Babies','Для малышей','适用于婴儿',11,0),(162,'Sand Toys','Для песочницы','沙盘玩具',11,0),(163,'Playsets','Игровые комплексы','游戏套装',11,0),(164,'Play Kits','Игровые наборы','游戏套装',11,0),(165,'Toy Weapons and Accessories','Игрушечное оружие и аксессуары','玩具武器和配饰',11,0),(166,'Toy Vehicles','Игрушечный транспорт','玩具车辆',11,0),(167,'Bath Toys','Игрушки для ванной','浴室玩具',11,0),(168,'Interactive Toys','Интерактивные','互动玩具',11,0),(169,'Kinetic Sand','Кинетический песок','动感沙',11,0),(170,'Building Blocks','Конструкторы','搭积木',11,0),(171,'LEGO Sets','Конструкторы LEGO','乐高套装',11,0),(172,'Dolls and Accessories','Куклы и аксессуары','玩偶和配饰',11,0),(173,'Musical Toys','Музыкальные','Musical Toys',11,0),(174,'Bubble Toys','Мыльные пузыри','泡泡玩具',11,0),(175,'Soft Toys','Мягкие игрушки','柔软玩具',11,0),(176,'Experiment Kits','Наборы для опытов','实验套装',11,0),(177,'Board Games','Настольные игры','棋类游戏',11,0),(178,'Remote Control Toys','Радиоуправляемые','遥控玩具',11,0),(179,'Educational Toys','Развивающие игрушки','教育玩具',11,0),(180,'Model Kits','Сборные модели','模型套装',11,0),(181,'Sports Games','Спортивные игры','运动游戏',11,0),(182,'Role-Playing Games','Сюжетно-ролевые игры','故事角色扮演游戏',11,0),(183,'Creativity and Crafts','Творчество и рукоделие','创意和手工艺',11,0),(184,'Figures and Robots','Фигурки и роботы','玩偶和机器人',11,0),(185,'Frameless Furniture','Бескаркасная мебель','无框家具',12,0),(186,'Kids Furniture','Детская мебель','儿童家具',12,0),(187,'Sofas and Armchairs','Диваны и кресла','沙发和扶手椅',12,0),(188,'Tables and Chairs','Столы и стулья','桌子和椅子',12,0),(189,'Computer and Gaming Furniture','Компьютерная и геймерская мебель','电脑和游戏家具',12,0),(190,'Living Room Furniture','Мебель для гостиной','客厅家具',12,0),(191,'Kitchen Furniture','Мебель для кухни','厨房家具',12,0),(192,'Bedroom Furniture','Мебель для спальни','卧室家具',12,0),(193,'Wardrobe Furniture','Гардеробная мебель','衣柜家具',12,0),(194,'Office Furniture','Офисная мебель','办公家具',12,0),(195,'Commercial Furniture','Торговая мебель','商业家具',12,0),(196,'Mirrors','Зеркала','镜子',12,0),(197,'Furniture Fittings','Мебельная фурнитура','家具配件',12,0),(198,'Lingerie and Accessories','Белье и аксессуары','内衣和配饰',13,0),(199,'Games and Souvenirs','Игры и сувениры','Games and Souvenirs',13,0),(200,'Intimate Cosmetics','Интимная косметика','私密化妆品',13,0),(201,'Edible Intimate Cosmetics','Интимная съедобная косметика','可食用私密化妆品',13,0),(202,'Condoms and Lubricants','Презервативы и лубриканты','避孕套和润滑剂',13,0),(203,'Sex Toys','Секс игрушки','性玩具',13,0),(204,'Fetish and BDSM','Фетиш и БДСМ','恋物癖和BDSM',13,0),(205,'Delicious Gifts','Вкусные подарки','美味的礼物',14,0),(206,'Food Gift Sets','Подарочные наборы продуктов','食品礼品套装',14,0),(207,'Tea and Coffee','Чай и кофе','茶和咖啡',14,0),(208,'Sweets and Bakery Products','Сладости и хлебобулочные изделия','糖果和面点',14,0),(209,'Groceries','Бакалея','食品杂货',14,0),(210,'Baby Food','Детское питание','婴儿食品',14,0),(211,'Food Additives','Добавки пищевые','食品添加剂',14,0),(212,'Healthy Food','Здоровое питание','健康食品',14,0),(213,'Meat Products','Мясная продукция','肉类产品',14,0),(214,'Dairy Products','Молочные продукты','奶制品',14,0),(215,'Beverages','Напитки','饮料',14,0),(216,'Snacks','Снеки','小吃',14,0),(217,'Dessert Mixes','Смеси для десертов','甜点混合',14,0),(218,'Fruits and Berries','Фрукты и ягоды','水果和浆果',14,0),(219,'Vegetables','Овощи','蔬菜',14,0),(220,'Fitness and Exercise Equipment','Фитнес и тренажеры','健身和运动设备',15,0),(221,'Cycling','Велоспорт','自行车运动',15,0),(222,'Yoga/Pilates','Йога/Пилатес','瑜伽/普拉提',15,0),(223,'Hunting and Fishing','Охота и рыбалка','狩猎和钓鱼',15,0),(224,'Scooters/Rollerblades/Skateboards','Самокаты/Ролики/Скейтборды','滑板车/轮滑/滑板',15,0),(225,'Camping and Hiking','Туризм/Походы','野外旅行/徒步旅行',15,0),(226,'Running/Walking','Бег/Ходьба','跑步/步行',15,0),(227,'Team Sports','Командные виды спорта','团队运动',15,0),(228,'Water Sports','Водные виды спорта','水上运动',15,0),(229,'Winter Sports','Зимние виды спорта','冬季运动',15,0),(230,'Support and Recovery','Поддержка и восстановление','支持和康复',15,0),(231,'Sports Nutrition and Cosmetics','Спортивное питание и косметика','Sports Nutrition and Cosmetics',15,0),(232,'Badminton/Tennis','Бадминтон/Теннис','羽毛球/网球',15,0),(233,'Billiards/Golf/Darts/Knife Throwing','Бильярд/Гольф/Дартс/Метание ножей','桌球/高尔夫/飞镖/飞刀',15,0),(234,'Martial Arts','Единоборства','格斗术',15,0),(235,'Equestrian Sports','Конный спорт','马术运动',15,0),(236,'Motorsports','Мотоспорт','摩托运动',15,0),(237,'Equipment for Standards Testing','Оборудование для сдачи нормативов','标准测试设备',15,0),(238,'Sailing','Парусный спорт','帆船运动',15,0),(239,'Climbing/Alpinism','Скалолазание/Альпинизм','攀岩/高山',15,0),(240,'Airsoft and Paintball','Страйкбол и пейнтбол','仿真射击和彩弹射击',15,0),(241,'Dance/Gymnastics','Танцы/Гимнастика','舞蹈/体操',15,0),(242,'For Kids','Для детей','为儿童',15,0),(243,'For Women','Для женщин','为女性',15,0),(244,'For Men','Для мужчин','为男性',15,0),(245,'Sport Shoes','Спортивная обувь','运动鞋',15,0),(246,'Self-Defense Products','运动鞋','Self-Defense Products',15,0),(247,'Electronics','Электроника','电子产品',15,0),(248,'Climate Control Appliances','Климатическая техника','空调设备',16,0),(249,'Beauty and Health Appliances','Красота и здоровье','美容和健康电器',16,0),(250,'Garden Appliances','Садовая техника','园艺电器',16,0),(251,'Household Appliances','Техника для дома','家用电器',16,0),(252,'Kitchen Appliances','Техника для кухни','厨房电器',16,0),(253,'Major Appliances','Крупная бытовая техника','大型家电',16,0),(254,'For Cats','Для кошек','为猫',17,0),(255,'For Dogs','Для собак','为狗',17,0),(256,'For Birds','Для птиц','为鸟',17,0),(257,'For Rodents and Ferrets','Для грызунов и хорьков','为啮齿动物和雪貂',17,0),(258,'For Horses','Для лошадей','为马',17,0),(259,'Aquarium Supplies','Аквариумистика','水族用品',17,0),(260,'Terrarium Supplies','Террариумистика','爬行动物用品',17,0),(261,'Farming Supplies','Фермерство','农业用品',17,0),(262,'Food and Treats','Корм и лакомства','食品和零食',17,0),(263,'Feeding Accessories','Аксессуары для кормления','喂养配件',17,0),(264,'Litter and Bedding','Лотки и наполнители','猫砂和饲料',17,0),(265,'Scratching Posts and Houses','Когтеточки и домики','抓痕板和房子',17,0),(266,'Carriers and Transport','Транспортировка','运输箱和运输工具',17,0),(267,'Equipment and Training','Амуниция и дрессировка','装备和训练',17,0),(268,'Toys','Игрушки','玩具',17,0),(269,'Grooming and Care','Груминг и уход','美容和护理',17,0),(270,'Clothing','美容和护理','服装',17,0),(271,'Veterinary Pharmacy','Ветаптека','兽医药房',17,0),(272,'Spare Parts for Cars','Запчасти на легковые автомобили','小型汽车备件',18,0),(273,'Oils and Fluids','Масла и жидкости','油和液体',18,0),(274,'Car Care and Auto Chemicals','Автокосметика и автохимия','汽车护理和汽车化学品',18,0),(275,'Paints and Primers','Краски и грунтовки','油漆和底漆',18,0),(276,'Car Electronics and Navigation','Автоэлектроника и навигация','汽车电子和导航',18,0),(277,'Batteries and Related Products','Аккумуляторы и сопутствующие товары','电池及相关产品',18,0),(278,'Interior and Trunk Accessories','Аксессуары в салон и багажник','内饰和后备箱配件',18,0),(279,'Коврики','Коврики','地垫',18,0),(280,'Exterior Tuning','Внешний тюнинг','外部改装',18,0),(281,'Other Accessories and Additional Equipment','Другие аксессуары и дополнительное оборудование','其他配件和附加设备',18,0),(282,'Tires and Wheels','Шины и диски колесные','轮胎和轮毂',18,0),(283,'Tools','Инструменты','工具',18,0),(284,'High-Pressure Washers and Accessories','Мойки высокого давления и аксессуары',' 高压洗车机及配件 ',18,0),(285,'Motorcycle Accessories','Мототовары','摩托车配件',18,0),(286,'Off-Road Accessories','OFFroad','越野配件',18,0),(287,' Power Equipment Parts','Запчасти на силовую технику','动力设备零件',18,0),(288,'Boat and Yacht Parts','Запчасти для лодок и катеров','船舶零件',18,0),(289,'Fiction','Художественная литература','小说',19,0),(290,'Comics and Manga','Комиксы и манга','漫画和漫画',19,0),(291,'Children\'s Books','Книги для детей','儿童书籍',19,0),(292,'Parenting and Child Development','Воспитание и развитие ребенка','父母和儿童发展',19,0),(293,'Education','Образование','教育',19,0),(294,'Self-education and Development','Самообразование и развитие','自我教育和发展',19,0),(295,'Business and Management','Бизнес и менеджмент','商业和管理',19,0),(296,'Hobbies and Leisure','Хобби и досуг','爱好和休闲',19,0),(297,'Astrology and Esoterics','Астрология и эзотерика','占星术和神秘主义',19,0),(298,'Home and Gardening','Дом, сад и огород','家庭，花园和园艺',19,0),(299,'Beauty, Health and Sports','Красота, здоровье и спорт','美容，健康和体育',19,0),(300,'Popular Science Literature','Научно-популярная литература','科普文学',19,0),(301,'Internet and Technology','Интернет и технологии','互联网和技术',19,0),(302,'Literary Studies and Publicism','Литературоведение и публицистика','文学研究和新闻报道',19,0),(303,'Philosophy','Философия','哲学',19,0),(304,'Religion','Религия','宗教',19,0),(305,'Politics and Law','Политика и право','政治和法律',19,0),(306,'Antiquarian Books','Букинистика','古旧书籍',19,0),(307,'Foreign Language Books','Книги на иностранном языке','外语书籍',19,0),(308,'Posters','Плакаты','海报',19,0),(309,'Calendars','Календари','日历',19,0),(310,'Collector\'s Editions','Коллекционные издания','收藏版',19,0),(311,'Reprints','Репринтные издания','复刻版',19,0),(312,'Multimedia','Мультимедиа','多媒体',19,0),(313,'Audiobooks','Аудиокниги','音频书',19,0),(314,'Digital Books','Цифровые книги','数字图书',19,0),(315,'Digital Audiobooks','Цифровые аудиокниги','数字音频书',19,0),(316,'Doors, Windows and Hardware','Двери, окна и фурнитура','门窗与五金',20,0),(317,'Tools and Accessories','Инструменты и оснастка','工具和配件',20,0),(318,'Finishing Materials','Отделочные материалы','饰面材料',20,0),(319,'Electrical','Электрика','电气',20,0),(320,'Paints and Coatings','Лакокрасочные материалы','油漆和涂料',20,0),(321,'Plumbing, Heating and Gas Supply','Сантехника, отопление и газоснабжение','水暖，供暖和燃气',20,0),(322,'Ventilation','Вентиляция','通风',20,0),(323,'Fasteners','Крепеж','紧固件',20,0),(324,'Building Materials','Стройматериалы','建筑材料',20,0),(325,'Plants, Seeds, and Soil','Растения, семена и грунты','植物，种子和土壤',21,0),(326,'Pots, Supports, and Everything for Seedlings','Горшки, опоры и все для рассады','盆栽，支架和一切都是幼苗',21,0),(327,'Fertilizers, Chemicals, and Protection','Удобрения, химикаты и средства защиты','肥料，化学品和防护',21,0),(328,'Bath and Sauna Goods','Товары для бани и сауны','浴室和桑拿用品',21,0),(329,'Grills, BBQs, and Barbecue','Грили, мангалы и барбекю','烧烤架，烧烤架和烧烤',21,0),(330,'Greenhouses, Polytunnels, and Covering Materials','Теплицы, парники, укрывной материал','温室，大棚和覆盖材料',21,0),(331,'High-Pressure Washers and Accessories','Мойки высокого давления и аксессуары','高压清洗机和配件',21,0),(332,'Garden Tools','Садовая техника','园艺工具',21,0),(333,'Garden Implements','Садовый инструмент','园艺工具',21,0),(334,'Irrigation and Water Supply','Полив и водоснабжение','灌溉和供水',21,0),(335,'Camping, Picnic, and Outdoor Recreation','Товары для кемпинга, пикника и отдыха','露营，野餐和户外娱乐',21,0),(336,'Garden Decor','Садовый декор','园艺装饰',21,0),(337,'Garden Furniture','Садовая мебель','花园家具',21,0),(338,'Inflatable Furniture','Надувная мебель','充气家具',21,0),(339,'Summer Cottage Sinks, Showers, and Toilets','Дачные умывальники, души и туалеты','夏季小屋的水槽，淋浴和厕所',21,0),(340,'Swimming Pools','Бассейны','游泳池',21,0),(341,'Protection from Insects and Rodents','Защита от насекомых и грызунов','防虫和啮齿动物的防护',21,0),(342,'Dietary Supplements','БАДы','膳食补充剂',22,0),(343,'Dried and Capsulated Mushrooms','Грибы сушеные и капсулированные','干燥和胶囊蘑菇',22,0),(344,'Disinfection, Sterilization, and Disposal','Дезинфекция, стерилизация и утилизация','消毒，灭菌和处置',22,0),(345,'Ear, Throat, Nose','Ухо, горло, нос','耳，喉咙，鼻子',22,0),(346,'Contraceptives and Lubricants','Контрацептивы и лубриканты','避孕套和润滑剂',22,0),(347,'Therapeutic Nutrition','Лечебное питание','治疗性营养',22,0),(348,'Protective Masks','Маски защитные','防护面具',22,0),(349,'Medical Supplies','Медицинские изделия','医疗用品',22,0),(350,'Medical Devices','Медицинские приборы','医疗设备',22,0),(351,'Health Improvement','Оздоровление','健康改善',22,0),(352,'Optics','Оптика','光学',22,0),(353,'Orthopedics','Ортопедия','矫形',22,0),(354,'Rehabilitation','Реабилитация','康复',22,0),(355,'Syrups and Balms','Сиропы и бальзамы','糖浆和香膏',22,0),(356,'Oral Care','Уход за полостью рта','口腔护理',22,0),(357,'Anatomical Models','Анатомические модели','解剖模型',23,0),(358,'Paper Products','Бумажная продукция','纸制品',23,0),(359,'Maps and Globes','Карты и глобусы','地图和地球仪',23,0),(360,'Office Supplies','Офисные принадлежности','办公用品',23,0),(361,'Writing Supplies','Письменные принадлежности','写字用品',23,0),(362,'Drawing and Sculpture','Рисование и лепка','绘画和雕塑',23,0),(363,'Counting Material','Счетный материал','计数材料',23,0),(364,'Commercial Supplies','Торговые принадлежности','商业用品',23,0),(365,'Drawing Accessories','Чертежные принадлежности','绘图用品',23,0),(366,'Rings','Кольца','戒指',24,0),(367,'Earrings','Серьги','耳环',24,0),(368,'Bracelets','Браслеты','手镯',24,0),(369,'Pendants and Charms','Подвески и шармы','吊坠和吊坠',24,0),(370,'Sets','Комплекты','配套',24,0),(371,'Necklaces, Chains and Cords','Колье, цепи, шнурки','项链，链条和绳子',24,0),(372,'Brooches','Броши','胸针',24,0),(373,'Piercings','Пирсинг','穿孔',24,0),(374,'Watches','Часы','手表',24,0),(375,'Clips, Cufflinks, and Belts','Зажимы, запонки, ремни','夹子，领带夹和腰带',24,0),(376,'Rosaries','Четки','念珠',24,0),(377,'Souvenirs and Silverware','Сувениры и столовое серебро','纪念品和银器',24,0),(378,'Gold Jewelry','Украшения из золота','黄金珠宝',24,0),(379,'Silver Jewelry','Украшения из серебра','银饰品',24,0),(380,'Ceramic Jewelry','Украшения из керамики','陶瓷珠宝',24,0),(381,'Jewelry Accessories','Аксессуары для украшений','珠宝配饰',24,0),(382,'Test categorie','Тестовая категория','test',7,0);
/*!40000 ALTER TABLE `subcategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_delivery`
--

DROP TABLE IF EXISTS `type_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_delivery` (
  `id` int NOT NULL AUTO_INCREMENT,
  `en_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ru_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zh_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `available_for_all` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_delivery`
--

LOCK TABLES `type_delivery` WRITE;
/*!40000 ALTER TABLE `type_delivery` DISABLE KEYS */;
INSERT INTO `type_delivery` VALUES (1,'Slow air delivery','Медленное авиа','慢速航空旅行',0),(2,'Default car','Обычное авто','一辆普通的汽车',0),(3,'Fast car/ Kazakhstan','Быстрое авто/ Казахстан','快速汽车/哈萨克斯坦',0),(4,'Fast car/ Kyrgyzstan','Быстрое авто/ Киргизия','快速汽车/吉尔吉斯斯坦',0),(5,'Railway delivery','ЖД доставка','铁路运输',0),(6,'Auto delivery via Belarus','Авто доставка через Беларусь','通过白俄罗斯自动送货',0),(7,'Fast car','Быстрое авто','快速汽车',0),(8,'Slow car delivery','Медленное авто','陆路送货速度慢',0),(9,'Fast car	','Быстрое авто	','快速汽车	',1);
/*!40000 ALTER TABLE `type_delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_delivery_link_category`
--

DROP TABLE IF EXISTS `type_delivery_link_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_delivery_link_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_delivery_id` int NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk-type_delivery_link_category-type_delivery_id` (`type_delivery_id`),
  KEY `fk-type_delivery_link_category-category_id` (`category_id`),
  CONSTRAINT `fk-type_delivery_link_category-category_id` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `fk-type_delivery_link_category-type_delivery_id` FOREIGN KEY (`type_delivery_id`) REFERENCES `type_delivery` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_delivery_link_category`
--

LOCK TABLES `type_delivery_link_category` WRITE;
/*!40000 ALTER TABLE `type_delivery_link_category` DISABLE KEYS */;
INSERT INTO `type_delivery_link_category` VALUES (1,7,3),(2,7,4),(3,7,6),(4,8,3),(5,8,4),(6,8,6),(7,8,5),(8,7,5),(9,9,7),(10,9,8),(11,9,9),(12,9,10),(13,9,11),(14,9,12),(15,9,13),(16,9,14),(17,9,15),(18,9,16),(19,9,17),(20,9,18),(21,9,19),(22,9,20),(23,9,21);
/*!40000 ALTER TABLE `type_delivery_link_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_delivery_link_subcategory`
--

DROP TABLE IF EXISTS `type_delivery_link_subcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_delivery_link_subcategory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_delivery_id` int NOT NULL,
  `subcategory_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk-type_delivery_link_subcategory-type_delivery_id` (`type_delivery_id`),
  KEY `fk-type_delivery_link_subcategory-subcategory_id` (`subcategory_id`),
  CONSTRAINT `fk-type_delivery_link_subcategory-subcategory_id` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategory` (`id`),
  CONSTRAINT `fk-type_delivery_link_subcategory-type_delivery_id` FOREIGN KEY (`type_delivery_id`) REFERENCES `type_delivery` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_delivery_link_subcategory`
--

LOCK TABLES `type_delivery_link_subcategory` WRITE;
/*!40000 ALTER TABLE `type_delivery_link_subcategory` DISABLE KEYS */;
/*!40000 ALTER TABLE `type_delivery_link_subcategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_delivery_point`
--

DROP TABLE IF EXISTS `type_delivery_point`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_delivery_point` (
  `id` int NOT NULL AUTO_INCREMENT,
  `en_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ru_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zh_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_delivery_point`
--

LOCK TABLES `type_delivery_point` WRITE;
/*!40000 ALTER TABLE `type_delivery_point` DISABLE KEYS */;
INSERT INTO `type_delivery_point` VALUES (1,'Warehouse','Склад','仓库'),(2,'Fulfilment','Фулфилмент','履行职责');
/*!40000 ALTER TABLE `type_delivery_point` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_delivery_price`
--

DROP TABLE IF EXISTS `type_delivery_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_delivery_price` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_delivery_id` int NOT NULL,
  `range_min` int DEFAULT NULL,
  `range_max` int DEFAULT NULL,
  `price` decimal(10,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  KEY `fk-type_delivery_price-type_delivery_id` (`type_delivery_id`),
  CONSTRAINT `fk-type_delivery_price-type_delivery_id` FOREIGN KEY (`type_delivery_id`) REFERENCES `type_delivery` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_delivery_price`
--

LOCK TABLES `type_delivery_price` WRITE;
/*!40000 ALTER TABLE `type_delivery_price` DISABLE KEYS */;
INSERT INTO `type_delivery_price` VALUES (1,1,0,50,0.0000),(2,1,50,80,0.0000),(3,1,80,100,0.0000),(4,1,100,110,0.0000),(5,1,110,120,0.0000),(6,1,120,130,0.0000),(7,1,130,140,0.0000),(8,1,140,150,0.0000),(9,1,150,160,0.0000),(10,1,160,170,0.0000),(11,1,170,180,0.0000),(12,1,180,190,0.0000),(13,1,190,200,0.0000),(14,1,200,250,0.0000),(15,1,250,300,0.0000),(16,1,300,350,0.0000),(17,1,350,400,0.0000),(18,1,400,500,0.0000),(19,1,500,600,0.0000),(20,1,600,700,0.0000),(21,1,700,800,0.0000),(22,1,800,900,0.0000),(23,1,900,1000,0.0000),(24,1,1000,1000000,0.0000),(25,2,0,50,0.0000),(26,2,50,80,0.0000),(27,2,80,100,0.0000),(28,2,100,110,0.0000),(29,2,110,120,0.0000),(30,2,120,130,0.0000),(31,2,130,140,0.0000),(32,2,140,150,0.0000),(33,2,150,160,0.0000),(34,2,160,170,0.0000),(35,2,170,180,0.0000),(36,2,180,190,0.0000),(37,2,190,200,0.0000),(38,2,200,250,0.0000),(39,2,250,300,0.0000),(40,2,300,350,0.0000),(41,2,350,400,0.0000),(42,2,400,500,0.0000),(43,2,500,600,0.0000),(44,2,600,700,0.0000),(45,2,700,800,0.0000),(46,2,800,900,0.0000),(47,2,900,1000,0.0000),(48,2,1000,1000000,0.0000),(49,3,0,50,0.0000),(50,3,50,80,0.0000),(51,3,80,100,0.0000),(52,3,100,110,0.0000),(53,3,110,120,0.0000),(54,3,120,130,0.0000),(55,3,130,140,0.0000),(56,3,140,150,0.0000),(57,3,150,160,0.0000),(58,3,160,170,0.0000),(59,3,170,180,0.0000),(60,3,180,190,0.0000),(61,3,190,200,0.0000),(62,3,200,250,0.0000),(63,3,250,300,0.0000),(64,3,300,350,0.0000),(65,3,350,400,0.0000),(66,3,400,500,0.0000),(67,3,500,600,0.0000),(68,3,600,700,0.0000),(69,3,700,800,0.0000),(70,3,800,900,0.0000),(71,3,900,1000,0.0000),(72,3,1000,1000000,0.0000),(73,4,0,50,0.0000),(74,4,50,80,0.0000),(75,4,80,100,0.0000),(76,4,100,110,0.0000),(77,4,110,120,0.0000),(78,4,120,130,0.0000),(79,4,130,140,0.0000),(80,4,140,150,0.0000),(81,4,150,160,0.0000),(82,4,160,170,0.0000),(83,4,170,180,0.0000),(84,4,180,190,0.0000),(85,4,190,200,0.0000),(86,4,200,250,0.0000),(87,4,250,300,0.0000),(88,4,300,350,0.0000),(89,4,350,400,0.0000),(90,4,400,500,0.0000),(91,4,500,600,0.0000),(92,4,600,700,0.0000),(93,4,700,800,0.0000),(94,4,800,900,0.0000),(95,4,900,1000,0.0000),(96,4,1000,1000000,0.0000),(97,5,0,50,0.0000),(98,5,50,80,0.0000),(99,5,80,100,0.0000),(100,5,100,110,0.0000),(101,5,110,120,0.0000),(102,5,120,130,0.0000),(103,5,130,140,0.0000),(104,5,140,150,0.0000),(105,5,150,160,0.0000),(106,5,160,170,0.0000),(107,5,170,180,0.0000),(108,5,180,190,0.0000),(109,5,190,200,0.0000),(110,5,200,250,0.0000),(111,5,250,300,0.0000),(112,5,300,350,0.0000),(113,5,350,400,0.0000),(114,5,400,500,0.0000),(115,5,500,600,0.0000),(116,5,600,700,0.0000),(117,5,700,800,0.0000),(118,5,800,900,0.0000),(119,5,900,1000,0.0000),(120,5,1000,1000000,0.0000),(121,6,0,50,0.0000),(122,6,50,80,0.0000),(123,6,80,100,0.0000),(124,6,100,110,0.0000),(125,6,110,120,0.0000),(126,6,120,130,0.0000),(127,6,130,140,0.0000),(128,6,140,150,0.0000),(129,6,150,160,0.0000),(130,6,160,170,0.0000),(131,6,170,180,0.0000),(132,6,180,190,0.0000),(133,6,190,200,0.0000),(134,6,200,250,0.0000),(135,6,250,300,0.0000),(136,6,300,350,0.0000),(137,6,350,400,0.0000),(138,6,400,500,0.0000),(139,6,500,600,0.0000),(140,6,600,700,0.0000),(141,6,700,800,0.0000),(142,6,800,900,0.0000),(143,6,900,1000,0.0000),(144,6,1000,1000000,0.0000),(145,7,0,50,5.5000),(146,7,50,80,5.5000),(147,7,80,100,5.5000),(148,7,100,110,5.6000),(149,7,110,120,5.5000),(150,7,120,130,5.4000),(151,7,130,140,5.3000),(152,7,140,150,5.2000),(153,7,150,160,5.1000),(154,7,160,170,5.0000),(155,7,170,180,4.9000),(156,7,180,190,4.8000),(157,7,190,200,4.7000),(158,7,200,250,4.6000),(159,7,250,300,4.5000),(160,7,300,350,4.4000),(161,7,350,400,4.3000),(162,7,400,500,4.3000),(163,7,500,600,4.3000),(164,7,600,700,4.3000),(165,7,700,800,4.3000),(166,7,800,900,4.3000),(167,7,900,1000,4.3000),(168,7,1000,1000000,4.3000),(169,8,0,50,0.0000),(170,8,50,80,0.0000),(171,8,80,100,0.0000),(172,8,100,110,0.0000),(173,8,110,120,0.0000),(174,8,120,130,0.0000),(175,8,130,140,0.0000),(176,8,140,150,0.0000),(177,8,150,160,0.0000),(178,8,160,170,0.0000),(179,8,170,180,0.0000),(180,8,180,190,0.0000),(181,8,190,200,0.0000),(182,8,200,250,0.0000),(183,8,250,300,0.0000),(184,8,300,350,0.0000),(185,8,350,400,0.0000),(186,8,400,500,0.0000),(187,8,500,600,0.0000),(188,8,600,700,0.0000),(189,8,700,800,0.0000),(190,8,800,900,0.0000),(191,8,900,1000,0.0000),(192,8,1000,1000000,0.0000),(193,9,0,50,0.0000),(194,9,50,80,0.0000),(195,9,80,100,0.0000),(196,9,100,110,0.0000),(197,9,110,120,0.0000),(198,9,120,130,0.0000),(199,9,130,140,0.0000),(200,9,140,150,0.0000),(201,9,150,160,0.0000),(202,9,160,170,0.0000),(203,9,170,180,0.0000),(204,9,180,190,0.0000),(205,9,190,200,0.0000),(206,9,200,250,0.0000),(207,9,250,300,0.0000),(208,9,300,350,0.0000),(209,9,350,400,0.0000),(210,9,400,500,0.0000),(211,9,500,600,0.0000),(212,9,600,700,0.0000),(213,9,700,800,0.0000),(214,9,800,900,0.0000),(215,9,900,1000,0.0000),(216,9,1000,1000000,0.0000);
/*!40000 ALTER TABLE `type_delivery_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_packaging`
--

DROP TABLE IF EXISTS `type_packaging`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_packaging` (
  `id` int NOT NULL AUTO_INCREMENT,
  `en_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ru_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zh_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(10,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_packaging`
--

LOCK TABLES `type_packaging` WRITE;
/*!40000 ALTER TABLE `type_packaging` DISABLE KEYS */;
INSERT INTO `type_packaging` VALUES (1,'Bag','Мешок','袋',2.4000),(2,'Bag and tape','Мешок + скотч',' 袋子和胶带',3.6000),(3,'Crate','Обрешетка','板条箱',36.0000),(4,'Cardboard corners and tape','Картонные уголки и скотч','纸板角和胶带',10.8000);
/*!40000 ALTER TABLE `type_packaging` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `access_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `personal_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `surname` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `organization_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone_number` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rating` float NOT NULL DEFAULT '0',
  `feedback_count` int NOT NULL DEFAULT '0',
  `is_deleted` int DEFAULT '0',
  `is_email_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `avatar_id` int DEFAULT NULL,
  `mpstats_token` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `phone_country_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone_number_UNIQUE` (`phone_number`),
  UNIQUE KEY `idx_user_personal_id` (`personal_id`),
  UNIQUE KEY `nickname_UNIQUE` (`nickname`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  KEY `fk_user_avatar_id` (`avatar_id`),
  CONSTRAINT `fk_user_avatar_id` FOREIGN KEY (`avatar_id`) REFERENCES `attachment` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'admin9@mail.com','$2y$13$sXhmDttcihoUrv56oOXB9ebeubQNazhYc6P/z8IKn3AxgszoDjPoa','c6_agdyQzW2tVCrslmC2NVcWl5b7_MMpReOyaDMOnGchz3h33p1FOmYrde6fQKFM','718118edb74a07ac96da9df9a7b55989','John','Makele','Organization','+375290000000',NULL,NULL,NULL,NULL,'admin',0,0,0,1,1,NULL,NULL,NULL,'BY'),(2,'dev1@mail.com','$2y$13$tDvtQDHYLil33MMKY04EpecvRjApRmyy090sxN6qVoQcZ8TFcO7nm','dngsF1DLCu93OJvA-4PepO7N-Tme4mdK0mib9puRPMJud61Xuwyzj9a3-imrK5VJ','9dc66e9053a5873b95e60f70be916447','Имя','Фамилия','ООО «Успех»','+375290000001',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'BY'),(3,'maxzaharov2003@mail.ru','$2y$13$cz8drHcPPovGjEa0Xf3pNuuioH3865HcmfFm4qc1B6vZKvZxnFYHy','Ul59OAut1jwQXJb8Ea2mKC-Y3zcyTLqFgbq-E9BNBeMFMGaLMkSs2DMb-H8YfSlZ','321f0417ec85b8938f1629b79bc9bd0c','Максим','Захаров','ИП Захаров Максим Владимирович','+79159698873',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(4,'dev2@mail.com','$2y$13$tDvtQDHYLil33MMKY04EpecvRjApRmyy090sxN6qVoQcZ8TFcO7nm','v9Bl7HoU4nEzTyByENuWDo5tZm6IZWTS0jb-yy4f2GhVrTpnywYdDXXaqT22BQJv','c2470d401af8dca86b059e1d399db3ff','Имя','Фамилия','ООО «Успех»','+375290000002',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'BY'),(5,'dev3@mail.com','$2y$13$tDvtQDHYLil33MMKY04EpecvRjApRmyy090sxN6qVoQcZ8TFcO7nm','jGUQcY74ezDGQRGxUQ09GTQTXkV4fAhMwXEIDXiBuOEt2XRPVZtGgz0sfGpkRryF','1ded956df1a2c39ea9c9b0f9c8edda24','Имя','Фамилия','ООО «Успех»','+375290000003',NULL,'BY','минск','проспект Мира 15','fulfillment',4.7,0,0,0,0,NULL,NULL,NULL,'BY'),(6,'arsensaruhanyan1987@icloud.com','$2y$13$Nsxwx0C1EpNJnmu0hTlD3O1GJTNqYvrQKCcDQVpFciNflHzaIB9AC','iGQw_HIl0RWUi8oK7ItdLGNJiwcURuOrYirFlgMmU9lHhYzFaS5nZFgA5ga5NF2i','45f3908a5c33cbcd6dd8a1a4abfd2334','Arsen','Sarukhanian','JOYCITY ','+79822219040',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(7,'arsensaruhanan@gmail.com','$2y$13$QVxgZFn93ifDQ9BjQ.FexOufxUsi1q/rI1VPa/XDav5beXqUBmNcK','JiYMUeB67uYOhQLCF-LtnHC_-7PrkWtYQ_yuS61f5XDnRBVoBZucLBu1FFXs-2ky','d6f1194ade742d613dcc614ef59abbb4','fulfillment','fulfillment','Арсен','+79183844654',NULL,'RU','Москва','Апаренки  ','fulfillment',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(8,'arsensaruhanan+1@gmail.com','$2y$13$cjEzcvFuBqRAU4pzQQUp7.gGni8bmU1EHwKuN1I03V4k2po7sWwgW','U8mROxY_gQTsWP650KJYsKGOMRkbH2J610Minzi4qshbu8WF-EzlV9p--MRBXB4k','f19f9c336ec361bd2fc952a00194291a','Арсен','Саруханян','JoyCity','+71111111111',NULL,NULL,NULL,NULL,'manager',0,0,0,0,0,NULL,NULL,NULL,NULL),(9,'username-liter@yandex.ru','$2y$13$hodc.vEM2Gx6aK6ouHvm5eyhfkbH8rcrmqkLmOXIT//MNIcevevs.','-lQ9NCypZ8ikjiu3Opms8E9u2E7Y40EdyS21-0aRygqFs04ddo9qQjOvESfNUwE1','c01518874a4ac5e8f8c96ec9d8fc81b8','fulfillment','fulfillment','балаган','+79680261131',NULL,'RU','Москва','Шипиловская 14','fulfillment',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(10,'daryargs@icloud.com','$2y$13$4czeYczMAxk5QIKeg5/2zeV2sCNRCFgX3I8G.tH1MBuQobmYhCo4i','_Zn2a8RZnJXd68nerbKRUqbAD_c6IU2WhSnEe-Fw8h_nkGBQXdt1hFCTzdrJ2RRG','5376ca657d831cae6ccbcd5fd09bee24','fulfillment','fulfillment','Дарья','+79055074433',NULL,'RU','Москва','Кутузовское шоссе 45/7','fulfillment',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(11,'vladkrut78@gmail.com','$2y$13$cR9a68mq6BwFC1W7wGhWU.4iRkR/fmxuU0.7W5DRvPZz41KcQ4BQC','Y2asOJR6GgJdFeoAm30Zkl7WMUGndOTNPty2qw0scCYI0j112hwHqv8cIzpaWsnv','f3e721b1ca73034397b58844de107202','Владислав','Исаев','балаган','+79055593670',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(12,'gjumik.mar@gmail.com','$2y$13$Ko37utoVzttln70baJ.h1u/YZoFe0XEdIDoQ4TU1bF2czuYC.4Od6','FqwJ79tF3PeUAelNkaZ0Zm4jZ2CovI1sP4s4sGX3QZQH9_rf7jn-fxsC5qavPy4g','44fcbbb21b0f6b6f501e15ba3bdfdde5','buyer','buyer','марат','+79999986402',NULL,'RU','Москва','пролетарская 7','buyer',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(13,'gjumik.mar1@gmail.com','$2y$13$5041/t8k7ILit7b0gYAHQOUGiTI6Wyp/Y3cWlmP83c57Wm4vZwc5y','REycQXCg4FLX1v9Y0SUeQA1D--9taS4qngOyNjebPw49BWhdPCyGXQCMZ6Mwdj2b','08f7e9c3897ae4273f8c6ff9cc87438c','fulfillment','fulfillment','JC FF','+79998986402',NULL,'RU','Москва','313JC','fulfillment',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(14,'gjumik.mar2@gmail.com','$2y$13$l/GtTWwuPyfgUUb.C72bzevFkMbLFNM1YYk4Hjlz9vhLfGrnox1NG','feo95Q3hTalQhhgWZFX0gJ0YGAvuNlicy1RNBfy-N4iUIMJfzJ1VlncCPLkf1XQh','0b0f923e32b0307b1553bb0ef66abd91','marat','19','JC','+79375425854',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(15,'arsensaruhanyan1987@icl.com','$2y$13$FHR/PtBLFmxX7.XFLJQSZ.5.V3KVnlhwyB.gKMoTaipTb4zgYclbO','FzuJqNpTDX8q1h5SNAShDMVSd3IFtQI7SHnehopTSzDlAM4FFcCaV9PinCAmPczk','bcaaa4aad417ca2916efe6a9e2ad3d9b','buyer','buyer','ARSEN ','+79548437948',NULL,'CN','Москва','Преображенская','buyer',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(16,'shdav123456@gmail.com','$2y$13$uTh5uQMIM6Y5ry5e5unHbuqoovi.h5ISRJP6Y13indvaDQeL2U3VG','9WWXinAujxwGmcQmQPnd29JZwlTMEavAw4coffiI8f06oHw_30oHWB1uLKkLdxuD','21fe1751de57a638c5db87b75f47e6b3','Шако','Давиташвили','Shadik','+79032652882',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(17,'anush-anka@mail.ru','$2y$13$YbluhrpMC29xIl.mG9I4c.VXKuHZoBVf/2LhqGTl3dKUbIb//lNhO','tA2-EcS0s97JL9S27uwYKrp4Nxt-3HaYLG4XdbOPInnwU3d1uxt8JgoV7QP9sKtN','0a4f7622a15e1d21be5e89c0a3997f9f','Ануш','Шмагина','ИП Шмагин','+79858087023',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(18,'shdav123456+1@gmail.com','$2y$13$LxM6tVx6xlRH5O0m2RUmSOO.7Lm2GTtoWW.eOFSoIraXCfWGJDybi','v3-yhCzghVY9EtzPY86qgdee0H6deotDUU5a7rxc_iDtBMVUk-wdTxy7DOcsV9Om','b8fe57b5b0021d3000d093cafab371d9','fulfillment','fulfillment','shadik','+79851753299',NULL,'RU','москва','кутузовское шоссе 45','fulfillment',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(19,'m.pestina@icloud.com','$2y$13$MPPvjGBd5NKALOCRmH7gOuzaesPUAl6P5d8IyRWaQngOdFBHDbdHG','B1ZRaW7H07Bkuhh98TJD-KutTa0f9NiupfG9b5Og0RpqllD2zbCknU0UzxAMPUaW','cc851711bac648869898417dd2be743f','fulfillment','fulfillment','ИП Говоркова Мария Александровна','+79775966835',NULL,'RU','Москва','Нагатинская набережная 66-2','fulfillment',4.7,0,0,0,0,71,NULL,NULL,'RU'),(20,'m.pestina+1@icloud.com','$2y$13$NVQzEC5fEvXTExE.Oa8Kcuf8vSNTtbaPlV2d9xh67geiwvITpRv6K','_CX7jgROVQ9ez7oJMRmioTT-OieiT96g2f9X784nduI4orQ7jRWdJMgYZMaTm286','a930e50a28726064d8688b46a8e3198b','Мария','Говоркова','ИП Говоркова','+79776589635',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,77,NULL,NULL,'RU'),(21,'arlekinosurgut@gmail.com','$2y$13$DX9TcY7tHW.23g/FJ8.qmuHz4l0oubUDiE.K4uU4dG9UwsfbiuJaG','EaaTM6f-W1FgQ5KPYoex_0V4iDEuv_jsefCqtPj5y9UeR3UQ40oV0gQhX30VvrWr','b414b75107593f39f5c7f19808ee6340','Армен','Е','ООО Армен','+79128159950',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(22,'arlekinosurgut+1@gmail.com','$2y$13$02H7vZ1BsjOntJl9/zyCM.hsemICYbLTjRwFcfqNWd3.HVnVuNN9e','r2xzwVFfG-cuJLudRJWNwYt9X7UVS_g2UGrAXxgQANay-kzypGSPog3xxxU6VV0V','eaf4a282d8a2d13904b285c5aebb4c41','fulfillment','fulfillment','ООО Армен','+79128159951',NULL,'RU','Москва','Ленина 11','fulfillment',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(23,'and.kasat7@gmail.com','$2y$13$9X0B4UE8HIKqXkNXVr9YWesAg9UqneirrAEK0VVquNiSgFQEOKHFS','sLA85ye6FpEjMM6Dy0JkteKGPcVJma4TaPqPWJJceAfsh1vvdl5QUxa_l9lDz2qB','e898fa3851535e5703265f05e62c3bf7','Андрей','Касаткин','ИП Касаткин Андрей Леонидович','+79933600837',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(24,'and.kasat7+1@gmail.com','$2y$13$aiGjD67zMz4YOZ8HD0i44O2ySaCk5ohCmug3mwX9IvQn9kAvZKzzm','QH3cx6LKcg4m_YCmy8SNurDaaxGDzkxLf3CsoVvs4hqRq23JyJAuDO-SFjb2d5En','cba1e5b6729a8590747956f82c5a0667','fulfillment','fulfillment','ИП Касаткин Андрей Леонидович','+79687960083',NULL,'RU','Москва','Красная площадь 3','fulfillment',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(25,'stifbi44@icloud.com','$2y$13$ET8.3ycEGo9ii9CJMrfQr.QafL7smpsYfuvXgexy0NJ8OXGuT7ZcK','jnC2zq1cDuFlDO-QkOWbWI9FSUmcakeAkyf4Z4lIS9Mc1jcZcXWTX0ZI5XyQ8G3V','0ff457da537c82c0ed29ffcfd977fccb','ivan','abdin','ИП Абдин','+79231022023',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(26,'terarutyunyanedgat@gmail.com','$2y$13$NQ2/VaFbrbtFNAaVVr/uK.BnwwyZGVziMH/DyzlqkH655m/2iFqK.','XR49rHzX2zyRn6iMDzpklIUjqXMrtnsLMJ8IH_-kRFY-_qYnYlwscswM6rIX1c-t','1ace0869abedfc9034df7db127832ada','Эдгар','Арутюнян','ИП Арутюнян Э А','+79203517336',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(27,'terarutyunyanedgat+1@gmail.com','$2y$13$4nZPxDEWupadJ6fb0Z6xHOacKQvATLXt1/Ve/wAtPGOZXJsQrIm7e','ibVPdfFdzBHKKXxH9CBgcEXI5tUSvcgm-LP6jXt4jjkEw-pt8_UGkB_xT9TFim-D','6d4357c2dc3da307c6f910de355cebb8','fulfillment','fulfillment','Русский Фулфилмент ','+79801611344',NULL,'RU','Москва','Пермская дом 1 стр 1','fulfillment',4.7,0,0,0,0,86,NULL,NULL,'RU'),(28,'zvahieva@bk.ru','$2y$13$pieTKzMoVLAJm7WT44tpk.LTDK1JXgNB3BNSQYvUW38fug3K24FFW','mIz85XTjahJYqVm7Nf3LLizmw69IhEMedWBZyz6n-x8Wh2m17VyC7VrlsdyBZIrL','9d81b56b062baf7f17f0b57fea329b08','залина1','вахиева1','Залина ','+79670022224',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(29,'zvahieva+1@bk.ru','$2y$13$kTHhwr9yNPbAVgRBw44hS.pXSGw7OMMWxotgabzfxrkEsbAtepJFu','CITMGHWG1lCKs4h2KGisQnEy-5WAZeV5EUCdTBMIn3z-Tc95vYYLXiFBSgJCieqv','1575e5542c505adbdd61c3a17772867d','fulfillment','fulfillment','залина','+79201071111',NULL,'RU','москва','ул. 1905г','fulfillment',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(30,'sdvijcjciciccicicic@gmail.com','$2y$13$HjU1QSP4mzzu8yKqXOKdC.aCEqZV7KSPgJYboebcUY3LP5SITgI0u','PBJTpGsQP8vg7BKqxx9nphKBeFucVywP9Z9bikH9IqveV3yMQZ4VIHOeziaCm-Cq','ca8ba89052079fdb4c66b3a5db47b74b','buyer','buyer','Arsen','+75886990525',NULL,'BH','Москва-Сити','Армиамистии о','buyer',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(31,'gjumik.mar2+5@gmail.com','$2y$13$jFEUqSkO7UI34gHEhbAUburH9QKutr43a9xwkfUC3kBH3fcdCPRHG','PGx1NlqWnXHWF1qfUkHWnkMM56_0m80Z9bT1WyK1Ihysz_8n6b6GA8NCPBc6ZBTW','6e19d556c01a7e1d91b9986078070935','Марат','Джумагалиев','JoyCity','+79123456789',NULL,NULL,NULL,NULL,'manager',0,0,0,0,0,NULL,NULL,NULL,NULL),(32,'oksa-starostina@yandex.ru','$2y$13$jk/wqoErXX8zdq3WrwMb/.PGJ7npkGCx0r8n462Xw3NYeABXzGgfq','j4XhSVrzvmXlfLhTvGs4cc7YmAzqYCjDJYvKCLx7-viZsZw_kgWgMnDWizt7tW2R','5f36226de9081bce819cb653fcd9d4df','Оксана','Старостина','ИП Старостина','+79267152161',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(33,'dev4@mail.com','$2y$13$tDvtQDHYLil33MMKY04EpecvRjApRmyy090sxN6qVoQcZ8TFcO7nm','VapzhZ3nGTOS0wRamGjgQcAveH9AktMrE2_b-L7R3YWk6vFs9Y7TutL_9luDKpUG','c12a3d905e0e6b70223d9391e7b80d66','Имя','Фамилия','ООО «Успех»','+375290000004',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,0,NULL,NULL,NULL,'BY'),(34,'arsensaruhanyan198g@icloud.com','$2y$13$yMBOM76cP2Jie3bTUjW4yeAx8sdMVSP1hqwcj8st0wgOLC8ZQ35ja','bzZVofcKdO7Vxo9q7q4982KFQMufb-Vrbow97zeTEcHv1kOLmzIfUHFBXiwg_HKK','bb8c1ecfd1d42adfd3ec1ff65e9d6ac8','Dima','Stinov','fhgcbj','+72882596688',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(35,'appletest@gmail.com','$2y$13$1lqU377OEc2/jAnT01jvzeos8PRZrUvfqf27fEzokzzf3SqYhE4ge','8yt7FgpaeKUi14ngGy7Wp9q2yZ75Y0_OWkP7Yv_QndZCECslKyB2LCLx-x4Fryvl','d7cfe865d6562afff195957b26a9c8de','apple','test','Apple test','+77777777777',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(36,'5856356@mail.ru','$2y$13$1SbmcyP7qiiAj.pawg/TQuOxeLsdkF5tr1bXGAh37TaMUeOAgQHDe','I9Xs3hyOcBypQ9VAQ9B7EhWbbt-oP4g0WrM8ahMbJACYCbLKlIcVoI3bL_axI-NR','521bdad1a8c2060bc3a480f20d40da30','Марат','Садыков','Telosofia','+79150688858',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(37,'pc+joycity@friflex.com','$2y$13$JP81BF/iSq4XaeA3kPZJqOuWvtR3q1xCFNU/j9HBelJqEihA4LqnS','W-qFbZiSaDSpfi_DkgRQWg0HKM1E6ltWRa_4sE36aYBUlHoKZZDWbr89aCnGAo_1','353cc4566bb1e68be349e0de6ce9bee5','Петр','Че','Ромашка','+79268180621',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(38,'test@nagash.org','$2y$13$2XM1xau7ZSjrIQXab6jgBexRvwk8A4pwirVR9plOMTc0vaVAkGyDS','4mlx8nvHO0pMziI0AVs9oK-jp7EF1x0CJg5aQVTbQOl0Z6lTV5LjQvBEZGsH5z_o','1e5e0be98dee4ceefbe6776aeac7f1c7','вячеслав','гордеенков','Friflex','+79161346052',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(42,'test@test.test','$2y$13$sXhmDttcihoUrv56oOXB9ebeubQNazhYc6P/z8IKn3AxgszoDjPoa','c6_agdyQzW2tVCrslmC2NVcWl5b7_MMpReOyaDMOnGchz3h33p1FOmYrde6fQKFM','718118edb74a07ac96da9df9a7b55988','John','Makele','Organization','+375290000020',NULL,NULL,NULL,NULL,'client',0,0,0,1,1,NULL,NULL,NULL,'BY'),(44,'buyer@buyer.bb','$2y$13$sXhmDttcihoUrv56oOXB9ebeubQNazhYc6P/z8IKn3AxgszoDjPoa','c6_agdyQzW2tVCrslmC2NVcWl5b7_MMpReOyaDMOnGchz3h33p1FOmYrde6fQKFD','718118edb74a07ac96da9df9a7b55987','John','Makele','Organization','+375292200002',NULL,NULL,NULL,NULL,'buyer',0,0,0,1,1,NULL,NULL,'test','BY'),(45,'dima.withsmile@gmail.com','$2y$13$sZfExwB2oVPCuBnI.N0LDevwS8200RROmi1r.IHUxJ8CSu/maH36m','2p0mZ6996BHRWjq2obATroZHnOwrZu-s4M08T8X7xB6cgCTzV4GZN03I1fqI0Doa','ce0f5738d6505ad8ef92ebc15ad34bd8','дмитрий','сулыбкин','агроторг','+79626848974',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,1,NULL,NULL,NULL,'RU'),(88,'admin@test.com','$2y$13$sXhmDttcihoUrv56oOXB9ebeubQNazhYc6P/z8IKn3AxgszoDjPoa','c6_agdyQzW2tVCrslmC2NVcWl5b7_MMpReOyaDMOnGchz3h33p1FOmYrde6fQKFM','718118edb74a07ac96da9df9a7b55990','John','Makele','Organization','+79000000000',NULL,NULL,NULL,NULL,'admin',0,0,0,1,1,NULL,NULL,NULL,'RU'),(89,'superadmin@test.com','$2y$13$sXhmDttcihoUrv56oOXB9ebeubQNazhYc6P/z8IKn3AxgszoDjPoa','c6_agdyQzW2tVCrslmC2NVcWl5b7_MMpReOyaDMOnGchz3h33p1FOmYrde6fQKFM','718118edb74a07ac96da9df9a7b55945','Jane','Doe','Company','+79000000001',NULL,NULL,NULL,NULL,'super-admin',0,0,0,1,1,NULL,NULL,NULL,'RU'),(90,'client@test.com','$2y$13$sXhmDttcihoUrv56oOXB9ebeubQNazhYc6P/z8IKn3AxgszoDjPoa','c6_agdyQzW2tVCrslmC2NVcWl5b7_MMpReOyaDMOnGchz3h33p1FOmYrde6fQKFM','718118edb74a07ac96da9df9a7b55327','Alice','Smith','Agency','+79000000002',NULL,NULL,NULL,NULL,'client',0,0,0,1,1,NULL,NULL,NULL,'RU'),(91,'manager@test.com','$2y$13$sXhmDttcihoUrv56oOXB9ebeubQNazhYc6P/z8IKn3AxgszoDjPoa','c6_agdyQzW2tVCrslmC2NVcWl5b7_MMpReOyaDMOnGchz3h33p1FOmYrde6fQKFM','718118edb74a07ac96da9df9a7b51186','Bob','Johnson','Store','+79000000003',NULL,NULL,NULL,NULL,'manager',0,0,0,1,1,NULL,NULL,NULL,'RU'),(92,'buyer@test.com','$2y$13$sXhmDttcihoUrv56oOXB9ebeubQNazhYc6P/z8IKn3AxgszoDjPoa','c6_agdyQzW2tVCrslmC2NVcWl5b7_MMpReOyaDMOnGchz3h33p1FOmYrde6fQKFM','718118edb74a07ac96da9df9a3255985','Eve','Williams','Shop','+79000000004',NULL,NULL,NULL,NULL,'buyer',0,0,0,1,1,NULL,NULL,NULL,'RU'),(93,'fulfillment@test.com','$2y$13$sXhmDttcihoUrv56oOXB9ebeubQNazhYc6P/z8IKn3AxgszoDjPoa','c6_agdyQzW2tVCrslmC2NVcWl5b7_MMpReOyaDMOnGchz3h33p1FOmYrde6fQKFM','718118edb74a07ac96da9df9a7225984','Grace','Brown','Warehouse','+79000000005',NULL,NULL,NULL,NULL,'fulfillment',0,0,0,1,1,NULL,NULL,NULL,'RU'),(94,'yura.volkovskiy.2006@gmail.com','$2y$13$hJsyf267XEpCqboL7IXfVu5fdG4ZpcKOtblpoxYAXq2LQgVwgrkUy','Mi5PfGFmF3_i93WhzP0eVebJ22LnTU158Y3w7fxI_Z-IKLtWJJAbYrlYbX_q1lNe','695b123d3fced03874fe92d107dc161a','buyer','buyer','Friflex','+79381809022',NULL,'RU','москва','тест','client',4.7,0,0,0,0,NULL,NULL,NULL,'RU'),(95,'code70@inbox.ru','$2y$13$V8QQk07EDelgygbr7GJpTenxbnDk9O7HCV8M0iZ.BQAThBBd5Sc/y','1jsxqd_XHqG9UL9naEIMZhA1X8Z5W7F7KoV5qZ-b7Hc0NZxyHMxnXE3-G-aYTw5L','d3e19d4ba24a00effb8eb3f4b7071426','Andrew','Korobkov','argtorg','+79833409040',NULL,NULL,NULL,NULL,'client',4.7,0,0,1,1,NULL,NULL,NULL,'RU'),(96,'testingo@gmail.com','$2y$13$tNmtbQIKnHshhIP7r9Xgt.E8xdZY0hNSdH4IS.wnKDO.oobyBRz16','CtMJHJ3nlGJ2P_pq7Qixu4KhGh4eSLihDvdKr73dhU7U2JIIhfUvdMrIsAcXKJVr','9c7d9f3cc8fa6df90bf3342893d47f13','Энгин','Замаль','Товар чина ру','+75331467264',NULL,NULL,NULL,NULL,'client',4.7,0,0,0,0,NULL,NULL,NULL,'RU');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_link_category`
--

DROP TABLE IF EXISTS `user_link_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_link_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`id`,`user_id`,`category_id`),
  KEY `fk_user_has_category_category1_idx` (`category_id`),
  KEY `fk_user_has_category_user1_idx` (`user_id`),
  CONSTRAINT `fk_user_has_category_category1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `fk_user_has_category_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_link_category`
--

LOCK TABLES `user_link_category` WRITE;
/*!40000 ALTER TABLE `user_link_category` DISABLE KEYS */;
INSERT INTO `user_link_category` VALUES (4,3,3),(15,4,3),(20,14,3),(21,15,3),(31,17,3),(55,20,3),(60,23,3),(74,25,3),(82,28,3),(87,32,3),(111,35,3),(112,36,3),(122,45,3),(126,95,3),(151,44,3),(1,2,4),(16,6,4),(22,15,4),(32,17,4),(61,23,4),(75,25,4),(83,30,4),(98,34,4),(124,94,4),(127,95,4),(5,3,5),(23,15,5),(29,16,5),(33,17,5),(56,20,5),(62,23,5),(76,25,5),(91,33,5),(99,34,5),(128,95,5),(6,3,6),(34,17,6),(63,23,6),(81,26,6),(92,33,6),(113,36,6),(117,38,6),(129,95,6),(7,3,7),(24,15,7),(35,17,7),(57,20,7),(59,21,7),(64,23,7),(77,25,7),(88,32,7),(118,38,7),(125,94,7),(130,95,7),(36,17,8),(65,23,8),(78,25,8),(93,33,8),(100,34,8),(131,95,8),(148,96,8),(8,3,9),(17,11,9),(25,15,9),(37,17,9),(66,23,9),(79,25,9),(89,32,9),(94,33,9),(101,34,9),(132,95,9),(149,96,9),(9,3,10),(18,11,10),(38,17,10),(67,23,10),(84,30,10),(95,33,10),(102,34,10),(115,37,10),(119,38,10),(133,95,10),(10,3,11),(30,16,11),(39,17,11),(68,23,11),(96,33,11),(134,95,11),(150,96,11),(40,17,12),(85,30,12),(97,33,12),(103,34,12),(135,95,12),(26,15,13),(41,17,13),(69,23,13),(104,34,13),(136,95,13),(27,15,14),(105,34,14),(123,45,14),(137,95,14),(11,3,15),(42,17,15),(58,20,15),(70,23,15),(90,32,15),(114,36,15),(116,37,15),(120,38,15),(138,95,15),(2,2,16),(12,3,16),(28,15,16),(43,17,16),(139,95,16),(13,3,17),(19,12,17),(44,17,17),(71,23,17),(106,34,17),(140,95,17),(3,2,18),(45,17,18),(72,23,18),(107,34,18),(121,38,18),(141,95,18),(46,17,19),(108,34,19),(142,95,19),(47,17,20),(109,34,20),(143,95,20),(14,3,21),(48,17,21),(73,23,21),(86,30,21),(144,95,21),(110,34,22),(145,95,22),(49,17,23),(80,25,23),(146,95,23),(50,17,24),(147,95,24);
/*!40000 ALTER TABLE `user_link_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_link_type_delivery`
--

DROP TABLE IF EXISTS `user_link_type_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_link_type_delivery` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_delivery_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_type_delivery_has_user_user1_idx` (`user_id`),
  KEY `fk_type_delivery_has_user_type_delivery1_idx` (`type_delivery_id`),
  CONSTRAINT `fk_type_delivery_has_user_type_delivery1` FOREIGN KEY (`type_delivery_id`) REFERENCES `type_delivery` (`id`),
  CONSTRAINT `fk_type_delivery_has_user_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_link_type_delivery`
--

LOCK TABLES `user_link_type_delivery` WRITE;
/*!40000 ALTER TABLE `user_link_type_delivery` DISABLE KEYS */;
INSERT INTO `user_link_type_delivery` VALUES (1,2,12),(2,2,15),(3,1,15),(4,3,15),(5,4,15),(6,1,30),(7,2,30),(8,3,30),(9,4,30),(10,6,30),(11,5,30),(12,7,30),(13,1,94),(14,2,94),(15,3,94),(16,4,94),(17,1,44),(18,2,44),(19,3,44),(20,5,44),(21,4,44),(22,6,44),(23,8,44),(24,7,44),(25,9,44);
/*!40000 ALTER TABLE `user_link_type_delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_link_type_packaging`
--

DROP TABLE IF EXISTS `user_link_type_packaging`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_link_type_packaging` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_packaging_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_type_packaging_has_user_user1_idx` (`user_id`),
  KEY `fk_type_packaging_has_user_type_packaging1_idx` (`type_packaging_id`),
  CONSTRAINT `fk_type_packaging_has_user_type_packaging1` FOREIGN KEY (`type_packaging_id`) REFERENCES `type_packaging` (`id`),
  CONSTRAINT `fk_type_packaging_has_user_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_link_type_packaging`
--

LOCK TABLES `user_link_type_packaging` WRITE;
/*!40000 ALTER TABLE `user_link_type_packaging` DISABLE KEYS */;
INSERT INTO `user_link_type_packaging` VALUES (1,1,12),(2,2,12),(3,1,15),(4,2,15),(5,3,15),(6,1,30),(7,2,30),(8,3,30),(9,1,94),(10,2,94),(11,3,94),(12,1,44),(13,2,44),(14,3,44),(15,4,44);
/*!40000 ALTER TABLE `user_link_type_packaging` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enable_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `application_language` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `chat_language` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `use_only_selected_categories` tinyint(1) NOT NULL DEFAULT '0',
  `high_workload` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fk_user_settings_user_id` (`user_id`),
  CONSTRAINT `fk_user_settings_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_settings`
--

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (2,0,'RUB','ru','ru',1,0,0),(3,1,'RUB','ru','en',2,0,0),(4,1,'RUB','ru','ru',3,0,0),(5,1,'RUB','ru','ru',4,0,0),(6,1,'RUB','ru','ru',5,0,0),(7,1,'RUB','ru','ru',6,0,0),(8,1,'RUB','ru','ru',7,1,0),(9,1,'CNY','ru','ru',8,0,0),(10,1,'RUB','ru','ru',9,0,0),(11,1,'RUB','ru','ru',10,0,0),(12,1,'RUB','ru','ru',11,0,0),(13,1,'CNY','ru','ru',12,0,0),(14,1,'RUB','ru','ru',13,0,0),(15,1,'RUB','ru','ru',14,0,0),(16,1,'CNY','ru','ru',15,0,0),(17,1,'RUB','ru','ru',16,0,0),(18,1,'RUB','ru','ru',17,0,0),(19,1,'RUB','ru','ru',18,0,0),(20,1,'RUB','ru','ru',19,0,1),(21,1,'RUB','ru','ru',20,0,0),(22,1,'RUB','ru','ru',21,0,0),(23,1,'RUB','ru','ru',22,0,0),(24,1,'RUB','ru','ru',23,0,0),(25,1,'RUB','ru','ru',24,0,0),(26,1,'RUB','ru','ru',25,0,0),(27,1,'RUB','ru','ru',26,0,0),(28,1,'RUB','ru','ru',27,0,0),(29,1,'RUB','ru','ru',28,0,0),(30,1,'RUB','ru','ru',29,0,0),(31,1,'CNY','ru','ru',30,0,0),(32,1,'CNY','ru','ru',31,0,0),(33,1,'RUB','ru','ru',32,0,0),(34,1,'RUB','ru','ru',33,0,0),(35,1,'RUB','ru','ru',34,0,0),(36,1,'RUB','ru','ru',35,0,0),(37,1,'RUB','ru','ru',36,0,0),(38,1,'RUB','ru','ru',37,0,0),(39,1,'RUB','ru','ru',38,0,0),(40,1,'RUB','ru','ru',45,0,0),(41,1,'CNY','ru','ru',94,0,0),(42,1,'RUB','ru','ru',95,0,0),(43,1,'RUB','ru','ru',96,0,0),(44,0,'RUB','ru','ru',44,0,0),(45,0,'RUB','ru','ru',92,0,0);
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_verification_request`
--

DROP TABLE IF EXISTS `user_verification_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_verification_request` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_by_id` int NOT NULL,
  `manager_id` int NOT NULL,
  `approved_by_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `amount` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_user_verification_request_created_by_id` (`created_by_id`),
  KEY `fk_user_verification_request_approved_by_id` (`approved_by_id`),
  KEY `fk_user_verification_request_manager_id` (`manager_id`),
  CONSTRAINT `fk_user_verification_request_approved_by_id` FOREIGN KEY (`approved_by_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_user_verification_request_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`),
  CONSTRAINT `fk_user_verification_request_manager_id` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_verification_request`
--

LOCK TABLES `user_verification_request` WRITE;
/*!40000 ALTER TABLE `user_verification_request` DISABLE KEYS */;
INSERT INTO `user_verification_request` VALUES (1,2,8,8,'2024-02-13 11:16:44',103.4483,1),(2,14,8,8,'2024-02-13 15:19:26',103.4483,1),(3,6,8,8,'2024-02-13 18:29:14',103.4483,1),(4,16,8,8,'2024-02-14 11:51:46',103.4483,1),(5,20,8,1,'2024-02-14 14:14:59',103.4483,1),(6,21,8,8,'2024-02-14 17:12:48',103.4483,1),(7,11,8,8,'2024-02-14 22:19:54',103.4483,1),(8,25,8,8,'2024-02-15 00:16:34',103.4483,1),(9,26,8,1,'2024-02-15 13:19:18',103.4483,1),(10,28,8,1,'2024-02-16 00:30:19',103.4483,1),(11,32,8,1,'2024-02-17 01:27:48',103.4483,1),(12,4,8,1,'2024-02-18 18:01:39',103.4483,1),(13,34,31,1,'2024-02-21 00:47:12',103.4483,1),(14,36,8,1,'2024-02-26 17:10:58',113.2075,1),(15,35,8,1,'2024-02-27 22:04:54',113.2075,1),(16,37,8,1,'2024-04-09 11:33:23',113.2075,1),(17,38,8,1,'2024-04-10 11:17:25',113.2075,1),(18,45,31,1,'2024-07-11 19:34:50',113.2075,1);
/*!40000 ALTER TABLE `user_verification_request` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-07-29 12:55:53
