-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 28, 2025 at 02:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tarumtvs`
--
CREATE DATABASE IF NOT EXISTS `tarumtvs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tarumtvs`;

-- --------------------------------------------------------

--
-- Table structure for table `academicdocument`
--

CREATE TABLE `academicdocument` (
  `academicID` int(10) NOT NULL,
  `academicFilename` longtext NOT NULL,
  `applicationSubmissionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academicdocument`
--

INSERT INTO `academicdocument` (`academicID`, `academicFilename`, `applicationSubmissionID`) VALUES
(1, 'cgpa_1.jpeg', 1),
(2, 'achievement_1.jpeg', 1),
(3, 'achievement_2.jpeg', 1),
(4, 'behaviorReport_1.jpg', 1),
(5, 'cgpa_1.jpeg', 2),
(6, 'behaviorReport_1.jpg', 2),
(7, 'achievement_1.jpeg', 2),
(8, 'achievement_2.jpeg', 2),
(9, 'cgpa_1.jpeg', 3),
(10, 'achievement_1.jpeg', 3),
(11, 'achievement_2.jpeg', 3),
(12, 'behaviorReport_1.jpg', 3),
(13, 'cgpa_1.jpeg', 4),
(14, 'achievement_1.jpeg', 4),
(15, 'behaviorReport_1.jpg', 4),
(16, 'cgpa_1.jpeg', 5),
(17, 'achievement_1.jpg', 5),
(18, 'behaviorReport_1.jpeg', 5),
(19, 'cgpa_1.jpeg', 6),
(20, 'achievement_1.jpeg', 6),
(21, 'behaviorReport_1.jpg', 6),
(22, 'cgpa_1.jpeg', 7),
(23, 'achievement_1.jpeg', 7),
(24, 'behaviorReport_1.jpg', 7),
(25, 'cgpa_1.jpeg', 8),
(26, 'achievement_1.jpeg', 8),
(27, 'behaviorReport_1.jpg', 8),
(28, 'cgpa_1.jpeg', 9),
(29, 'achievement_1.jpeg', 9),
(30, 'behaviorReport_1.jpg', 9),
(31, 'cgpa_2.jpeg', 10),
(32, 'achievement_2.jpeg', 10),
(33, 'behaviorReport_2.jpeg', 10),
(34, 'cgpa_2.jpeg', 11),
(35, 'achievement_2.jpeg', 11),
(36, 'behaviorReport_2.jpg', 11),
(37, 'cgpa_1.jpeg', 12),
(38, 'achievement_1.jpg', 12),
(39, 'behaviorReport_1.jpeg', 12),
(40, 'cgpa_1.jpeg', 13),
(41, 'achievement_1.jpeg', 13),
(42, 'behaviorReport_1.jpg', 13),
(43, 'cgpa_1.jpeg', 14),
(44, 'achievement_1.jpeg', 14),
(45, 'behaviorReport_1.jpg', 14),
(46, 'cgpa_1.jpeg', 15),
(47, 'achievement_1.jpeg', 15),
(48, 'behaviorReport_1.jpg', 15),
(49, 'cgpa_1.jpeg', 16),
(50, 'achievement_1.jpeg', 16),
(51, 'achievement_2.jpeg', 16),
(52, 'behaviorReport_1.jpg', 16),
(53, 'cgpa_1.jpeg', 17),
(54, 'achievement_1.jpeg', 17),
(55, 'behaviorReport_1.jpg', 17),
(56, 'cgpa_1.jpeg', 18),
(57, 'achievement_1.jpeg', 18),
(58, 'behaviorReport_1.jpg', 18),
(59, 'cgpa_1.jpeg', 19),
(60, 'achievement_1.jpg', 19),
(61, 'behaviorReport_1.jpeg', 19),
(62, 'cgpa_1.jpeg', 20),
(63, 'achievement_1.jpeg', 20),
(64, 'behaviorReport_1.jpg', 20),
(65, 'cgpa_1.jpeg', 21),
(66, 'achievement_1.jpeg', 21),
(67, 'behaviorReport_1.jpg', 21),
(68, 'cgpa_1.jpeg', 22),
(69, 'achievement_1.jpeg', 22),
(70, 'behaviorReport_1.jpeg', 22);

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `accountID` int(10) NOT NULL,
  `role` enum('STUDENT','NOMINEE','ADMIN') NOT NULL,
  `loginID` int(8) NOT NULL,
  `passwordHash` varchar(255) NOT NULL,
  `status` enum('ACTIVE','SUSPENDED','DEACTIVATED') NOT NULL DEFAULT 'ACTIVE',
  `lastLoginAt` datetime NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `gender` enum('M','F') NOT NULL,
  `email` varchar(100) NOT NULL,
  `phoneNumber` varchar(20) NOT NULL,
  `profilePhotoURL` longtext DEFAULT NULL,
  `facultyID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`accountID`, `role`, `loginID`, `passwordHash`, `status`, `lastLoginAt`, `fullName`, `gender`, `email`, `phoneNumber`, `profilePhotoURL`, `facultyID`) VALUES
