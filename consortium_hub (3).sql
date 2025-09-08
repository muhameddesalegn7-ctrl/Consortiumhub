-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 30, 2025 at 11:26 PM
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
-- Database: `consortium_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `budget_data`
--

CREATE TABLE `budget_data` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `actual` decimal(10,2) DEFAULT NULL,
  `forecast` decimal(10,2) DEFAULT NULL,
  `actual_plus_forecast` decimal(10,2) DEFAULT NULL,
  `variance_percentage` decimal(5,2) DEFAULT NULL,
  `quarter_number` tinyint(4) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `certified` enum('certified','uncertified') DEFAULT 'uncertified',
  `cluster` varchar(100) DEFAULT NULL,
  `year2` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_data`
--

INSERT INTO `budget_data` (`id`, `year`, `category_name`, `period_name`, `budget`, `actual`, `forecast`, `actual_plus_forecast`, `variance_percentage`, `quarter_number`, `start_date`, `end_date`, `certified`, `cluster`, `year2`) VALUES
(1, 1, '1. Administrative costs', 'Q1', 20000.00, 3200.00, 16800.00, 20000.00, 0.00, 1, '2025-07-01', '2025-09-30', 'uncertified', 'Woldiya', 2025),
(2, 1, '1. Administrative costs', 'Q2', 3000.00, 400.00, 500.00, 600.00, 0.00, 2, '2025-10-01', '2025-12-31', 'uncertified', 'Woldiya', 2023),
(3, 1, '1. Administrative costs', 'Q3', 40000.00, 200.00, 300.00, 500.00, 98.75, 3, '2026-01-01', '2026-03-31', 'uncertified', 'Woldiya', 2025),
(4, 1, '1. Administrative costs', 'Q4', 20000.00, 300.00, 400.00, 700.00, 96.50, 4, '2026-04-01', '2026-06-30', 'uncertified', 'Woldiya', 2025),
(6, 1, '2. Operational support costs', 'Q1', 10000.00, 100.00, 9900.00, 10000.00, 0.00, 1, '2025-07-01', '2025-09-30', 'uncertified', 'Woldiya', 2025),
(7, 1, '2. Operational support costs', 'Q2', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 2, '2025-10-01', '2025-12-31', 'uncertified', 'Woldiya', 2025),
(8, 1, '2. Operational support costs', 'Q3', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 3, '2026-01-01', '2026-03-31', 'uncertified', 'Woldiya', 2025),
(9, 1, '2. Operational support costs', 'Q4', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 4, '2026-04-01', '2026-06-30', 'uncertified', 'Woldiya', 2025),
(11, 1, '3. Consortium Activities', 'Q1', 10000.00, 200.00, 300.00, 500.00, 95.00, 1, '2025-07-01', '2025-09-30', 'uncertified', 'Woldiya', 2025),
(12, 1, '3. Consortium Activities', 'Q2', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 2, '2025-10-01', '2025-12-31', 'uncertified', 'Woldiya', 2025),
(13, 1, '3. Consortium Activities', 'Q3', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 3, '2026-01-01', '2026-03-31', 'uncertified', 'Woldiya', 2025),
(14, 1, '3. Consortium Activities', 'Q4', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 4, '2026-04-01', '2026-06-30', 'uncertified', 'Woldiya', 2025),
(16, 1, '4. Targeting new CSOs', 'Q1', 30000.00, 200.00, 400.00, 600.00, 98.00, 1, '2025-07-01', '2025-09-30', 'uncertified', 'Woldiya', 2025),
(17, 1, '4. Targeting new CSOs', 'Q2', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 2, '2025-10-01', '2025-12-31', 'uncertified', 'Woldiya', 2025),
(18, 1, '4. Targeting new CSOs', 'Q3', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 3, '2026-01-01', '2026-03-31', 'uncertified', 'Woldiya', 2025),
(19, 1, '4. Targeting new CSOs', 'Q4', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 4, '2026-04-01', '2026-06-30', 'uncertified', 'Woldiya', 2025),
(21, 1, '5. Contingency', 'Q1', 1000000.00, 200.00, 400.00, 600.00, 99.94, 1, '2025-07-01', '2025-09-30', 'uncertified', 'Woldiya', 2025),
(22, 1, '5. Contingency', 'Q2', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 2, '2025-10-01', '2025-12-31', 'uncertified', 'Woldiya', 2025),
(23, 1, '5. Contingency', 'Q3', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 3, '2026-01-01', '2026-03-31', 'uncertified', 'Woldiya', 2025),
(24, 1, '5. Contingency', 'Q4', 99999999.99, 99999999.99, 99999999.99, 99999999.99, -100.00, 4, '2026-04-01', '2026-06-30', 'uncertified', 'Woldiya', 2025),
(26, 1, 'Grand Total', 'Overall', 99999999.99, 99999999.99, 99999999.99, NULL, -100.00, NULL, NULL, NULL, 'uncertified', 'Woldiya', 2025),
(28, 1, '1. Administrative costs', 'Q1', 300.00, 98.00, 202.00, 300.00, 0.00, 1, '2025-08-30', '2025-10-27', 'uncertified', 'Mekele', 2025);

-- --------------------------------------------------------

--
-- Table structure for table `budget_ppreview`
--

