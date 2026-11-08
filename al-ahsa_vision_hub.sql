-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 08 نوفمبر 2026 الساعة 16:31
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

--
-- إرجاع أو استيراد بيانات الجدول `accessibility_options`
--

INSERT INTO `accessibility_options` (`Accessibility_ID`, `Accessibility_Name`, `Image`) VALUES
(1, 'Wheelchair User', 'images/WheelchairUser.jpg'),
(2, 'Mobility Assistance', 'images/MobilityAssistance.jpg'),
(3, 'Hearing Assistance', 'images/HearingAssistance.jpg'),
(4, 'Visual Assistance', 'images/VisualAssistance.jpg'),
(5, 'Sensory Friendly', 'images/SensoryFriendly.jpg');

-- --------------------------------------------------------

--
-- بنية الجدول `admin`
--

CREATE TABLE `admin` (
  `Admin_ID` int(11) NOT NULL,
  `Name` varchar(250) NOT NULL,
  `Email` varchar(250) NOT NULL,
  `Password` varchar(250) NOT NULL,
  `Created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `admin`
--

INSERT INTO `admin` (`Admin_ID`, `Name`, `Email`, `Password`, `Created_at`) VALUES
(1, 'admin', 'admin@alhasavisionhub.com', '12345678', '2026-11-08 15:23:46');

-- --------------------------------------------------------

--
-- بنية الجدول `business_owners`
--

CREATE TABLE `business_owners` (
  `Business_Owner_ID` int(11) NOT NULL,
  `Email` varchar(250) NOT NULL,
  `Password` varchar(250) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Name` varchar(150) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL
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
  `Is_Published` tinyint(1) NOT NULL DEFAULT 0,
  `Short_Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `places`
--

INSERT INTO `places` (`Place_ID`, `Name`, `Place_Type`, `Description`, `Location`, `Image`, `Opening_Hours`, `Website`, `Business_Owner_ID`, `Business_Size`, `Is_Published`, `Short_Description`) VALUES
(1, 'Al-Mashqar Park', 'entertainment', 'Al Mushaqar Park is a family-friendly recreational destination in Al-Ahsa, featuring green spaces, peaceful natural surroundings, children\'s play areas, a traditional market, and Al Mushaqar Palace, inspired by the region\'s historical heritage. Accessibility services reportedly include designated parking spaces and accessible restrooms for people with disabilities.', 'https://maps.app.goo.gl/6jwGQf8QbaLDtyP86', 'images/Al-MashqarPark.jpg', '12:00 AM – 6:00 AM, 4:00 PM – 12:00 AM', NULL, NULL, NULL, 1, 'A family park with green spaces, seating areas, and games.'),
(2, 'Mount Qara', 'entertainment', 'Jabal Al-Qarah is one of Al-Ahsa’s most prominent natural and historical landmarks, known for its unique rock formations, caves, and distinctive pathways, offering visitors an experience that combines nature exploration with local heritage. Accessibility services include designated parking and accessible restrooms. The entrance and some facilities are accessible by wheelchair, although accessibility inside the caves and pathways has not been confirmed', 'https://maps.app.goo.gl/MF8TrFWUrHtf4BiMA', 'images/MountQara.jpg', '8:00 AM - 11:00 PM', NULL, NULL, NULL, 1, 'A mountain in Al-Ahsa famous for its cool caves.'),
(3, 'Ibrahim Palace', 'entertainment', 'Ibrahim Palace is one of Al-Ahsa’s most prominent historical landmarks, featuring a blend of Islamic and military architecture. It includes a distinctive domed mosque, towers, and historic courtyards that reflect the region’s cultural heritage.', 'https://maps.app.goo.gl/MD4UbJGXZxj7FgAg8', 'images/IbrahimPalace.jpg', '8:00 AM - 4:00 PM Closed on Friday', NULL, NULL, NULL, 1, 'A historic palace that served as a fortress and an administrative headquarters.'),
(4, 'Black Corner', 'restaurants', 'Black Corner is a burger restaurant offering a variety of beef and chicken burgers, loaded fries with signature sauces, and desserts such as churros. The restaurant provides dine-in, takeaway, and delivery services.', 'https://maps.app.goo.gl/Zbh81pd1mgczSTRE9', 'images/BlackCorner.jpg', '12:00 PM - 3:00 AM ', NULL, NULL, NULL, 1, 'A burger restaurant offering beef and chicken burgers along with a variety of .fast meals'),
(5, 'Shekhdar', 'restaurants', 'Shekhdar is a popular restaurant in Al-Ahsa offering a variety of Iraqi and Middle Eastern dishes, including grilled meats, kebabs, rice dishes, and appetizers. It provides indoor and outdoor seating, family seating, dine-in, takeaway, and delivery services. Published information also indicates wheelchair accessibility.', 'https://maps.app.goo.gl/QjaGhW6wAHLxmqM86', 'images/Shekhdar.jpg', '12:00 PM - 1:00 AM', NULL, NULL, NULL, 1, 'A family restaurant offering a variety of foods in a comfortable atmosphere.'),
(6, 'Pasta Gusto', 'restaurants', 'Pasta Gusto is an Italian restaurant in Al-Ahsa specializing in a variety of pasta dishes, including chicken and shrimp fettuccine, vegetable pasta, and truffle pasta, along with salads, appetizers, and beverages. The restaurant offers dine-in, takeaway, and delivery services.', 'https://maps.app.goo.gl/nViDhg9r8qxMY6rf9', 'images/Pastagusto.jpg', '11:30 AM - 2:30 PM', NULL, NULL, NULL, 1, 'A restaurant specializing in delicious pasta that caters to all tastes.'),
(7, 'Ocean', 'cafes', 'Ocean Speciality Coffee & Brunch is a café in Al-Ahsa specializing in coffee and light meals. It offers specialty coffee such as V60, hot and cold beverages, as well as breakfast and brunch options. The café provides indoor seating, dine-in, and takeaway services.', 'https://maps.app.goo.gl/hYQpZgYNdFawPtLdA', 'images/ocean.jpg', '6:00 AM - 12:30 AM ', NULL, NULL, NULL, 1, 'Famous for its signature breakfast plates, fresh croissants, and refreshing coffee brews.'),
(8, 'Berah Coffee House', 'cafes', 'BERAH Coffee House is a local café in Al-Ahsa, located in the Al-Qadisiyah district of Al-Mubarraz. It offers a café experience with coffee and various beverages. Detailed information about its menu, desserts, and disability-accessible facilities is not currently available.', 'https://maps.app.goo.gl/gHTkVy2dCpTKkcvo8', 'images/berah.jpg', '6:00 AM - 12:00 AM ', NULL, NULL, NULL, 1, 'A cozy spot with a warm architectural style, serving premium coffee and freshly baked pastries.'),
(9, 'Aknan', 'cafes', 'Aknan Café is a distinctive café in Al-Qarah, Al-Ahsa, located at the foot of Jabal Al-Qarah. It features outdoor seating with views of the mountain\'s rock formations and offers a variety of coffee, hot and cold beverages, and desserts.', 'https://maps.app.goo.gl/aWPhf2wKqqkvjtmh7', 'images/aknan.jpg', 'Open for 24 hours', NULL, NULL, NULL, 1, 'A heritage-themed café with a lovely atmosphere, situated in a location that showcases the beauty of Al-Ahsa.'),
(10, 'Al Ahsa Mall', 'markets', 'Al Ahsa Mall is one of the prominent shopping and entertainment destinations in Al Hofuf. It features a variety of local and international stores offering fashion, accessories, perfumes, and electronics, along with restaurants, cafés, Muvi Cinema, and children\'s entertainment areas. Accessibility services include designated parking spaces and wheelchair access within the mall.', 'https://maps.app.goo.gl/RRjMmdGGpaNSfV427', 'images/al-ahsa-mall.jpg', '9:30 AM - 11:00 PM ', NULL, NULL, NULL, 1, 'A modern shopping destination featuring a variety of stores, dining options, and entertainment.'),
(11, 'Al Othaim Mall', 'markets', 'Al Othaim Mall is one of the leading shopping and entertainment destinations in Al-Ahsa. It features a variety of local and international stores offering fashion, accessories, perfumes, and electronics, along with restaurants, cafés, children\'s entertainment areas, and a cinema. Accessibility services include designated parking, accessible entrances and restrooms, and wheelchair access throughout the mall.', 'https://maps.app.goo.gl/uUHy2u8KXgquarMaA', 'images/al-othaim-mall.jpg', '8:00 AM - 2:00 AM ', NULL, NULL, NULL, 1, 'A shopping destination offering retail stores, dining options, and entertainment for families.'),
(12, 'Al-Qaisariya Market', 'markets', 'Al-Qaisariya Market is one of the oldest and most famous traditional markets in Al-Ahsa, dating back to 1822. It features traditional walkways and more than 422 shops selling dates, spices, perfumes, fabrics, bishts, handicrafts, and local products.', 'https://maps.app.goo.gl/BHzoBxtRwjN5xfTj7', 'images/al-qaisariya.jpg', '8:00 AM – 11:30 AM, 4:00 PM – 11:00 PM \r\nFriday: 4:00 PM – 11:00 PM', NULL, NULL, NULL, 1, 'Discover the heritage of Al-Ahsa through traditional shops, local crafts, and cultural products.'),
(13, 'InterContinental Al Ahsa', 'hotels', 'InterContinental Al Ahsa is a luxurious five-star hotel featuring elegant design and a variety of facilities, including hotel rooms and suites, restaurants, cafés, indoor swimming pools, a fitness center, and a spa. Accessibility services include designated parking spaces, ramp-accessible entrances and pathways, accessible elevators, accessible restrooms and rooms, and wheelchair availability.', 'https://maps.app.goo.gl/VkAvLmiExBCcA7EQA', 'images/intercontinental.jpg', 'Working all day\r\nCheck-in: 3:00 PM.\r\nCheck-out: 12:00 PM.', NULL, NULL, NULL, 1, 'A luxurious five-star hotel offering elegant rooms, excellent hospitality, and a relaxing stay in Al-Ahsa.'),
(14, 'Braira Al Ahsa', 'hotels', 'Braira Al Ahsa is an upscale four-star hotel featuring modern design and comfortable rooms and suites. Its facilities include a restaurant, café, outdoor swimming pool, fitness center, and spa. Accessibility services include wheelchair access, free parking, and accessible restrooms equipped with grab bars and lowered sinks.', 'https://maps.app.goo.gl/Qns4nYisQina4MWs7', 'images/braira.jpg', 'Working all day\r\nCheck-in: 4:00 PM – 12:00 AM.\r\nCheck-out: 12:00 PM – 12:30 PM.', NULL, NULL, NULL, 1, 'A modern and elegant hotel featuring comfortable rooms and a welcoming atmosphere for visitors.'),
(15, 'Somewhere Hotel Al Ahsa', 'hotels', 'Somewhere Hotel Al Ahsa is a luxurious five-star hotel featuring modern design and a location connected to Al Rashid Town Square. It offers hotel rooms and suites, restaurants, cafés, swimming pools, a fitness center, a spa, a children\'s play area, and event halls. Accessibility services include designated parking and accessible facilities, with disability-friendly services listed in its booking information.', 'https://maps.app.goo.gl/Arfxca9DhcDuXbA36', 'images/somewhere.jpg', 'Working all day\r\nCheck-in: 2:00 PM – 12:00 AM.\r\nCheck-out: Until 12:00 PM.', NULL, NULL, NULL, 1, 'A stylish hotel with contemporary interiors and comfortable accommodation for a pleasant stay in Al-Ahsa.'),
(16, 'Al Koot Heritage Hotel', 'hotels', 'Al Koot Heritage Hotel is a distinctive heritage hotel in Al-Ahsa, located in a restored historic building that reflects traditional Hasawi architecture, opposite the historic Ibrahim Palace. It features hotel suites, a restaurant serving traditional Hasawi dishes such as Hasawi rice, harees, and marqooq, as well as antique collections and a heritage library. Accessibility facilities include adapted entrances and safe pathways that support wheelchair users.', 'https://maps.app.goo.gl/nDuQq49tifPAu7Df9', 'images/alkoothotel.jpg', 'Working all day\r\nCheck-in: 3:00 PM.\r\nCheck-out: 1:00 PM.', NULL, NULL, NULL, 1, 'A hotel with a traditional Al-Ahsa-inspired aesthetic, offering a comfortable and distinctive stay.'),
(17, 'Al Ahsa Grand Hotel & Suites', 'hotels', 'Al Ahsa Grand Hotel & Suites is a distinctive hotel in Al Hofuf, offering spacious studios and hotel suites designed for guest comfort, with kitchenettes in some units. Its facilities include an indoor swimming pool, sauna, jacuzzi, Wi-Fi, and parking. Wheelchair accessibility and suitable parking facilities are also available.', 'https://maps.app.goo.gl/hvFem7kyquDgG4YX9', 'images/grandhotel.jpg', 'Working all day\r\nCheck-in: 3:00 PM.\r\nCheck-out: 12:00 PM.', NULL, NULL, NULL, 1, 'An exceptional hotel in Al-Ahsa, offering wonderful and comfortable amenities.'),
(18, 'Al Amer Mall', 'markets', 'Al Amer Mall is a shopping and entertainment destination located in Al Mubarraz, Al Ahsa. It features a variety of retail stores, restaurants, cafes, and family entertainment facilities. Visitors can also explore Al Amer Hypermarket and enjoy dedicated children\'s entertainment areas, including Smarvy Spot and Kids Island. The mall provides free parking and guest assistance services. Accessibility assistance is available upon request.', 'https://maps.app.goo.gl/TLuHQ9Zg4xYkEUj2A', 'images/Al-Amer-Mall.jpg', 'Saturday – Thursday	6:00 AM – 2:30 AM\r\nFriday	9:00–10:30 AM , 1:00 PM – 2:30 AM', NULL, NULL, NULL, 1, 'A family-friendly shopping and entertainment destination offering retail stores, dining, and activities.'),
(19, 'Al Ahsa Craftsmen Souq', 'markets', 'Al Ahsa Craftsmen Souq is a traditional heritage market located in the historic city of Al Hofuf, showcasing the region\'s rich handicraft traditions and cultural identity. It features shops offering handmade products, including pottery, palm-leaf weaving, woodwork, traditional crafts, and local heritage items. The market also includes a traditional courtyard, a popular café, a traditional bakery, and spaces for cultural events and festivals. It houses a handicrafts academy that provides training and helps preserve traditional skills for future generations. Parking for people with disabilities is listed among its accessibility facilities.', 'https://maps.app.goo.gl/46kXg4WmDoaL79wn6', 'images/Al-Ahsa-Craftsmen-Souq.jpg', '7:30 AM – 12:00 AM\r\nFriday	2:30 PM – 12:00 AM', '', NULL, NULL, 1, 'A traditional market showcasing Al Ahsa\'s handmade crafts and cultural heritage.'),
(20, 'Al Qariah Al Shaabiah', 'markets', 'Al Qariah Al Shaabiah is a traditional-style shopping and entertainment destination located in Al Mubarraz, Al Ahsa. It features various shops selling clothing, accessories, perfumes, household items, and traditional products. Visitors can also enjoy restaurants, cafes, family seating areas, and children\'s entertainment facilities.', 'https://maps.app.goo.gl/nUVkVsUdsjVwWnUc9', 'images/Al-Qariah-Al-Shaabiah.jpg', '8:00 AM - 11:30 PM ', NULL, NULL, NULL, 1, 'A traditional-style shopping destination featuring various stores, restaurants, and family entertainment.'),
(21, 'Al Ahsa Gold Market', 'markets', 'Al Ahsa Gold Market is a traditional shopping destination located in Al Hofuf, near Al Qaisariya Market. It features numerous jewelry shops specializing in gold, silver, diamonds, and traditional jewelry designs. Visitors can explore different collections, compare prices, and purchase jewelry for various occasions. The market also has designated parking for people with disabilities.', 'https://maps.app.goo.gl/C9bKFtobDpxiXCB36', 'images/Al-Ahsa-Gold-Market.jpg', '9:00 AM – 12:00 PM\r\nFriday	4:00 PM – 10:00 PM', NULL, NULL, NULL, 1, 'A traditional market featuring gold, silver, diamonds, and jewelry shops.'),
(22, 'Saaf Walk', 'entertainment', 'Saaf Walk is a modern lifestyle and commercial destination located in Al Hofuf, Al Ahsa. It features a variety of restaurants, cafes, retail stores, and entertainment spaces in an open-air pedestrian-friendly environment. The development also includes green spaces, outdoor seating, and family-friendly facilities.', 'https://maps.app.goo.gl/ygJ3MmLcz9KRAJ6w8', 'images/Saaf-Walk.jpg', NULL, NULL, NULL, NULL, 1, 'A modern outdoor destination featuring restaurants, cafes, shops, and green spaces.'),
(23, 'The Walk – Al Sifah', 'entertainment', 'The Walk is a shopping and dining destination located in Al Sifah, Al Hofuf. It features various restaurants, cafes, and retail shops, offering visitors a place to enjoy food and shopping. The destination provides parking facilities, including designated parking for people with disabilities.', 'https://maps.app.goo.gl/E9h5o3BJrEZzHYE17', 'images/The-Walk-Al-Sifah.jpg', NULL, NULL, NULL, NULL, 1, 'A shopping and dining destination featuring various restaurants, cafes, and stores'),
(24, 'The Walk – Al Waha', 'entertainment', 'The Walk Mall is a modern shopping and entertainment destination located in Al Waha District, Al Hofuf. It features a variety of retail stores, restaurants, cafes, and entertainment facilities for visitors and families. The mall offers a convenient environment for shopping, dining, and leisure activities.', 'https://maps.app.goo.gl/2i4rWPDhrwz7L47W7', 'images/The-Walk-Mall-Al-Waha.jpg', NULL, NULL, NULL, NULL, 1, 'A modern shopping and entertainment destination with stores, restaurants, and cafes'),
(25, 'King Abdullah Environmental Park', 'entertainment', 'King Abdullah Environmental Park is one of the largest public parks in the Eastern Province, located south of Al Hofuf. It features beautiful green spaces, a large artificial lake, a spectacular dancing fountain, walking paths, and children\'s playgrounds. The park also offers sports facilities, restaurants, and a heritage village showcasing traditional Al Ahsa culture. Accessibility services for people with disabilities are reported among the park\'s facilities.', 'https://maps.app.goo.gl/9ZT4CSTsDo4MDRUK9', 'images/King-Abdullah-Environmental-Park.jpg', 'Saturday – Wednesday	4:00 PM – 11:00 PM\r\nThursday – Friday	4:00 PM – 12:00 AM', NULL, NULL, NULL, 1, 'A large family park featuring green spaces, a lake, fountains, and recreational activities.'),
(26, 'Jawatha Tourist City', 'entertainment', 'Jawatha Tourist City is a large family entertainment destination located in Al-Kilabiyah, northeast of Al Hofuf. It features a beautiful lake, green spaces, amusement rides, children\'s playgrounds, a small zoo, restaurants, and cafes. Visitors can also enjoy boat rides and various outdoor recreational activities. The park provides parking facilities, including designated parking for people with disabilities.', 'https://maps.app.goo.gl/k8KCBq4uBRjJGqUy5', 'images/Jawatha-Tourist-City.jpg', 'Saturday – Wednesday	4:00 PM – 12:00 AM\r\nThursday – Friday	4:00 PM – 1:00 AM', NULL, NULL, NULL, 1, 'A family entertainment destination featuring a lake, amusement rides, green spaces, and recreational activities.'),
(27, 'Al-Asfar Lake', 'entertainment', 'Al-Asfar Lake, also known as Yellow Lake, is a natural wetland located northeast of Al-Ahsa Oasis, near Al-Umran. It is surrounded by golden sand dunes and desert vegetation, creating a unique natural landscape. The lake attracts migratory birds and is a popular destination for nature photography, birdwatching, and outdoor picnics. It is part of the Al-Ahsa Oasis UNESCO World Heritage Site.', 'https://maps.app.goo.gl/bt1dNZFZDUyvXTTV6', 'images/Al-Asfar-Lake.jpg', NULL, NULL, NULL, NULL, 1, 'A scenic desert lake surrounded by golden dunes and known for its migratory birds.'),
(28, 'Jabal Al-Arba', 'entertainment', 'Jabal Al-Arba is a natural landmark located southeast of Al Hofuf, near Al-Taraf village. It consists of four distinctive reddish rocky hills, three close together and one separated from the others. The area is popular for camping, hiking, horseback riding, desert trips, and enjoying scenic views, especially during sunset.', 'https://maps.app.goo.gl/EkL4QdaGM4tgNnZt9', 'images/Jabal-Al-Arba.jpg', NULL, NULL, NULL, NULL, 1, 'A natural landmark featuring four rocky hills, popular for camping and outdoor adventures.'),
(29, 'Futureland', 'entertainment', 'Futureland is an educational entertainment city for children located on the second floor of Saaf Walk in Al Ahsa. It offers more than 22 interactive activities where children can experience real-life professions such as doctors, pilots, firefighters, and police officers. The center combines learning and entertainment in a safe environment designed for children aged 3–14. It also offers special ticket packages for visitors with disabilities.', 'https://maps.app.goo.gl/tvtxTfPBc3aJwWSg6', 'images/Futureland.jpg', '3:00 PM – 11:00 PM', 'https://www.futurelandsa.com/ar-sa', NULL, NULL, 1, 'An educational entertainment city where children explore real-life professions through interactive activities.'),
(30, 'Trampo Zone', 'entertainment', 'Trampo Zone is an indoor entertainment center for children located in Al Hofuf, Al Ahsa. It offers various physical activities, including trampolines, climbing, sliding, and interactive play challenges. The center provides a fun environment for children aged 2–13 and is suitable for family outings and group activities.', 'https://maps.app.goo.gl/4wTpYKkfJHV8BNtbA', 'images/Trampo-Zone.jpg', '3:30 PM – 12:00 AM', 'https://trampozone.order.sa/', NULL, NULL, 1, 'An indoor children\'s entertainment center featuring trampolines, climbing, and interactive activities.'),
(31, 'Gravity Ahsa', 'entertainment', 'Gravity is an indoor family entertainment center located on Ain Najem Road in Al Mubarraz, Al Ahsa. It offers a variety of activities, including trampolines, climbing walls, zipline, soft play areas, and duckpin bowling. The center is suitable for children, teenagers, and families, and also hosts birthday parties and group events.', 'https://maps.app.goo.gl/Krf6Q1Gq4bAF592d6', 'images/Gravity-Ahsa.jpg', 'Sunday – Wednesday	12:00 PM – 12:00 AM\r\nThursday	12:00 PM – 1:00 AM\r\nFriday	9:00 AM – 1:00 AM\r\nSaturday	9:00 AM – 12:00 AM', 'https://gravitysaudi.com/', NULL, NULL, 1, 'An indoor adventure center featuring trampolines, climbing walls, zipline, and bowling.'),
(32, 'Dar Saad Equestrian Center', 'entertainment', 'Dar Saad Equestrian Center is a horse-riding and training destination located in Al Waziyah, Al Ahsa. It offers horse-riding lessons for children, beginners, and experienced riders under professional supervision. The center also provides horse-care training, horse boarding, archery activities, and special events. It is a destination for visitors interested in equestrian sports and outdoor experiences.', 'https://maps.app.goo.gl/oCcVB5MVMFtFMN3SA', 'images/Dar-Saad-Equestrian-Center.jpg', 'Monday - Saturday \r\n8:00 AM , 11:00 AM - 5:00 PM , 11;00 PM', 'https://daarsaad.com/', NULL, NULL, 1, 'An equestrian center offering horse-riding lessons, horse care training, and archery activities.'),
(33, 'Padelx', 'entertainment', 'Padelx is a padel sports club located in Al Salmaniyah, Al Hofuf. It offers padel court rentals for individuals and groups, providing a space for friendly matches and sports activities. The club also has a private court for women.', 'https://maps.app.goo.gl/iip8wvzuc9MnJC6g9', 'images/Padelx.jpg', '4:00 PM – 2:00 AM.', NULL, NULL, NULL, 1, 'A padel club offering court rentals for sports and recreational matches.'),
(34, 'Bowling Path', 'entertainment', 'Bowling Path is a bowling alley located on King Abdullah Road in South Al Hofuf, Al Ahsa. It offers bowling activities in a casual setting, making it a suitable destination for friends and groups looking for fun and friendly competition.', 'https://maps.app.goo.gl/5DZT71b68ijSpdfR7', 'images/Bowling-Path.jpg', '5:30 PM – 3:30 AM.', NULL, NULL, NULL, 1, 'A bowling alley offering fun games and friendly competitions'),
(35, 'MOC Restaurant', 'restaurants', 'MOC is an upscale international restaurant located in Al Mazroua district, Al Hofuf. It serves a variety of dishes inspired by different cuisines, including Italian food, pasta, pizza, and burgers. The restaurant offers a stylish dining atmosphere with music and special entertainment.', 'https://maps.app.goo.gl/kKcp9QbCqWQAv5Sq6', 'images/MOC-Restaurant.jpg', 'Saturday – Wednesday: 12:00 PM – 1:00 AM\r\nThursday and Friday: 12:00 PM – 1:30 AM', 'https://moc.sa/', NULL, NULL, 1, 'An upscale international restaurant serving Italian and other global dishes in a stylish atmosphere'),
(36, 'LIEVITO', 'restaurants', 'LIEVITO is an Italian restaurant located on King Abdullah Road in Al Hofuf, Al Ahsa. It offers a variety of Italian dishes, including freshly prepared pasta, pizza, lasagna, risotto, and appetizers. The restaurant features a modern and elegant atmosphere, making it suitable for dining with family and friends.', 'https://maps.app.goo.gl/jmGjicUa8GbadXit6', 'images/Lievito.jpg', 'Saturday – Wednesday: 1:00 PM – 12:00 AM\r\nThursday and Friday: 1:00 PM – 2:00 AM', 'https://lievito.sa/', NULL, NULL, 1, 'An Italian restaurant serving pasta, pizza, and other classic Italian dishes'),
(37, 'Feed Restaurant', 'restaurants', 'Feed is an Italian-inspired restaurant located in Al Hamra district, Al Hofuf. It offers a variety of dishes, including Italian-style pizza, pasta, salads, and other meals. The restaurant is known for its cozy atmosphere and is suitable for dining with family and friends. It is listed as wheelchair accessible.', 'https://maps.app.goo.gl/HQvFyqusXF8cm9Ue8', 'images/Feed-Restaurant.jpg', 'Saturday – Wednesday: 6:00 PM – 1:00 AM\r\nThursday and Friday: 6:00 PM – 2:00 AM', NULL, NULL, NULL, 1, 'An Italian-inspired restaurant serving pizza, pasta, and salads in a cozy atmosphere'),
(38, 'Dar Basma', 'restaurants', 'Dar Basma is a restaurant located in the historic center of Al Hofuf, near Ibrahim Palace. It is housed in a traditional building over 100 years old and offers a modern interpretation of authentic Hasawi cuisine. Its menu includes traditional local dishes, grilled meals, seafood, salads, and desserts. The restaurant offers wheelchair-accessible facilities.', 'https://maps.app.goo.gl/epkEUtE1P2kvMoAY8', 'images/Dar-Basma.jpg', '1:00 PM – 12:00 AM', NULL, NULL, NULL, 1, 'A heritage restaurant serving authentic Hasawi cuisine with a modern touch'),
(39, 'Al-Koot Heritage Restaurant', 'restaurants', 'Al-Koot Heritage Restaurant is located in the historic Al-Koot district of Al Hofuf, directly opposite Ibrahim Palace. It is part of a restored heritage building featuring traditional Hasawi architecture. The restaurant serves local dishes such as Hasawi rice, harees, marqooq, and mufallaq, offering visitors an authentic taste of Al-Ahsa\'s culinary heritage. ', 'https://maps.app.goo.gl/hzHBjmA1zBmTEzmU8', 'images/Al-Koot-Heritage-Restaurant.jpg', '7:00 AM – 12:00 AM.', NULL, NULL, NULL, 1, 'A heritage restaurant serving traditional Hasawi dishes in a historic setting'),
(40, 'Mandi Hassa', 'restaurants', 'Mandi Hassa is a traditional rice restaurant located in Al Omairiyah, Al Hofuf. It specializes in Saudi-style mandi, offering rice dishes served with chicken and meat. The restaurant provides takeaway and delivery services, making it a convenient option for enjoying traditional mandi meals.', 'https://maps.app.goo.gl/JH25hy6xVB46kK4q9', 'images/Mandi-Hassa.jpg', '12:00 PM – 11:00 PM.', NULL, NULL, NULL, 1, 'A traditional restaurant specializing in chicken and meat mandi dishes'),
(41, 'Al Rabee Hasawi Bakery', 'restaurants', 'Al Rabee Hasawi Bakery is a local bakery located in Al Zahrah district, Al Hofuf. It specializes in traditional Hasawi red bread, also known as date bread, and offers freshly baked goods with different fillings, including cheese. The bakery provides takeaway services and is a place to experience a traditional part of Al-Ahsa\'s food culture.', 'https://maps.app.goo.gl/TcJU9yVecyhKBjjp6', 'images/Al-Rabee-Hasawi-Bakery.jpg', 'Saturday–Thursday: 4:30 AM – 10:00 AM, then 3:30 PM – 11:00 PM.\r\nFriday: 3:30 PM – 11:00 PM.', NULL, NULL, NULL, 1, 'A traditional bakery specializing in Hasawi red date bread and freshly baked goods'),
(42, 'Craving', 'restaurants', 'Craving is a fast-casual restaurant located on Al Khalij Road in Al Hofuf, Al Ahsa. It specializes in crispy chicken burgers, chicken strips, loaded fries, and chicken wings, with a variety of signature sauces such as buffalo, ranch, and honey mustard. The restaurant also offers seafood options, including crispy shrimp. It provides takeaway and delivery services, and is listed as wheelchair accessible.', 'https://maps.app.goo.gl/YeyvMxpGrwCk5CtJ8', 'images/Craving.jpg', 'Sunday: 12:00 PM – 3:00 AM\r\nMonday: 12:00 PM – 12:00 AM\r\nTuesday – Saturday: 12:00 PM – 3:00 AM', NULL, NULL, NULL, 1, 'A fast-casual restaurant specializing in crispy chicken burgers, strips, and loaded fries'),
(43, 'Shamyar Restaurant', 'restaurants', 'Shamyar is an upscale restaurant located in Al Aziziyah Al Thani, Al Hofuf. It offers a diverse menu combining Levantine, Armenian, and international cuisine, including grilled meats, kebabs, Indian dishes, seafood, pasta, and fresh pastries. The restaurant features elegant interiors and comfortable family seating, making it suitable for family gatherings and dining with friends.', 'https://maps.app.goo.gl/TpVh37yW5qx6dWww5', 'images/Shamyar-Restaurant.jpg', '5:00 AM – 11:45 AM\r\n12:00 PM – 2:00 AM', NULL, NULL, NULL, 1, 'An upscale restaurant serving a variety of Levantine, Armenian, and international dishes'),
(44, 'PAN UP', 'restaurants', 'PAN UP is a fast-casual restaurant located on King Abdullah Road in Al Naseem district, Al Hofuf. It specializes in grilled and crispy chicken burgers, offering a selection of chicken sandwiches and sides. The restaurant provides a casual dining experience with a focus on chicken-based fast food.', 'https://maps.app.goo.gl/xmyijrT9WcM1XBad9', 'images/Pan-Up.jpg', 'Saturday – Wednesday: 12:00 PM – 1:00 AM\r\nThursday and Friday: 12:00 PM – 2:00 AM', NULL, NULL, NULL, 1, 'A fast-food restaurant specializing in grilled and crispy chicken burgers'),
(45, 'LOCO Restaurant', 'restaurants', 'LOCO is a burger restaurant located on King Abdullah Road in Al Mazrooa district, Al Hofuf. It specializes in freshly prepared burgers, particularly juicy beef burgers, along with chicken options, fries, and a variety of sauces. The restaurant offers a modern and casual dining atmosphere, making it suitable for burger lovers and groups of friends.', 'https://maps.app.goo.gl/xg7Yf9tucFhtiGLeA', 'images/LOCO-Restaurant.jpg', 'Saturday – Wednesday: 4:00 PM – 12:00 AM\r\nThursday and Friday: 4:00 PM – 1:00 AM', NULL, NULL, NULL, 1, 'A modern burger restaurant specializing in juicy beef burgers and chicken sandwiches'),
(46, 'Smatt Hasawi', 'restaurants', 'Smatt Hasawi is a traditional Saudi restaurant For lunch and breakfast located in Al Mubarraz, Al Ahsa. It specializes in authentic Hasawi cuisine, offering traditional rice and meat dishes inspired by the region\'s culinary heritage. And variety of traditional breakfast dishes. The restaurant provides a local dining experience that highlights the flavors and food traditions of Al Ahsa.', 'https://maps.app.goo.gl/Gco9VnzoSWpY2LHSA', 'images/Smatt-Hasawi.jpg', '1:00 PM – 8:00 PM.', NULL, NULL, NULL, 1, 'A traditional Saudi restaurant specializing in authentic Hasawi rice and meat dishes'),
(47, 'Al Huwai Restaurant', 'restaurants', 'Al Huwai is a traditional breakfast restaurant in Al Hofuf, Al-Ahsa, serving popular Saudi breakfast dishes. Its menu includes foul, falafel, shakshuka, liver, hummus, and fresh bread. The restaurant offers a traditional atmosphere and is located opposite King Faisal University.', 'https://maps.app.goo.gl/GqWozqePQH9TSdh3A', 'images/Al-Huwai.jpg', 'Saturday – Thursday: 4:30 AM – 11:30 AM.\r\nFriday: 4:30 AM – 10:30 AM.', 'https://linktr.ee/al7wwy', NULL, NULL, 1, 'A traditional breakfast restaurant serving popular Saudi dishes'),
(48, 'Evoke Restaurant', 'restaurants', 'Evoke is a modern restaurant in Al Hofuf, Al-Ahsa, known for its breakfast and brunch menu. It offers a variety of dishes, including Eggs Benedict, omelettes, French toast, pancakes, and specialty coffee. The restaurant features elegant décor and a comfortable dining atmosphere.', 'https://maps.app.goo.gl/JWoM5RQxsd5tRiut5', 'images/Evoke.jpg', 'Saturday to Thursday: 6:00 AM – 2:30 PM.\r\nFriday: 6:00 AM – 11:30 AM.', NULL, NULL, NULL, 1, 'A modern restaurant serving breakfast, brunch, and specialty coffee.'),
(49, 'Dot Bakery & Cafe', 'restaurants', 'Dot is a modern bakery, café, and breakfast spot in Al-Ahsa. It serves a variety of breakfast dishes, including eggs, pancakes, kibdah, and falafel, alongside freshly baked croissants, pastries, sourdough bread, cakes, and specialty coffee. It offers a welcoming place to enjoy breakfast, coffee, and freshly prepared baked goods.', 'https://maps.app.goo.gl/THRDnAocvxCZNdVX7', 'images/Dot.jpg', 'Saturday–Wednesday: 5:00 AM – 11:45 PM.\r\nThursday: 5:00 AM – 2:00 AM.\r\nFriday: 5:00 AM – 12:00 midnight.', 'https://www.dotbakerycafe.com/', NULL, NULL, 1, 'A bakery, café, and breakfast spot serving fresh pastries and specialty coffee.'),
(50, 'Nabil Restaurant', 'restaurants', 'Nabil is a casual fast-food restaurant in Al Hofuf, Al-Ahsa, specializing in chicken shawarma and other chicken-based meals. Its menu includes shawarma, fries, and samosas. It is a popular spot for quick evening meals, takeaway, and delivery.', 'https://maps.app.goo.gl/NXN29Z38Qx7KqC7m8', 'images/Nabil-Restaurant.jpg', '5:00 PM – 2:00 AM', NULL, NULL, NULL, 1, 'A fast-food restaurant specializing in chicken shawarma and quick meals'),
(51, 'Sinyar Seafood Restaurant', 'restaurants', 'Sinyar is a seafood restaurant in Al-Ahsa, located on Al-Khaleej Road in the Al-Uwaimriyah area of Hofuf. It offers a variety of seafood dishes, including fish, shrimp, seafood soups, and grilled and fried options. The restaurant features a modern design inspired by traditional local architecture.', 'https://maps.app.goo.gl/t7yHAnUsNvHpfygo8', 'images/Sinyar.jpg', 'Saturday to Wednesday: 12:00 PM – 12:00 AM.\r\nThursday and Friday: 12:00 PM – 12:30 AM.', 'https://sinyar.easymenu.site/', NULL, NULL, 1, 'A seafood restaurant serving fresh fish, shrimp, and a variety of seafood dishes.'),
(52, 'Zaatar & Sesame', 'restaurants', 'Zaatar & Sesame is a bakery and casual dining spot in Al Hofuf, Al-Ahsa. It offers a selection of freshly prepared pastries, pies, and pizza, with options suitable for breakfast and evening meals. The place also provides takeaway and delivery services.', 'https://maps.app.goo.gl/2H9kF2JfQVyZVzuk8', 'images/Zaatar-And-Sesame.jpg', 'Saturday to Wednesday: 6:00–11:00 AM and 4:00–11:30 PM.\r\nThursday: 6:00–11:00 AM and 4:00 PM–12:00 AM.\r\nFriday: 3:00 PM–12:00 AM.', NULL, NULL, NULL, 1, 'A bakery serving fresh pastries, pies, and pizza for breakfast and dinner.'),
(53, 'Ashaaz Indian Restaurant', 'restaurants', 'Ashaz is a spacious Indian restaurant in Al Hofuf, Al-Ahsa, offering a wide selection of traditional Indian and Asian dishes. Its menu includes biryani, curries, tandoori grilled dishes, kebabs, appetizers, and desserts. The restaurant provides a comfortable dining atmosphere for families and groups, as well as takeaway and delivery services', 'https://maps.app.goo.gl/bF9QDk2g4ZuDMqo69', 'images/Ashaaz.jpg', '12:00 PM – 12:30 AM', NULL, NULL, NULL, 1, 'An Indian restaurant serving biryani, curries, tandoori dishes, and more.'),
(54, 'Code Restaurant & Cafe', 'restaurants', 'Code is a modern restaurant and café in Al Hofuf, Al-Ahsa, offering a variety of international dishes in a contemporary setting. Its menu includes main courses, appetizers, salads, and desserts, making it suitable for casual dining with family and friends.', 'https://maps.app.goo.gl/RLqouwc1evYjbF7h7', 'images/Code.jpg', '6:00 PM – 1:00 AM', NULL, NULL, NULL, 1, 'A modern restaurant and café serving a variety of international dishes.'),
(55, 'MOZZARO', 'restaurants', 'MOZZARO is an Italian restaurant in Al Hofuf, Al-Ahsa, specializing in pizza and pasta. Its menu features a variety of pizzas, including Margherita and Pepperoni, as well as pasta dishes such as Rigatoni Pink and Truffle. It also serves focaccia sandwiches and side dishes in a modern dining setting.', 'https://maps.app.goo.gl/WiwjyGhFMMiRzbFH7', 'images/Mozzaro.jpg', 'Saturday: 8:30 PM – 12:00 AM.\r\nMonday – Wednesday: 8:30 PM – 12:00 AM.\r\nThursday and Friday: 8:30 PM – 2:00 AM.\r\nClosed on Sunday.', NULL, NULL, NULL, 1, 'An Italian restaurant serving pizza, pasta, and focaccia sandwiches.'),
(56, 'Yummy Fork', 'restaurants', 'Yummy Fork is a modern Asian and Italian fusion restaurant in Al Hofuf, Al-Ahsa. Its menu offers a variety of dishes, including sushi, noodles, ramen, dumplings, rice dishes, and pasta. It also serves appetizers, salads, desserts, and refreshing drinks, making it a suitable choice for lunch and dinner.', 'https://maps.app.goo.gl/5C3G5P8e7Z6ZHZgC9', 'images/Yummy-Fork.jpg', '1:00 PM – 1:00 AM', 'https://linktr.ee/yummyfork', NULL, NULL, 1, 'An Asian and Italian fusion restaurant serving sushi, noodles, and pasta.'),
(57, 'Moment Café', 'cafes', 'Moment is a modern specialty coffee shop located in Al Jameyeen, Al Hofuf, Al-Ahsa. It serves a variety of hot and cold coffee drinks, including espresso-based beverages and iced V60, alongside tea, desserts, and freshly baked treats. The café offers a stylish and comfortable atmosphere, with indoor and outdoor seating.', 'https://maps.app.goo.gl/RiH5UsdeiDE3S1b67', 'images/Moment-Cafe.jpg', 'Sunday – Wednesday: 7:00 AM – 1:00 AM.\r\nThursday: 7:00 AM – 2:00 AM.\r\nFriday and Saturday: 1:00 PM – 2:00 AM.', NULL, NULL, NULL, 1, 'A specialty coffee shop serving hot and cold drinks, tea, desserts, and baked treats.'),
(58, '7st. Specialty Coffee', 'cafes', '7st. Specialty Coffee is a specialty coffee shop in Al-Ahsa, with a branch in Al-Rifaa North, Al Hofuf, near the historic Al-Koot district and Ibrahim Palace. It serves a variety of specialty coffee drinks and desserts, offering a cozy atmosphere for visitors to enjoy coffee and relax.', 'https://maps.app.goo.gl/gHXKUwpU8ajJLjcz8', 'images/7st-Specialty-Coffee.jpg', '7:00 PM – 2:30 AM', NULL, NULL, NULL, 1, 'A specialty coffee shop serving coffee and desserts in a cozy atmosphere'),
(59, 'Ratio Al-Koot', 'cafes', 'Ratio Al-Koot is a specialty coffee café located inside Al-Qaisariya Souq in the historic center of Al Hofuf, Al-Ahsa. It combines specialty coffee with traditional Hasawi heritage through its architectural design, inspired by Al-Koot Fort and decorated with local patterns. The café also serves traditional Hasawi dishes and sweets, alongside hot and cold drinks, making it a distinctive cultural and dining experience.', 'https://maps.app.goo.gl/CJxgz5hUYiBBAB869', 'images/Ratio-Al-Koot.jpg', 'Saturday – Wednesday: 3:00 PM – 1:00 AM.\r\nThursday: 3:00 PM – 2:00 AM.\r\nFriday: 1:00 PM – 2:00 AM.', NULL, NULL, NULL, 1, 'A heritage-inspired specialty coffee café serving Hasawi dishes and traditional sweets.'),
(60, 'Bethanah Café', 'cafes', 'Bethanah is a heritage-inspired café located in the historic Al-Rifaa North neighborhood of Al Hofuf, Al-Ahsa. It is set inside a traditional-style house, creating an atmosphere that reflects the character of old Hofuf. The café serves specialty coffee, tea, and traditional breakfast dishes, with indoor and outdoor seating.', 'https://maps.app.goo.gl/mvFprFH2GU59KDet6', 'images/Bethanah-Cafe.jpg', 'Sunday – Wednesday: 6:00 AM – 1:00 PM, 3:00 PM – 1:00 AM.\r\nThursday and Saturday: 6:00 AM – 1:00 PM, 3:00 PM – 2:00 AM.\r\nFriday: 2:00 PM – 2:00 AM.', NULL, NULL, NULL, 1, 'A heritage-style café serving specialty coffee, tea, and traditional breakfast.'),
(61, 'Karito Bakehouse', 'cafes', 'Karito is a French-inspired bakery and café in Al-Mubarraz, Al-Ahsa. It specializes in freshly baked French pastries, croissants, premium desserts, and specialty beverages. Its menu includes cakes, cheesecakes, and a variety of hot and cold coffee drinks, offering a pleasant spot for breakfast, brunch, and coffee breaks.', 'https://maps.app.goo.gl/EcjrdY2FNkfzEZdE8', 'images/Karito-Bakehouse.jpg', 'Saturday–Wednesday: 5:00 AM – 1:00 AM.\r\nThursday: 5:00 AM – 2:00 AM.\r\nFriday: 7:00 AM – 2:00 AM.', NULL, NULL, NULL, 1, 'A French-inspired bakery and café serving fresh pastries, desserts, and specialty coffee.'),
(62, 'Bodo', 'cafes', 'Bodo is a coffee shop located on King Abdullah Road in Al-Hofuf, Al-Ahsa. It offers a selection of coffee, hot chocolate, cakes, and cheesecakes. The café also provides takeaway and delivery services, making it a suitable spot for coffee and dessert lovers.', 'https://maps.app.goo.gl/zEHvFDuxbBFBwhVt8', 'images/Bodo.jpg', 'Sunday – Wednesday: 8:00 AM – 12:00 AM\r\nThursday: 8:00 AM – 2:00 AM\r\nFriday: 1:00 PM – 2:00 AM\r\nSaturday: 9:00 AM – 1:00 AM', NULL, NULL, NULL, 1, 'A cozy café serving coffee, hot chocolate, cakes, and cheesecakes.'),
(63, 'Bon Specialty Coffee', 'cafes', 'Bon is a specialty coffee shop and roastery located on King Abdullah Road in Al-Mazrooa, Al-Hofuf. It serves a variety of hot and cold coffee drinks, including espresso-based beverages and V60 pour-over coffee, alongside selected desserts. The café features a bright, minimalist atmosphere suitable for enjoying coffee and meeting friends.', 'https://maps.app.goo.gl/NaYxDn4KismWKom2A', 'images/Bon-Specialty-Coffee.jpg', '6:00 AM – 12:00 AM.', NULL, NULL, NULL, 1, 'A specialty coffee shop and roastery serving espresso, V60, and desserts.'),
(64, 'WACAFE', 'cafes', 'WACAFE is a Saudi specialty coffee brand that originated in Al-Ahsa in 2018. It offers a variety of hot and cold coffee drinks, including espresso and V60, alongside freshly baked pastries, cookies, and desserts. The café combines specialty coffee with local hospitality and a welcoming atmosphere.', 'https://maps.app.goo.gl/8YhAHqkw62nAJEEG8', 'images/WACAFE.jpg', 'Sunday – Thursday: 6:00 AM – 1:00 AM\r\nFriday – Saturday: 6:00 AM – 1:00 AM', 'https://wacafe.sa/', NULL, NULL, 1, 'A specialty coffee shop serving coffee, fresh pastries, cookies, and desserts.'),
(65, 'MLGA Café', 'cafes', 'MLGA is a specialty coffee shop located on Ain Najm Road in Al-Mubarraz, Al-Ahsa. It offers a selection of specialty coffee and desserts, including cinnamon rolls. The café is known for its calm and comfortable atmosphere, making it a pleasant place to relax and enjoy coffee.', 'https://maps.app.goo.gl/etRC92Ta8ek9eZRm7', 'images/MLGA.jpg', 'Saturday – Thursday: 6:00 AM – 2:00 AM\r\nFriday: 1:00 PM – 2:00 AM', NULL, NULL, NULL, 1, 'A specialty coffee shop serving coffee and desserts in a calm atmosphere.'),
(66, 'White Café', 'cafes', 'White Café is a popular café brand founded in Al-Ahsa in 2018. It specializes in specialty coffee and ice cream, offering a variety of hot and cold coffee drinks, desserts, cheesecakes, and freshly baked pastries. The café also provides takeaway and delivery services.', 'https://maps.app.goo.gl/yNWjDk49GxpCFjos9', 'images/White-Cafe.jpg', 'Saturday–Wednesday: 6:00 AM – 12:00 AM\r\nThursday: 6:00 AM – 2:00 AM\r\nFriday: 12:00 PM – 2:00 AM', 'https://whitecafe.order.sa/', NULL, NULL, 1, 'A specialty coffee café serving coffee, ice cream, desserts, and fresh pastries.'),
(67, 'Hwass Café', 'cafes', 'Hwass is a coffee shop located on King Abdullah Road in South Al-Hofuf, Al-Ahsa. It serves a variety of coffee drinks and desserts, offering a comfortable atmosphere for casual visits and gatherings. The café also provides takeaway and delivery services.', 'https://maps.app.goo.gl/TZUiMetEJzTwiX7HA', 'images/Hwass-Cafe.jpg', 'Open for 24 hours', NULL, NULL, NULL, 1, 'A coffee shop serving a variety of coffee drinks and desserts.'),
(68, 'SHYLM Bakehouse', 'cafes', 'SHYLM is a modern bakery and café located on King Abdullah Road in Al-Hofuf, Al-Ahsa. It offers specialty coffee, freshly baked savory and sweet pastries, cakes, and desserts. The menu also includes breakfast options such as sandwiches and traditional breakfast dishes.', 'https://maps.app.goo.gl/DJgrvfzho8C1aVoo9', 'images/SHYLM.jpg', 'Saturday–Wednesday: 7:00 AM – 12:00 AM\r\nThursday: 7:00 AM – 2:00 AM\r\nFriday: 12:00 PM – 2:00 AM', NULL, NULL, NULL, 1, 'A modern bakery and café serving specialty coffee, breakfast, pastries, and desserts.'),
(69, 'Modern Supply', 'cafes', 'Modern Supply is a specialty coffee store located in Al-Rawdah, Al-Hofuf, Al-Ahsa. It offers a wide range of specialty coffee beans from different roasters, coffee brewing equipment, barista tools, grinders, cups, and coffee accessories. The branch also has a coffee section where visitors can enjoy freshly prepared coffee drinks while exploring the store.', 'https://maps.app.goo.gl/iSx5xtbpDniGcF7e6', 'images/Modern-Supply.jpg', 'Sunday–Thursday: 7:00 AM – 1:00 AM\r\nFriday: 9:00 AM – 1:00 AM', 'https://modernsupply.com.sa/', NULL, NULL, 1, 'A coffee store and café offering specialty beans, brewing equipment, barista tools, and coffee drinks.'),
(70, 'Alma Specialty Coffee', 'cafes', 'Alma Specialty Coffee is a Saudi coffee brand founded in Al-Ahsa. It specializes in carefully selected coffee beans and freshly prepared specialty drinks, including espresso-based beverages and V60. The café also offers coffee beans and brewing products for coffee enthusiasts.', 'https://maps.app.goo.gl/EakX4ucRCMBsNiw1A', 'images/Alma-Specialty-Coffee.jpg', 'Saturday – Thursday: 5:00 AM – 12:00 AM\r\nFriday: 1:00 PM – 12:00 AM', 'https://almabn.com/ar', NULL, NULL, 1, 'A specialty coffee café offering selected coffee beans, espresso drinks, and V60.'),
(71, 'Huge Zone', 'cafes', 'Huge Zone is a specialty coffee retail and wholesale store located on King Saud Road in Al-Mubarraz, Al-Ahsa. It offers a wide range of specialty coffee beans from different roasters, espresso machines, grinders, brewing equipment, barista tools, and coffee accessories. The store also provides an online shopping service with delivery.', 'https://maps.app.goo.gl/DMBQFcMwCxnK7pW37', 'images/Huge-Zone.jpg', 'Saturday–Thursday: 8:00 AM – 11:45 PM\r\nFriday: 3:30 PM – 11:45 PM', 'https://hugezone-sa.com/', NULL, NULL, 1, 'A specialty coffee store offering coffee beans, espresso machines, brewing tools, and accessories.'),
(72, 'Tri Specialty Coffee', 'cafes', 'Tri is a specialty coffee shop located on Dhahran Road in Al-Bustan, Al-Ahsa. It serves a variety of hot and cold specialty coffee drinks, including espresso-based beverages and V60 pour-over coffee. The café also offers breakfast options, pastries, and desserts in a modern and comfortable setting.', 'https://maps.app.goo.gl/YqsA7SJMfyBdcSrBA', 'images/Tri-Specialty-Coffee.jpg', '6:00 AM – 1:30 AM', NULL, NULL, NULL, 1, 'A specialty coffee shop serving espresso, V60, breakfast, pastries, and desserts'),
(73, 'Greend', 'cafes', 'Greend is a floral-themed café and flower shop located on King Abdullah Road in Al-Hofuf, Al-Ahsa. It offers a selection of coffee and matcha drinks, alongside a floral concept featuring decorative flowers and arrangements. The café provides a cozy setting for enjoying drinks and relaxing.', 'https://maps.app.goo.gl/EixKhV5UcToTCHHs9', 'images/Greend.jpg', 'Saturday–Wednesday: 6:00 AM – 12:00 AM\r\nThursday and Friday: 6:00 AM – 1:00 AM', NULL, NULL, NULL, 1, 'A floral-themed café serving coffee and matcha with a flower shop concept.'),
(74, 'CEEN Coffee', 'cafes', 'CEEN is a coffee shop located near King Faisal University in Al-Mazrooa, Al-Hofuf, Al-Ahsa. It offers a variety of coffee drinks and refreshments, with a convenient takeaway and drive-through experience. It is suitable for visitors looking for a quick coffee stop.', 'https://maps.app.goo.gl/F7EZpRta7BRo8fsFA', 'images/CEEN-Coffee.jpg', 'Sunday – Wednesday: 6:00 AM – 1:30 AM\r\nThursday: 6:00 AM – 2:00 AM\r\nFriday: 12:00 PM – 2:00 AM\r\nSaturday: 3:00 PM – 1:00 AM', NULL, NULL, NULL, 1, 'A coffee shop near King Faisal University offering coffee and refreshing drinks.'),
(75, 'Shai Al Sateh', 'cafes', 'Shai Al Sateh is a traditional tea house located in Al-Nayfiyah district, Al-Hofuf, Al-Ahsa. It offers a variety of hot and cold beverages, including traditional tea, karak, and refreshing drinks. Its menu also includes savory pastries and light snacks, making it a suitable place for casual evening gatherings.', 'https://maps.app.goo.gl/d4YZ3Sdddvgp9mCM8', 'images/Shai-Al-Sateh.jpg', '4:00 PM – 4:00 AM.', NULL, NULL, NULL, 1, 'A tea house serving traditional tea, karak, cold drinks, and savory snacks.'),
(76, 'Biz Tea', 'cafes', 'Biz Tea is a tea house located on Dhahran Road in Al-Bustan, Al-Ahsa. It specializes in traditional tea and karak, offering a variety of hot and cold beverages, including ginger drinks and Arabic coffee. The menu also includes fresh savory pastries, light snacks, and sweets, making it a suitable place for casual gatherings.', 'https://maps.app.goo.gl/XGW6vXkycwfRCEYy8', 'images/Biz-Tea.jpg', 'Open for 24 hours', NULL, NULL, NULL, 1, 'A tea house serving traditional tea, karak, Arabic coffee, and fresh savory pastries.'),
(77, 'SHAI & MALIH', 'cafes', 'Shai & Malih is a traditional Saudi tea house located on King Abdullah Road in South Al-Hofuf, Al-Ahsa. It offers a selection of traditional teas, Saudi coffee, and freshly prepared savory pastries and baked goods. The café features a heritage-inspired atmosphere that reflects the traditional hospitality of Al-Ahsa.', 'https://maps.app.goo.gl/p8HbULnXfsb61MTVA', 'images/Shai-And-Malih.jpg', 'Saturday–Wednesday: 4:00 PM – 2:00 AM\r\nThursday and Friday: 4:00 PM – 3:00 AM', NULL, NULL, NULL, 1, 'A traditional Saudi tea house serving tea, Saudi coffee, and savory pastries.'),
(78, 'EVER Cafe Roaster', 'cafes', 'Ever Cafe Roaster is a specialty coffee shop and roastery located in Al-Jameyeen district, Al-Hofuf, Al-Ahsa. It offers a variety of specialty coffee drinks, including espresso-based beverages and pour-over coffee, alongside breakfast options, sweets, and desserts. The café features a convenient drive-through service and operates 24 hours a day.', 'https://maps.app.goo.gl/1dXAu5YFbCb1kdT8A', 'images/Ever-Cafe-Roaster.jpg', 'Open for 24 hours', NULL, NULL, NULL, 1, 'A 24-hour specialty coffee roastery offering coffee, breakfast, desserts, and drive-through service.'),
(79, 'Al-Ahsa Dates Festival', 'seasonal events', 'Wiya Al-Tamar Ahla is one of Al-Ahsa\'s most prominent seasonal festivals, celebrating the region\'s dates and promoting local date products. The festival features date factories and retail stalls, processed date products, traditional handicrafts, cultural exhibitions, folk performances, and family-friendly activities. It offers visitors an opportunity to explore Al-Ahsa\'s agricultural heritage, local traditions, and cultural identity.', 'https://maps.app.goo.gl/esFrLDcHSL9MAM246', 'images/AlAhsa_Dates_Festival.jpg', '4:00 PM - 11:00 PM ', NULL, NULL, NULL, 1, 'A seasonal festival celebrating Al-Ahsa dates through cultural, heritage, and entertainment activities.'),
(80, 'Al-Ahsa Lomi Exhibition', 'seasonal events', 'Al-Ahsa Lomi Exhibition is a seasonal agricultural and tourism event celebrating the region\'s traditional Hasawi lemon and its cultural heritage. The exhibition features stalls showcasing and selling fresh lomi and its derived products, with participation from local farmers, factories, and productive families. Visitors can also enjoy restaurants, cafés, workshops, interactive activities, children\'s areas, art exhibitions, and family spaces. The event aims to support local businesses and promote Al-Ahsa\'s agricultural products and heritage.', 'https://maps.app.goo.gl/Y8NLXKKaPH2YH13o6', 'images/AlAhsa_Lomi_Exhibition.jpg', '5:00 PM – 11:00 PM.', 'https://hcci.org.sa/lomi_hasawi25/', NULL, NULL, 1, 'A seasonal exhibition celebrating Hasawi lomi, its agricultural products, and local heritage');
INSERT INTO `places` (`Place_ID`, `Name`, `Place_Type`, `Description`, `Location`, `Image`, `Opening_Hours`, `Website`, `Business_Owner_ID`, `Business_Size`, `Is_Published`, `Short_Description`) VALUES
(81, 'Al Nakheel Village', 'seasonal events', 'Al Nakheel Village is a rural tourism and cultural destination in the Al-Ahsa Oasis, celebrating palm trees, dates, and the region\'s agricultural heritage. It features date shops, processed date products, traditional handicrafts, local rural products, restaurants, and cafés. Visitors can also explore the Palm Museum, cultural houses, interactive theater, folk performances, and children\'s activities and games, making it a welcoming destination for families and visitors interested in Hasawi heritage. Additionally, free admission is granted to individuals with disabilities who hold a disability proof card.', 'https://maps.app.goo.gl/6wNfqUxozcjrYBa47', 'images/AlNakheel_Village.jpg', '4:00 PM – 11:00 PM.', 'https://wahaevents.hozma.tech/', NULL, NULL, 1, 'A rural cultural destination celebrating palm trees, dates, and Hasawi crafts'),
(82, 'Hasawi Rice Festival', 'seasonal events', 'The Hasawi Rice Festival is an agricultural and heritage event celebrating one of Al-Ahsa\'s most distinctive local crops, the famous red Hasawi rice. The festival features stalls run by local farmers and productive families, showcasing Hasawi rice, its derived products, and traditional food and handicrafts. It also offers educational activities and workshops about rice cultivation, modern farming practices, and agricultural sustainability. The event aims to support local farmers, promote their products, and preserve Al-Ahsa\'s agricultural heritage.', 'https://maps.app.goo.gl/dK2TsrYACMdtFfa17', 'images/Hasawi_Rice_Festival.jpg', '4:00 PM to 10:00 PM.', NULL, NULL, NULL, 1, 'An agricultural festival celebrating Hasawi rice and supporting local farmers and products'),
(83, 'Al-Ahsa Bisht Festival', 'seasonal events', 'Al-Ahsa Bisht Festival is a cultural and heritage event celebrating the traditional bisht-making craft for which Al-Ahsa is widely known. The festival highlights the history of this craft and the skills passed down through generations of local artisans. It features exhibitions and shopping stalls showcasing bisht garments, fabrics, gold embroidery, weaving, and traditional tailoring techniques, alongside workshops, theatrical performances, and folk dances such as Ardah and Samri. Visitors can also enjoy interactive children\'s activities, traditional women\'s abaya exhibitions, and experiences that introduce them to the art of wearing the bisht.', 'https://maps.app.goo.gl/7XjYbtrbgomGUULs9', 'images/AlAhsa_Bisht_Festival.jpg', '4:00 PM to 11:00 PM.', NULL, NULL, NULL, 1, 'A heritage festival celebrating Hasawi bisht craftsmanship, traditional arts, and folk performances.'),
(84, 'Qaisariya Nights Festival', 'seasonal events', 'Qaisariya Nights is a seasonal cultural and heritage festival held at the historic Qaisariya Souq in Al Hofuf. The event celebrates Al-Ahsa\'s traditional identity and the history of a market that dates back more than 200 years. It features folk performances, traditional music, family and community gatherings, heritage games, and theatrical shows recreating the atmosphere of old commercial life. Visitors can also enjoy guided market routes, arts and crafts workshops for children and young people, and explore traditional shops, local products, and handmade crafts.', 'https://maps.app.goo.gl/8Q6vLJ64VSaDfyzN8', 'images/Qaisariya_Nights_Festival.jpg', '4:00 PM to 11:00 PM.', NULL, NULL, NULL, 1, 'A heritage festival celebrating Qaisariya Souq through folk performances, crafts, and cultural activities.'),
(85, 'Layali KFU – Kfu Ramadan Nights', 'seasonal events', 'Layali KFU is a community and entertainment festival organized by King Faisal University in Al-Ahsa during the holy month of Ramadan. It aims to strengthen community connections and celebrate Ramadan traditions. The festival features several zones, including a stage for cultural and entertainment performances, a children\'s area, electronic games, and a live cooking experience, alongside interactive exhibitions and stalls operated by local businesses and productive families. Visitors can also enjoy traditional Ramadan gatherings and Ghabqa dining experiences, making it a destination that combines entertainment, culture, and community engagement.', 'https://maps.app.goo.gl/ZxjAWkQBVvrgU89j9', 'images/Layali_KFU_Ramadan_Nights.jpg', '5:00 PM – 2:30 AM.', 'https://layalikfu.com/', NULL, NULL, 1, 'A Ramadan festival featuring cultural entertainment, family activities, and traditional gatherings'),
(86, 'Al-Ahsa National Day Parade', 'seasonal events', 'The Al-Ahsa National Day Parade is an annual public celebration held in honor of Saudi National Day. It brings together government and security authorities, community organizations, and local residents in a festive atmosphere celebrating national identity. The parade features traditional folk performances, patriotic songs, classic and modified cars, horse riders, and organized public displays. It offers residents and visitors an opportunity to enjoy the celebrations and take part in the national festivities along the parade route.', 'https://maps.app.goo.gl/CxKrRhGAz8yKRMLe6', 'images/AlAhsa_National_Day_Parade.jpg', 'At night', NULL, NULL, NULL, 1, 'An annual national parade featuring folk performances, classic cars, and horse riders'),
(87, 'Saudi National Day Celebrations', 'seasonal events', 'Family celebrations for Saudi National Day featuring musical performances, national activities, drawing and graphic art, and a dedicated children\'s area. The program also includes interactive fountain and paragliding shows, driving demonstrations, and a market supporting local artisans and productive families.', 'https://maps.app.goo.gl/JtaJSgvw8e2XCyKv5', 'images/King_Abdullah_National_Day.jpg', NULL, NULL, NULL, NULL, 1, 'Family National Day celebrations featuring cultural performances, entertainment, and a local market.'),
(88, 'Winter Al-Wafrah', 'seasonal events', 'A seasonal winter destination in Al-Wafrah, south of Hofuf, featuring more than 200 food trucks offering a variety of meals and drinks, including traditional Hasawi dishes. The area provides outdoor winter seating and a welcoming atmosphere for families and friends while supporting local entrepreneurs and small businesses.', 'https://maps.app.goo.gl/bccxhTPFar7q4HWW8', 'images/Winter_AlWafrah.jpg', NULL, NULL, NULL, NULL, 1, 'A winter destination featuring food trucks, outdoor seating, and traditional and international cuisine.');

-- --------------------------------------------------------

--
-- بنية الجدول `place_images`
--

CREATE TABLE `place_images` (
  `Image_ID` int(11) NOT NULL,
  `Place_ID` int(11) NOT NULL,
  `Image_Path` varchar(255) NOT NULL,
  `Uploaded_At` timestamp NOT NULL DEFAULT current_timestamp()
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

--
-- إرجاع أو استيراد بيانات الجدول `seasonal_events`
--

INSERT INTO `seasonal_events` (`Place_ID`, `Start_Date`, `End_Date`, `Ticket_URL`) VALUES
(79, '2027-01-05', '2027-02-16', 'https://evento.sa/event-details/9284187c-7605-49dc-ae87-04306098c009'),
(80, '2027-08-19', '2026-09-13', 'https://www.evento.sa/home'),
(81, '2026-11-05', '2027-02-28', 'https://wahaevents.hozma.tech/event/10/register'),
(82, '2027-02-04', '2027-02-08', NULL),
(83, '2027-01-28', '2027-02-07', NULL),
(84, '2027-02-01', '2027-03-15', NULL),
(85, '2027-02-08', '2027-02-27', 'https://layalikfu.com/'),
(86, '2027-09-22', '2027-09-22', NULL),
(87, '2027-09-22', '2027-09-26', NULL),
(88, '2026-11-01', '2027-02-01', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `site_visits`
--

CREATE TABLE `site_visits` (
  `Visit_ID` int(11) NOT NULL,
  `Tourist_ID` int(11) DEFAULT NULL,
  `Session_ID` varchar(250) DEFAULT NULL,
  `Page_Visited` varchar(250) DEFAULT NULL,
  `Visit_Date` timestamp NOT NULL DEFAULT current_timestamp()
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

--
-- إرجاع أو استيراد بيانات الجدول `survey_options`
--

INSERT INTO `survey_options` (`Option_ID`, `Question_ID`, `Option_Text`, `Parent_Option_ID`, `Display_Order`) VALUES
(1, 1, '1 day', NULL, 1),
(2, 1, '2 days', NULL, 2),
(3, 1, '3 days', NULL, 3),
(4, 1, '4 days', NULL, 4),
(5, 1, '5 days', NULL, 5),
(6, 1, ' 6 days', NULL, 6),
(7, 1, 'Week', NULL, 7),
(8, 1, 'More than a week', NULL, 8),
(9, 2, 'Morning (6 AM – 12 PM)', NULL, 1),
(10, 2, 'Afternoon (12 PM – 5 PM)', NULL, 2),
(11, 2, 'Evening (5 PM – 10 PM)', NULL, 3),
(12, 2, 'All day', NULL, 4),
(13, 3, 'Alone', NULL, 1),
(14, 3, 'With family (without children)', NULL, 2),
(15, 3, 'With family (with children)', NULL, 3),
(16, 3, 'With friends', NULL, 4),
(17, 4, 'Less than 500 SAR', NULL, 1),
(18, 4, '500 – 1000 SAR', NULL, 2),
(19, 4, '1000 – 3000 SAR', NULL, 3),
(20, 4, 'More than 3000 SAR', NULL, 4),
(21, 5, 'Modern entertainment places', NULL, 1),
(22, 5, 'Heritage / traditional places', NULL, 2),
(23, 5, 'Both', NULL, 3),
(24, 6, 'Traditional local restaurants', NULL, 1),
(25, 6, 'International restaurants', NULL, 2),
(26, 6, 'No specific preference', NULL, 3),
(28, 7, 'Quiet cafes for relaxation', NULL, 1),
(29, 7, 'Trendy / modern cafes', NULL, 2),
(30, 7, 'Scenic view cafes', NULL, 3),
(31, 7, 'Not interested in cafes', NULL, 4),
(32, 8, 'Yes', NULL, 1),
(33, 8, 'No', NULL, 2),
(34, 5, 'Parks', 21, 1),
(35, 5, 'Entertainment complexes', 21, 2),
(36, 5, 'Highlighting the heritage of Al-Ahsa', 22, 1),
(37, 5, 'Heritage-themed complexes', 22, 2),
(38, 6, 'A unique experience with Al-Ahsa rice', 24, 1),
(39, 6, 'Restaurants serving Al-Ahsa cuisine', 24, 2),
(40, 6, 'Restaurants with a heritage and traditional ', 24, 3);

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

--
-- إرجاع أو استيراد بيانات الجدول `survey_questions`
--

INSERT INTO `survey_questions` (`Question_ID`, `Question_Text`, `Question_Type`, `Is_Required`, `Display_Order`) VALUES
(1, 'How many days will you stay in Al-Ahsa?', 'Multiple Choice', 1, 1),
(2, 'What are your preferred outing times?', 'Multiple Select', 1, 2),
(3, 'Who are you traveling with?', 'Multiple Choice', 1, 3),
(4, 'What is your approximate budget for the trip?', 'Multiple Choice', 1, 4),
(5, 'What type of places do you prefer to visit?', 'Multiple Select', 1, 5),
(6, 'What type of restaurants do you prefer?', 'Multiple Select', 1, 6),
(7, 'What type of cafes do you prefer?', 'Multiple Select', 1, 7),
(8, 'Is shopping one of your trip goals?', 'Multiple Choice', 1, 8);

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
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Admin_ID`),
  ADD UNIQUE KEY `Email` (`Email`);

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
-- Indexes for table `place_images`
--
ALTER TABLE `place_images`
  ADD PRIMARY KEY (`Image_ID`),
  ADD KEY `Place_ID` (`Place_ID`);

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
-- Indexes for table `site_visits`
--
ALTER TABLE `site_visits`
  ADD PRIMARY KEY (`Visit_ID`),
  ADD KEY `Tourist_ID` (`Tourist_ID`);

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
  MODIFY `Accessibility_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `Place_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `place_images`
--
ALTER TABLE `place_images`
  MODIFY `Image_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `place_reviews`
--
ALTER TABLE `place_reviews`
  MODIFY `Place_Review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_visits`
--
ALTER TABLE `site_visits`
  MODIFY `Visit_ID` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `Option_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `survey_questions`
--
ALTER TABLE `survey_questions`
  MODIFY `Question_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- قيود الجداول `place_images`
--
ALTER TABLE `place_images`
  ADD CONSTRAINT `place_images_ibfk_1` FOREIGN KEY (`Place_ID`) REFERENCES `places` (`Place_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- قيود الجداول `site_visits`
--
ALTER TABLE `site_visits`
  ADD CONSTRAINT `site_visits_ibfk_1` FOREIGN KEY (`Tourist_ID`) REFERENCES `tourist` (`tourist_id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
