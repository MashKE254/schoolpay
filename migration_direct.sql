-- ============================================================================
-- SchoolPay Enhancement Migration for Bloomsfield Fee Structure
-- ============================================================================
-- Run this script directly in MySQL to add support for:
-- 1. Transport Zone Management (8 zones with round-trip/one-way pricing)
-- 2. One-Time & Annual Fees (Admission, Diary, Insurance, etc.)
-- 3. Student Activity Enrollment (Extra-curricular activities)
-- ============================================================================
-- IMPORTANT: Set your school_id below (default is 1)
-- ============================================================================

SET @school_id = 1;  -- CHANGE THIS to your actual school_id if different

-- ============================================================================
-- PART 1: TRANSPORT ZONE MANAGEMENT
-- ============================================================================

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

-- ============================================================================
-- PART 2: ONE-TIME & ANNUAL FEES SUPPORT
-- ============================================================================

-- Add fee_frequency column to items table (if not exists)
ALTER TABLE `items`
ADD COLUMN IF NOT EXISTS `fee_frequency` enum('recurring','one_time','annual') NOT NULL DEFAULT 'recurring' AFTER `description`;

-- Add applies_to column to items table (if not exists)
ALTER TABLE `items`
ADD COLUMN IF NOT EXISTS `applies_to` enum('all','new_students_only','existing_students_only') DEFAULT 'all' AFTER `fee_frequency`;

-- Create one_time_fees_billed table (tracks which one-time fees have been billed)
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

-- ============================================================================
-- PART 3: STUDENT ACTIVITY ENROLLMENT
-- ============================================================================

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

-- ============================================================================
-- PART 4: SEED DATA FOR BLOOMSFIELD KINDERGARTEN & SCHOOL
-- ============================================================================

-- Insert Bloomsfield Transport Zones
INSERT INTO `transport_zones` (`school_id`, `zone_name`, `description`, `round_trip_amount`, `one_way_amount`, `status`) VALUES
(@school_id, 'ZONE-1', 'Delta, Ruaka town, Joyland', 10000.00, 7000.00, 'active'),
(@school_id, 'ZONE-2', 'Mifereji, Kahigo, Guango, Kigwaru', 11500.00, 9000.00, 'active'),
(@school_id, 'ZONE-3', 'Mucatha, Gacharage, Sacred heart, Ndenderu', 14000.00, 12500.00, 'active'),
(@school_id, 'ZONE-4', 'Runda, Gigiri, Banana, Karura, Gachie', 15500.00, 13000.00, 'active'),
(@school_id, 'ZONE-5', 'Kiambu, Marurul, Kihara, Laini Ridgeways', 19500.00, 17500.00, 'active'),
(@school_id, 'ZONE-6', 'Redhill, Nazareth, Windsor', 22500.00, 18500.00, 'active'),
(@school_id, 'ZONE-7', 'Lower Kabete, Mwimuto, Kitsuru', 24500.00, 21500.00, 'active'),
(@school_id, 'ZONE-8', 'Poster Kabuku, Kiambu town', 26500.00, 23000.00, 'active')
ON DUPLICATE KEY UPDATE
  description=VALUES(description),
  round_trip_amount=VALUES(round_trip_amount),
  one_way_amount=VALUES(one_way_amount);

-- Insert Bloomsfield Activities
INSERT INTO `activities` (`school_id`, `activity_name`, `description`, `fee_per_term`, `category`, `status`) VALUES
(@school_id, 'Skating', 'Ice skating lessons', 5000.00, 'Sports', 'active'),
(@school_id, 'Ballet', 'Classical ballet classes', 5000.00, 'Arts', 'active'),
(@school_id, 'Taekwondo', 'Martial arts training', 5000.00, 'Sports', 'active'),
(@school_id, 'Chess', 'Strategic chess lessons', 5000.00, 'Games', 'active'),
(@school_id, 'Music (Piano, Guitar, Drums or Violin)', 'Musical instrument training', 5000.00, 'Music', 'active'),
(@school_id, 'Languages (Chinese or French)', 'Foreign language classes', 5000.00, 'Languages', 'active'),
(@school_id, 'Basketball', 'Basketball training', 5000.00, 'Sports', 'active'),
(@school_id, 'Gymnastics', 'Gymnastics and flexibility training', 5000.00, 'Sports', 'active'),
(@school_id, 'Swimming', 'Swimming lessons', 5000.00, 'Sports', 'active'),
(@school_id, 'Sports (Basketball, badminton, Dance and Fitness)', 'General sports and fitness', 5000.00, 'Sports', 'active'),
(@school_id, 'Abacus (Optional Grade 1 - 3)', 'Mental math with abacus', 2000.00, 'Academic', 'active')
ON DUPLICATE KEY UPDATE
  description=VALUES(description),
  fee_per_term=VALUES(fee_per_term),
  category=VALUES(category);

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check tables created
SELECT 'Tables Created Successfully!' as Status;

SELECT
  'transport_zones' as TableName,
  COUNT(*) as RecordCount
FROM transport_zones
WHERE school_id = @school_id
UNION ALL
SELECT
  'activities' as TableName,
  COUNT(*) as RecordCount
FROM activities
WHERE school_id = @school_id;

-- Show transport zones
SELECT
  zone_name,
  description,
  round_trip_amount,
  one_way_amount,
  status
FROM transport_zones
WHERE school_id = @school_id
ORDER BY zone_name;

-- Show activities
SELECT
  activity_name,
  category,
  fee_per_term,
  status
FROM activities
WHERE school_id = @school_id
ORDER BY category, activity_name;

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================

SELECT '============================================' as '';
SELECT 'MIGRATION COMPLETED SUCCESSFULLY!' as Status;
SELECT '============================================' as '';
SELECT 'Next Steps:' as '';
SELECT '1. Verify tables and data above' as Step1;
SELECT '2. Go to Transport Management to view/edit zones' as Step2;
SELECT '3. Go to Activities Management to view/edit activities' as Step3;
SELECT '4. Configure your fee items with fee_frequency' as Step4;
SELECT '5. Start assigning students to transport zones' as Step5;
SELECT '6. Start enrolling students in activities' as Step6;
SELECT '============================================' as '';
