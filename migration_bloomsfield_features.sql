-- ============================================================================
-- SchoolPay Enhancement Migration for Bloomsfield Fee Structure
-- ============================================================================
-- This migration adds support for:
-- 1. Transport Zone Management (8 zones with round-trip/one-way pricing)
-- 2. One-Time & Annual Fees (Admission, Diary, Insurance, etc.)
-- 3. Student Activity Enrollment (Extra-curricular activities)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. TRANSPORT ZONE MANAGEMENT
-- ----------------------------------------------------------------------------

-- Create transport_zones table
CREATE TABLE IF NOT EXISTS `transport_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `description` text,
  `round_trip_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `one_way_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  UNIQUE KEY `unique_zone_per_school` (`school_id`, `zone_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create student_transport table (links students to transport zones)
CREATE TABLE IF NOT EXISTS `student_transport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `transport_zone_id` int(11) NOT NULL,
  `trip_type` enum('round_trip','one_way') NOT NULL DEFAULT 'round_trip',
  `academic_year` varchar(20) NOT NULL,
  `term` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `student_id` (`student_id`),
  KEY `transport_zone_id` (`transport_zone_id`),
  UNIQUE KEY `unique_student_transport_per_term` (`student_id`, `academic_year`, `term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 2. ONE-TIME & ANNUAL FEES SUPPORT
-- ----------------------------------------------------------------------------

-- Add fee_frequency column to items table
ALTER TABLE `items`
ADD COLUMN `fee_frequency` enum('recurring','one_time','annual') NOT NULL DEFAULT 'recurring' AFTER `description`,
ADD COLUMN `applies_to` enum('all','new_students_only','existing_students_only') DEFAULT 'all' AFTER `fee_frequency`;

-- Create one_time_fees_billed table (tracks which one-time fees have been billed to students)
CREATE TABLE IF NOT EXISTS `one_time_fees_billed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `billed_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `student_id` (`student_id`),
  KEY `item_id` (`item_id`),
  KEY `invoice_id` (`invoice_id`),
  UNIQUE KEY `unique_one_time_fee` (`student_id`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create annual_fees_billed table (tracks annual fees per academic year)
CREATE TABLE IF NOT EXISTS `annual_fees_billed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `billed_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `student_id` (`student_id`),
  KEY `item_id` (`item_id`),
  KEY `invoice_id` (`invoice_id`),
  UNIQUE KEY `unique_annual_fee` (`student_id`, `item_id`, `academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 3. STUDENT ACTIVITY ENROLLMENT
-- ----------------------------------------------------------------------------

-- Create activities table (master list of available activities)
CREATE TABLE IF NOT EXISTS `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `activity_name` varchar(100) NOT NULL,
  `description` text,
  `fee_per_term` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(50) DEFAULT NULL COMMENT 'e.g., Sports, Arts, Music, Languages',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  UNIQUE KEY `unique_activity_per_school` (`school_id`, `activity_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create student_activities table (tracks student enrollments in activities)
CREATE TABLE IF NOT EXISTS `student_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `term` varchar(20) NOT NULL,
  `enrolled_date` date NOT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `student_id` (`student_id`),
  KEY `activity_id` (`activity_id`),
  UNIQUE KEY `unique_student_activity_per_term` (`student_id`, `activity_id`, `academic_year`, `term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4. SEED DATA FOR BLOOMSFIELD KINDERGARTEN & SCHOOL
-- ----------------------------------------------------------------------------

-- Note: Replace {SCHOOL_ID} with actual school_id when running
-- Example: UPDATE below SET {SCHOOL_ID} = 1;

-- Insert Bloomsfield Transport Zones
INSERT INTO `transport_zones` (`school_id`, `zone_name`, `description`, `round_trip_amount`, `one_way_amount`, `status`) VALUES
({SCHOOL_ID}, 'ZONE-1', 'Delta, Ruaka town, Joyland', 10000.00, 7000.00, 'active'),
({SCHOOL_ID}, 'ZONE-2', 'Mifereji, Kahigo, Guango, Kigwaru', 11500.00, 9000.00, 'active'),
({SCHOOL_ID}, 'ZONE-3', 'Mucatha, Gacharage, Sacred heart, Ndenderu', 14000.00, 12500.00, 'active'),
({SCHOOL_ID}, 'ZONE-4', 'Runda, Gigiri, Banana, Karura, Gachie', 15500.00, 13000.00, 'active'),
({SCHOOL_ID}, 'ZONE-5', 'Kiambu, Marurul, Kihara, Laini Ridgeways', 19500.00, 17500.00, 'active'),
({SCHOOL_ID}, 'ZONE-6', 'Redhill, Nazareth, Windsor', 22500.00, 18500.00, 'active'),
({SCHOOL_ID}, 'ZONE-7', 'Lower Kabete, Mwimuto, Kitsuru', 24500.00, 21500.00, 'active'),
({SCHOOL_ID}, 'ZONE-8', 'Poster Kabuku, Kiambu town', 26500.00, 23000.00, 'active');

-- Insert Bloomsfield Activities
INSERT INTO `activities` (`school_id`, `activity_name`, `description`, `fee_per_term`, `category`, `status`) VALUES
({SCHOOL_ID}, 'Skating', 'Ice skating lessons', 5000.00, 'Sports', 'active'),
({SCHOOL_ID}, 'Ballet', 'Classical ballet classes', 5000.00, 'Arts', 'active'),
({SCHOOL_ID}, 'Taekwondo', 'Martial arts training', 5000.00, 'Sports', 'active'),
({SCHOOL_ID}, 'Chess', 'Strategic chess lessons', 5000.00, 'Games', 'active'),
({SCHOOL_ID}, 'Music (Piano, Guiter, Drums or Violin)', 'Musical instrument training', 5000.00, 'Music', 'active'),
({SCHOOL_ID}, 'Languages (Chinese or French)', 'Foreign language classes', 5000.00, 'Languages', 'active'),
({SCHOOL_ID}, 'Basketball', 'Basketball training', 5000.00, 'Sports', 'active'),
({SCHOOL_ID}, 'Gymnastics', 'Gymnastics and flexibility training', 5000.00, 'Sports', 'active'),
({SCHOOL_ID}, 'Swimming', 'Swimming lessons', 5000.00, 'Sports', 'active'),
({SCHOOL_ID}, 'Sports (Basketball, badminton, Dance and Fitness)', 'General sports and fitness', 5000.00, 'Sports', 'active'),
({SCHOOL_ID}, 'Abacus (Optional Grade 1 - 3)', 'Mental math with abacus', 2000.00, 'Academic', 'active');

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