CREATE TABLE `budget_ppreview` (
  `PreviewID` int(11) NOT NULL,
  `BudgetHeading` varchar(255) DEFAULT NULL,
  `Outcome` varchar(255) DEFAULT NULL,
  `Activity` varchar(255) DEFAULT NULL,
  `BudgetLine` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Partner` varchar(255) DEFAULT NULL,
  `EntryDate` date DEFAULT NULL,
  `Amount` decimal(18,2) DEFAULT NULL,
  `PVNumber` varchar(50) DEFAULT NULL,
  `Documents` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_preview`
--

CREATE TABLE `budget_preview` (
  `PreviewID` int(11) NOT NULL,
  `BudgetHeading` varchar(255) DEFAULT NULL,
  `Outcome` varchar(255) DEFAULT NULL,
  `Activity` varchar(255) DEFAULT NULL,
  `BudgetLine` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Partner` varchar(255) DEFAULT NULL,
  `EntryDate` date DEFAULT NULL,
  `Amount` decimal(18,2) DEFAULT NULL,
  `PVNumber` varchar(50) DEFAULT NULL,
  `Documents` varchar(255) DEFAULT NULL,
  `DocumentPaths` text DEFAULT NULL,
  `DocumentTypes` varchar(500) DEFAULT NULL,
  `OriginalNames` varchar(500) DEFAULT NULL,
  `QuarterPeriod` varchar(10) DEFAULT NULL,
  `CategoryName` varchar(255) DEFAULT NULL,
  `OriginalBudget` decimal(18,2) DEFAULT NULL,
  `RemainingBudget` decimal(18,2) DEFAULT NULL,
  `ActualSpent` decimal(18,2) DEFAULT NULL,
  `VariancePercentage` decimal(5,2) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cluster` varchar(255) DEFAULT NULL,
  `budget_id` int(11) DEFAULT NULL,
  `ForecastAmount` decimal(18,2) DEFAULT NULL,
  `COMMENTS` varchar(255) DEFAULT NULL,
  `ACCEPTANCE` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_preview`
--

INSERT INTO `budget_preview` (`PreviewID`, `BudgetHeading`, `Outcome`, `Activity`, `BudgetLine`, `Description`, `Partner`, `EntryDate`, `Amount`, `PVNumber`, `Documents`, `DocumentPaths`, `DocumentTypes`, `OriginalNames`, `QuarterPeriod`, `CategoryName`, `OriginalBudget`, `RemainingBudget`, `ActualSpent`, `VariancePercentage`, `CreatedAt`, `UpdatedAt`, `cluster`, `budget_id`, `ForecastAmount`, `COMMENTS`, `ACCEPTANCE`) VALUES
(50, 'Operational support costs', 'muhamed', 'desalegn', 'muhamed', 'hassen', 'ahmed', '2025-08-22', 2499.98, '300', '[{\"documentType\":\"Invoice\",\"serverPath\":\"admin\\/documents\\/68a87eb6bf7b9_1755872950.pdf\",\"filename\":\"68a87eb6bf7b9_1755872950.pdf\",\"originalName\":\"Professional Annual Report Reports.pdf\"},{\"documentType\":\"Service_Agreement\",\"serverPath\":\"admin\\/documents\\', NULL, NULL, NULL, 'Q1', '2. Operational support costs', 2500.00, 0.02, 2499.98, 0.00, '2025-08-22 14:37:41', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(51, 'Operational support costs', 'muhamed', 'desalegn', 'muhamed', 'hassen', 'ahmed', '2025-08-22', 2499.98, '300', NULL, 'd/admin/pdfs/sample.pdf,admin/documents/68a87eb6bf7b9_1755872950.pdf,admin/documents/68a87eb6bf8a4_1755872950.pdf', 'Sample Document,Invoice,Service_Agreement', 'sample.pdf,Professional Annual Report Reports.pdf,formatted-document.pdf', 'Q1', '2. Operational support costs', 2600.00, 100.02, 2499.98, -3.85, '2025-08-22 14:37:57', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(52, 'Operational support costs', 'muhamed', 'desalegn', 'muhamed', 'hassen', 'ahmed', '2025-08-22', 2499.98, '600', NULL, 'd/admin/pdfs/sample.pdf,admin/documents/68a88133709f7_1755873587.pdf', 'Sample Document,Service_Agreement', 'sample.pdf,evaluations-report-2023.pdf', 'Q2', '2. Operational support costs', 2700.00, 200.02, 2499.98, -7.41, '2025-08-22 14:39:50', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(53, 'Operational support costs', 'muhamed', 'desalegn', 'muhamed', 'hassen', 'ahmed', '2025-08-22', 2499.98, '600', NULL, 'admin/documents/sample.pdf,admin/documents/68a88133709f7_1755873587.pdf', 'Sample Document,Service_Agreement', 'sample.pdf,evaluations-report-2023.pdf', 'Q2', '2. Operational support costs', 2800.00, 300.02, 2499.98, -10.72, '2025-08-22 14:50:04', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(54, 'Operational support costs', 'muhamed', 'desalegn', 'muhamed', 'hassen', 'ahmed', '2025-08-22', 2499.98, '600', NULL, 'admin/documents/sample.pdf,admin/documents/68a88133709f7_1755873587.pdf', 'Sample Document,Service_Agreement', 'sample.pdf,evaluations-report-2023.pdf', 'Q2', '2. Operational support costs', 2900.00, 400.02, 2499.98, -13.79, '2025-08-22 14:50:08', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(55, 'Operational support costs', 'muhamed', 'desalegn', 'muhamed', 'hassen', 'ahmed', '2025-08-22', 2499.98, '600', NULL, 'admin/documents/sample.pdf,admin/documents/68a88133709f7_1755873587.pdf', 'Sample Document,Service_Agreement', 'sample.pdf,evaluations-report-2023.pdf', 'Q3', '2. Operational support costs', 3000.00, 500.02, 2499.98, -16.67, '2025-08-22 14:55:15', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(56, 'Operational support costs', 'muhamed', 'desalegn', 'muhamed', 'hassen', 'ahmed', '2025-08-22', 2499.98, '600', NULL, 'admin/documents/sample.pdf,admin/documents/68a88133709f7_1755873587.pdf', 'Sample Document,Service_Agreement', 'sample.pdf,evaluations-report-2023.pdf', 'Q3', '2. Operational support costs', 3100.00, 600.02, 2499.98, -19.36, '2025-08-22 14:57:27', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(57, 'Operational support costs', 'muhamed', 'desalegn', 'muhamed', 'hassen', 'ahmed', '2025-08-22', 2499.98, '600', NULL, 'admin/documents/sample.pdf,admin/documents/68a88133709f7_1755873587.pdf', 'Sample Document,Service_Agreement', 'sample.pdf,evaluations-report-2023.pdf', 'Q3', '2. Operational support costs', 3200.00, 700.02, 2499.98, -21.88, '2025-08-22 15:00:03', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(58, 'Administrative costs', 'muhamed', 'hi', 'muhamed', 'hi', 'muhamed', '2025-08-24', 2999.99, '300', NULL, 'admin/documents/68a984c41065d_1755940036.pdf', 'Service_Agreement', '4056844e35a7d6fea0afb70112c72609.pdf', 'Q4', '1. Administrative costs', 2800.00, -199.99, 2999.99, 7.14, '2025-08-23 09:07:53', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(59, 'Administrative costs', 'hi', 'ahmd', 'muhamed', '300', '400', '2025-08-23', 6000.00, '300', NULL, 'admin/documents/68a9853c67c77_1755940156.docx', 'Service_Agreement', 'Safeguarding Manual Last Updated (1).docx', 'Q3', '1. Administrative costs', 5000.00, -1000.00, 6000.00, 20.00, '2025-08-23 09:09:22', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(60, 'Administrative costs', 'muhamed', 'spenting', 'test', 'muhamed', 'MMI', '2025-08-24', 12999.95, 'pv 312', NULL, 'admin/documents/68a9859876012_1755940248.pdf', 'Service_Agreement', 'formatted-document.pdf', 'Q4', '3. Consortium Activities', 10000.00, -2999.95, 12999.95, 30.00, '2025-08-23 09:11:06', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(61, 'Administrative costs', '200', '300', '400', '500', '600', '2025-08-24', 2999.99, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q1', '1. Administrative costs', 3000.00, 0.01, 2999.99, 0.00, '2025-08-23 09:28:50', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(62, 'Administrative costs', '200', '300', '400', '500', '600', '2025-08-24', 2999.99, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q1', '4. Targeting new CSOs', 3100.00, 100.01, 2999.99, -3.23, '2025-08-23 09:28:55', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(63, 'Administrative costs', '200', '300', '400', '500', '600', '2025-08-24', 2999.99, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q2', '5. Contingency', 3200.00, 200.01, 2999.99, -6.25, '2025-08-23 09:28:59', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(64, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q2', '1. Administrative costs', 3000.00, 0.00, 3000.00, 0.00, '2025-08-23 09:41:36', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(65, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q2', '4. Targeting new CSOs', 2900.00, -100.00, 3000.00, 3.45, '2025-08-23 09:41:40', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(66, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q3', '5. Contingency', 2800.00, -200.00, 3000.00, 7.14, '2025-08-23 09:41:43', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(67, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q3', '1. Administrative costs', 2700.00, -300.00, 3000.00, 11.11, '2025-08-23 09:41:50', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(68, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q3', '4. Targeting new CSOs', 2600.00, -400.00, 3000.00, 15.38, '2025-08-23 09:41:53', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(69, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q4', '5. Contingency', 2500.00, -500.00, 3000.00, 20.00, '2025-08-23 09:41:56', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(70, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q4', '1. Administrative costs', 2400.00, -600.00, 3000.00, 25.00, '2025-08-23 09:41:59', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(71, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q4', '4. Targeting new CSOs', 2300.00, -700.00, 3000.00, 30.43, '2025-08-23 09:42:03', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(72, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q1', '5. Contingency', 2200.00, -800.00, 3000.00, 36.36, '2025-08-23 09:42:07', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(73, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q1', '1. Administrative costs', 2100.00, -900.00, 3000.00, 42.86, '2025-08-23 09:42:12', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(74, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q2', '4. Targeting new CSOs', 2000.00, -1000.00, 3000.00, 50.00, '2025-08-23 09:42:27', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(75, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q2', '5. Contingency', 1900.00, -1100.00, 3000.00, 57.89, '2025-08-23 09:42:31', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(76, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q3', '1. Administrative costs', 1800.00, -1200.00, 3000.00, 66.67, '2025-08-23 09:42:34', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(77, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q3', '4. Targeting new CSOs', 1700.00, -1300.00, 3000.00, 76.47, '2025-08-23 09:42:37', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(78, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q4', '5. Contingency', 1600.00, -1400.00, 3000.00, 87.50, '2025-08-23 09:42:40', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(79, 'Administrative costs', '300', 'hi', 'muhamed', 'hi', 'hi', '2025-08-24', 3000.00, '400', NULL, 'admin/documents/68a989c99d0dc_1755941321.docx', 'Service_Agreement', 'CSIF - Governance and Capacity Assessment - MMI - Final_ww (1).docx', 'Q4', '1. Administrative costs', 1500.00, -1500.00, 3000.00, 100.00, '2025-08-23 09:45:43', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(80, 'Administrative costs', 'muhamed', 'muhamed', 'ahmed', 'muhamed', 'mamie', '2025-08-26', 100.00, '300', NULL, 'admin/documents/68a990e40c7fa_1755943140.docx', 'Service_Agreement', 'Baseline Assessment Questions (2).docx', 'Q1', '1. Administrative costs', 912.90, -2187.10, 3100.00, 239.58, '2025-08-23 09:59:02', '2025-08-25 06:18:17', 'Woldiya', NULL, NULL, NULL, NULL),
(81, 'Administrative costs', 'muhamed', 'muhamed', 'muhamed', 'ahmed', 'ahmed', '2025-08-25', 100.00, '200', 'ptah/pathy.pdf', 'ptah/pathy.pdf', 'pdf', 'mamila', 'Q1', '1. Administrative costs', 912.90, 912.90, 912.90, 200.00, '2025-08-23 10:48:04', '2025-08-25 06:18:17', 'Woldiya', 1, 100.00, NULL, NULL),
(82, 'Administrative costs', 'muhamed', 'hi', 'hi', 'hi', 'hi', '2025-08-23', 200.00, '400', NULL, 'admin/documents/68a9e8504084e_1755965520.pdf,admin/documents/68a9e850409f8_1755965520.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll,Income_Tax_Payment_Slip_/_Receipt_from_Tax_Authority', 'formatted-document (1)_merged.pdf,4056844e35a7d6fea0afb70112c72609.pdf', 'Q1', '1. Administrative costs', 912.90, 712.90, 3300.00, 334.84, '2025-08-23 16:15:44', '2025-08-25 06:18:17', 'Woldiya', NULL, 0.00, NULL, NULL),
(83, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 200.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 200.00, 0.00, 200.00, 0.00, '2025-08-25 06:48:33', '2025-08-25 06:48:33', 'Woldiya', NULL, 0.00, NULL, NULL),
(84, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 200.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 200.00, 0.00, 200.00, 0.00, '2025-08-25 06:50:29', '2025-08-25 06:50:29', 'Woldiya', NULL, 0.00, NULL, NULL),
(85, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 200.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 200.00, 0.00, 200.00, 0.00, '2025-08-25 06:51:37', '2025-08-25 06:51:37', 'Woldiya', NULL, 0.00, NULL, NULL),
(86, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'm', '2025-08-25', 200.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 200.00, 0.00, 200.00, 0.00, '2025-08-25 06:51:58', '2025-08-25 06:51:58', 'Woldiya', NULL, 0.00, NULL, NULL),
(87, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 10.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 10.00, 0.00, 10.00, 0.00, '2025-08-25 06:52:09', '2025-08-25 06:52:09', 'Woldiya', NULL, 0.00, NULL, NULL),
(88, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 15.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 15.00, 0.00, 15.00, 0.00, '2025-08-25 06:59:03', '2025-08-25 06:59:03', 'Woldiya', NULL, 0.00, NULL, NULL),
(89, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 15.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 15.00, 0.00, 15.00, 0.00, '2025-08-25 06:59:07', '2025-08-25 06:59:07', 'Woldiya', NULL, 0.00, NULL, NULL),
(90, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 15.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 15.00, 0.00, 15.00, 0.00, '2025-08-25 06:59:11', '2025-08-25 06:59:11', 'Woldiya', NULL, 0.00, NULL, NULL),
(91, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 15.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 15.00, 0.00, 15.00, 0.00, '2025-08-25 06:59:29', '2025-08-25 06:59:29', 'Woldiya', NULL, 0.00, NULL, NULL),
(92, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 3.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 3.00, 0.00, 3.00, 0.00, '2025-08-25 07:01:32', '2025-08-25 07:01:32', 'Woldiya', NULL, 0.00, NULL, NULL),
(93, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 3.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 3.00, 0.00, 3.00, 0.00, '2025-08-25 07:01:58', '2025-08-25 07:01:58', 'Woldiya', NULL, 0.00, NULL, NULL),
(94, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 3.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 3.00, 0.00, 3.00, 0.00, '2025-08-25 07:03:08', '2025-08-25 07:03:08', 'Woldiya', NULL, 0.00, NULL, NULL),
(95, 'Administrative costs', 'muha', 'mamie', 'hi', 'h', 'h', '2025-08-25', 3.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Unknown', '1. Administrative costs', 3.00, 0.00, 3.00, 0.00, '2025-08-25 07:03:12', '2025-08-25 07:03:12', 'Woldiya', NULL, 0.00, NULL, NULL),
(96, 'Administrative costs', 'muh', 'mamie', 'hi', 'h', 'h', '2025-08-25', 20.00, '200', NULL, 'admin/documents/68ac073bcdb13_1756104507.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'mm.pdf', 'Q1', '1. Administrative costs', 532.90, 512.90, 3320.00, 543.40, '2025-08-25 07:19:53', '2025-08-25 07:19:53', 'Woldiya', NULL, 0.00, NULL, NULL),
(111, 'Administrative costs', 'muha', 'mamie', 'muha', 'muh', 'mh', '2025-08-25', 100.00, '258097', NULL, 'admin/documents/68ada3997080c_1756210073.pdf,admin/documents/68ada39970965_1756210073.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll,Income_Tax_Payment_Slip_/_Receipt_from_Tax_Authority', 'blue professional financial report anual report.pdf,Baseline_Assessment_Report.pdf', 'Q1', '1. Administrative costs', 120831.68, 120731.68, 551318.56, 573.56, '2025-08-26 13:03:22', '2025-08-26 13:03:22', 'Woldiya', NULL, 57472.64, NULL, NULL),
(113, 'Administrative costs', 'hi', 'mamie', 'hi', 'hi', 'hi', '2025-08-26', 20.00, '2765', NULL, 'admin/documents/68adb2ac02832_1756213932.pdf,admin/documents/68adb2ac09f23_1756213932.pdf', 'Approved_Timesheets_/_Attendance_Records,Payroll_Register_Sheet_(_For_Each_Project_)', '4056844e35a7d6fea0afb70112c72609.pdf,formatted-document.pdf', 'Q1', '1. Administrative costs', 9656054.40, 9656034.40, 44100004.80, 356.71, '2025-08-26 13:12:17', '2025-08-26 13:12:17', 'Woldiya', NULL, 4597811.20, NULL, NULL),
(114, 'Administrative costs', 'mamie', 'mamie', 'maie', 'hi', 'muhamed', '2025-08-26', 2000.00, '2034', NULL, 'admin/documents/68adc5bc9c75f_1756218812.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'merged_documents_258097.pdf', 'Q1', '1. Administrative costs', 102000.00, 100000.00, 4000.00, 0.00, '2025-08-26 14:33:39', '2025-08-26 14:33:39', 'Woldiya', NULL, 40000.00, NULL, NULL),
(115, 'Administrative costs', 'hi', 'mamie', 'mamie', 'mamie', 'mamie', '2025-08-26', 400.00, '123', NULL, 'admin/documents/68adc80083c42_1756219392.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'merged_documents_258097.pdf', 'Q1', '1. Administrative costs', 100400.00, 100000.00, 1400.00, 0.00, '2025-08-26 14:43:16', '2025-08-26 14:43:16', 'Woldiya', NULL, 0.00, NULL, NULL),
(116, 'Administrative costs', 'hi', 'mamie', '1000', 'hi', 'hi', '2025-08-26', 2000.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 100001999.99, 99999999.99, 100001999.99, 0.00, '2025-08-26 14:44:55', '2025-08-26 14:44:55', 'Woldiya', NULL, 99999999.99, NULL, NULL),
(117, 'Administrative costs', 'hi', 'mamie', 'mamie', 'mamie', 'mamie', '2025-08-26', 2000.00, '400', NULL, 'admin/documents/68adc8ccdf7b5_1756219596.pdf', 'Approved_Timesheets_/_Attendance_Records', 'merged_documents_258097.pdf', 'Q1', '1. Administrative costs', 402000.00, 400000.00, 12000.00, 0.00, '2025-08-26 14:46:40', '2025-08-26 14:46:40', 'Woldiya', NULL, 200000.00, NULL, NULL),
(118, 'Administrative costs', '200', 'mamie', 'mamie', 'mamie', 'mamie', '2025-08-27', 200.00, '120', NULL, 'admin/documents/68adc9750be3f_1756219765.pdf', 'Approved_Timesheets_/_Attendance_Records', 'merged_documents_258097.pdf', 'Q1', '1. Administrative costs', 100000199.99, 99999999.99, 100000199.99, 0.00, '2025-08-26 14:49:27', '2025-08-26 14:49:27', 'Woldiya', NULL, 99999999.99, NULL, NULL),
(119, 'Administrative costs', 'hi', 'mamie', 'hi', 'hi', 'hi', '2025-08-26', 2000.00, '1344', NULL, 'admin/documents/68adcc7ce1998_1756220540.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'merged_documents_258097.pdf', 'Q1', '1. Administrative costs', 202000.00, 200000.00, 12000.00, 0.00, '2025-08-26 15:02:26', '2025-08-26 15:02:26', 'Woldiya', NULL, 50.00, NULL, NULL),
(120, 'Administrative costs', 'hi', 'mamie', 'hi', 'hi', 'hi', '2025-08-26', 100.00, '120', NULL, 'admin/documents/68adcd8fabeb9_1756220815.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'merged_documents_258097.pdf', 'Q1', '1. Administrative costs', 20100.00, 20000.00, 200.00, 0.00, '2025-08-26 15:06:57', '2025-08-26 15:06:57', 'Woldiya', NULL, 20.00, NULL, NULL),
(121, 'Administrative costs', 'muhamed', 'mamie', 'muhamed', 'muhamed', 'muhamed', '2025-08-26', 200.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20200.00, 20000.00, 400.00, -99.00, '2025-08-26 15:55:53', '2025-08-26 15:55:53', 'Woldiya', NULL, 20.00, NULL, NULL),
(122, 'Administrative costs', 'ma', 'mamie', 'mam', 'mam', 'mam', '2025-08-26', 200.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20200.00, 20000.00, 8200.00, -98.00, '2025-08-26 15:57:49', '2025-08-26 15:57:49', 'Woldiya', NULL, 19400.00, NULL, NULL),
(123, 'Administrative costs', 'mamie', 'mamie', 'MAMIE', '100', '100', '2025-08-26', 1000.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 21000.00, 20000.00, 9200.00, -59.00, '2025-08-26 16:03:57', '2025-08-26 16:03:57', 'Woldiya', NULL, 11600.00, NULL, NULL),
(124, 'Administrative costs', 'mamila', 'mamie', 'hi', 'mamie', 'maminet', '2025-08-26', 198.00, '300', NULL, 'admin/documents/68ade1a09da72_1756225952.pdf', 'Income_Tax_Calculation_Summary_Sheet_/Payroll', 'merged_documents_258097.pdf', 'Q1', '1. Administrative costs', 20000.00, 0.00, 9398.00, -0.99, '2025-08-26 16:32:35', '2025-08-26 16:32:35', 'Woldiya', NULL, 10800.00, NULL, NULL),
(125, 'Administrative costs', 'muhamed', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 1999.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11397.00, -10.99, '2025-08-28 06:11:32', '2025-08-28 06:11:32', 'Woldiya', NULL, 10800.00, NULL, NULL),
(126, 'Administrative costs', 'muhamed', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 1999.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11397.00, -10.99, '2025-08-28 06:11:43', '2025-08-28 06:11:43', 'Woldiya', NULL, 10800.00, NULL, NULL),
(127, 'Administrative costs', 'muhamed', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 1999.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11397.00, -10.99, '2025-08-28 06:12:00', '2025-08-28 06:12:00', 'Woldiya', NULL, 10800.00, NULL, NULL),
(128, 'Administrative costs', 'muhamed', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 2000.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11398.00, -10.99, '2025-08-28 06:12:42', '2025-08-28 06:12:42', 'Woldiya', NULL, 10800.00, NULL, NULL),
(129, 'Administrative costs', 'muhamed', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 2000.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11398.00, -10.99, '2025-08-28 06:13:23', '2025-08-28 06:13:23', 'Woldiya', NULL, 10800.00, NULL, NULL),
(130, 'Administrative costs', 'muhamed', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 2000.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11398.00, -10.99, '2025-08-28 06:15:11', '2025-08-28 06:15:11', 'Woldiya', NULL, 10800.00, NULL, NULL),
(131, 'Administrative costs', 'muhamed', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 1999.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11397.00, -10.99, '2025-08-28 06:15:26', '2025-08-28 06:15:26', 'Woldiya', NULL, 10800.00, NULL, NULL),
(132, 'Administrative costs', 'muhamed', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 1999.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11397.00, -10.99, '2025-08-28 06:16:08', '2025-08-28 06:16:08', 'Woldiya', NULL, 10800.00, NULL, NULL),
(133, 'Administrative costs', 'hi', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 1999.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11397.00, -10.99, '2025-08-28 06:22:10', '2025-08-28 06:22:10', 'Woldiya', NULL, 10800.00, NULL, NULL),
(134, 'Administrative costs', 'hi', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 1999.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11397.00, -10.99, '2025-08-28 06:22:24', '2025-08-28 06:22:24', 'Woldiya', NULL, 10800.00, NULL, NULL),
(135, 'Administrative costs', 'muhamed', 'mamie', 'hi', 'hi', 'hi', '2025-08-28', 2000.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 20000.00, 0.00, 11398.00, -10.99, '2025-08-28 06:24:52', '2025-08-28 06:24:52', 'Woldiya', NULL, 10800.00, NULL, NULL),
(137, 'Administrative costs', 'muhamed', 'mamie', 'muhamed', 'muhamed', 'muhamed', '2025-08-28', 1000.00, '4000', '[{\"documentType\":\"1 Withholding Tax (WHT) Payments\",\"serverPath\":\"admin\\/documents\\/68aff7e05478d_1756362720.pdf\",\"filename\":\"68aff7e05478d_1756362720.pdf\"},{\"documentType\":\"Pension Calculation Sheet\",\"serverPath\":\"admin\\/documents\\/68b19664d72f3_17564688', 'admin/documents/68aff7e05478d_1756362720.pdf,admin/documents/68b19664d72f3_1756468836.pdf', '1 Withholding Tax (WHT) Payments,Pension Calculation Sheet', 'blue professional financial report anual report.pdf', 'Q1', '1. Administrative costs', 20000.00, 1000.00, 3000.00, 5.00, '2025-08-28 06:32:10', '2025-08-29 12:14:20', 'Woldiya', NULL, 16000.00, 'muhamed', '0'),
(138, 'Administrative costs', 'mmmm', 'mamie', 'mm', 'mm', 'mm', '2025-08-29', 200.00, '300', NULL, 'admin/documents/68b14ac7ea9be_1756449479.pdf,admin/documents/68b14ac809fd6_1756449480.docx', 'Income_Tax_Calculation_Summary_Sheet_/Payroll,Income_Tax_Payment_Slip_/_Receipt_from_Tax_Authority', 'merged_documents_258097.pdf,Project Title_ Improving Gender Inclusivity of Ethiopian Media through Journalism Internship for Female Students (1).docx', 'Q1', '1. Administrative costs', 20000.00, 0.00, 3200.00, -1.00, '2025-08-29 06:38:11', '2025-08-29 08:38:22', 'Woldiya', NULL, 17000.00, 'mamie', '1'),
(140, 'Administrative costs', 'muhamed', 'muhamed', 'Chcke2', 'hi', 'hi', '2025-08-30', 100.00, '', NULL, '', '', '', 'Unknown', '1. Administrative costs', 0.00, 0.00, 100.00, 0.00, '2025-08-30 09:26:00', '2025-08-30 12:56:39', 'Mekele', NULL, 0.00, NULL, '1'),
(141, 'Administrative costs', 'muhamed', 'muhamed', 'Chcke2', 'hi', 'hi', '2025-08-30', 100.00, '', NULL, '', '', '', 'Unknown', '1. Administrative costs', 0.00, 0.00, 100.00, 0.00, '2025-08-30 09:26:05', '2025-08-30 09:26:05', 'Mekele', NULL, 0.00, NULL, NULL),
(142, 'Administrative costs', 'muhamed', 'muhamed', 'Chcke2', 'hi', 'hi', '2025-08-30', 100.00, '', NULL, '', '', '', 'Unknown', '1. Administrative costs', 0.00, 0.00, 100.00, 0.00, '2025-08-30 09:26:10', '2025-08-30 09:26:10', 'Mekele', NULL, 0.00, NULL, NULL),
(143, 'Administrative costs', 'muhamed', 'muhamed', 'Chcke2', 'hi', 'hi', '2025-08-30', 100.00, '', NULL, '', '', '', 'Unknown', '1. Administrative costs', 0.00, 0.00, 100.00, 0.00, '2025-08-30 09:26:17', '2025-08-30 09:26:17', 'Mekele', NULL, 0.00, NULL, NULL),
(144, 'Administrative costs', 'muhamed', 'muhamed', 'Chcke2', 'hi', 'hi', '2025-08-30', 100.00, '', NULL, '', '', '', 'Unknown', '1. Administrative costs', 0.00, 0.00, 100.00, 0.00, '2025-08-30 09:26:23', '2025-08-30 09:26:23', 'Mekele', NULL, 0.00, NULL, NULL),
(158, 'Administrative costs', 'muhamed', 'muhamed', 'Chcke2', 'muhmed', 'go', '2025-08-30', 45.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 300.00, 105.00, 195.00, 0.00, '2025-08-30 12:16:00', '2025-08-30 12:16:00', 'Mekele', 28, 105.00, NULL, NULL),
(159, 'Administrative costs', 'muhamed', 'muhamed', 'Chcke2', 'muhamed', 'hi', '2025-08-30', 48.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 300.00, 252.00, 48.00, 0.00, '2025-08-30 12:21:01', '2025-08-30 12:21:01', 'Mekele', 28, 252.00, NULL, NULL),
(161, 'Administrative costs', 'muhamed', 'muhamed', 'Chcke2', 'muhamed', 'muamed', '2025-08-30', 50.00, '', NULL, '', '', '', 'Q1', '1. Administrative costs', 300.00, 202.00, 98.00, 0.00, '2025-08-30 21:11:22', '2025-08-30 21:12:39', 'Mekele', 28, 202.00, NULL, '1');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `budget` decimal(18,2) DEFAULT NULL,
  `actual` decimal(18,2) DEFAULT NULL,
  `forecast` decimal(18,2) DEFAULT NULL,
  `actual_plus_forecast` decimal(18,2) DEFAULT NULL,
  `variance_percentage` decimal(5,2) DEFAULT NULL,
  `quarter_number` int(1) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `certificate_path` varchar(500) NOT NULL,
  `uploaded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` varchar(255) DEFAULT 'admin',
  `status` enum('active','archived') DEFAULT 'active',
  `cluster` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `year`, `category_name`, `period_name`, `budget`, `actual`, `forecast`, `actual_plus_forecast`, `variance_percentage`, `quarter_number`, `start_date`, `end_date`, `certificate_path`, `uploaded_date`, `uploaded_by`, `status`, `cluster`) VALUES
(1, 2025, '1. Administrative costs', 'Q1', 712.90, 3100.00, NULL, 3100.00, 334.84, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(2, 2025, '1. Administrative costs', 'Q2', 3592.04, NULL, 3592.04, 3592.04, NULL, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(3, 2025, '1. Administrative costs', 'Q3', 0.00, 30999.91, NULL, 30999.91, 100.00, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(4, 2025, '1. Administrative costs', 'Q4', 3592.04, NULL, NULL, 0.00, NULL, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(5, 2025, '1. Administrative costs', 'Annual Total', 7896.98, 34099.91, 3592.04, 37691.95, 331.81, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(6, 2025, '2. Operational support costs', 'Q1', 13704.93, NULL, NULL, 0.00, NULL, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(7, 2025, '2. Operational support costs', 'Q2', 13284.93, NULL, NULL, 0.00, NULL, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(8, 2025, '2. Operational support costs', 'Q3', 13494.93, NULL, NULL, 0.00, NULL, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(9, 2025, '2. Operational support costs', 'Q4', 13494.93, NULL, NULL, 0.00, NULL, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(10, 2025, '2. Operational support costs', 'Annual Total', 40484.79, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(11, 2025, '3. Consortium Activities', 'Q1', 19358.72, NULL, NULL, 0.00, NULL, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(12, 2025, '3. Consortium Activities', 'Q2', 13800.28, NULL, NULL, 0.00, NULL, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(13, 2025, '3. Consortium Activities', 'Q3', 25845.28, NULL, NULL, 0.00, NULL, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(14, 2025, '3. Consortium Activities', 'Q4', NULL, NULL, NULL, 0.00, 0.00, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(15, 2025, '3. Consortium Activities', 'Annual Total', 59004.29, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(16, 2025, '4. Targeting new CSOs', 'Q1', NULL, NULL, NULL, 0.00, 0.00, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(17, 2025, '4. Targeting new CSOs', 'Q2', NULL, NULL, NULL, 0.00, 0.00, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(18, 2025, '4. Targeting new CSOs', 'Q3', NULL, NULL, NULL, 0.00, 0.00, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(19, 2025, '4. Targeting new CSOs', 'Q4', NULL, NULL, NULL, 0.00, 0.00, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(20, 2025, '4. Targeting new CSOs', 'Annual Total', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(21, 2025, '5. Contingency', 'Q1', 701.92, NULL, NULL, 0.00, NULL, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(22, 2025, '5. Contingency', 'Q2', 701.92, NULL, NULL, 0.00, NULL, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(23, 2025, '5. Contingency', 'Q3', 701.92, NULL, NULL, 0.00, NULL, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(24, 2025, '5. Contingency', 'Q4', 701.92, NULL, NULL, 0.00, NULL, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(25, 2025, '5. Contingency', 'Annual Total', 2105.76, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(26, 2025, 'Grand Total', 'Overall', 112591.82, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-25-58_68a9cf76e4555.pdf', '2025-08-23 14:25:58', 'admin', 'active', 'Woldiya'),
(32, 2025, '1. Administrative costs', 'Q1', 712.90, 3100.00, NULL, 3100.00, 334.84, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(33, 2025, '1. Administrative costs', 'Q2', 3592.04, NULL, 3592.04, 3592.04, NULL, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(34, 2025, '1. Administrative costs', 'Q3', 0.00, 30999.91, NULL, 30999.91, 100.00, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(35, 2025, '1. Administrative costs', 'Q4', 3592.04, NULL, NULL, 0.00, NULL, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(36, 2025, '1. Administrative costs', 'Annual Total', 7896.98, 34099.91, 3592.04, 37691.95, 331.81, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(37, 2025, '2. Operational support costs', 'Q1', 13704.93, NULL, NULL, 0.00, NULL, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(38, 2025, '2. Operational support costs', 'Q2', 13284.93, NULL, NULL, 0.00, NULL, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(39, 2025, '2. Operational support costs', 'Q3', 13494.93, NULL, NULL, 0.00, NULL, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(40, 2025, '2. Operational support costs', 'Q4', 13494.93, NULL, NULL, 0.00, NULL, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(41, 2025, '2. Operational support costs', 'Annual Total', 40484.79, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(42, 2025, '3. Consortium Activities', 'Q1', 19358.72, NULL, NULL, 0.00, NULL, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(43, 2025, '3. Consortium Activities', 'Q2', 13800.28, NULL, NULL, 0.00, NULL, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(44, 2025, '3. Consortium Activities', 'Q3', 25845.28, NULL, NULL, 0.00, NULL, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(45, 2025, '3. Consortium Activities', 'Q4', NULL, NULL, NULL, 0.00, 0.00, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(46, 2025, '3. Consortium Activities', 'Annual Total', 59004.29, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(47, 2025, '4. Targeting new CSOs', 'Q1', NULL, NULL, NULL, 0.00, 0.00, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(48, 2025, '4. Targeting new CSOs', 'Q2', NULL, NULL, NULL, 0.00, 0.00, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(49, 2025, '4. Targeting new CSOs', 'Q3', NULL, NULL, NULL, 0.00, 0.00, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(50, 2025, '4. Targeting new CSOs', 'Q4', NULL, NULL, NULL, 0.00, 0.00, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(51, 2025, '4. Targeting new CSOs', 'Annual Total', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(52, 2025, '5. Contingency', 'Q1', 701.92, NULL, NULL, 0.00, NULL, 1, '2025-07-01', '2025-09-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(53, 2025, '5. Contingency', 'Q2', 701.92, NULL, NULL, 0.00, NULL, 2, '2025-10-01', '2025-12-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(54, 2025, '5. Contingency', 'Q3', 701.92, NULL, NULL, 0.00, NULL, 3, '2026-01-01', '2026-03-31', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(55, 2025, '5. Contingency', 'Q4', 701.92, NULL, NULL, 0.00, NULL, 4, '2026-04-01', '2026-06-30', 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(56, 2025, '5. Contingency', 'Annual Total', 2105.76, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya'),
(57, 2025, 'Grand Total', 'Overall', 112591.82, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin/uploads/certificates/certificate_2025_2025-08-23_16-27-41_68a9cfdd54c0a.pdf', '2025-08-23 14:27:41', 'admin', 'active', 'Woldiya');

-- --------------------------------------------------------

--
-- Table structure for table `certificates_simple`
--

CREATE TABLE `certificates_simple` (
  `id` int(11) NOT NULL,
  `cluster_name` varchar(100) NOT NULL,
  `year` int(4) NOT NULL,
  `certificate_path` varchar(500) NOT NULL,
  `uploaded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` varchar(255) DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates_simple`
--

INSERT INTO `certificates_simple` (`id`, `cluster_name`, `year`, `certificate_path`, `uploaded_date`, `uploaded_by`) VALUES
(1, 'Woldiya', 1, 'admin/uploads/certificates/certificate_1_2025-08-30_22-16-00_68b35c0063b03.pdf', '2025-08-30 20:16:00', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `clusters`
--

CREATE TABLE `clusters` (
  `id` int(11) NOT NULL,
  `cluster_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clusters`
--

INSERT INTO `clusters` (`id`, `cluster_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Woldiya', 'Woldiya Cluster', 1, '2025-08-24 21:15:28', '2025-08-24 21:15:28'),
(2, 'Mekele', 'muhamed', 1, '2025-08-24 22:08:51', '2025-08-24 22:08:51');

-- --------------------------------------------------------

--
-- Table structure for table `predefined_fields`
--

CREATE TABLE `predefined_fields` (
  `id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_type` enum('dropdown','input') NOT NULL DEFAULT 'dropdown',
  `field_values` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cluster_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `predefined_fields`
--

INSERT INTO `predefined_fields` (`id`, `field_name`, `field_type`, `field_values`, `is_active`, `created_at`, `updated_at`, `cluster_name`) VALUES
(1, 'Activity', 'input', 'mamie', 1, '2025-08-24 19:37:01', '2025-08-29 07:36:46', 'Woldiya'),
(2, 'BudgetLine', 'input', 'muhamed', 1, '2025-08-24 19:37:01', '2025-08-29 12:12:46', 'Woldiya'),
(3, 'Partner', 'dropdown', 'wol,hi', 1, '2025-08-24 19:37:01', '2025-08-29 12:51:02', 'Woldiya'),
(4, 'Amount', 'input', '', 0, '2025-08-24 19:37:01', '2025-08-24 23:14:26', 'Woldiya'),
(5, 'BudgetHeading', 'dropdown', 'Administrative costs,Operational support costs,Consortium Activities,Targeting new CSOs,Contingency,TestValue', 1, '2025-08-24 20:19:37', '2025-08-24 23:10:03', 'Woldiya'),
(6, 'Outcome', 'input', 'muhamed', 1, '2025-08-24 20:19:37', '2025-08-24 23:23:32', 'Mekele'),
(23, 'Activity', 'dropdown', 'muhamed', 1, '2025-08-30 07:52:30', '2025-08-30 07:53:25', 'Mekele'),
(24, 'Activity', 'dropdown', NULL, 0, '2025-08-30 07:55:13', '2025-08-30 07:55:13', ''),
(26, 'BudgetLine', 'dropdown', 'Chcke2,check2,Check3', 1, '2025-08-30 09:01:52', '2025-08-30 09:04:19', 'Mekele'),
(27, 'check', 'input', NULL, 1, '2025-08-30 09:19:44', '2025-08-30 09:19:44', 'Mekele'),
(29, 'BudgetHeading', 'dropdown', 'Administrative costs,Operational support costs,Consortium Activities,Targeting new CSOs,Contingency,TestValue', 1, '2025-08-30 09:47:13', '2025-08-30 09:47:13', 'Mekele');

-- --------------------------------------------------------

--
-- Table structure for table `project_documents`
--

CREATE TABLE `project_documents` (
  `id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `custom_document_name` varchar(255) DEFAULT NULL,
  `cluster` varchar(100) NOT NULL,
  `document_file_names` text DEFAULT NULL,
  `document_file_paths` text DEFAULT NULL,
  `image_file_names` text DEFAULT NULL,
  `image_file_paths` text DEFAULT NULL,
  `progress_title` varchar(255) DEFAULT NULL,
  `progress_date` date DEFAULT NULL,
  `progress_summary` text DEFAULT NULL,
  `progress_details` text DEFAULT NULL,
  `challenge_title` varchar(255) DEFAULT NULL,
  `challenge_description` text DEFAULT NULL,
  `challenge_impact` text DEFAULT NULL,
  `proposed_solution` text DEFAULT NULL,
  `success_title` varchar(255) DEFAULT NULL,
  `success_description` text DEFAULT NULL,
  `beneficiaries` int(11) DEFAULT NULL,
  `success_date` date DEFAULT NULL,
  `uploaded_by` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `photo_titles` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_documents`
--

INSERT INTO `project_documents` (`id`, `document_type`, `custom_document_name`, `cluster`, `document_file_names`, `document_file_paths`, `image_file_names`, `image_file_paths`, `progress_title`, `progress_date`, `progress_summary`, `progress_details`, `challenge_title`, `challenge_description`, `challenge_impact`, `proposed_solution`, `success_title`, `success_description`, `beneficiaries`, `success_date`, `uploaded_by`, `uploaded_at`, `updated_at`, `photo_titles`) VALUES
(1, 'Progress Report', NULL, 'Woldiya', '[\"PROGEDI_Baseline Assessment Report_EN.pdf\"]', '[\"uploads\\/documents\\/68b347db8e1fa_1756579803.pdf\"]', '[\"1.3 Objectives of the Baseline - visual selection.png\",\"Context and Target Population Profile - visual selection (1).png\",\"Context and Target Population Profile - visual selection.png\"]', '[\"uploads\\/images\\/68b347db8e397_1756579803.png\",\"uploads\\/images\\/68b347db8e4e4_1756579803.png\",\"uploads\\/images\\/68b347db9167d_1756579803.png\"]', 'muhamed', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 18:50:03', '2025-08-30 18:50:03', '[\"mamila\",\"mamila\",\"mamila\"]'),
(2, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\",\"Context and Target Population Profile - visual selection.png\"]', '[\"uploads\\/images\\/68b34877d84a0_1756579959.png\",\"uploads\\/images\\/68b34877d8709_1756579959.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 18:52:39', '2025-08-30 18:52:39', '[\"hi\",\"hi\"]'),
(3, 'Financial Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\",\"Context and Target Population Profile - visual selection.png\"]', '[\"uploads\\/images\\/68b34b99bbfa5_1756580761.png\",\"uploads\\/images\\/68b34b99bc0b2_1756580761.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:06:01', '2025-08-30 19:06:01', '[\"hi\",\"hi\"]'),
(4, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\"]', '[\"uploads\\/images\\/68b34ba782f11_1756580775.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:06:15', '2025-08-30 19:06:15', '[\"mamie\"]'),
(5, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\"]', '[\"uploads\\/images\\/68b34c3ed9f6a_1756580926.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:08:46', '2025-08-30 19:08:46', '[\"hi\"]'),
(6, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\"]', '[\"uploads\\/images\\/68b34d3cd18fa_1756581180.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:13:00', '2025-08-30 19:13:00', '[\"mamila\"]'),
(7, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\"]', '[\"uploads\\/images\\/68b34dd399682_1756581331.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:15:31', '2025-08-30 19:15:31', '[\"mamila\"]'),
(8, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection.png\"]', '[\"uploads\\/images\\/68b34e503161b_1756581456.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:17:36', '2025-08-30 19:17:36', '[\"mamila\"]'),
(9, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\"]', '[\"uploads\\/images\\/68b34e8702ba1_1756581511.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:18:31', '2025-08-30 19:18:31', '[\"mamila\"]'),
(10, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\"]', '[\"uploads\\/images\\/68b34e99c88ce_1756581529.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:18:49', '2025-08-30 19:18:49', '[\"mamila\"]'),
(11, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection.png\"]', '[\"uploads\\/images\\/68b34eb3f4127_1756581555.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:19:16', '2025-08-30 19:19:16', '[\"mamila\"]'),
(12, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection.png\"]', '[\"uploads\\/images\\/68b34efb231db_1756581627.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', 'mamie', 'hi', 200, '2025-08-30', 'woldiya_finance', '2025-08-30 19:20:27', '2025-08-30 19:20:27', '[\"mamila\"]'),
(13, 'Progress Report', NULL, 'Woldiya', '[\"PROGEDI_Baseline Assessment Report_EN.pdf\"]', '[\"uploads\\/documents\\/68b34f2c49492_1756581676.pdf\"]', '[\"Context and Target Population Profile - visual selection.png\"]', '[\"uploads\\/images\\/68b34f2c49718_1756581676.png\"]', 'hi', '2025-08-30', NULL, NULL, '', '', '', '', 'mamie', 'hi', 200, '2025-08-30', 'woldiya_finance', '2025-08-30 19:21:16', '2025-08-30 19:21:16', '[\"mamila\"]'),
(14, 'Progress Report', NULL, 'Woldiya', '[\"PROGEDI_Baseline Assessment Report_EN.pdf\"]', '[\"uploads\\/documents\\/68b34f4d00d0d_1756581709.pdf\"]', '[\"Context and Target Population Profile - visual selection.png\"]', '[\"uploads\\/images\\/68b34f4d00e02_1756581709.png\"]', 'hi', '2025-08-30', NULL, NULL, '', '', '', '', 'mamie', 'hi', 200, '2025-08-30', 'woldiya_finance', '2025-08-30 19:21:49', '2025-08-30 19:21:49', '[\"mamila\"]'),
(15, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"ChatGPT Image Aug 25, 2025, 01_07_08 PM.png\"]', '[\"uploads\\/images\\/68b34f57dd875_1756581719.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:21:59', '2025-08-30 19:21:59', '[\"muhamed\"]'),
(16, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\"]', '[\"uploads\\/images\\/68b3500f70da0_1756581903.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:25:03', '2025-08-30 19:25:03', '[\"hi\"]'),
(17, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"4.1 Study Design - visual selection.png\"]', '[\"uploads\\/images\\/68b35061dd5fa_1756581985.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:26:25', '2025-08-30 19:26:25', '[\"mamila\"]'),
(18, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[]', '[]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:29:58', '2025-08-30 19:29:58', '[]'),
(19, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\"]', '[\"uploads\\/images\\/68b35141bce78_1756582209.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:30:09', '2025-08-30 19:30:09', '[\"\"]'),
(20, 'Progress Report', NULL, 'Woldiya', '[]', '[]', '[\"Context and Target Population Profile - visual selection (1).png\"]', '[\"uploads\\/images\\/68b35247bdd1b_1756582471.png\"]', '', '2025-08-30', NULL, NULL, '', '', '', '', '', '', 0, '0000-00-00', 'woldiya_finance', '2025-08-30 19:34:31', '2025-08-30 19:34:31', '[\"mamila\"]');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','finance_officer') NOT NULL DEFAULT 'finance_officer',
  `cluster_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `cluster_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gmail.com', '1234', 'admin', 'Mekele', 1, '2025-08-24 21:15:28', '2025-08-24 23:30:59'),
(2, 'woldiya_finance', 'finance@woldiya.com', '1234', 'finance_officer', 'Woldiya', 1, '2025-08-24 21:22:58', '2025-08-24 21:45:43'),
(3, 'check', 'muhamedd823@gmail.com', '1234', 'finance_officer', 'Mekele', 1, '2025-08-24 22:14:13', '2025-08-24 22:14:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget_data`
--
ALTER TABLE `budget_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_ppreview`
--
ALTER TABLE `budget_ppreview`
  ADD PRIMARY KEY (`PreviewID`);

--
-- Indexes for table `budget_preview`
--
ALTER TABLE `budget_preview`
  ADD PRIMARY KEY (`PreviewID`),
  ADD KEY `budget_id` (`budget_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_year_category` (`year`,`category_name`),
  ADD KEY `idx_certificate_path` (`certificate_path`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `certificates_simple`
--
ALTER TABLE `certificates_simple`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clusters`
--
ALTER TABLE `clusters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cluster_name` (`cluster_name`);

--
-- Indexes for table `predefined_fields`
--
ALTER TABLE `predefined_fields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_documents`
--
ALTER TABLE `project_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budget_data`
--
ALTER TABLE `budget_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `budget_ppreview`
--
ALTER TABLE `budget_ppreview`
  MODIFY `PreviewID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_preview`
--
ALTER TABLE `budget_preview`
  MODIFY `PreviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `certificates_simple`
--
ALTER TABLE `certificates_simple`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clusters`
--
ALTER TABLE `clusters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `predefined_fields`
--
ALTER TABLE `predefined_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `project_documents`
--
ALTER TABLE `project_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
