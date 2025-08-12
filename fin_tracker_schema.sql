-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 11, 2025 at 05:52 PM
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
-- Database: `fin_tracker_schema`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `ProcessInvoicePayment` (IN `p_transaction_code` VARCHAR(50), IN `p_client_id` INT, IN `p_invoice_id` INT, IN `p_payment_date` DATE, IN `p_amount` DECIMAL(15,2), IN `p_payment_mode` VARCHAR(50), IN `p_received_by` VARCHAR(255), IN `p_notes` TEXT, IN `p_pay_side` TINYINT, IN `p_debit_account_id` INT, IN `p_credit_account_id` INT, OUT `p_status` VARCHAR(50))   proc_body: BEGIN

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status = 'FAILED';
    END;

    START TRANSACTION;

    -- 1. Insert payment record
    INSERT INTO tbl_invoice_payments (
        transaction_code, client_id, invoice_id, payment_date, amount,
        payment_mode, received_by, notes, created_at, status, pay_side
    ) VALUES (
        p_transaction_code, p_client_id, p_invoice_id, p_payment_date, p_amount,
        p_payment_mode, p_received_by, p_notes, NOW(), 'COMPLETED', p_pay_side
    );

    -- 2. Insert journal entry for debit and credit sides
    IF p_pay_side = 1 THEN
        -- pay_side=1 means payment is debit side (money going out)
        INSERT INTO tbl_journal_entries (
            entry_date, debit_account_id, credit_account_id, amount,
            method_id, reference_id, description, created_at
        ) VALUES (
            p_payment_date, p_debit_account_id, p_credit_account_id, p_amount,
            NULL, LAST_INSERT_ID(), CONCAT('Payment ', p_transaction_code), NOW()
        );

        -- 3. Update account balances
        UPDATE tbl_accounts
        SET balance = balance - p_amount
        WHERE account_id = p_debit_account_id;

    ELSEIF p_pay_side = 2 THEN
        -- pay_side=2 means payment is credit side (money coming in)
        INSERT INTO tbl_journal_entries (
            entry_date, debit_account_id, credit_account_id, amount,
            method_id, reference_id, description, created_at
        ) VALUES (
            p_payment_date, p_debit_account_id, p_credit_account_id, p_amount,
            NULL, LAST_INSERT_ID(), CONCAT('Payment ', p_transaction_code), NOW()
        );

        -- 3. Update account balances (reverse direction)
        UPDATE tbl_accounts
        SET balance = balance + p_amount
        WHERE account_id = p_debit_account_id;

    ELSE
        -- Invalid pay_side
        ROLLBACK;
        SET p_status = 'INVALID_PAY_SIDE';
        LEAVE proc_body;  -- Leave the main labeled block to exit procedure
    END IF;

    COMMIT;
    SET p_status = 'SUCCESS';

END proc_body$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `get_total_expenses` (`p_consumer_id` INT) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    DECLARE total DECIMAL(10,2);
    SELECT SUM(amount) INTO total
    FROM tbl_expenses
    WHERE consumer_id = p_consumer_id;
    RETURN IFNULL(total, 0.00);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `client_type`
--

CREATE TABLE `client_type` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_type`
--

INSERT INTO `client_type` (`type_id`, `type_name`) VALUES
(1, 'Government Organisation'),
(2, 'Private Organisation'),
(3, 'NGO '),
(4, 'Personal Itself'),
(5, 'Others');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Fuel', 'Vehicle fueling'),
(2, 'Office Supplies', 'Pens, papers, toners'),
(3, 'Travel', 'Transport, accommodation'),
(4, 'Maintenance', 'Repairs and servicing'),
(5, 'Internet & Communication', 'ISP, mobile phone recharge');

-- --------------------------------------------------------

--
-- Table structure for table `financial_classification`
--

CREATE TABLE `financial_classification` (
  `classify_id` int(11) NOT NULL,
  `classfy_names` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `financial_classification`
--

INSERT INTO `financial_classification` (`classify_id`, `classfy_names`) VALUES
(1, 'Pay as Service(s)'),
(2, 'Contract Client(s)'),
(3, 'One-Time Payment Client'),
(4, 'Subscription-Based Client');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_statuses`
--

