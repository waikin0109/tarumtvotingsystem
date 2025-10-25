-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 01:28 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `academicdocument`
--

CREATE TABLE `academicdocument` (
  `academicID` int(10) NOT NULL,
  `academicFilename` longtext NOT NULL,
  `nomineeApplicationID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `accountID` int(10) NOT NULL,
  `role` enum('STUDENT','NOMINEE','ADMIN') NOT NULL,
  `loginID` int(8) NOT NULL,
  `passwordHash` varchar(255) NOT NULL,
  `passwordSalt` varbinary(32) NOT NULL,
  `status` enum('ACTIVE','SUSPENDED','DEACTIVATED') NOT NULL DEFAULT 'ACTIVE',
  `lastLoginAt` datetime NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `gender` enum('M','F') NOT NULL,
  `email` varchar(100) NOT NULL,
  `phoneNumber` varchar(20) NOT NULL,
  `profilePhotoURL` longtext NOT NULL,
  `facultyID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `accountID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attachment`
--

CREATE TABLE `attachment` (
  `attachmentID` int(10) NOT NULL,
  `fileUrl` longtext NOT NULL,
  `fileType` varchar(255) NOT NULL,
  `attachmentUploadedAt` datetime NOT NULL,
  `announcementID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditlog`
--

CREATE TABLE `auditlog` (
  `auditID` int(10) NOT NULL,
  `authorType` enum('STUDENT','NOMINEE','ADMIN','SYSTEM') NOT NULL,
  `action` longtext NOT NULL,
  `accountID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ballot`
--

CREATE TABLE `ballot` (
  `ballotID` char(26) NOT NULL,
  `ballotCreatedAt` datetime NOT NULL,
  `ballotStatus` enum('CREATED','SUBMITTED','VOID') NOT NULL DEFAULT 'CREATED',
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
  `ballotEnvelopeStatus` enum('ISSUED','SUBMITTED','VOID','EXPIRED') NOT NULL DEFAULT 'ISSUED',
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
  `adminID` int(10) NOT NULL,
  `nomineeID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaignmaterialsdocument`
--

CREATE TABLE `campaignmaterialsdocument` (
  `materialsID` int(10) NOT NULL,
  `materialsFilename` longtext NOT NULL,
  `materialApplicationID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `eventID` int(10) NOT NULL,
  `eventDateTime` datetime NOT NULL,
  `eventApplicationID` int(10) NOT NULL,
  `eventLocationID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eventapplication`
--

CREATE TABLE `eventapplication` (
  `eventApplicationID` int(10) NOT NULL,
  `eventName` varchar(255) NOT NULL,
  `eventType` enum('CAMPAIGN','DEBATE') NOT NULL,
  `desiredDateTime` datetime NOT NULL,
  `eventApplicationStatus` enum('ACCEPTED','REJECTED','PENDING') NOT NULL,
  `adminID` int(10) NOT NULL,
  `nomineeID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eventlocation`
--

CREATE TABLE `eventlocation` (
  `eventLocationID` int(10) NOT NULL,
  `eventLocationName` varchar(100) NOT NULL,
  `eventLocationStatus` enum('AVAILABLE','NOT AVAILABLE') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `facultyID` int(10) NOT NULL,
  `facultyCode` varchar(10) NOT NULL,
  `facultyName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nominee`
--

CREATE TABLE `nominee` (
  `nomineeID` int(10) NOT NULL,
  `manifesto` longtext NOT NULL,
  `materialsApprovedQuantity` int(11) NOT NULL DEFAULT 0,
  `withdrawnAt` datetime DEFAULT NULL,
  `raceID` int(10) NOT NULL,
  `accountID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nomineeapplication`
--

CREATE TABLE `nomineeapplication` (
  `nomineeApplicationID` int(10) NOT NULL,
  `submittedDate` date NOT NULL,
  `applicationStatus` enum('ACCEPTED','REJECTED','PENDING') DEFAULT NULL,
  `registrationFormID` int(10) NOT NULL,
  `adminID` int(10) NOT NULL,
  `studentID` int(10) NOT NULL,
  `electionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `registrationattributecatalogue`
--

CREATE TABLE `registrationattributecatalogue` (
  `registrationCatalogueID` int(10) NOT NULL,
  `catalogueName` varchar(100) NOT NULL,
  `catalogueDataType` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrationform`
--

CREATE TABLE `registrationform` (
  `registrationFormID` int(10) NOT NULL,
  `registerStartDate` datetime NOT NULL,
  `registerEndDate` datetime NOT NULL,
  `dateCreated` date NOT NULL,
  `electionID` int(10) NOT NULL,
  `adminID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrationformattribute`
--

CREATE TABLE `registrationformattribute` (
  `registrationAttributeID` int(10) NOT NULL,
  `isRequired` tinyint(1) NOT NULL,
  `registrationFormID` int(10) NOT NULL,
  `registrationCatalogueID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `reportID` int(10) NOT NULL,
  `reportName` varchar(100) NOT NULL,
  `reportType` enum('RESULTS_SUMMARY','RACE_BREAKDOWN','TURNOUT','AUDIT_LOG','EARLY_VOTE_STATUS') NOT NULL,
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
  `voteSessionStatus` enum('SCHEDULED','OPEN','LOCKED','CLOSED','CANCELED') NOT NULL DEFAULT 'SCHEDULED',
  `electionID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academicdocument`
--
ALTER TABLE `academicdocument`
  ADD PRIMARY KEY (`academicID`),
  ADD KEY `academicdocument_ibfk_1` (`nomineeApplicationID`);

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
-- Indexes for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD PRIMARY KEY (`auditID`),
  ADD KEY `auditlog_ibfk_1` (`accountID`);

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
  ADD KEY `campaignmaterialsapplication_ibfk_2` (`nomineeID`);

--
-- Indexes for table `campaignmaterialsdocument`
--
ALTER TABLE `campaignmaterialsdocument`
  ADD PRIMARY KEY (`materialsID`),
  ADD KEY `campaignmaterialsdocument_ibfk_1` (`materialApplicationID`);

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
  ADD KEY `eventapplication_ibfk_2` (`nomineeID`);

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
  ADD KEY `nominee_ibfk_2` (`accountID`);

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
-- Indexes for table `race`
--
ALTER TABLE `race`
  ADD PRIMARY KEY (`raceID`),
  ADD KEY `race_ibfk_1` (`electionID`),
  ADD KEY `race_ibfk_2` (`facultyID`);

--
-- Indexes for table `registrationattributecatalogue`
--
ALTER TABLE `registrationattributecatalogue`
  ADD PRIMARY KEY (`registrationCatalogueID`);

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
  ADD KEY `registrationformattribute_ibfk_1` (`registrationFormID`),
  ADD KEY `registrationformattribute_ibfk_2` (`registrationCatalogueID`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academicdocument`
--
ALTER TABLE `academicdocument`
  MODIFY `academicID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `accountID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `administrator`
--
ALTER TABLE `administrator`
  MODIFY `adminID` int(10) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `auditlog`
--
ALTER TABLE `auditlog`
  MODIFY `auditID` int(10) NOT NULL AUTO_INCREMENT;

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
  MODIFY `materialsApplicationID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaignmaterialsdocument`
--
ALTER TABLE `campaignmaterialsdocument`
  MODIFY `materialsID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `electionevent`
--
ALTER TABLE `electionevent`
  MODIFY `electionID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `eventID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eventapplication`
--
ALTER TABLE `eventapplication`
  MODIFY `eventApplicationID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eventlocation`
--
ALTER TABLE `eventlocation`
  MODIFY `eventLocationID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `facultyID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nominee`
--
ALTER TABLE `nominee`
  MODIFY `nomineeID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nomineeapplication`
--
ALTER TABLE `nomineeapplication`
  MODIFY `nomineeApplicationID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `race`
--
ALTER TABLE `race`
  MODIFY `raceID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrationattributecatalogue`
--
ALTER TABLE `registrationattributecatalogue`
  MODIFY `registrationCatalogueID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrationform`
--
ALTER TABLE `registrationform`
  MODIFY `registrationFormID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrationformattribute`
--
ALTER TABLE `registrationformattribute`
  MODIFY `registrationAttributeID` int(10) NOT NULL AUTO_INCREMENT;

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
  MODIFY `ruleID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `studentID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `votesession`
--
ALTER TABLE `votesession`
  MODIFY `voteSessionID` int(10) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academicdocument`
--
ALTER TABLE `academicdocument`
  ADD CONSTRAINT `academicdocument_ibfk_1` FOREIGN KEY (`NomineeApplicationID`) REFERENCES `nomineeapplication` (`NomineeApplicationID`);

--
-- Constraints for table `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_1` FOREIGN KEY (`FacultyID`) REFERENCES `faculty` (`FacultyID`);

--
-- Constraints for table `administrator`
--
ALTER TABLE `administrator`
  ADD CONSTRAINT `administrator_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`);

--
-- Constraints for table `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `announcement_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`);

--
-- Constraints for table `attachment`
--
ALTER TABLE `attachment`
  ADD CONSTRAINT `attachment_ibfk_1` FOREIGN KEY (`AnnouncementID`) REFERENCES `announcement` (`AnnouncementID`);

--
-- Constraints for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD CONSTRAINT `auditlog_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`);

--
-- Constraints for table `ballot`
--
ALTER TABLE `ballot`
  ADD CONSTRAINT `ballot_ibfk_1` FOREIGN KEY (`VoteSessionID`) REFERENCES `votesession` (`VoteSessionID`);

--
-- Constraints for table `ballotenvelope`
--
ALTER TABLE `ballotenvelope`
  ADD CONSTRAINT `ballotenvelope_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`),
  ADD CONSTRAINT `ballotenvelope_ibfk_2` FOREIGN KEY (`VoteSessionID`) REFERENCES `votesession` (`VoteSessionID`);

--
-- Constraints for table `ballotselection`
--
ALTER TABLE `ballotselection`
  ADD CONSTRAINT `ballotselection_ibfk_1` FOREIGN KEY (`RaceID`) REFERENCES `race` (`RaceID`),
  ADD CONSTRAINT `ballotselection_ibfk_2` FOREIGN KEY (`NomineeID`) REFERENCES `nominee` (`NomineeID`),
  ADD CONSTRAINT `ballotselection_ibfk_3` FOREIGN KEY (`BallotID`) REFERENCES `ballot` (`BallotID`);

--
-- Constraints for table `campaignmaterialsapplication`
--
ALTER TABLE `campaignmaterialsapplication`
  ADD CONSTRAINT `campaignmaterialsapplication_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `administrator` (`AdminID`),
  ADD CONSTRAINT `campaignmaterialsapplication_ibfk_2` FOREIGN KEY (`NomineeID`) REFERENCES `nominee` (`NomineeID`);

--
-- Constraints for table `campaignmaterialsdocument`
--
ALTER TABLE `campaignmaterialsdocument`
  ADD CONSTRAINT `campaignmaterialsdocument_ibfk_1` FOREIGN KEY (`MaterialApplicationID`) REFERENCES `campaignmaterialsapplication` (`MaterialsApplicationID`);

--
-- Constraints for table `electionevent`
--
ALTER TABLE `electionevent`
  ADD CONSTRAINT `electionevent_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`);

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`EventApplicationID`) REFERENCES `eventapplication` (`EventApplicationID`),
  ADD CONSTRAINT `event_ibfk_2` FOREIGN KEY (`EventLocationID`) REFERENCES `eventlocation` (`EventLocationID`);

--
-- Constraints for table `eventapplication`
--
ALTER TABLE `eventapplication`
  ADD CONSTRAINT `eventapplication_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `administrator` (`AdminID`),
  ADD CONSTRAINT `eventapplication_ibfk_2` FOREIGN KEY (`NomineeID`) REFERENCES `nominee` (`NomineeID`);

--
-- Constraints for table `nominee`
--
ALTER TABLE `nominee`
  ADD CONSTRAINT `nominee_ibfk_1` FOREIGN KEY (`RaceID`) REFERENCES `race` (`RaceID`),
  ADD CONSTRAINT `nominee_ibfk_2` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`);

--
-- Constraints for table `nomineeapplication`
--
ALTER TABLE `nomineeapplication`
  ADD CONSTRAINT `nomineeapplication_ibfk_1` FOREIGN KEY (`RegistrationFormID`) REFERENCES `registrationform` (`RegistrationFormID`),
  ADD CONSTRAINT `nomineeapplication_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `administrator` (`AdminID`),
  ADD CONSTRAINT `nomineeapplication_ibfk_3` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `nomineeapplication_ibfk_4` FOREIGN KEY (`ElectionID`) REFERENCES `electionevent` (`ElectionID`);

--
-- Constraints for table `race`
--
ALTER TABLE `race`
  ADD CONSTRAINT `race_ibfk_1` FOREIGN KEY (`ElectionID`) REFERENCES `electionevent` (`ElectionID`),
  ADD CONSTRAINT `race_ibfk_2` FOREIGN KEY (`FacultyID`) REFERENCES `faculty` (`FacultyID`);

--
-- Constraints for table `registrationform`
--
ALTER TABLE `registrationform`
  ADD CONSTRAINT `registrationform_ibfk_1` FOREIGN KEY (`ElectionID`) REFERENCES `electionevent` (`ElectionID`),
  ADD CONSTRAINT `registrationform_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `administrator` (`AdminID`);

--
-- Constraints for table `registrationformattribute`
--
ALTER TABLE `registrationformattribute`
  ADD CONSTRAINT `registrationformattribute_ibfk_1` FOREIGN KEY (`RegistrationFormID`) REFERENCES `registrationform` (`RegistrationFormID`),
  ADD CONSTRAINT `registrationformattribute_ibfk_2` FOREIGN KEY (`RegistrationCatalogueID`) REFERENCES `registrationattributecatalogue` (`RegistrationCatalogueID`);

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`ElectionID`) REFERENCES `electionevent` (`ElectionID`);

--
-- Constraints for table `result`
--
ALTER TABLE `result`
  ADD CONSTRAINT `result_ibfk_1` FOREIGN KEY (`VoteSessionID`) REFERENCES `votesession` (`VoteSessionID`),
  ADD CONSTRAINT `result_ibfk_2` FOREIGN KEY (`RaceID`) REFERENCES `race` (`RaceID`),
  ADD CONSTRAINT `result_ibfk_3` FOREIGN KEY (`NomineeID`) REFERENCES `nominee` (`NomineeID`),
  ADD CONSTRAINT `result_ibfk_4` FOREIGN KEY (`AnnouncementID`) REFERENCES `announcement` (`AnnouncementID`);

--
-- Constraints for table `rule`
--
ALTER TABLE `rule`
  ADD CONSTRAINT `rule_ibfk_1` FOREIGN KEY (`ElectionID`) REFERENCES `electionevent` (`ElectionID`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`);

--
-- Constraints for table `votesession`
--
ALTER TABLE `votesession`
  ADD CONSTRAINT `votesession_ibfk_1` FOREIGN KEY (`ElectionID`) REFERENCES `electionevent` (`ElectionID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