(1, 'ADMIN', 1000001, '$2b$12$n/e3Us56a6iHxlMimqoWYum3xzqpGVii2QzYWjS1y495T6fzDwx2e', 'ACTIVE', '2025-11-24 17:32:38', 'Lim Chee Kuan', 'M', 'limcheekuan@tarc.edu.my', '012-5565465', NULL, 1),
(2, 'ADMIN', 1000002, '$2b$12$tNfJ4iCG8y7fJmtm/nc1XOYtaMyVgXQrEjJnSdX9dSsgmjtYdQphG', 'ACTIVE', '2025-11-24 17:24:49', 'Tan Siew Ling', 'F', 'tansiewling@tarc.edu.my', '017-3183652', NULL, 1),
(3, 'ADMIN', 1000003, '$2b$12$rgyJETBQBeFcaEDAqjwbseqrsC3932LuNgm7Jnkipprfjb0Wunbeu', 'ACTIVE', '2025-11-24 16:48:32', 'Goh Khai Yuen', 'M', 'gohkhaiyuen@tarc.edu.my', '018-7536436', NULL, 1),
(4, 'ADMIN', 1000004, '$2b$12$2jiHMgO5mSBzagAKycu/sOKgCaq7AOzVu5mk5coc6aCkEZlXq/Fqe', 'ACTIVE', '2025-11-24 17:15:53', 'Nur Hidayah Malik', 'F', 'nurhidayah@tarc.edu.my', '013-8247951', NULL, 2),
(5, 'ADMIN', 1000005, '$2b$12$RjL2cxq3swJ6FjQM5CxE1u5gvtbPsiyr1qEmhTtiEqMXl7k73Rgla', 'ACTIVE', '2025-11-24 09:00:00', 'Rajesh Kumar', 'M', 'rajeshkumar@tarc.edu.my', '016-9042376', NULL, 2),
(6, 'NOMINEE', 2300001, '$2b$12$IvMuse6S9RybYz/zRfdzQOpBDTYEGZygoWkCpb.qviINCw58DE5ba', 'ACTIVE', '2025-11-24 17:31:38', 'Yong Vin Sen', 'M', 'yvs-wm@student.tarc.edu.my', '012-5565465', NULL, 1),
(7, 'NOMINEE', 2300002, '$2b$12$auHMk99rYIdHFNbI/I3iWusLNQ5g4pOic2MEXaWru0aUu9x2f5SgO', 'ACTIVE', '2025-11-24 17:22:45', 'Tan Jia Wei', 'F', 'tjw-wm@student.tarc.edu.my', '017-3183652', NULL, 1),
(8, 'NOMINEE', 2300003, '$2b$12$UB2sMV91QwVy32RZAp88YeT5jK5v2024pNwOTK0PhUvTmKKuDLJf6', 'ACTIVE', '2025-11-24 17:23:32', 'Lee Kai Xin', 'F', 'lkx-wm@student.tarc.edu.my', '018-7536436', NULL, 1),
(9, 'NOMINEE', 2300004, '$2b$12$DWTnf0VHNpHXUi3jvBY23O930u6xRVReIP2d/8QCQZxpjp2IhjULW', 'ACTIVE', '2025-11-24 09:00:00', 'Lim Zi Xuan', 'F', 'lzx-wm@student.tarc.edu.my', '013-8247951', NULL, 1),
(10, 'NOMINEE', 2300005, '$2b$12$uYOjTfnt7LlNzMTaTwNEouvz..d6G9dUwuytlc2Yqa2bM6A7j30fO', 'ACTIVE', '2025-11-24 09:00:00', 'Chan Wei Ling', 'F', 'cwl-wm@student.tarc.edu.my', '016-9042376', NULL, 1),
(11, 'STUDENT', 2300006, '$2b$12$1/QvGTfpWmkZsCdwHkR8V.CpeZtv5IrzWfHzMbSimZIJpX2pmB8kG', 'ACTIVE', '2025-11-24 09:00:00', 'Ng Jun Hao', 'M', 'njh-wm@student.tarc.edu.my', '019-6674823', NULL, 1),
(12, 'STUDENT', 2300007, '$2b$12$GPGLESvzKR0mfTsTT4Vc6u3fmzJN8zEcmc1KnPz0.hsyXOodqNA/K', 'ACTIVE', '2025-11-24 09:00:00', 'Goh Xin Yi', 'F', 'gxy-wm@student.tarc.edu.my', '011-4298654', NULL, 1),
(13, 'STUDENT', 2300008, '$2b$12$Jz4DvCysI3f9FciKIAdOU.MA9pknDMvXQJ6ZQ./u.l3MC81mbto1W', 'ACTIVE', '2025-11-24 09:00:00', 'Lau Zhi Ying', 'F', 'lzy-wm@student.tarc.edu.my', '014-7382910', NULL, 1),
(14, 'STUDENT', 2300009, '$2b$12$oPQGpWDIPEtocKreU5hIoOo1Xx0/C3Nqqku37iVwSEtw7pBaksiOi', 'ACTIVE', '2025-11-24 09:00:00', 'Teh Jia Min', 'F', 'tjm-wm@student.tarc.edu.my', '010-5928473', NULL, 1),
(15, 'STUDENT', 2300010, '$2b$12$7iIfshPTL5hhHOtWiLkXAepX7N3dRcsYuGx5EtxuXwkP1W9oJdnYm', 'ACTIVE', '2025-11-24 09:00:00', 'Ong Yu Heng', 'M', 'oyh-wm@student.tarc.edu.my', '018-2198347', NULL, 1),
(16, 'NOMINEE', 2300011, '$2b$12$FUE5W0FuQQCLdEKWJUf52uZ21NmEr/ly3n41YVnc70bnKj1aDB716', 'ACTIVE', '2025-11-24 17:24:12', 'Ahmad Firdaus Razak', 'M', 'afr-wm@student.tarc.edu.my', '012-5565465', NULL, 2),
(17, 'NOMINEE', 2300012, '$2b$12$ZjsoRAj5YDzH7pUh4NxhOeZU2XH1DD7E5WFFIGqMcP/hK8huQ6NKC', 'ACTIVE', '2025-11-24 09:00:00', 'Nur Aina Sofea', 'F', 'nas-wm@student.tarc.edu.my', '017-3183652', NULL, 2),
(18, 'NOMINEE', 2300013, '$2b$12$dZABqeQeQH9SAU.FpnabP.iQUlqZpAI1RTA5RIFD3PaIl3xd2M8gm', 'ACTIVE', '2025-11-24 09:00:00', 'Muhammad Danish Hakim', 'M', 'mdh-wm@student.tarc.edu.my', '018-7536436', NULL, 2),
(19, 'NOMINEE', 2300014, '$2b$12$13m.yU0m0dMyapmGQ2R.bucXz9.I7D0kESRQPhXC7O9e.kLt1u3cm', 'ACTIVE', '2025-11-24 09:00:00', 'Siti Nur Balqis', 'F', 'snb-wm@student.tarc.edu.my', '013-8247951', NULL, 2),
(20, 'NOMINEE', 2300015, '$2b$12$JaksuTYCxky/kX.KZWqY0OGBe8ChfYbu7poiP74tg8qko3j7f4RMy', 'ACTIVE', '2025-11-24 09:00:00', 'Farhan Haziq', 'M', 'fh-wm@student.tarc.edu.my', '016-9042376', NULL, 2),
(21, 'STUDENT', 2300016, '$2b$12$EA/OtVt1GPUr9g2UP.Ui8u6W2vtx1ijAgVWtDSoJf54izJhdAQXZK', 'ACTIVE', '2025-11-24 09:00:00', 'Nurul Izzah Rahman', 'F', 'nir-wm@student.tarc.edu.my', '019-6674823', NULL, 2),
(22, 'STUDENT', 2300017, '$2b$12$Cao4JsC7/LLJYtYrwKLEcOuKmMKOh6azkOUNLQSKaa90KaU98iWPi', 'ACTIVE', '2025-11-24 09:00:00', 'Hakim Azhar', 'M', 'ha-wm@student.tarc.edu.my', '011-4298654', NULL, 2),
(23, 'STUDENT', 2300018, '$2b$12$Drja2VIBklhm24IUY2/Y3.sI9dkzSOD96vSSglFCkNzwwb36lKAiy', 'ACTIVE', '2025-11-24 09:00:00', 'Syafiqah Nabila', 'F', 'sn-wm@student.tarc.edu.my', '014-7382910', NULL, 2),
(24, 'STUDENT', 2300019, '$2b$12$5qOfivQHUhgXRLTxpIQXquixrUzY6t.ph1ENO07w0tDP9GJzb4qju', 'ACTIVE', '2025-11-24 09:00:00', 'Aisyah Humaira', 'F', 'ah-wm@student.tarc.edu.my', '010-5928473', NULL, 2),
(25, 'STUDENT', 2300020, '$2b$12$PODqP2XTiZdhk5ErFY3HCuGTFqr4sdcB4GdG5hvxJfH3dlhRrysRC', 'ACTIVE', '2025-11-24 09:00:00', 'Imran Zulfahmi', 'M', 'iz-wm@student.tarc.edu.my', '018-2198347', NULL, 2),
(26, 'NOMINEE', 2300021, '$2b$12$v5uH2s84xBQ7Lzapza/Wt.uPn5wPlE6YP3.0e2xOUhj0ZNGjjJ0H6', 'ACTIVE', '2025-11-24 09:00:00', 'Arun Kumar', 'M', 'ak-ws@student.tarc.edu.my', '012-5565465', NULL, 3),
(27, 'NOMINEE', 2300022, '$2b$12$w2Ep4Z7t8qQ8YsAqdkXl3ujvZjYa/pxLxyBuS626qunNH2FLStv9u', 'ACTIVE', '2025-11-24 09:00:00', 'Priya Devi', 'F', 'pd-ws@student.tarc.edu.my', '017-3183652', NULL, 3),
(28, 'NOMINEE', 2300023, '$2b$12$K347f9uaTKGul.T3rx0DpuafS7fQxZvrYQL0Tqm0pVfsQ2iPu/Zcu', 'ACTIVE', '2025-11-24 09:00:00', 'Karthik Raj', 'M', 'kr-ws@student.tarc.edu.my', '018-7536436', NULL, 3),
(29, 'NOMINEE', 2300024, '$2b$12$LzpNaNQ2mGLEJb4p/EweA.rlS21QJXPMAEXMOVd8gdfJiTkep2kHS', 'ACTIVE', '2025-11-24 09:00:00', 'Deepa Rani', 'F', 'dr-ws@student.tarc.edu.my', '013-8247951', NULL, 3),
(30, 'STUDENT', 2300025, '$2b$12$AV68F8dFAAbNfNj2JDaq/Op35OL/.3fUHsoesuf7uQ/Wq9msgfQa6', 'ACTIVE', '2025-11-24 09:00:00', 'Rahul Singh', 'M', 'rs-ws@student.tarc.edu.my', '016-9042376', NULL, 3),
(31, 'NOMINEE', 2300026, '$2b$12$Fo8x/QIqnQiYvjA5POR9R.po4giwH4Jy75AfiYs9Aao9SsagXhoQS', 'ACTIVE', '2025-11-24 09:00:00', 'Anita Kumari', 'F', 'ak-ws@student.tarc.edu.my', '019-6674823', NULL, 3),
(32, 'STUDENT', 2300027, '$2b$12$ccEzVE7rU9lS86acSvbFb.hyxJDkTZKUmz.9Ght2upGMW1H1TUwXO', 'ACTIVE', '2025-11-24 09:00:00', 'Vijay Kumar', 'M', 'vk-ws@student.tarc.edu.my', '011-4298654', NULL, 3),
(33, 'STUDENT', 2300028, '$2b$12$doclH0Gy/GHIufHlvP0MvOZHZ6Q46ka7DOb81ukTW3kSpEJz.PlS6', 'ACTIVE', '2025-11-24 09:00:00', 'Sonia Rani', 'F', 'sr-ws@student.tarc.edu.my', '014-7382910', NULL, 3),
(34, 'STUDENT', 2300029, '$2b$12$ThveVCghh8K7Z0dIk4cnLON7FRnEHUcCevLaZqtgA5tEB/WpFL4D.', 'ACTIVE', '2025-11-24 09:00:00', 'Rohan Nair', 'M', 'rn-ws@student.tarc.edu.my', '010-5928473', NULL, 3),
(35, 'STUDENT', 2300030, '$2b$12$EhuULjecWXMt.LY2bMhzf.oGQYmfL2Km2a.NnylX/biu32HqisVYK', 'ACTIVE', '2025-11-24 09:00:00', 'Meera Nair', 'F', 'mn-ws@student.tarc.edu.my', '018-2198347', NULL, 3),
(36, 'NOMINEE', 2300031, '$2b$12$Wnh4z1bUfaLFP8eVXJmKl.xEmA450QmtWbiQDvYjs6mMlFMyGdEri', 'ACTIVE', '2025-11-24 09:00:00', 'Chong Wei Jian', 'M', 'cwj-ws@student.tarc.edu.my', '012-5565465', NULL, 4),
(37, 'NOMINEE', 2300032, '$2b$12$GXkP6jMwo8MWkjGX6KKbze.Vk4tdKN3M7/.r7rNCO3H7eshiWU3WS', 'ACTIVE', '2025-11-24 09:00:00', 'Lim Pei Yi', 'F', 'lpy-ws@student.tarc.edu.my', '017-3183652', NULL, 4),
(38, 'STUDENT', 2300033, '$2b$12$I1Kk6gByvQglOUTzsZl22uESvLfj8.I0vqPRjHcozAX2/qECaEEI2', 'ACTIVE', '2025-11-24 09:00:00', 'Tan Zhen Hong', 'M', 'tzh-ws@student.tarc.edu.my', '018-7536436', NULL, 4),
(39, 'NOMINEE', 2300034, '$2b$12$Z5BOb/ECG8nayrCnet79Iu3HmoeIalLWn4ss5pswuE4te4.hP9kpa', 'ACTIVE', '2025-11-24 16:02:07', 'Liew Jia Qi', 'F', 'ljq-ws@student.tarc.edu.my', '013-8247951', NULL, 4),
(40, 'STUDENT', 2300035, '$2b$12$LqT7zwSS6El3EEU2ZyRhhuyFulBKyzAMjhlHTL8pOSc5HpowCzus6', 'ACTIVE', '2025-11-24 09:00:00', 'Ang Hui Xin', 'F', 'ahx-ws@student.tarc.edu.my', '016-9042376', NULL, 4),
(41, 'STUDENT', 2300036, '$2b$12$EueS0aVaXFc22ok6AGU5KO34RRjN5RLggyU4BhDZiZAu91jmI.ckG', 'ACTIVE', '2025-11-24 09:00:00', 'Ho Jun Jie', 'M', 'hjj-ws@student.tarc.edu.my', '019-6674823', NULL, 4),
(42, 'STUDENT', 2300037, '$2b$12$uRhQmfYSpkqJrhtwGNswq.fEAZQNWsu821vMq638Xp.X2Sk953WBe', 'ACTIVE', '2025-11-24 17:18:23', 'Pang Yu Xin', 'F', 'pyx-ws@student.tarc.edu.my', '011-4298654', NULL, 4),
(43, 'STUDENT', 2300038, '$2b$12$513XUfh3uY35NrvVLs47DuTuw99IfI/JnxlMfCKf7r9.xf6Dlk.Vq', 'ACTIVE', '2025-11-24 09:00:00', 'Sim Yi Wen', 'F', 'syw-ws@student.tarc.edu.my', '014-7382910', NULL, 4),
(44, 'STUDENT', 2300039, '$2b$12$jjAL/46Luin1JoeAKzc6UObFhd0Iq4R8RNEMNyQsTMwOEfxXRr/vu', 'ACTIVE', '2025-11-24 09:00:00', 'Foong Zi Jie', 'M', 'fzj-ws@student.tarc.edu.my', '010-5928473', NULL, 4),
(45, 'STUDENT', 2300040, '$2b$12$cJucCbnimTm2rcenq42ZKuJqzGun1CvUvB1StKMJ94vmd.wKyRlPG', 'ACTIVE', '2025-11-24 09:00:00', 'Kok Li Ying', 'F', 'kly-ws@student.tarc.edu.my', '018-2198347', NULL, 4),
(46, 'STUDENT', 2300041, '$2b$12$HZu4X56zZq65m0WUSRAV8.tEquPDP1wqpvfnAwS22NXsbmALHSOZu', 'ACTIVE', '2025-11-24 09:00:00', 'Low Wen Hao', 'M', 'lwh-wp@student.tarc.edu.my', '012-5565465', NULL, 5),
(47, 'STUDENT', 2300042, '$2b$12$b59sOgqc6Mf7sJJik3DX5.dAeouAfDSD5AKVGYZWXdoyAIFDEjbzi', 'ACTIVE', '2025-11-24 09:00:00', 'Chew Zi Ning', 'F', 'czn-wp@student.tarc.edu.my', '017-3183652', NULL, 5),
(48, 'STUDENT', 2300043, '$2b$12$ezAnhx1g61gkYnp6Bq08oeusIvyn1CmJX4lmscMB5038.14bts/Oe', 'ACTIVE', '2025-11-24 09:00:00', 'Goh Jia Ying', 'F', 'gjy-wp@student.tarc.edu.my', '018-7536436', NULL, 5),
(49, 'STUDENT', 2300044, '$2b$12$lXZsk4fTqi2qwVhw.tpkZewDevxRQxtYPRDNTFgT5pAQ5wa5Kaakq', 'ACTIVE', '2025-11-24 09:00:00', 'Tan Hao Ming', 'M', 'thm-wp@student.tarc.edu.my', '013-8247951', NULL, 5),
(50, 'STUDENT', 2300045, '$2b$12$6mP73Q4rPDM4uw0jIuh1geBkqhNsFr84.r2g2crs/HzMXF/iRYxpG', 'ACTIVE', '2025-11-24 09:00:00', 'Leong Jia Hui', 'F', 'ljh-wp@student.tarc.edu.my', '016-9042376', NULL, 5),
(51, 'STUDENT', 2300046, '$2b$12$o1.zapJKn3y7aJlqBYVTU.yrm1YwnyuN7jVRBQhNwnvH2c.GCxD4W', 'ACTIVE', '2025-11-24 09:00:00', 'Chia Xuan Ying', 'F', 'cxy-wp@student.tarc.edu.my', '019-6674823', NULL, 5),
(52, 'STUDENT', 2300047, '$2b$12$fnzJjTFCtWthv5TVEUxlqurCTYBYvhtx7VHXf1XLuGX5P4aInWVXm', 'ACTIVE', '2025-11-24 09:00:00', 'Yap Jun Kiat', 'M', 'yjk-wp@student.tarc.edu.my', '011-4298654', NULL, 5),
(53, 'STUDENT', 2300048, '$2b$12$CWslT1GUObq4bobQmmYfSuJvA/c8lXtw1UhynUXiCIdIw.YrEHAOK', 'ACTIVE', '2025-11-24 09:00:00', 'Koh Xin Hui', 'F', 'kxh-wp@student.tarc.edu.my', '014-7382910', NULL, 5),
(54, 'STUDENT', 2300049, '$2b$12$g5hCX1z22CfbLLFKYvCj1utS1oJeMSnNB2DqOJx8wrwpunlsHQFNe', 'ACTIVE', '2025-11-24 09:00:00', 'Liew Yu Zhen', 'F', 'lyz-wp@student.tarc.edu.my', '010-5928473', NULL, 5),
(55, 'STUDENT', 2300050, '$2b$12$ck4DAOFUn1uf2dgaWqxfC.6xV8Y6ZJ8Y/WX2sxf9pgbFLrIx/Ft7e', 'ACTIVE', '2025-11-24 09:00:00', 'Teo Wei Han', 'M', 'twh-wp@student.tarc.edu.my', '018-2198347', NULL, 5),
(56, 'STUDENT', 2300051, '$2b$12$94HwsdYm001904O1eGht/Omgw5w04zQgKuGgToxm0ic8O/WBxlSw2', 'ACTIVE', '2025-11-24 09:00:00', 'Nguyen Thanh An', 'M', 'nta-wp@student.tarc.edu.my', '012-5565465', NULL, 6),
(57, 'STUDENT', 2300052, '$2b$12$K1YjfClVL3qBChLEeqpE3ONJ1gXPS5GE69y0GDomKh7CYkJd/1eAG', 'ACTIVE', '2025-11-24 09:00:00', 'Tran Minh Chau', 'F', 'tmc-wp@student.tarc.edu.my', '017-3183652', NULL, 6),
(58, 'STUDENT', 2300053, '$2b$12$rsA8XJCkvTNj/sMEVX268..MDFybbKSzkTTOIzEjRkF7uU/G3mhUG', 'ACTIVE', '2025-11-24 09:00:00', 'Pham Quoc Bao', 'M', 'pqb-wp@student.tarc.edu.my', '018-7536436', NULL, 6),
(59, 'STUDENT', 2300054, '$2b$12$S7WrNdbXQaee6Q3UU5GNquN/4MvritU8F5NJGWzdQ2RZTp1KIqqh.', 'ACTIVE', '2025-11-24 09:00:00', 'Le Thi Anh', 'F', 'lta-wp@student.tarc.edu.my', '013-8247951', NULL, 6),
(60, 'STUDENT', 2300055, '$2b$12$AQFSxJGMmHohQMLaFM/jg.8YCu73awmssci81blOu1CRSeGXNRxbK', 'ACTIVE', '2025-11-24 09:00:00', 'Hoang Gia Bao', 'M', 'hgb-wp@student.tarc.edu.my', '016-9042376', NULL, 6),
(61, 'STUDENT', 2300056, '$2b$12$jgS3YcfldA/MlkoS52fQvuQ7wXf87ivm3ZRSD5Zt57JdpL8LZqb..', 'ACTIVE', '2025-11-24 09:00:00', 'Vo Thi Mai', 'F', 'vtm-wp@student.tarc.edu.my', '019-6674823', NULL, 6),
(62, 'STUDENT', 2300057, '$2b$12$gSPFV411.7N2offizP.9leGPzKxg.U.L.nCurJM9M2DsXT5Sg2lom', 'ACTIVE', '2025-11-24 09:00:00', 'Do Minh Khang', 'M', 'dmk-wp@student.tarc.edu.my', '011-4298654', NULL, 6),
(63, 'STUDENT', 2300058, '$2b$12$lNB/cB/eGxlpxBsHgnCmkO78AUxxfXlNYF5ICVzyJV.0dtCXWX15G', 'ACTIVE', '2025-11-24 09:00:00', 'Bui Phuong Linh', 'F', 'bpl-wp@student.tarc.edu.my', '014-7382910', NULL, 6),
(64, 'STUDENT', 2300059, '$2b$12$DfmyEOMS145YdmIeE8ba0uzGh2onwgB2/ukgnAwqrdUFtpPpnfSU6', 'ACTIVE', '2025-11-24 09:00:00', 'Dang Hoai Nam', 'M', 'dhn-wp@student.tarc.edu.my', '010-5928473', NULL, 6),
(65, 'STUDENT', 2300060, '$2b$12$X/7Lwf9h8aP8n6vX2rc3weFA/XD4U2thDAxEJaRxeP1b5Nf/mz1yi', 'ACTIVE', '2025-11-24 09:00:00', 'Phan Ngoc Anh', 'F', 'pna-wp@student.tarc.edu.my', '018-2198347', NULL, 6),
(66, 'STUDENT', 2300061, '$2b$12$qi565Nmxr752gT/6RHa4jOOkzpm4mTsuR9JhX6mUSzZtvYb9upZX6', 'ACTIVE', '2025-11-24 09:00:00', 'Jason Lim', 'M', 'jl-ot@student.tarc.edu.my', '012-5565465', NULL, 7),
(67, 'STUDENT', 2300062, '$2b$12$RMRb1DDFW2YySeQCkc0Sj.w7q1gmt6sPCAFaKOmqZbERrv.6pWvr.', 'ACTIVE', '2025-11-24 09:00:00', 'Chloe Tan', 'F', 'ct-ot@student.tarc.edu.my', '017-3183652', NULL, 7),
(68, 'STUDENT', 2300063, '$2b$12$QJ3G7D4ClZxYPlIpezbWS./rTheDiKnTRJVBQSCskYlejNJuyXeEi', 'ACTIVE', '2025-11-24 09:00:00', 'Brian Lee', 'M', 'bl-ot@student.tarc.edu.my', '018-7536436', NULL, 7),
(69, 'STUDENT', 2300064, '$2b$12$Pk1SB.bYXfGDJYJ8vcG/3OvRicjEhKi4ZH2sVgM2Y5PLA8xBV7IRG', 'ACTIVE', '2025-11-24 09:00:00', 'Amanda Wong', 'F', 'aw-ot@student.tarc.edu.my', '013-8247951', NULL, 7),
(70, 'STUDENT', 2300065, '$2b$12$gfBs2fhM0VCS6keq/CpguOxda0l9a7jWifTKK0nnnSYmiOgpCxRwC', 'ACTIVE', '2025-11-24 09:00:00', 'Nicholas Chan', 'M', 'nc-ot@student.tarc.edu.my', '016-9042376', NULL, 7),
(71, 'STUDENT', 2300066, '$2b$12$iPzMe1SmhtMsBfquaMB/COszlFlDsozij4ib9dwS6NoSuTvlbCbmm', 'ACTIVE', '2025-11-24 09:00:00', 'Rachel Lim', 'F', 'rl-ot@student.tarc.edu.my', '019-6674823', NULL, 7),
(72, 'STUDENT', 2300067, '$2b$12$GcTvMTAdjkjqPhxeKX1y.ezkQJMu5u.5zzhEqOIxct4UCS17w/PJu', 'ACTIVE', '2025-11-24 09:00:00', 'Oscar Yong', 'M', 'oy-ot@student.tarc.edu.my', '011-4298654', NULL, 7),
(73, 'STUDENT', 2300068, '$2b$12$4pTXRux.l70Td2jGbxLsl.n56uahUmhS546K.yKghlNj5lDoxeawe', 'ACTIVE', '2025-11-24 09:00:00', 'Faith Goh', 'F', 'fg-ot@student.tarc.edu.my', '014-7382910', NULL, 7),
(74, 'STUDENT', 2300069, '$2b$12$z3orVzEyNMKAWEJ83MuvjenGcTvmkLzdUmnbHy7orK3QvM.YVjI5K', 'ACTIVE', '2025-11-24 09:00:00', 'Calvin Teh', 'M', 'ct-ot@student.tarc.edu.my', '010-5928473', NULL, 7),
(75, 'STUDENT', 2300070, '$2b$12$F5qjdoINVcQndeZLqA0ZRu3sL7y6h3ERlZjDiD0tbymNqaju7vxvO', 'ACTIVE', '2025-11-24 09:00:00', 'Isabelle Chua', 'F', 'ic-ot@student.tarc.edu.my', '018-2198347', NULL, 7);

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

