-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 25 أكتوبر 2026 الساعة 17:44
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `al-ahsa vision hub`
--

-- --------------------------------------------------------

--
-- بنية الجدول `accessibility_options`
--

CREATE TABLE `accessibility_options` (
  `Accessibility_ID` int(11) NOT NULL,
  `Accessibility_Name` varchar(250) NOT NULL,
  `Image` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `business_owners`
--

CREATE TABLE `business_owners` (
  `Business_Owner_ID` int(11) NOT NULL,
  `Email` varchar(250) NOT NULL,
  `Password` varchar(250) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `favorites`
--

CREATE TABLE `favorites` (
  `Tourist_ID` int(11) NOT NULL,
  `Place_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `itinerary`
--

CREATE TABLE `itinerary` (
  `itinerary_id` int(11) NOT NULL,
  `tourist_Id` int(11) NOT NULL,
  `Survey_id` int(11) NOT NULL,
  `Days` tinyint(4) NOT NULL,
  `Create_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `itinerary_items`
--

CREATE TABLE `itinerary_items` (
  `item_id` int(11) NOT NULL,
  `itinerary_id` int(11) NOT NULL,
  `Day_Number` tinyint(4) NOT NULL,
  `Place_ID` int(11) NOT NULL,
  `Start_Time` time NOT NULL,
  `End_Time` time NOT NULL,
  `Sequence_Number` tinyint(4) NOT NULL,
  `Reason_Chosen` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `itinerary_reviews`
--

CREATE TABLE `itinerary_reviews` (
  `Itinerary_Review_ID` int(11) NOT NULL,
  `Tourist_ID` int(11) NOT NULL,
  `Itinerary_ID` int(11) NOT NULL,
  `Rating` tinyint(4) NOT NULL,
  `Comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `places`
--

CREATE TABLE `places` (
  `Place_ID` int(11) NOT NULL,
  `Name` varchar(250) NOT NULL,
  `Place_Type` varchar(30) NOT NULL,
  `Description` text DEFAULT NULL,
  `Location` varchar(250) DEFAULT NULL,
  `Image` varchar(250) DEFAULT NULL,
  `Opening_Hours` varchar(250) DEFAULT NULL,
  `Website` varchar(250) DEFAULT NULL,
  `Business_Owner_ID` int(11) DEFAULT NULL,
  `Business_Size` varchar(20) DEFAULT NULL,
  `Is_Published` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `place_reviews`
--

CREATE TABLE `place_reviews` (
  `Place_Review_id` int(11) NOT NULL,
  `tourist_Id` int(11) NOT NULL,
  `Comment` text NOT NULL,
  `Rating` tinyint(4) NOT NULL,
  `Create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Place_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `seasonal_events`
--

CREATE TABLE `seasonal_events` (
  `Place_ID` int(11) NOT NULL,
  `Start_Date` date NOT NULL,
  `End_Date` date NOT NULL,
  `Ticket_URL` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `survey`
--

CREATE TABLE `survey` (
  `Survey_id` int(11) NOT NULL,
  `tourist_Id` int(11) NOT NULL,
  `Create_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `survey_answers`
--

CREATE TABLE `survey_answers` (
  `Answer_ID` int(11) NOT NULL,
  `Survey_ID` int(11) NOT NULL,
  `Question_ID` int(11) NOT NULL,
  `Option_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `survey_options`
--

CREATE TABLE `survey_options` (
  `Option_ID` int(11) NOT NULL,
  `Question_ID` int(11) NOT NULL,
  `Option_Text` varchar(500) NOT NULL,
  `Parent_Option_ID` int(11) DEFAULT NULL,
  `Display_Order` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `survey_questions`
--

CREATE TABLE `survey_questions` (
  `Question_ID` int(11) NOT NULL,
  `Question_Text` varchar(500) NOT NULL,
  `Question_Type` varchar(30) NOT NULL,
  `Is_Required` tinyint(1) NOT NULL DEFAULT 1,
  `Display_Order` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `system_reviews`
--

CREATE TABLE `system_reviews` (
  `System_Review_ID` int(11) NOT NULL,
  `Tourist_ID` int(11) NOT NULL,
  `Rating` tinyint(4) NOT NULL,
  `Comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `tourist`
--

CREATE TABLE `tourist` (
  `Tourist_id` int(11) NOT NULL,
  `Name` varchar(250) NOT NULL,
  `Email` varchar(250) NOT NULL,
  `Password` varchar(250) NOT NULL,
  `Create_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `tourist_accessibility`
--

CREATE TABLE `tourist_accessibility` (
  `Tourist_ID` int(11) NOT NULL,
  `Accessibility_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accessibility_options`
--
ALTER TABLE `accessibility_options`
  ADD PRIMARY KEY (`Accessibility_ID`);

--
-- Indexes for table `business_owners`
--
ALTER TABLE `business_owners`
  ADD PRIMARY KEY (`Business_Owner_ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`Tourist_ID`,`Place_ID`);

--
-- Indexes for table `itinerary`
--
ALTER TABLE `itinerary`
  ADD PRIMARY KEY (`itinerary_id`),
  ADD KEY `tourist_Id` (`tourist_Id`),
  ADD KEY `Survey_id` (`Survey_id`);

--
-- Indexes for table `itinerary_items`
--
ALTER TABLE `itinerary_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `itinerary_id` (`itinerary_id`,`Day_Number`,`Sequence_Number`),
  ADD KEY `Place_ID` (`Place_ID`);

--
-- Indexes for table `itinerary_reviews`
--
ALTER TABLE `itinerary_reviews`
  ADD PRIMARY KEY (`Itinerary_Review_ID`),
  ADD UNIQUE KEY `Tourist_ID` (`Tourist_ID`,`Itinerary_ID`),
  ADD KEY `Itinerary_ID` (`Itinerary_ID`);

--
-- Indexes for table `places`
--
ALTER TABLE `places`
  ADD PRIMARY KEY (`Place_ID`),
  ADD KEY `Business_Owner_ID` (`Business_Owner_ID`);

--
-- Indexes for table `place_reviews`
--
ALTER TABLE `place_reviews`
  ADD PRIMARY KEY (`Place_Review_id`),
  ADD KEY `tourist_Id` (`tourist_Id`),
  ADD KEY `Place_ID` (`Place_ID`);

--
-- Indexes for table `seasonal_events`
--
ALTER TABLE `seasonal_events`
  ADD PRIMARY KEY (`Place_ID`);

--
-- Indexes for table `survey`
--
ALTER TABLE `survey`
  ADD PRIMARY KEY (`Survey_id`),
  ADD KEY `tourist_Id` (`tourist_Id`);

--
-- Indexes for table `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD PRIMARY KEY (`Answer_ID`),
  ADD UNIQUE KEY `Survey_ID` (`Survey_ID`,`Question_ID`,`Option_ID`),
  ADD KEY `Question_ID` (`Question_ID`),
  ADD KEY `Option_ID` (`Option_ID`);

--
-- Indexes for table `survey_options`
--
ALTER TABLE `survey_options`
  ADD PRIMARY KEY (`Option_ID`),
  ADD KEY `Question_ID` (`Question_ID`),
  ADD KEY `Parent_Option_ID` (`Parent_Option_ID`);

--
-- Indexes for table `survey_questions`
--
ALTER TABLE `survey_questions`
  ADD PRIMARY KEY (`Question_ID`);

--
-- Indexes for table `system_reviews`
--
ALTER TABLE `system_reviews`
  ADD PRIMARY KEY (`System_Review_ID`),
  ADD UNIQUE KEY `Tourist_ID` (`Tourist_ID`);

--
-- Indexes for table `tourist`
--
ALTER TABLE `tourist`
  ADD PRIMARY KEY (`Tourist_id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `tourist_accessibility`
--
ALTER TABLE `tourist_accessibility`
  ADD PRIMARY KEY (`Tourist_ID`,`Accessibility_ID`),
  ADD KEY `Accessibility_ID` (`Accessibility_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accessibility_options`
--
ALTER TABLE `accessibility_options`
  MODIFY `Accessibility_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_owners`
--
ALTER TABLE `business_owners`
  MODIFY `Business_Owner_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `itinerary`
--
ALTER TABLE `itinerary`
  MODIFY `itinerary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `itinerary_items`
--
ALTER TABLE `itinerary_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `itinerary_reviews`
--
ALTER TABLE `itinerary_reviews`
  MODIFY `Itinerary_Review_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `places`
--
ALTER TABLE `places`
  MODIFY `Place_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `place_reviews`
--
ALTER TABLE `place_reviews`
  MODIFY `Place_Review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey`
--
ALTER TABLE `survey`
  MODIFY `Survey_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_answers`
--
ALTER TABLE `survey_answers`
  MODIFY `Answer_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_options`
--
ALTER TABLE `survey_options`
  MODIFY `Option_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_questions`
--
ALTER TABLE `survey_questions`
  MODIFY `Question_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_reviews`
--
ALTER TABLE `system_reviews`
  MODIFY `System_Review_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tourist`
--
ALTER TABLE `tourist`
  MODIFY `Tourist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `itinerary`
--
ALTER TABLE `itinerary`
  ADD CONSTRAINT `itinerary_ibfk_1` FOREIGN KEY (`tourist_Id`) REFERENCES `tourist` (`tourist_id`),
  ADD CONSTRAINT `itinerary_ibfk_2` FOREIGN KEY (`Survey_id`) REFERENCES `survey` (`Survey_id`);

--
-- قيود الجداول `itinerary_items`
--
ALTER TABLE `itinerary_items`
  ADD CONSTRAINT `itinerary_items_ibfk_1` FOREIGN KEY (`itinerary_id`) REFERENCES `itinerary` (`itinerary_id`),
  ADD CONSTRAINT `itinerary_items_ibfk_2` FOREIGN KEY (`Place_ID`) REFERENCES `places` (`Place_ID`);

--
-- قيود الجداول `itinerary_reviews`
--
ALTER TABLE `itinerary_reviews`
  ADD CONSTRAINT `itinerary_reviews_ibfk_1` FOREIGN KEY (`Tourist_ID`) REFERENCES `tourist` (`tourist_id`),
  ADD CONSTRAINT `itinerary_reviews_ibfk_2` FOREIGN KEY (`Itinerary_ID`) REFERENCES `itinerary` (`itinerary_id`);

--
-- قيود الجداول `places`
--
ALTER TABLE `places`
  ADD CONSTRAINT `places_ibfk_1` FOREIGN KEY (`Business_Owner_ID`) REFERENCES `business_owners` (`Business_Owner_ID`);

--
-- قيود الجداول `place_reviews`
--
ALTER TABLE `place_reviews`
  ADD CONSTRAINT `place_reviews_ibfk_1` FOREIGN KEY (`tourist_Id`) REFERENCES `tourist` (`tourist_id`),
  ADD CONSTRAINT `place_reviews_ibfk_2` FOREIGN KEY (`Place_ID`) REFERENCES `places` (`Place_ID`);

--
-- قيود الجداول `seasonal_events`
--
ALTER TABLE `seasonal_events`
  ADD CONSTRAINT `seasonal_events_ibfk_1` FOREIGN KEY (`Place_ID`) REFERENCES `places` (`Place_ID`);

--
-- قيود الجداول `survey`
--
ALTER TABLE `survey`
  ADD CONSTRAINT `survey_ibfk_1` FOREIGN KEY (`tourist_Id`) REFERENCES `tourist` (`tourist_id`);

--
-- قيود الجداول `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD CONSTRAINT `survey_answers_ibfk_1` FOREIGN KEY (`Survey_ID`) REFERENCES `survey` (`Survey_id`),
  ADD CONSTRAINT `survey_answers_ibfk_2` FOREIGN KEY (`Question_ID`) REFERENCES `survey_questions` (`Question_ID`),
  ADD CONSTRAINT `survey_answers_ibfk_3` FOREIGN KEY (`Option_ID`) REFERENCES `survey_options` (`Option_ID`);

--
-- قيود الجداول `survey_options`
--
ALTER TABLE `survey_options`
  ADD CONSTRAINT `survey_options_ibfk_1` FOREIGN KEY (`Question_ID`) REFERENCES `survey_questions` (`Question_ID`),
  ADD CONSTRAINT `survey_options_ibfk_2` FOREIGN KEY (`Parent_Option_ID`) REFERENCES `survey_options` (`Option_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- قيود الجداول `system_reviews`
--
ALTER TABLE `system_reviews`
  ADD CONSTRAINT `system_reviews_ibfk_1` FOREIGN KEY (`Tourist_ID`) REFERENCES `tourist` (`tourist_id`);

--
-- قيود الجداول `tourist_accessibility`
--
ALTER TABLE `tourist_accessibility`
  ADD CONSTRAINT `tourist_accessibility_ibfk_1` FOREIGN KEY (`Tourist_ID`) REFERENCES `tourist` (`tourist_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tourist_accessibility_ibfk_2` FOREIGN KEY (`Accessibility_ID`) REFERENCES `accessibility_options` (`Accessibility_ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