CREATE TABLE `invoice_statuses` (
  `id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_statuses`
--

INSERT INTO `invoice_statuses` (`id`, `status_name`, `description`) VALUES
(1, 'draft', 'Invoice created but not yet finalized or sent'),
(2, 'pending_approval', 'Invoice is awaiting approval'),
(3, 'approved', 'Invoice has been approved but not yet sent'),
(4, 'sent', 'Invoice has been sent to the client'),
(5, 'partially_paid', 'Invoice has been partially paid'),
(6, 'paid', 'Invoice has been fully paid'),
(7, 'overdue', 'Invoice payment is overdue'),
(8, 'cancelled', 'Invoice has been cancelled and is no longer valid'),
(9, 'refunded', 'Amount on invoice has been refunded'),
(10, 'disputed', 'Invoice is under dispute by the client'),
(11, 'archived', 'Invoice is closed and archived for record-keeping');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_types`
--

CREATE TABLE `invoice_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `direction` enum('incoming','outgoing') NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_types`
--

INSERT INTO `invoice_types` (`type_id`, `type_name`, `direction`, `description`) VALUES
(1, 'sales', 'outgoing', 'Invoice for product or service sold to clients'),
(2, 'purchase', 'incoming', 'Invoice for goods/services bought from vendors'),
(3, 'credit_note', 'outgoing', 'Reduces a previously issued invoice'),
(4, 'debit_note', 'incoming', 'Additional charge from a vendor'),
(5, 'refund', 'outgoing', 'Refund payment to a customer'),
(6, 'advance', 'incoming', 'Advance payment request from a vendor or contractor'),
(7, 'retainer', 'outgoing', 'Prepaid amount to secure future service'),
(8, 'adjustment', 'outgoing', 'Financial adjustment related to a previous invoice'),
(9, 'reimbursement', 'incoming', 'Reimbursement request for internal expenses'),
(10, 'intercompany', 'outgoing', 'Invoices exchanged between departments or branches'),
(11, 'miscellaneous', 'outgoing', 'Other small charges not categorized under main invoice types'),
(12, 'tax_invoice', 'outgoing', 'Invoice issued specifically for tax recording and reporting'),
(13, 'service_invoice', 'outgoing', 'Invoice related only to services without product delivery'),
(14, 'maintenance', 'incoming', 'Recurring invoice from vendor for maintenance services'),
(15, 'subscription', 'incoming', 'Invoice for software or service subscriptions');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `method_id` int(11) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `log_temp` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`method_id`, `method_name`, `description`, `log_temp`) VALUES
(1, 'cash', 'Cash payment', '../img/cash.png'),
(2, 'card', 'Credit or debit card', '../img/card.png'),
(3, 'bank_transfer', 'Bank account transfer', '../img/bank_transfer.png'),
(4, 'mobile_money', 'Mobile payment such as M-Pesa, Airtel Money', '../img/momo.png');

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `receipt_id` int(11) NOT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `payment_id` int(11) NOT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issued_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_accounts`
--

CREATE TABLE `tbl_accounts` (
  `account_id` int(11) NOT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_reference` varchar(255) DEFAULT NULL,
  `account_method_type` int(11) DEFAULT NULL,
  `balance` decimal(14,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_accounts`
--

INSERT INTO `tbl_accounts` (`account_id`, `account_name`, `account_reference`, `account_method_type`, `balance`, `created_at`) VALUES
(1, 'Cash on Hand', NULL, 1, 1500.00, '2025-08-05 08:30:00'),
(2, 'Bank - Equity Bank', NULL, 2, -43749.50, '2025-08-05 08:31:00'),
(3, 'Accounts Receivable', NULL, 3, 3450.00, '2025-08-05 08:31:30'),
(4, 'Accounts Payable', NULL, 4, 2200.00, '2025-08-05 08:32:00'),
(5, 'Mobile Money Wallet', NULL, 5, 870.75, '2025-08-05 08:33:00'),
(6, 'Bank - Bank of Kigali', NULL, 2, 4850.00, '2025-08-05 08:34:00');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_clients`
--

CREATE TABLE `tbl_clients` (
  `client_id` int(11) NOT NULL,
  `client_code` varchar(20) DEFAULT NULL,
  `f_name` varchar(50) DEFAULT NULL,
  `l_name` varchar(50) DEFAULT NULL,
  `client_type` int(11) NOT NULL DEFAULT 0,
  `financial_type` int(11) NOT NULL DEFAULT 0,
  `tin_number` varchar(20) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email_address` varchar(50) DEFAULT NULL,
  `status` tinyint(2) NOT NULL DEFAULT 1 COMMENT '1=active, 0=disactivated',
  `location_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_clients`
--

INSERT INTO `tbl_clients` (`client_id`, `client_code`, `f_name`, `l_name`, `client_type`, `financial_type`, `tin_number`, `phone_number`, `email_address`, `status`, `location_address`) VALUES
(1, 'ITEC001', 'CHAMPIONS', 'Cooperation', 1, 1, '1111118', '1234567890', 'john.doe@example.com', 1, 'Kigali, Rwanda'),
(2, 'ITEC002', 'MIC', NULL, 1, 1, '1151118', '07883095345', 'mic.doe@example.com', 1, 'Kigali, Rwanda');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_clients_contracts`
--

CREATE TABLE `tbl_clients_contracts` (
  `contract_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `contract_number` varchar(100) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `contract_value` decimal(15,2) DEFAULT NULL,
  `payment_interval` decimal(15,2) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_client_projects`
--

CREATE TABLE `tbl_client_projects` (
  `project_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_name` varchar(150) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_client_projects`
--

INSERT INTO `tbl_client_projects` (`project_id`, `client_id`, `project_name`, `product_id`, `description`, `start_date`, `end_date`, `budget`, `status`) VALUES
(1, 1, 'Software Rent', 1, 'Software developed to manage stock and sales', '2025-08-01', '2026-08-01', 200000.00, 1),
(2, 1, 'Ikwim Station and Engeen ', 1, 'all software centeralization all features and functionality', '2025-08-01', '2027-08-01', 200000.00, 1),
(3, 1, 'Sales and Stock management', 1, 'Clother production and sales from low material to product', '2025-08-01', '2028-08-01', 5000000.00, 1),
(4, 1, 'software devlopment for Sales and Stock management', 3, 'Clother production and sales from low material to product', '2025-08-01', '2028-08-01', 5000000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_communications`
--

CREATE TABLE `tbl_communications` (
  `communication_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `file_temp` varchar(50) DEFAULT NULL,
  `STATUS` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_contract_types`
--

CREATE TABLE `tbl_contract_types` (
  `contract_type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_contract_types`
--

INSERT INTO `tbl_contract_types` (`contract_type_id`, `type_name`, `description`, `created_at`) VALUES
(1, 'Service Agreement', 'Agreement for services provided to the client', '2025-08-09 14:30:44'),
(2, 'NDA', 'Non-disclosure agreement', '2025-08-09 14:30:44'),
(3, 'Maintenance Contract', 'Contract for ongoing maintenance services', '2025-08-09 14:30:44'),
(4, 'Sales Contract', 'Contract related to sales transactions', '2025-08-09 14:30:44'),
(5, 'Consulting Agreement', 'Contract for consulting services', '2025-08-09 14:30:44');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_expenses`
--

CREATE TABLE `tbl_expenses` (
  `expense_id` int(11) NOT NULL,
  `expense_category` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `payment_mode` int(11) DEFAULT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_expenses`
--

INSERT INTO `tbl_expenses` (`expense_id`, `expense_category`, `description`, `amount`, `payment_date`, `recorded_by`, `payment_mode`, `consumer_id`, `created_at`) VALUES
(1, 1, 'tips and bonus', 450000.00, '2025-08-11', 11, 1, 12, '2025-08-11 15:39:15'),
(2, 1, 'lunch and dinner', 35000.00, '2025-08-11', 11, 1, 12, '2025-08-11 15:39:46'),
(3, 1, 'lunch and dinner', 70000.00, '2025-08-11', 11, 1, 12, '2025-08-11 15:42:36'),
(4, 1, 'tips and bonus', 250000.00, '2025-08-11', 11, 1, 12, '2025-08-11 15:44:58'),
(6, 3, 'lunch and dinner', 70000.00, '2025-08-11', 11, 1, 12, '2025-08-11 15:45:37'),
(7, 3, 'lunch and dinner', 70000.00, '2025-08-11', 11, 1, 12, '2025-08-11 15:46:40'),
(8, 3, 'lunch and dinner', 70000.00, '2025-08-11', 11, 1, 12, '2025-08-11 15:48:35'),
(9, 3, 'Janiya and Wingo Transport', 70000.00, '2025-08-11', 11, 1, 12, '2025-08-11 15:49:00'),
(12, 3, 'Janiya and Wingo Transport', 90000.00, '2025-08-11', 11, 1, 12, '2025-08-11 16:57:09');

--
-- Triggers `tbl_expenses`
--
DELIMITER $$
CREATE TRIGGER `after_expense_delete` AFTER DELETE ON `tbl_expenses` FOR EACH ROW BEGIN
    INSERT INTO tbl_expenses_audit (
        action_type,
        expense_id,
        expense_category,
        description,
        amount,
        payment_date,
        recorded_by,
        payment_mode,
        consumer_id,
        created_at
    ) VALUES (
        'DELETE',
        OLD.expense_id,
        OLD.expense_category,
        OLD.description,
        OLD.amount,
        OLD.payment_date,
        OLD.recorded_by,
        OLD.payment_mode,
        OLD.consumer_id,
        OLD.created_at
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_expense_update` AFTER UPDATE ON `tbl_expenses` FOR EACH ROW BEGIN
    INSERT INTO tbl_expenses_audit (
        action_type,
        expense_id,
        expense_category,
        description,
        amount,
        payment_date,
        recorded_by,
        payment_mode,
        consumer_id,
        created_at
    ) VALUES (
        'UPDATE',
        OLD.expense_id,
        OLD.expense_category,
        OLD.description,
        OLD.amount,
        OLD.payment_date,
        OLD.recorded_by,
        OLD.payment_mode,
        OLD.consumer_id,
        OLD.created_at
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_expenses_archive`
--

CREATE TABLE `tbl_expenses_archive` (
  `expense_id` int(11) NOT NULL,
  `expense_category` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `payment_mode` int(11) DEFAULT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_expenses_archive`
--

INSERT INTO `tbl_expenses_archive` (`expense_id`, `expense_category`, `description`, `amount`, `payment_date`, `recorded_by`, `payment_mode`, `consumer_id`, `created_at`) VALUES
(11, 0, 'Old Expense', 100.00, '2020-01-01', 0, 0, 11, '2025-08-11 16:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_expenses_audit`
--

CREATE TABLE `tbl_expenses_audit` (
  `audit_id` int(11) NOT NULL,
  `action_type` enum('UPDATE','DELETE') DEFAULT NULL,
  `expense_id` int(11) DEFAULT NULL,
  `expense_category` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `recorded_by` varchar(255) DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_expenses_audit`
--

INSERT INTO `tbl_expenses_audit` (`audit_id`, `action_type`, `expense_id`, `expense_category`, `description`, `amount`, `payment_date`, `recorded_by`, `payment_mode`, `consumer_id`, `created_at`, `action_time`) VALUES
(1, 'UPDATE', 4, '1', 'tips and bonus', 15000.00, '2025-08-11', '11', '1', 12, '2025-08-11 15:44:58', '2025-08-11 14:09:58'),
(2, 'UPDATE', 4, '1', 'tips and bonus', 150000.00, '2025-08-11', '11', '1', 12, '2025-08-11 15:44:58', '2025-08-11 14:10:37'),
(3, 'DELETE', 5, '1', 'lunch and dinner', 70000.00, '2025-08-11', '11', '1', 12, '2025-08-11 15:45:14', '2025-08-11 14:13:24'),
(4, 'UPDATE', 1, '1', 'lunch and dinner', 70000.00, '2025-08-11', '11', '1', 12, '2025-08-11 15:39:15', '2025-08-11 14:25:34'),
(5, 'DELETE', 11, '0', 'Old Expense', 100.00, '2020-01-01', '0', '0', 11, '2025-08-11 16:51:31', '2025-08-11 14:51:58'),
(6, 'UPDATE', 1, '1', 'tips and bonus', 450000.00, '2025-08-11', '11', '1', 12, '2025-08-11 15:39:15', '2025-08-11 14:55:50'),
(7, 'UPDATE', 1, '1', 'tips and bonus', 450000.00, '2025-08-11', '11', '1', 12, '2025-08-11 15:39:15', '2025-08-11 14:56:32');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoices`
--

CREATE TABLE `tbl_invoices` (
  `invoice_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `invoice_type` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(3) DEFAULT 1,
  `total_paid` decimal(15,2) DEFAULT 0.00,
  `payment_status` enum('Unpaid','Partially Paid','Paid') DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_invoices`
--

INSERT INTO `tbl_invoices` (`invoice_id`, `client_id`, `invoice_number`, `invoice_date`, `due_date`, `total_amount`, `notes`, `created_at`, `invoice_type`, `status`, `total_paid`, `payment_status`) VALUES
(1, 1, 'ITEC002', '2025-08-10', '2025-08-06', 20000.00, 'July invoices', '2025-08-06 17:15:02', 1, 1, 0.00, 'Unpaid'),
(5, 1, 'ITEC003', '2025-08-10', '2025-08-06', 20000.00, 'July invoices', '2025-08-06 17:20:50', 1, 1, 0.00, 'Unpaid'),
(10, 1, 'ITEC0034', '2025-08-10', '2025-08-06', 20000.00, 'July invoices', '2025-08-06 17:28:34', 1, 1, 0.00, 'Unpaid'),
(12, 1, 'ITEC008', '2025-08-10', '2025-08-06', 20000.00, 'July invoices', '2025-08-06 17:29:36', 1, 1, 0.00, 'Unpaid'),
(14, 1, 'ITEC1048', '2025-08-10', '2025-08-06', 70000.00, 'July invoices', '2025-08-07 09:01:02', 1, 1, 0.00, 'Unpaid'),
(15, 1, 'ITEC15048', '2025-08-10', '2025-08-06', 70000.00, 'July invoices', '2025-08-07 12:19:01', 1, 1, 68000.00, 'Partially Paid');

--
-- Triggers `tbl_invoices`
--
DELIMITER $$
CREATE TRIGGER `trg_before_delete_invoice` BEFORE DELETE ON `tbl_invoices` FOR EACH ROW BEGIN
    INSERT INTO tbl_invoices_audit 
    (invoice_id, client_id, invoice_number, invoice_date, due_date, total_amount, notes, created_at, invoice_type, status, operation_type)
    VALUES 
    (OLD.invoice_id, OLD.client_id, OLD.invoice_number, OLD.invoice_date, OLD.due_date, OLD.total_amount, OLD.notes, OLD.created_at, OLD.invoice_type, OLD.status, 'DELETE');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoices_audit`
--

CREATE TABLE `tbl_invoices_audit` (
  `audit_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `invoice_type` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `operation_type` enum('UPDATE','DELETE') DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_invoices_audit`
--

INSERT INTO `tbl_invoices_audit` (`audit_id`, `invoice_id`, `client_id`, `invoice_number`, `invoice_date`, `due_date`, `total_amount`, `notes`, `created_at`, `invoice_type`, `status`, `operation_type`, `changed_at`) VALUES
(7, 13, 1, 'ITEC0048', '2025-08-10', '2025-08-06', 20000.00, 'July invoices', '2025-08-06 17:30:32', 1, '1', 'DELETE', '2025-08-09 15:11:27');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoice_items`
--

CREATE TABLE `tbl_invoice_items` (
  `invo_item_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `discount` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) GENERATED ALWAYS AS (`unit_price` * `quantity` - `discount` + (`unit_price` * `quantity` - `discount`) * `tax_rate` / 100) STORED,
  `created_at` datetime DEFAULT current_timestamp(),
  `invoice_type` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_invoice_items`
--

INSERT INTO `tbl_invoice_items` (`invo_item_id`, `invoice_id`, `product_id`, `quantity`, `unit_price`, `tax_rate`, `discount`, `created_at`, `invoice_type`) VALUES
(1, 1, 4, 2, 4000.00, 0.00, 0.00, '2025-08-07 11:37:40', 1),
(2, 12, 3, 2, 25000.00, 0.00, 0.00, '2025-08-07 12:31:26', 2),
(3, 12, 3, 2, 25000.00, 0.00, 0.00, '2025-08-07 12:32:03', 2),
(4, 12, 3, 2, 25000.00, 0.00, 0.00, '2025-08-07 12:32:11', 2),
(5, 12, 3, 2, 25000.00, 0.00, 0.00, '2025-08-07 12:32:12', 2),
(7, 1, 3, 2, 25000.00, 0.00, 0.00, '2025-08-07 12:32:34', 2),
(8, 1, 3, 2, 25000.00, 0.00, 1000.00, '2025-08-07 12:32:55', 2),
(9, 1, 3, 2, 25000.00, 10.00, 1000.00, '2025-08-07 12:50:45', 2),
(10, 1, 3, 2, 25000.00, 10.00, 1000.00, '2025-08-07 12:52:48', 2),
(11, 1, 3, 2, 75000.00, 10.00, 1000.00, '2025-08-09 11:05:52', 2),
(13, 5, 3, 2, 75000.00, 10.00, 1000.00, '2025-08-09 11:16:21', 2),
(14, 5, 3, 2, 75000.00, 10.00, 1000.00, '2025-08-09 12:25:49', 2),
(15, 5, 4, 1, 30000.00, 5.00, 0.00, '2025-08-09 12:25:49', 2),
(16, 5, 6, 5, 15000.00, 0.00, 500.00, '2025-08-09 12:25:49', 2),
(18, 10, 3, 2, 75000.00, 10.00, 1000.00, '2025-08-09 12:30:07', 2),
(19, 10, 4, 1, 30000.00, 5.00, 0.00, '2025-08-09 12:30:07', 2),
(20, 10, 6, 5, 15000.00, 0.00, 500.00, '2025-08-09 12:30:07', 2);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoice_payments`
--

CREATE TABLE `tbl_invoice_payments` (
  `payment_id` int(11) NOT NULL,
  `transaction_code` varchar(255) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `payment_mode` int(11) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` tinyint(2) NOT NULL DEFAULT 1 COMMENT '1=real,0=cancelled',
  `pay_side` int(2) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_invoice_payments`
--

INSERT INTO `tbl_invoice_payments` (`payment_id`, `transaction_code`, `client_id`, `invoice_id`, `payment_date`, `amount`, `payment_mode`, `received_by`, `notes`, `created_at`, `status`, `pay_side`) VALUES
(11, '20250809145336-689744d0d8d971770', 1, 1, '2025-08-10', 20000.00, 1, 1, 'payment for July invoices', '2025-08-09 14:53:36', 1, 1),
(12, '20250809145439-6897450f30fcc8275', 1, 5, '2025-08-10', 20000.00, 1, 1, 'payment for July invoices', '2025-08-09 14:54:39', 1, 1),
(13, '20250809151957-68974afd58eaf4346', 1, 10, '2025-08-10', 20000.00, 1, 1, 'payment for July invoices', '2025-08-09 15:19:57', 1, 1),
(14, '20250809152941-68974d45300025675', 1, 12, '2025-08-10', 15000.00, 1, 1, 'payment for July invoices', '2025-08-09 15:29:41', 1, 1),
(15, '20250809153028-68974d74738269486', 1, 12, '2025-08-10', 4000.00, 1, 1, 'sending payment for July invoices', '2025-08-09 15:30:28', 1, 1),
(17, '20250809153638-68974ee687cec3117', 1, 14, '2025-08-10', 4000.00, 1, 1, 'sending payment for July invoices', '2025-08-09 15:36:38', 1, 1),
(18, '20250809153658-68974efab02b45641', 1, 15, '2025-08-10', 4000.00, 1, 1, 'sending payment for July invoices', '2025-08-09 15:36:58', 1, 1),
(19, '20250809153729-68974f196e67b1984', 1, 15, '2025-08-10', 16000.00, 1, 1, 'sending payment for July invoices', '2025-08-09 15:37:29', 1, 1),
(20, '20250809153731-68974f1bbe46c4000', 1, 15, '2025-08-10', 16000.00, 1, 1, 'sending payment for July invoices', '2025-08-09 15:37:31', 1, 1),
(21, '20250809153735-68974f1f6e1046588', 1, 15, '2025-08-10', 16000.00, 1, 1, 'sending payment for July invoices', '2025-08-09 15:37:35', 1, 1),
(22, '20250809153736-68974f20a33433774', 1, 15, '2025-08-10', 16000.00, 1, 1, 'sending payment for July invoices', '2025-08-09 15:37:36', 1, 1),
(26, 'TXN12345', 1, 15, '2025-08-11', 150.00, 0, 0, 'Paid in full', '2025-08-11 17:43:24', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_journal_entries`
--

CREATE TABLE `tbl_journal_entries` (
  `entry_id` int(11) NOT NULL,
  `entry_date` date DEFAULT NULL,
  `debit_account_id` int(11) DEFAULT NULL,
  `credit_account_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `method_id` int(11) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_journal_entries`
--

INSERT INTO `tbl_journal_entries` (`entry_id`, `entry_date`, `debit_account_id`, `credit_account_id`, `amount`, `method_id`, `reference_id`, `description`, `created_at`) VALUES
(1, '2025-08-10', 1, NULL, 20000.00, 1, 11, 'payment for July invoices Payment received for invoice #1, Transaction: 20250809145336-689744d0d8d971770', '2025-08-09 14:53:36'),
(2, '2025-08-10', 1, NULL, 20000.00, 1, 12, 'payment for July invoices Payment received for invoice #5, Transaction: 20250809145439-6897450f30fcc8275', '2025-08-09 14:54:39'),
(3, '2025-08-10', 1, NULL, 20000.00, 1, 13, 'payment for July invoices Payment received for invoice #10, Transaction: 20250809151957-68974afd58eaf4346', '2025-08-09 15:19:57'),
(4, '2025-08-10', NULL, 1, 15000.00, 1, 14, 'payment for July invoices Payment received for invoice #12, Transaction: 20250809152941-68974d45300025675', '2025-08-09 15:29:41'),
(5, '2025-08-10', NULL, 1, 4000.00, 1, 15, 'sending payment for July invoices Payment received for invoice #12, Transaction: 20250809153028-68974d74738269486', '2025-08-09 15:30:28'),
(6, '2025-08-10', 2, NULL, 20000.00, 1, 16, 'payment for July invoices Payment received for invoice #13, Transaction: 20250809153454-68974e7e076491007', '2025-08-09 15:34:54'),
(7, '2025-08-10', NULL, 2, 4000.00, 1, 17, 'sending payment for July invoices Payment received for invoice #14, Transaction: 20250809153638-68974ee687cec3117', '2025-08-09 15:36:38'),
(8, '2025-08-10', NULL, 2, 4000.00, 1, 18, 'sending payment for July invoices Payment received for invoice #15, Transaction: 20250809153658-68974efab02b45641', '2025-08-09 15:36:58'),
(9, '2025-08-10', NULL, 2, 16000.00, 1, 19, 'sending payment for July invoices Payment received for invoice #15, Transaction: 20250809153729-68974f196e67b1984', '2025-08-09 15:37:29'),
(10, '2025-08-10', NULL, 2, 16000.00, 1, 20, 'sending payment for July invoices Payment received for invoice #15, Transaction: 20250809153731-68974f1bbe46c4000', '2025-08-09 15:37:31'),
(11, '2025-08-10', NULL, 2, 16000.00, 1, 21, 'sending payment for July invoices Payment received for invoice #15, Transaction: 20250809153735-68974f1f6e1046588', '2025-08-09 15:37:35'),
(12, '2025-08-10', NULL, 2, 16000.00, 1, 22, 'sending payment for July invoices Payment received for invoice #15, Transaction: 20250809153736-68974f20a33433774', '2025-08-09 15:37:36'),
(13, '2025-08-10', NULL, 2, 2000.00, 1, 23, 'sending payment for July invoices Payment received for invoice #15, Transaction: 20250809160435-68975573e43a38155', '2025-08-09 16:04:35'),
(14, '2025-08-11', 6, 6, 150.00, NULL, 26, 'Payment TXN12345', '2025-08-11 17:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_products`
--

CREATE TABLE `tbl_products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_products`
--

INSERT INTO `tbl_products` (`product_id`, `product_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'SaaS Subscription - Basic Plan', 'Monthly subscription for cloud software basic plan', 1, '2025-08-07 09:05:58'),
(2, 'Custom Software Development', 'Bespoke software development services per project basis', 1, '2025-08-07 09:05:58'),
(3, 'Technical Support - Tier 1', 'Entry-level technical support for software or hardware issues', 1, '2025-08-07 09:05:58'),
(4, 'Cloud Storage - 100GB', 'Cloud-based storage of 100GB capacity per user/month', 1, '2025-08-07 09:05:58'),
(5, 'Onsite IT Consultation', 'Professional consultation services for IT infrastructure', 1, '2025-08-07 09:05:58'),
(6, 'License Renewal - Enterprise Suite', 'Annual license renewal for enterprise suite tools', 1, '2025-08-07 09:05:58'),
(7, 'Server Maintenance Plan', 'Yearly server maintenance and uptime assurance package', 1, '2025-08-07 09:05:58'),
(8, 'IoT Device Kit', 'Hardware kit including sensors and microcontrollers for IoT solutions', 1, '2025-08-07 09:05:58'),
(9, 'ITEC Tab for RIEX', 'Software developed to manage stock and sales and other', 1, '2025-08-07 09:37:12'),
(10, 'Software management Rent', 'Software developed to manage stock and sales and other', 1, '2025-08-07 09:39:13');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_projects`
--

CREATE TABLE `tbl_projects` (
  `project_id` int(11) NOT NULL,
  `project_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `rank` int(11) DEFAULT 1,
  `budget` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_projects`
--

INSERT INTO `tbl_projects` (`project_id`, `project_name`, `description`, `start_date`, `rank`, `budget`, `created_at`) VALUES
(1, 'Cloud Infrastructure Setup', 'Design and deploy cloud infrastructure using AWS and Azure.', '2025-08-01', 1, 50000.00, '2025-08-09 14:36:24'),
(2, 'Mobile App Development', 'Build cross-platform mobile app for e-commerce platform.', '2025-07-15', 2, 35000.00, '2025-08-09 14:36:24'),
(3, 'AI Chatbot Integration', 'Develop and integrate AI chatbot for customer support.', '2025-08-10', 3, 20000.00, '2025-08-09 14:36:24'),
(4, 'Cybersecurity Audit', 'Comprehensive security audit and vulnerability assessment.', '2025-06-20', 4, 15000.00, '2025-08-09 14:36:24'),
(5, 'Data Analytics Platform', 'Create a scalable data analytics platform for business intelligence.', '2025-08-05', 5, 40000.00, '2025-08-09 14:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_project_assignments`
--

CREATE TABLE `tbl_project_assignments` (
  `assignment_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_role` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_project_assignments`
--

INSERT INTO `tbl_project_assignments` (`assignment_id`, `project_id`, `user_id`, `assigned_role`, `assigned_by`, `assigned_at`) VALUES
(4, 1, 11, 2, 2, '2025-08-11 12:33:15'),
(5, 1, 11, 2, 2, '2025-08-11 12:33:16'),
(6, 1, 11, 2, 2, '2025-08-11 12:33:17'),
(7, 1, 11, 2, 2, '2025-08-11 12:33:18'),
(8, 2, 11, 2, 2, '2025-08-11 12:36:29'),
(9, 3, 11, 2, 2, '2025-08-11 12:36:46');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_roles`
--

CREATE TABLE `tbl_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_roles`
--

INSERT INTO `tbl_roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'admin', 'Full access'),
(2, 'finance', 'Manage payments and invoices'),
(3, 'viewer', 'Read-only access'),
(4, 'General Staff', 'Specified Features');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `acc_id` int(11) NOT NULL,
  `user_code` varchar(20) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `security_key` varchar(500) DEFAULT NULL,
  `role_id` varchar(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `last_logged_in` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`acc_id`, `user_code`, `last_name`, `first_name`, `phone`, `email`, `security_key`, `role_id`, `status`, `last_logged_in`) VALUES
(11, '250001', 'Jean Baptiste', ' SHUMBUSHO', '+250788644687', 'dev@itec.rw', '$argon2i$v=19$m=65536,t=4,p=1$ZHBjT3E1YlU0d2JWV2tzbA$MfSCemOaCDfb3LYl/GSJYY0XwFBVJgAyIVIHewmqXXw', '1', 1, NULL),
(12, '250002', 'Jean', ' Baptiste', '+250788644687', 'jean@itec.com', '$argon2i$v=19$m=65536,t=4,p=1$clRLTExBR2taWGRRcUNEWQ$+lJ+rrn07hPUpeZe04CtGMucmR5BSh8tGKyTzyCCIzA', '1', 1, NULL),
(13, '250003', 'Jean', ' Baptiste', '+250788644687', 'finance@itec.com', '$argon2i$v=19$m=65536,t=4,p=1$SVZHTWhwekZNUzk3YzhMVA$bp8Gww5LChp/qeVu+eX/Ze1jLS16Mwr4kxfd5lLqveU', '2', 1, NULL),
(14, '250004', 'Celestin', ' MPAYIMANA', '+250788644687', 'finance@itec.rw', '$argon2i$v=19$m=65536,t=4,p=1$dW5OTWNoYzV1M1JCeklWSQ$gQ9f0O5G/A5qJa6zjWoirMRHqjOTHkQ5/okfKoGatJw', '1', 1, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_expenses_audit`
-- (See below for the actual view)
--
CREATE TABLE `view_expenses_audit` (
`audit_id` int(11)
,`action_type` enum('UPDATE','DELETE')
,`expense_id` int(11)
,`expense_category` varchar(255)
,`description` text
,`amount` decimal(10,2)
,`payment_date` date
,`recorded_by` varchar(255)
,`payment_mode` varchar(50)
,`consumer_id` int(11)
,`created_at` datetime
,`action_time` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `view_expenses_audit`
--
DROP TABLE IF EXISTS `view_expenses_audit`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_expenses_audit`  AS SELECT `tbl_expenses_audit`.`audit_id` AS `audit_id`, `tbl_expenses_audit`.`action_type` AS `action_type`, `tbl_expenses_audit`.`expense_id` AS `expense_id`, `tbl_expenses_audit`.`expense_category` AS `expense_category`, `tbl_expenses_audit`.`description` AS `description`, `tbl_expenses_audit`.`amount` AS `amount`, `tbl_expenses_audit`.`payment_date` AS `payment_date`, `tbl_expenses_audit`.`recorded_by` AS `recorded_by`, `tbl_expenses_audit`.`payment_mode` AS `payment_mode`, `tbl_expenses_audit`.`consumer_id` AS `consumer_id`, `tbl_expenses_audit`.`created_at` AS `created_at`, `tbl_expenses_audit`.`action_time` AS `action_time` FROM `tbl_expenses_audit` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client_type`
--
ALTER TABLE `client_type`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `financial_classification`
--
ALTER TABLE `financial_classification`
  ADD PRIMARY KEY (`classify_id`);

--
-- Indexes for table `invoice_statuses`
--
ALTER TABLE `invoice_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `invoice_types`
--
ALTER TABLE `invoice_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`method_id`),
  ADD UNIQUE KEY `method_name` (`method_name`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `issued_by` (`issued_by`);

--
-- Indexes for table `tbl_accounts`
--
ALTER TABLE `tbl_accounts`
  ADD PRIMARY KEY (`account_id`);

--
-- Indexes for table `tbl_clients`
--
ALTER TABLE `tbl_clients`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `tbl_clients_contracts`
--
ALTER TABLE `tbl_clients_contracts`
  ADD PRIMARY KEY (`contract_id`),
  ADD UNIQUE KEY `contract_number` (`contract_number`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `tbl_client_projects`
--
ALTER TABLE `tbl_client_projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `tbl_communications`
--
ALTER TABLE `tbl_communications`
  ADD PRIMARY KEY (`communication_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `tbl_contract_types`
--
ALTER TABLE `tbl_contract_types`
  ADD PRIMARY KEY (`contract_type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `tbl_expenses`
--
ALTER TABLE `tbl_expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `consumer_id` (`consumer_id`);

--
-- Indexes for table `tbl_expenses_archive`
--
ALTER TABLE `tbl_expenses_archive`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `consumer_id` (`consumer_id`);

--
-- Indexes for table `tbl_expenses_audit`
--
ALTER TABLE `tbl_expenses_audit`
  ADD PRIMARY KEY (`audit_id`);

--
-- Indexes for table `tbl_invoices`
--
ALTER TABLE `tbl_invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `tbl_invoices_audit`
--
ALTER TABLE `tbl_invoices_audit`
  ADD PRIMARY KEY (`audit_id`);

--
-- Indexes for table `tbl_invoice_items`
--
ALTER TABLE `tbl_invoice_items`
  ADD PRIMARY KEY (`invo_item_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_invoice_payments`
--
ALTER TABLE `tbl_invoice_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `tbl_journal_entries`
--
ALTER TABLE `tbl_journal_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `debit_account_id` (`debit_account_id`),
  ADD KEY `credit_account_id` (`credit_account_id`);

--
-- Indexes for table `tbl_products`
--
ALTER TABLE `tbl_products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  ADD PRIMARY KEY (`project_id`);

--
-- Indexes for table `tbl_project_assignments`
--
ALTER TABLE `tbl_project_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `acc_id` (`user_id`);

--
-- Indexes for table `tbl_roles`
--
ALTER TABLE `tbl_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`acc_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client_type`
--
ALTER TABLE `client_type`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `financial_classification`
--
ALTER TABLE `financial_classification`
  MODIFY `classify_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoice_statuses`
--
ALTER TABLE `invoice_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `invoice_types`
--
ALTER TABLE `invoice_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `method_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_accounts`
--
ALTER TABLE `tbl_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_clients`
--
ALTER TABLE `tbl_clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_clients_contracts`
--
ALTER TABLE `tbl_clients_contracts`
  MODIFY `contract_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_client_projects`
--
ALTER TABLE `tbl_client_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_communications`
--
ALTER TABLE `tbl_communications`
  MODIFY `communication_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_contract_types`
--
ALTER TABLE `tbl_contract_types`
  MODIFY `contract_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_expenses`
--
ALTER TABLE `tbl_expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_expenses_archive`
--
ALTER TABLE `tbl_expenses_archive`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_expenses_audit`
--
ALTER TABLE `tbl_expenses_audit`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_invoices`
--
ALTER TABLE `tbl_invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_invoices_audit`
--
ALTER TABLE `tbl_invoices_audit`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_invoice_items`
--
ALTER TABLE `tbl_invoice_items`
  MODIFY `invo_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tbl_invoice_payments`
--
ALTER TABLE `tbl_invoice_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `tbl_journal_entries`
--
ALTER TABLE `tbl_journal_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tbl_products`
--
ALTER TABLE `tbl_products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_project_assignments`
--
ALTER TABLE `tbl_project_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_roles`
--
ALTER TABLE `tbl_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `acc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `receipts`
--
ALTER TABLE `receipts`
  ADD CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `tbl_invoice_payments` (`payment_id`),
  ADD CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`issued_by`) REFERENCES `tbl_users` (`acc_id`);

--
-- Constraints for table `tbl_clients_contracts`
--
ALTER TABLE `tbl_clients_contracts`
  ADD CONSTRAINT `tbl_clients_contracts_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `tbl_clients` (`client_id`);

--
-- Constraints for table `tbl_client_projects`
--
ALTER TABLE `tbl_client_projects`
  ADD CONSTRAINT `tbl_client_projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `tbl_clients` (`client_id`);

--
-- Constraints for table `tbl_communications`
--
ALTER TABLE `tbl_communications`
  ADD CONSTRAINT `tbl_communications_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `tbl_clients` (`client_id`),
  ADD CONSTRAINT `tbl_communications_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `tbl_projects` (`project_id`);

--
-- Constraints for table `tbl_expenses`
--
ALTER TABLE `tbl_expenses`
  ADD CONSTRAINT `tbl_expenses_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `tbl_users` (`acc_id`);

--
-- Constraints for table `tbl_invoices`
--
ALTER TABLE `tbl_invoices`
  ADD CONSTRAINT `tbl_invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `tbl_clients` (`client_id`);

--
-- Constraints for table `tbl_invoice_items`
--
ALTER TABLE `tbl_invoice_items`
  ADD CONSTRAINT `tbl_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_invoices` (`invoice_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`);

--
-- Constraints for table `tbl_invoice_payments`
--
ALTER TABLE `tbl_invoice_payments`
  ADD CONSTRAINT `tbl_invoice_payments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `tbl_clients` (`client_id`),
  ADD CONSTRAINT `tbl_invoice_payments_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_invoices` (`invoice_id`);

--
-- Constraints for table `tbl_journal_entries`
--
ALTER TABLE `tbl_journal_entries`
  ADD CONSTRAINT `tbl_journal_entries_ibfk_1` FOREIGN KEY (`debit_account_id`) REFERENCES `tbl_accounts` (`account_id`),
  ADD CONSTRAINT `tbl_journal_entries_ibfk_2` FOREIGN KEY (`credit_account_id`) REFERENCES `tbl_accounts` (`account_id`);

--
-- Constraints for table `tbl_project_assignments`
--
ALTER TABLE `tbl_project_assignments`
  ADD CONSTRAINT `tbl_project_assignments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `tbl_projects` (`project_id`),
  ADD CONSTRAINT `tbl_project_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`acc_id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `archive_old_expenses` ON SCHEDULE EVERY 1 MONTH STARTS '2025-08-11 17:00:31' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    INSERT INTO tbl_expenses_archive
    SELECT * FROM tbl_expenses
    WHERE payment_date < NOW() - INTERVAL 1 YEAR;
   
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