CREATE TABLE `administrator` (
  `adminID` int(10) NOT NULL,
  `administratorLevel` enum('SUPER_ADMIN','ELECTION_ADMIN','FACULTY_ADMIN') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `accountID` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `administrator`
--

INSERT INTO `administrator` (`adminID`, `administratorLevel`, `department`, `accountID`) VALUES
(1, 'SUPER_ADMIN', 'University IT Services', 1),
(2, 'ELECTION_ADMIN', 'Student Affairs Department (SAD)', 2),
(3, 'ELECTION_ADMIN', 'Student Affairs Department (SAD)', 3),
(4, 'FACULTY_ADMIN', 'Faculty Administrator', 4),
(5, 'FACULTY_ADMIN', 'Faculty Administrator', 5);

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `announcementID` int(10) NOT NULL,
  `announcementTitle` varchar(100) NOT NULL,
  `announcementContent` longtext NOT NULL,
  `announcementCreatedAt` datetime NOT NULL,
  `announcementPublishedAt` datetime DEFAULT NULL,
  `announcementStatus` enum('DRAFT','SCHEDULED','PUBLISHED') NOT NULL,
  `accountID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attachment`
--

CREATE TABLE `attachment` (
  `attachmentID` int(10) NOT NULL,
  `originalFilename` varchar(255) NOT NULL,
  `storedFilename` varchar(255) NOT NULL,
  `fileUrl` longtext NOT NULL,
  `fileType` varchar(255) NOT NULL,
  `fileSize` bigint(20) NOT NULL DEFAULT 0,
  `attachmentUploadedAt` datetime NOT NULL,
  `announcementID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ballot`
--

CREATE TABLE `ballot` (
  `ballotID` char(26) NOT NULL,
  `ballotCreatedAt` datetime NOT NULL,
  `ballotStatus` enum('CREATED','SUBMITTED') NOT NULL DEFAULT 'CREATED',
  `voteSessionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ballotenvelope`
--

CREATE TABLE `ballotenvelope` (
  `ballotEnvelopeID` int(10) NOT NULL,
  `ballotEnvelopeIssuedAt` datetime NOT NULL,
  `ballotEnvelopeSubmittedAt` datetime DEFAULT NULL,
  `ballotEnvelopeStatus` enum('ISSUED','SUBMITTED','EXPIRED') NOT NULL DEFAULT 'ISSUED',
  `receiptCodeHash` char(64) NOT NULL,
  `accountID` int(10) NOT NULL,
  `voteSessionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ballotselection`
--

CREATE TABLE `ballotselection` (
  `selectionID` int(10) NOT NULL,
  `selectedAt` datetime NOT NULL,
  `raceID` int(10) NOT NULL,
  `nomineeID` int(10) NOT NULL,
  `ballotID` char(26) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaignmaterialsapplication`
--

CREATE TABLE `campaignmaterialsapplication` (
  `materialsApplicationID` int(10) NOT NULL,
  `materialsTitle` varchar(100) NOT NULL,
  `materialsType` enum('PHYSICAL','DIGITAL') NOT NULL,
  `materialsDesc` longtext DEFAULT NULL,
  `materialsQuantity` int(100) NOT NULL,
  `materialsApplicationStatus` enum('APPROVED','REJECTED','PENDING') DEFAULT NULL,
  `adminID` int(10) DEFAULT NULL,
  `nomineeID` int(10) NOT NULL,
  `electionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaignmaterialsapplication`
--

INSERT INTO `campaignmaterialsapplication` (`materialsApplicationID`, `materialsTitle`, `materialsType`, `materialsDesc`, `materialsQuantity`, `materialsApplicationStatus`, `adminID`, `nomineeID`, `electionID`) VALUES
(1, 'test2', 'PHYSICAL', 'hehe', 2, 'REJECTED', NULL, 1, 1),
(2, 'kwaki', 'DIGITAL', 'kwaski talk', 1, 'APPROVED', NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `campaignmaterialsdocument`
--

CREATE TABLE `campaignmaterialsdocument` (
  `materialsID` int(10) NOT NULL,
  `materialsFilename` longtext NOT NULL,
  `materialsApplicationID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaignmaterialsdocument`
--

INSERT INTO `campaignmaterialsdocument` (`materialsID`, `materialsFilename`, `materialsApplicationID`) VALUES
(1, 'campaign_material_2.jpg', 1),
(2, 'campaign_material_3.jpeg', 1),
(3, 'campaign_material_2.jpg', 2);

-- --------------------------------------------------------

--
-- Table structure for table `electionevent`
--

CREATE TABLE `electionevent` (
  `electionID` int(10) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `electionStartDate` datetime NOT NULL,
  `electionEndDate` datetime NOT NULL,
  `dateCreated` date NOT NULL,
  `status` enum('PENDING','ONGOING','COMPLETED') NOT NULL,
  `accountID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `electionevent`
--

INSERT INTO `electionevent` (`electionID`, `title`, `description`, `electionStartDate`, `electionEndDate`, `dateCreated`, `status`, `accountID`) VALUES
(1, 'SRC Election Event 2025', 'This is SRC Election Event!!', '2025-11-24 16:10:00', '2025-12-15 17:00:00', '2025-11-24', 'ONGOING', 2),
(2, 'SRC Election Event 2026', 'This is SRC Election Event 2026!', '2026-01-26 16:10:00', '2026-02-09 17:00:00', '2025-11-24', 'PENDING', 2);

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `eventID` int(10) NOT NULL,
  `eventStartDateTime` datetime NOT NULL,
  `eventEndDateTime` datetime NOT NULL,
  `eventApplicationID` int(10) NOT NULL,
  `eventLocationID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`eventID`, `eventStartDateTime`, `eventEndDateTime`, `eventApplicationID`, `eventLocationID`) VALUES
(1, '2025-11-24 17:25:00', '2025-11-24 18:25:00', 1, 1),
(2, '2025-11-24 17:30:00', '2025-11-25 18:25:00', 3, 6);

-- --------------------------------------------------------

--
-- Table structure for table `eventapplication`
--

CREATE TABLE `eventapplication` (
  `eventApplicationID` int(10) NOT NULL,
  `eventName` varchar(255) NOT NULL,
  `eventType` enum('CAMPAIGN','DEBATE') NOT NULL,
  `desiredStartDateTime` datetime NOT NULL,
  `desiredEndDateTime` datetime NOT NULL,
  `eventApplicationStatus` enum('ACCEPTED','REJECTED','PENDING') NOT NULL,
  `eventApplicationSubmittedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `adminID` int(10) DEFAULT NULL,
  `nomineeID` int(10) NOT NULL,
  `electionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eventapplication`
--

INSERT INTO `eventapplication` (`eventApplicationID`, `eventName`, `eventType`, `desiredStartDateTime`, `desiredEndDateTime`, `eventApplicationStatus`, `eventApplicationSubmittedAt`, `adminID`, `nomineeID`, `electionID`) VALUES
(1, 'yong\'s', 'CAMPAIGN', '2025-11-24 17:25:00', '2025-11-24 18:25:00', 'ACCEPTED', '2025-11-24 17:22:29', 2, 1, 1),
(2, 'tan\'s', 'DEBATE', '2025-11-24 17:30:00', '2025-11-25 18:25:00', 'REJECTED', '2025-11-24 17:23:19', NULL, 2, 1),
(3, 'ooooooof', 'CAMPAIGN', '2025-11-24 17:30:00', '2025-11-25 18:25:00', 'ACCEPTED', '2025-11-24 17:23:59', 2, 3, 1),
(4, 'sd', 'CAMPAIGN', '2025-11-27 17:30:00', '2025-11-30 18:25:00', 'PENDING', '2025-11-24 17:24:35', NULL, 6, 1),
(5, 'cassy on self do', 'CAMPAIGN', '2025-11-24 17:30:00', '2025-11-24 18:30:00', 'REJECTED', '2025-11-24 17:28:11', 2, 8, 1),
(6, 'sdsadssd', 'CAMPAIGN', '2025-11-27 17:30:00', '2025-11-30 17:30:00', 'PENDING', '2025-11-24 17:29:00', NULL, 11, 1);

-- --------------------------------------------------------

--
-- Table structure for table `eventlocation`
--

CREATE TABLE `eventlocation` (
  `eventLocationID` int(10) NOT NULL,
  `eventLocationName` varchar(100) NOT NULL,
  `eventLocationStatus` enum('AVAILABLE','NOT AVAILABLE') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eventlocation`
--

INSERT INTO `eventlocation` (`eventLocationID`, `eventLocationName`, `eventLocationStatus`) VALUES
(1, 'Dewan Tunku Abdul Rahman (DTAR)', 'AVAILABLE'),
(2, 'Rimba', 'AVAILABLE'),
(3, 'Yum Yum Cafeteria', 'AVAILABLE'),
(4, 'Red Bricks Cafeteria', 'AVAILABLE'),
(5, 'Sport Complex', 'AVAILABLE'),
(6, 'East Campus Cafeteria', 'AVAILABLE'),
(7, 'Swimming Pool Cafeteria', 'AVAILABLE');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `facultyID` int(10) NOT NULL,
  `facultyCode` varchar(10) NOT NULL,
  `facultyName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`facultyID`, `facultyCode`, `facultyName`) VALUES
(1, 'FOCS', 'Faculty of Computing and Information Technology'),
(2, 'FAFB', 'Faculty of Accountancy, Finance and Business'),
(3, 'FOAS', 'Faculty of Applied Science'),
(4, 'FOBE', 'Faculty of Built Environment'),
(5, 'FOET', 'Faculty of Engineering and Technology'),
(6, 'FCCI', 'Faculty of Communication and Creative Industries'),
(7, 'FSSH', 'Faculty of Social Science and Humanities');

-- --------------------------------------------------------

--
-- Table structure for table `nominee`
--

CREATE TABLE `nominee` (
  `nomineeID` int(10) NOT NULL,
  `manifesto` longtext DEFAULT NULL,
  `raceID` int(10) DEFAULT NULL,
  `accountID` int(10) NOT NULL,
  `electionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nominee`
--

INSERT INTO `nominee` (`nomineeID`, `manifesto`, `raceID`, `accountID`, `electionID`) VALUES
(1, NULL, NULL, 6, 1),
(2, NULL, NULL, 7, 1),
(3, NULL, NULL, 8, 1),
(4, NULL, NULL, 9, 1),
(5, NULL, NULL, 10, 1),
(6, NULL, NULL, 16, 1),
(7, NULL, NULL, 17, 1),
(8, NULL, NULL, 18, 1),
(9, NULL, NULL, 19, 1),
(10, NULL, NULL, 20, 1),
(11, NULL, NULL, 26, 1),
(12, NULL, NULL, 27, 1),
(13, NULL, NULL, 28, 1),
(14, NULL, NULL, 29, 1),
(15, NULL, NULL, 31, 1),
(16, NULL, NULL, 36, 1),
(17, NULL, NULL, 37, 1),
(18, NULL, NULL, 39, 1);

-- --------------------------------------------------------

--
-- Table structure for table `nomineeapplication`
--

CREATE TABLE `nomineeapplication` (
  `nomineeApplicationID` int(10) NOT NULL,
  `submittedDate` date NOT NULL,
  `applicationStatus` enum('ACCEPTED','REJECTED','PENDING','PUBLISHED') DEFAULT NULL,
  `registrationFormID` int(10) NOT NULL,
  `adminID` int(10) DEFAULT NULL,
  `studentID` int(10) NOT NULL,
  `electionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nomineeapplication`
--

INSERT INTO `nomineeapplication` (`nomineeApplicationID`, `submittedDate`, `applicationStatus`, `registrationFormID`, `adminID`, `studentID`, `electionID`) VALUES
(1, '2025-11-24', 'PUBLISHED', 1, 2, 1, 1),
(2, '2025-11-24', 'PUBLISHED', 1, 3, 2, 1),
(3, '2025-11-24', 'PUBLISHED', 1, 2, 3, 1),
(4, '2025-11-24', 'PUBLISHED', 1, 3, 4, 1),
(5, '2025-11-24', 'PUBLISHED', 1, 3, 5, 1),
(6, '2025-11-24', 'REJECTED', 1, 3, 6, 1),
(7, '2025-11-24', 'PUBLISHED', 1, 3, 11, 1),
(8, '2025-11-24', 'PUBLISHED', 1, 3, 12, 1),
(9, '2025-11-24', 'PUBLISHED', 1, 3, 13, 1),
(10, '2025-11-24', 'PUBLISHED', 1, 3, 14, 1),
(11, '2025-11-24', 'PUBLISHED', 1, 3, 15, 1),
(12, '2025-11-24', 'REJECTED', 1, 1, 16, 1),
(13, '2025-11-24', 'PUBLISHED', 1, 2, 21, 1),
(14, '2025-11-24', 'PUBLISHED', 1, 3, 22, 1),
(15, '2025-11-24', 'PUBLISHED', 1, 3, 23, 1),
(16, '2025-11-24', 'PUBLISHED', 1, 3, 24, 1),
(17, '2025-11-24', 'REJECTED', 1, 3, 25, 1),
(18, '2025-11-24', 'PUBLISHED', 1, 3, 26, 1),
(19, '2025-11-24', 'PUBLISHED', 1, 1, 31, 1),
(20, '2025-11-24', 'PUBLISHED', 1, 4, 32, 1),
(21, '2025-11-24', 'REJECTED', 1, 4, 33, 1),
(22, '2025-11-24', 'PUBLISHED', 1, 4, 34, 1);

-- --------------------------------------------------------

--
-- Table structure for table `nomineeapplicationsubmission`
--

CREATE TABLE `nomineeapplicationsubmission` (
  `applicationSubmissionID` int(10) NOT NULL,
  `cgpa` double DEFAULT NULL,
  `reason` longtext DEFAULT NULL,
  `achievements` longtext DEFAULT NULL,
  `behaviorReport` tinyint(1) DEFAULT NULL,
  `nomineeApplicationID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nomineeapplicationsubmission`
--

INSERT INTO `nomineeapplicationsubmission` (`applicationSubmissionID`, `cgpa`, `reason`, `achievements`, `behaviorReport`, `nomineeApplicationID`) VALUES
(1, 4, 'I like to become SRC....', 'AWS\r\nIBM', 1, 1),
(2, 3.7, 'I want become nominee!', 'AWS\r\nIBM', 1, 2),
(3, 2.8, 'i like to become nominee', 'ibm\r\naws', 1, 3),
(4, 2.9, 'hehe i try only', 'ibm', 1, 4),
(5, 3, 'stest', 'test', 1, 5),
(6, 1, 'blablabla', '-', 1, 6),
(7, 4, 'test', 'test', 1, 7),
(8, 3, 'test', 'test', 1, 8),
(9, 2, 'test', 'test', 1, 9),
(10, 3.8, 'test', 'test', 1, 10),
(11, 2.8, 'test', 'test', 1, 11),
(12, 1, 'asas', 'asas', 1, 12),
(13, 4, 't', 't', 1, 13),
(14, 2, 'hehe', 'ee', 1, 14),
(15, 3, '2121', '1212', 1, 15),
(16, 2.9, 'saaskja', 'asashjd', 1, 16),
(17, 3, 'asasasd', 'ddfdf', 1, 17),
(18, 3, 'a', 'a', 1, 18),
(19, 4, 'ses', 'dfdf', 1, 19),
(20, 2.9, 'dsfdg', 'hgjh', 1, 20),
(21, 1, '3234', '3432', 1, 21),
(22, 3.9, 'asasda', 'fdfggh', 1, 22);

-- --------------------------------------------------------

--
-- Table structure for table `race`
--

CREATE TABLE `race` (
  `raceID` int(10) NOT NULL,
  `raceTitle` varchar(100) NOT NULL,
  `seatType` enum('FACULTY_REP','CAMPUS_WIDE') NOT NULL,
  `seatCount` int(11) NOT NULL,
  `maxSelectable` int(11) NOT NULL,
  `electionID` int(10) NOT NULL,
  `facultyID` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrationform`
--

CREATE TABLE `registrationform` (
  `registrationFormID` int(10) NOT NULL,
  `registrationFormTitle` varchar(100) NOT NULL,
  `registerStartDate` datetime NOT NULL,
  `registerEndDate` datetime NOT NULL,
  `dateCreated` date NOT NULL,
  `electionID` int(10) NOT NULL,
  `adminID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrationform`
--

INSERT INTO `registrationform` (`registrationFormID`, `registrationFormTitle`, `registerStartDate`, `registerEndDate`, `dateCreated`, `electionID`, `adminID`) VALUES
(1, 'SRC Election Registration Form 2025', '2025-11-24 16:15:00', '2025-11-24 17:17:53', '2025-11-24', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `registrationformattribute`
--

CREATE TABLE `registrationformattribute` (
  `registrationAttributeID` int(10) NOT NULL,
  `attributeName` varchar(100) NOT NULL,
  `registrationFormID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrationformattribute`
--

INSERT INTO `registrationformattribute` (`registrationAttributeID`, `attributeName`, `registrationFormID`) VALUES
(1, 'cgpa', 1),
(2, 'reason', 1),
(3, 'achievements', 1),
(4, 'behaviorReport', 1);

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `reportID` int(10) NOT NULL,
  `reportName` varchar(100) NOT NULL,
  `reportType` enum('RESULTS_SUMMARY','RACE_BREAKDOWN','TURNOUT','EARLY_VOTE_STATUS') NOT NULL,
  `reportUrl` longtext NOT NULL,
  `reportGeneratedAt` datetime NOT NULL,
  `electionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result`
--

CREATE TABLE `result` (
  `resultID` int(10) NOT NULL,
  `countTotal` int(11) NOT NULL DEFAULT 0,
  `resultStatus` enum('TENTATIVE','FINAL','PUBLISHED','VOID') NOT NULL DEFAULT 'TENTATIVE',
  `voteSessionID` int(10) NOT NULL,
  `raceID` int(10) NOT NULL,
  `nomineeID` int(10) NOT NULL,
  `announcementID` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rule`
--

CREATE TABLE `rule` (
  `ruleID` int(10) NOT NULL,
  `ruleTitle` varchar(100) NOT NULL,
  `content` longtext DEFAULT NULL,
  `dateCreated` date NOT NULL,
  `electionID` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rule`
--

INSERT INTO `rule` (`ruleID`, `ruleTitle`, `content`, `dateCreated`, `electionID`) VALUES
(1, 'SRC Election Event 2025', '(1) The students of the University shall together constitute a body to be known as the Students’ Body.\r\n(2) Students’ Body shall elect a ‘Student Representative Council’.\r\n(3) The Chief Executive / President may make rules for the conduct of elections to the SRC and for all matters related to it.\r\n(4) The SRC will consist of a total of 14 committee members.\r\n(5) The Branch SRC will consist of a total of 8 committee members.\r\n(6) The SRC shall elect from among its members a SRC President, a SRC Vice-President, a SRC\r\nSecretary, a SRC Treasurer and 4 Exco Members, who shall be its only office-bearers\r\n(Appendix A), unless otherwise authorized in writing by the Chief Executive / President, the\r\noffice-bearers so authorized by the Chief Executive / President shall be elected by the SRC\r\nfrom the members of the SRC. The KL SRC President shall be elected form the Campus Wide\r\nRepresentative.\r\n(7) The members of the SRC and its office-bearers shall be elected to hold office for one academic\r\nyear.\r\n(8) The SRC’s election for the above shall be taken by a majority vote with not less than two thirds\r\nof the members being present and voting.\r\n(9) The SRC may from time to time, with the prior approval in writing of the Chief Executive /\r\nPresident, appoint ad hoc committees from among its members for specific purposes or objects.\r\n(10) No student against whom disciplinary proceedings are pending, or who has been found guilty\r\nof a disciplinary offence, shall be elected or remain a member of the SRC or an office-bearer\r\nof any student body or committee, unless authorized in writing by the Chief Executive /\r\nPresident.\r\n(11) The objectives and functions of the SRC shall be:\r\n(a) To foster a spirit of corporate life among the students of the University;\r\n(b) To organize and supervise, subject to the direction of the Chief Executive / President,\r\nstudent welfare facilities and activities in the University including recreational facilities,\r\nspiritual and religious activities, and the supply of meals and refreshments;\r\n(c) To make representations to the Chief Executive / President on all matters relating to, or\r\nconnected with, the living and working conditions of the students of the University;\r\n(d) To be represented on anybody which may, in accordance with the rules made by the Chief\r\nExecutive / President for the purpose, be appointed to undertake student welfare activities\r\nin the University; and\r\n(e) To undertake such other activities as may be determined by the Senior Management\r\nCommittee of the University from time to time.\r\n(12) The Students’ Body or the SRC shall not maintain any fund or make any collection of money\r\nor any property from any source whatever, but such reasonable expenses as the SRC may be\r\nauthorized in advance in writing by the Chief Executive / President to incur may be paid by\r\nthe University where reasonable written claims supported by receipts and vouchers are\r\nsubmitted by the SRC to the Chief Executive / President and are approved by the Chief\r\nExecutive / President.\r\n(13) The Treasurer shall keep proper financial statement of the SRC and not later than three months\r\nafter the end of every financial year, being a financial year as specified by the Chief Executive\r\n/ President, a copy of the said financial statement shall be submitted by the SRC to the Chief\r\nExecutive / President.\r\n(14) The SRC shall hold meetings from time to time as it may deem necessary and it shall be the\r\nduty of the Secretary to keep minutes of every meeting of the SRC and such minutes shall be\r\nconfirmed at a subsequent meeting.\r\n(15) Validity of a registered student shall cease under this section: -\r\n(i) upon the publication of the results of the final examination for such course of study,\r\nif he passes such examination; or\r\n(ii) upon the publication of the results of any examination for such course of study, if he\r\nfails such examination, until he is, thereafter, registered again for that or another\r\ncourse of study applicable to a registered student under this subsection.\r\n(iii) if the student quits, or fails to complete the course of study for any reason\r\nwhatsoever; or is terminated due to disciplinary action.\r\n(16) The election of SRC may be determined by Chief Executive / President of the University by\r\ntheir own manner', '2025-11-24', 1);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `studentID` int(10) NOT NULL,
  `program` varchar(100) NOT NULL,
  `intakeYear` year(4) NOT NULL,
  `accountID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`studentID`, `program`, `intakeYear`, `accountID`) VALUES
(1, 'Bachelor of Software Engineering (Honours)', '2023', 6),
(2, 'Bachelor of Information Technology (Honours) in Software Systems Development', '2023', 7),
(3, 'Bachelor of Information Technology (Honours) in Information Security', '2024', 8),
(4, 'Bachelor of Information Systems (Honours) in Enterprise Information Systems', '2024', 9),
(5, 'Bachelor in Data Science (Honours)', '2025', 10),
(6, 'Bachelor of Computer Science (Honours) in Interactive Software Technology', '2025', 11),
(7, 'Bachelor of Science (Honours) in Management Mathematics with Computing', '2023', 12),
(8, 'Bachelor of Software Engineering (Honours)', '2024', 13),
(9, 'Bachelor in Data Science (Honours)', '2025', 14),
(10, 'Bachelor of Information Technology (Honours) in Information Security', '2023', 15),
(11, 'Bachelor of Accounting (Honours)', '2023', 16),
(12, 'Bachelor of Commerce (Honours)', '2023', 17),
(13, 'Bachelor of Business Administration (Honours)', '2024', 18),
(14, 'Bachelor of Business (Honours) in Accounting and Finance', '2024', 19),
(15, 'Bachelor of Finance and Investment (Honours)', '2025', 20),
(16, 'Bachelor of Banking and Finance (Honours)', '2025', 21),
(17, 'Bachelor of Economics (Honours)', '2023', 22),
(18, 'Bachelor of Business (Honours) in Business Analytics', '2024', 23),
(19, 'Bachelor of Business (Honours) in Marketing', '2025', 24),
(20, 'Bachelor of Business (Honours) in Human Resource Management', '2023', 25),
(21, 'BSc (Honours) in Sports and Exercise Science', '2023', 26),
(22, 'BSc (Honours) in Sports Coaching and Performance Analysis', '2023', 27),
(23, 'BSc (Honours) in Applied Physics (Instrumentation)', '2024', 28),
(24, 'BSc (Honours) in Bioscience with Chemistry', '2024', 29),
(25, 'BSc (Honours) in Analytical Chemistry', '2025', 30),
(26, 'BSc (Honours) in Food Science', '2025', 31),
(27, 'BSc (Honours) in Nutrition', '2023', 32),
(28, 'BSc (Honours) in Sports and Exercise Science', '2024', 33),
(29, 'BSc (Honours) in Sports Coaching and Performance Analysis', '2025', 34),
(30, 'BSc (Honours) in Applied Physics (Instrumentation)', '2023', 35),
(31, 'Bachelor of Science in Architecture (Honours)', '2023', 36),
(32, 'Bachelor of Interior Architecture (Honours)', '2023', 37),
(33, 'Bachelor of Quantity Surveying (Honours)', '2024', 38),
(34, 'Bachelor of Real Estate Management (Honours)', '2024', 39),
(35, 'Bachelor of Construction Management and Economics (Honours)', '2025', 40),
(36, 'Bachelor of Science in Architecture (Honours)', '2025', 41),
(37, 'Bachelor of Interior Architecture (Honours)', '2023', 42),
(38, 'Bachelor of Quantity Surveying (Honours)', '2024', 43),
(39, 'Bachelor of Real Estate Management (Honours)', '2025', 44),
(40, 'Bachelor of Construction Management and Economics (Honours)', '2023', 45),
(41, 'Bachelor of Electrical and Electronics Engineering with Honours', '2023', 46),
(42, 'Bachelor of Mechanical Engineering with Honours', '2023', 47),
(43, 'Bachelor of Mechatronics Engineering with Honours', '2024', 48),
(44, 'Bachelor of Materials and Manufacturing Technology with Honours', '2024', 49),
(45, 'Bachelor of Electronics Engineering Technology with Honours', '2025', 50),
(46, 'Bachelor of Manufacturing and Industrial Technology with Honours', '2025', 51),
(47, 'Bachelor of Electrical and Electronics Engineering with Honours', '2023', 52),
(48, 'Bachelor of Mechanical Engineering with Honours', '2024', 53),
(49, 'Bachelor of Mechatronics Engineering with Honours', '2025', 54),
(50, 'Bachelor of Materials and Manufacturing Technology with Honours', '2023', 55),
(51, 'Bachelor of Communication (Honours) in Advertising', '2023', 56),
(52, 'Bachelor of Communication (Honours) in Broadcasting', '2023', 57),
(53, 'Bachelor of Communication (Honours) in Journalism', '2024', 58),
(54, 'Bachelor of Communication (Honours) in Media Studies / Communication Studies', '2024', 59),
(55, 'Bachelor of Public Relations (Honours)', '2025', 60),
(56, 'Bachelor of Creative Multimedia (Honours)', '2025', 61),
(57, 'Bachelor of Design (Honours) in Graphic Design', '2023', 62),
(58, 'Bachelor of Design (Honours) in Fashion Design', '2024', 63),
(59, 'Bachelor of Communication (Honours) in Advertising', '2025', 64),
(60, 'Bachelor of Communication (Honours) in Broadcasting', '2023', 65),
(61, 'Bachelor of Hospitality Management (Honours)', '2023', 66),
(62, 'Bachelor of Hospitality and Catering Management (Honours)', '2023', 67),
(63, 'Bachelor of Tourism Management (Honours)', '2024', 68),
(64, 'Tourism Management (Honours) in Event Management', '2024', 69),
(65, 'Bachelor of Social Science (Honours) in Psychology', '2025', 70),
(66, 'Bachelor of Early Childhood Education (Honours)', '2025', 71),
(67, 'Bachelor of Gastropreneurship (Honours)', '2023', 72),
(68, 'Bachelor of Arts (Honours) English with Drama', '2024', 73),
(69, 'Bachelor of Arts (Honours) English with Education', '2025', 74),
(70, 'Bachelor of Arts in English Studies (Honours)', '2023', 75);

-- --------------------------------------------------------

--
-- Table structure for table `votesession`
--

CREATE TABLE `votesession` (
  `voteSessionID` int(10) NOT NULL,
  `voteSessionName` varchar(100) NOT NULL,
  `voteSessionType` enum('EARLY','MAIN') NOT NULL,
  `voteSessionStartAt` datetime NOT NULL,
  `voteSessionEndAt` datetime NOT NULL,
  `voteSessionStatus` enum('DRAFT','SCHEDULED','OPEN','CLOSED','CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
  `electionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `votesession_race`
--

CREATE TABLE `votesession_race` (
  `id` int(10) NOT NULL,
  `voteSessionID` int(10) NOT NULL,
  `raceID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academicdocument`
--
ALTER TABLE `academicdocument`
  ADD PRIMARY KEY (`academicID`),
  ADD KEY `academicdocument_ibfk_1` (`applicationSubmissionID`);

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`accountID`),
  ADD KEY `account_ibfk_1` (`facultyID`);

--
-- Indexes for table `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`adminID`),
  ADD KEY `administrator_ibfk_1` (`accountID`);

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`announcementID`),
  ADD KEY `announcement_ibfk_1` (`accountID`);

--
-- Indexes for table `attachment`
--
ALTER TABLE `attachment`
  ADD PRIMARY KEY (`attachmentID`),
  ADD KEY `attachment_ibfk_1` (`announcementID`);

--
-- Indexes for table `ballot`
--
ALTER TABLE `ballot`
  ADD PRIMARY KEY (`ballotID`),
  ADD KEY `ballot_ibfk_1` (`voteSessionID`);

--
-- Indexes for table `ballotenvelope`
--
ALTER TABLE `ballotenvelope`
  ADD PRIMARY KEY (`ballotEnvelopeID`),
  ADD KEY `ballotenvelope_ibfk_1` (`accountID`),
  ADD KEY `ballotenvelope_ibfk_2` (`voteSessionID`);

--
-- Indexes for table `ballotselection`
--
ALTER TABLE `ballotselection`
  ADD PRIMARY KEY (`selectionID`),
  ADD KEY `ballotselection_ibfk_1` (`raceID`),
  ADD KEY `ballotselection_ibfk_2` (`nomineeID`),
  ADD KEY `ballotselection_ibfk_3` (`ballotID`);

--
-- Indexes for table `campaignmaterialsapplication`
--
ALTER TABLE `campaignmaterialsapplication`
  ADD PRIMARY KEY (`materialsApplicationID`),
  ADD KEY `campaignmaterialsapplication_ibfk_1` (`adminID`),
  ADD KEY `campaignmaterialsapplication_ibfk_2` (`nomineeID`),
  ADD KEY `campaignmaterialsapplication_ibfk_3` (`electionID`);

--
-- Indexes for table `campaignmaterialsdocument`
--
ALTER TABLE `campaignmaterialsdocument`
  ADD PRIMARY KEY (`materialsID`),
  ADD KEY `campaignmaterialsdocument_ibfk_1` (`materialsApplicationID`);

--
-- Indexes for table `electionevent`
--
ALTER TABLE `electionevent`
  ADD PRIMARY KEY (`electionID`),
  ADD KEY `electionevent_ibfk_1` (`accountID`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`eventID`),
  ADD KEY `event_ibfk_1` (`eventApplicationID`),
  ADD KEY `event_ibfk_2` (`eventLocationID`);

--
-- Indexes for table `eventapplication`
--
ALTER TABLE `eventapplication`
  ADD PRIMARY KEY (`eventApplicationID`),
  ADD KEY `eventapplication_ibfk_1` (`adminID`),
  ADD KEY `eventapplication_ibfk_2` (`nomineeID`),
  ADD KEY `eventapplication_ibfk_3` (`electionID`);

--
-- Indexes for table `eventlocation`
--
ALTER TABLE `eventlocation`
  ADD PRIMARY KEY (`eventLocationID`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`facultyID`);

--
-- Indexes for table `nominee`
--
ALTER TABLE `nominee`
  ADD PRIMARY KEY (`nomineeID`),
  ADD KEY `nominee_ibfk_1` (`raceID`),
  ADD KEY `nominee_ibfk_2` (`accountID`),
  ADD KEY `nominee_ibfk_3` (`electionID`);

--
-- Indexes for table `nomineeapplication`
--
ALTER TABLE `nomineeapplication`
  ADD PRIMARY KEY (`nomineeApplicationID`),
  ADD KEY `nomineeapplication_ibfk_1` (`registrationFormID`),
  ADD KEY `nomineeapplication_ibfk_2` (`adminID`),
  ADD KEY `nomineeapplication_ibfk_3` (`studentID`),
  ADD KEY `nomineeapplication_ibfk_4` (`electionID`);

--
-- Indexes for table `nomineeapplicationsubmission`
--
ALTER TABLE `nomineeapplicationsubmission`
  ADD PRIMARY KEY (`applicationSubmissionID`),
  ADD KEY `nomineesubmissionapplicationid_ibfk_1` (`nomineeApplicationID`);

--
-- Indexes for table `race`
--
ALTER TABLE `race`
  ADD PRIMARY KEY (`raceID`),
  ADD KEY `race_ibfk_1` (`electionID`),
  ADD KEY `race_ibfk_2` (`facultyID`);

--
-- Indexes for table `registrationform`
--
ALTER TABLE `registrationform`
  ADD PRIMARY KEY (`registrationFormID`),
  ADD KEY `registrationform_ibfk_1` (`electionID`),
  ADD KEY `registrationform_ibfk_2` (`adminID`);

--
-- Indexes for table `registrationformattribute`
--
ALTER TABLE `registrationformattribute`
  ADD PRIMARY KEY (`registrationAttributeID`),
  ADD KEY `registrationformattribute_ibfk_1` (`registrationFormID`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`reportID`),
  ADD KEY `report_ibfk_1` (`electionID`);

--
-- Indexes for table `result`
--
ALTER TABLE `result`
  ADD PRIMARY KEY (`resultID`),
  ADD KEY `result_ibfk_1` (`voteSessionID`),
  ADD KEY `result_ibfk_2` (`raceID`),
  ADD KEY `result_ibfk_3` (`nomineeID`),
  ADD KEY `result_ibfk_4` (`announcementID`);

--
-- Indexes for table `rule`
--
ALTER TABLE `rule`
  ADD PRIMARY KEY (`ruleID`),
  ADD KEY `rule_ibfk_1` (`electionID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`studentID`),
  ADD KEY `student_ibfk_1` (`accountID`);

--
-- Indexes for table `votesession`
--
ALTER TABLE `votesession`
  ADD PRIMARY KEY (`voteSessionID`),
  ADD KEY `votesession_ibfk_1` (`electionID`);

--
-- Indexes for table `votesession_race`
--
ALTER TABLE `votesession_race`
  ADD PRIMARY KEY (`id`),
  ADD KEY `votesession_race_ibfk_1` (`raceID`),
  ADD KEY `votesession_race_ibfk_2` (`voteSessionID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academicdocument`
--
ALTER TABLE `academicdocument`
  MODIFY `academicID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `accountID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `administrator`
--
ALTER TABLE `administrator`
  MODIFY `adminID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `announcementID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attachment`
--
ALTER TABLE `attachment`
  MODIFY `attachmentID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ballotenvelope`
--
ALTER TABLE `ballotenvelope`
  MODIFY `ballotEnvelopeID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ballotselection`
--
ALTER TABLE `ballotselection`
  MODIFY `selectionID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaignmaterialsapplication`
--
ALTER TABLE `campaignmaterialsapplication`
  MODIFY `materialsApplicationID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `campaignmaterialsdocument`
--
ALTER TABLE `campaignmaterialsdocument`
  MODIFY `materialsID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `electionevent`
--
ALTER TABLE `electionevent`
  MODIFY `electionID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `eventID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `eventapplication`
--
ALTER TABLE `eventapplication`
  MODIFY `eventApplicationID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `eventlocation`
--
ALTER TABLE `eventlocation`
  MODIFY `eventLocationID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `facultyID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `nominee`
--
ALTER TABLE `nominee`
  MODIFY `nomineeID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `nomineeapplication`
--
ALTER TABLE `nomineeapplication`
  MODIFY `nomineeApplicationID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `nomineeapplicationsubmission`
--
ALTER TABLE `nomineeapplicationsubmission`
  MODIFY `applicationSubmissionID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `race`
--
ALTER TABLE `race`
  MODIFY `raceID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrationform`
--
ALTER TABLE `registrationform`
  MODIFY `registrationFormID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `registrationformattribute`
--
ALTER TABLE `registrationformattribute`
  MODIFY `registrationAttributeID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `reportID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `result`
--
ALTER TABLE `result`
  MODIFY `resultID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rule`
--
ALTER TABLE `rule`
  MODIFY `ruleID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `studentID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `votesession`
--
ALTER TABLE `votesession`
  MODIFY `voteSessionID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `votesession_race`
--
ALTER TABLE `votesession_race`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academicdocument`
--
ALTER TABLE `academicdocument`
  ADD CONSTRAINT `academicdocument_ibfk_1` FOREIGN KEY (`applicationSubmissionID`) REFERENCES `nomineeapplicationsubmission` (`applicationSubmissionID`);

--
-- Constraints for table `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_1` FOREIGN KEY (`facultyID`) REFERENCES `faculty` (`facultyID`);

--
-- Constraints for table `administrator`
--
ALTER TABLE `administrator`
  ADD CONSTRAINT `administrator_ibfk_1` FOREIGN KEY (`accountID`) REFERENCES `account` (`accountID`);

--
-- Constraints for table `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `announcement_ibfk_1` FOREIGN KEY (`accountID`) REFERENCES `account` (`accountID`);

--
-- Constraints for table `attachment`
--
ALTER TABLE `attachment`
  ADD CONSTRAINT `attachment_ibfk_1` FOREIGN KEY (`announcementID`) REFERENCES `announcement` (`announcementID`);

--
-- Constraints for table `ballot`
--
ALTER TABLE `ballot`
  ADD CONSTRAINT `ballot_ibfk_1` FOREIGN KEY (`voteSessionID`) REFERENCES `votesession` (`voteSessionID`);

--
-- Constraints for table `ballotenvelope`
--
ALTER TABLE `ballotenvelope`
  ADD CONSTRAINT `ballotenvelope_ibfk_1` FOREIGN KEY (`accountID`) REFERENCES `account` (`accountID`),
  ADD CONSTRAINT `ballotenvelope_ibfk_2` FOREIGN KEY (`voteSessionID`) REFERENCES `votesession` (`voteSessionID`);

--
-- Constraints for table `ballotselection`
--
ALTER TABLE `ballotselection`
  ADD CONSTRAINT `ballotselection_ibfk_1` FOREIGN KEY (`raceID`) REFERENCES `race` (`raceID`),
  ADD CONSTRAINT `ballotselection_ibfk_2` FOREIGN KEY (`nomineeID`) REFERENCES `nominee` (`nomineeID`),
  ADD CONSTRAINT `ballotselection_ibfk_3` FOREIGN KEY (`ballotID`) REFERENCES `ballot` (`ballotID`);

--
-- Constraints for table `campaignmaterialsapplication`
--
ALTER TABLE `campaignmaterialsapplication`
  ADD CONSTRAINT `campaignmaterialsapplication_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `administrator` (`adminID`),
  ADD CONSTRAINT `campaignmaterialsapplication_ibfk_2` FOREIGN KEY (`nomineeID`) REFERENCES `nominee` (`nomineeID`),
  ADD CONSTRAINT `campaignmaterialsapplication_ibfk_3` FOREIGN KEY (`electionID`) REFERENCES `electionevent` (`electionID`);

--
-- Constraints for table `campaignmaterialsdocument`
--
ALTER TABLE `campaignmaterialsdocument`
  ADD CONSTRAINT `campaignmaterialsdocument_ibfk_1` FOREIGN KEY (`materialsApplicationID`) REFERENCES `campaignmaterialsapplication` (`materialsApplicationID`);

--
-- Constraints for table `electionevent`
--
ALTER TABLE `electionevent`
  ADD CONSTRAINT `electionevent_ibfk_1` FOREIGN KEY (`accountID`) REFERENCES `account` (`accountID`);

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`eventApplicationID`) REFERENCES `eventapplication` (`eventApplicationID`),
  ADD CONSTRAINT `event_ibfk_2` FOREIGN KEY (`eventLocationID`) REFERENCES `eventlocation` (`eventLocationID`);

--
-- Constraints for table `eventapplication`
--
ALTER TABLE `eventapplication`
  ADD CONSTRAINT `eventapplication_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `administrator` (`adminID`),
  ADD CONSTRAINT `eventapplication_ibfk_2` FOREIGN KEY (`nomineeID`) REFERENCES `nominee` (`nomineeID`),
  ADD CONSTRAINT `eventapplication_ibfk_3` FOREIGN KEY (`electionID`) REFERENCES `electionevent` (`electionID`);

--
-- Constraints for table `nominee`
--
ALTER TABLE `nominee`
  ADD CONSTRAINT `nominee_ibfk_1` FOREIGN KEY (`raceID`) REFERENCES `race` (`raceID`),
  ADD CONSTRAINT `nominee_ibfk_2` FOREIGN KEY (`accountID`) REFERENCES `account` (`accountID`),
  ADD CONSTRAINT `nominee_ibfk_3` FOREIGN KEY (`electionID`) REFERENCES `electionevent` (`electionID`);

--
-- Constraints for table `nomineeapplication`
--
ALTER TABLE `nomineeapplication`
  ADD CONSTRAINT `nomineeapplication_ibfk_1` FOREIGN KEY (`registrationFormID`) REFERENCES `registrationform` (`registrationFormID`),
  ADD CONSTRAINT `nomineeapplication_ibfk_2` FOREIGN KEY (`adminID`) REFERENCES `administrator` (`adminID`),
  ADD CONSTRAINT `nomineeapplication_ibfk_3` FOREIGN KEY (`studentID`) REFERENCES `student` (`studentID`),
  ADD CONSTRAINT `nomineeapplication_ibfk_4` FOREIGN KEY (`electionID`) REFERENCES `electionevent` (`electionID`);

--
-- Constraints for table `nomineeapplicationsubmission`
--
ALTER TABLE `nomineeapplicationsubmission`
  ADD CONSTRAINT `nomineesubmissionapplicationid_ibfk_1` FOREIGN KEY (`nomineeApplicationID`) REFERENCES `nomineeapplication` (`nomineeApplicationID`);

--
-- Constraints for table `race`
--
ALTER TABLE `race`
  ADD CONSTRAINT `race_ibfk_1` FOREIGN KEY (`electionID`) REFERENCES `electionevent` (`electionID`),
  ADD CONSTRAINT `race_ibfk_2` FOREIGN KEY (`facultyID`) REFERENCES `faculty` (`facultyID`);

--
-- Constraints for table `registrationform`
--
ALTER TABLE `registrationform`
  ADD CONSTRAINT `registrationform_ibfk_1` FOREIGN KEY (`electionID`) REFERENCES `electionevent` (`electionID`),
  ADD CONSTRAINT `registrationform_ibfk_2` FOREIGN KEY (`adminID`) REFERENCES `administrator` (`adminID`);

--
-- Constraints for table `registrationformattribute`
--
ALTER TABLE `registrationformattribute`
  ADD CONSTRAINT `registrationformattribute_ibfk_1` FOREIGN KEY (`registrationFormID`) REFERENCES `registrationform` (`registrationFormID`);

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`electionID`) REFERENCES `electionevent` (`electionID`);

--
-- Constraints for table `result`
--
ALTER TABLE `result`
  ADD CONSTRAINT `result_ibfk_1` FOREIGN KEY (`voteSessionID`) REFERENCES `votesession` (`voteSessionID`),
  ADD CONSTRAINT `result_ibfk_2` FOREIGN KEY (`raceID`) REFERENCES `race` (`raceID`),
  ADD CONSTRAINT `result_ibfk_3` FOREIGN KEY (`nomineeID`) REFERENCES `nominee` (`nomineeID`),
  ADD CONSTRAINT `result_ibfk_4` FOREIGN KEY (`announcementID`) REFERENCES `announcement` (`announcementID`);

--
-- Constraints for table `rule`
--
ALTER TABLE `rule`
  ADD CONSTRAINT `rule_ibfk_1` FOREIGN KEY (`electionID`) REFERENCES `electionevent` (`electionID`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`accountID`) REFERENCES `account` (`accountID`);

--
-- Constraints for table `votesession`
--
ALTER TABLE `votesession`
  ADD CONSTRAINT `votesession_ibfk_1` FOREIGN KEY (`electionID`) REFERENCES `electionevent` (`electionID`);

--
-- Constraints for table `votesession_race`
--
ALTER TABLE `votesession_race`
  ADD CONSTRAINT `votesession_race_ibfk_1` FOREIGN KEY (`raceID`) REFERENCES `race` (`raceID`),
  ADD CONSTRAINT `votesession_race_ibfk_2` FOREIGN KEY (`voteSessionID`) REFERENCES `votesession` (`voteSessionID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
