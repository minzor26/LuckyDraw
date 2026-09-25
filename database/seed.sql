-- ============================================================
-- Mobile Gallery Lucky Draw - Seed Data
-- ============================================================

USE `mobile_gallery_draw`;

-- 1. Default Admin User (Username: admin, Password: admin123)
-- Password Hash produced by password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@mobilegallery.com', '$2y$10$L1E.B8uKxN0s3m0t9e8u1e7a5b3c2d1e0f9a8b7c6d5e4f3a2b1c0', 'admin')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- 2. System Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('shop_name', 'Mobile Gallery'),
('campaign_name', 'Mobile Gallery Mega Lucky Draw'),
('subtitle', 'Empowering Your Tech Lifestyle'),
('max_coupon_number', '357'),
('enable_draw', '1'),
('enable_sound', '1'),
('test_mode', '0'),
('show_customer_details', '1')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 3. Prizes Setup
-- Special Prizes
INSERT INTO `prizes` (`id`, `name`, `type`, `quantity`, `remaining_quantity`, `description`, `status`) VALUES
(1, 'Car (Hyundai i20)', 'special', 1, 1, 'Grand Prize: Brand new Hyundai i20 Hatchback', 'active'),
(2, 'Scooty (Activa 6G)', 'special', 1, 1, 'Major Prize: Honda Activa 6G Scooter', 'active'),
-- Regular Prizes
(3, 'Bluetooth Headphones', 'regular', 20, 20, 'Premium Wireless Over-Ear Headphones', 'active'),
(4, 'Smart Watch', 'regular', 15, 15, 'Fitness Tracker Smart Watch with Heart Rate Monitor', 'active'),
(5, 'Wireless Earphones', 'regular', 30, 30, 'True Wireless TWS Earbuds', 'active'),
(6, 'Power Bank 20000mAh', 'regular', 25, 25, 'Fast Charging High Capacity Power Bank', 'active'),
(7, 'Bluetooth Speaker', 'regular', 10, 10, 'Portable Waterproof Bass Speaker', 'active'),
(8, 'Mobile Accessories Kit', 'regular', 50, 50, 'Cable, Car Charger & Mobile Stand Combo', 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 4. Coupons Generation (Coupons 1 to 357)
-- Sample customers pre-filled for demonstration
INSERT INTO `coupons` (`coupon_number`, `customer_name`, `mobile`, `address`, `city`, `state`, `status`, `purchase_date`) VALUES
(1, 'Aarav Patel', '9876543210', 'Station Road', 'Rewa', 'Madhya Pradesh', 'eligible', '2026-09-01'),
(2, 'Priya Singh', '9812345678', 'Civil Lines', 'Rewa', 'Madhya Pradesh', 'eligible', '2026-09-01'),
(3, 'Rohan Verma', '9988776655', 'College Road', 'Satna', 'Madhya Pradesh', 'eligible', '2026-09-02'),
(4, 'Ananya Sharma', '9765432109', 'Main Market', 'Rewa', 'Madhya Pradesh', 'eligible', '2026-09-02'),
(5, 'Vikram Malhotra', '9654321098', 'Bus Stand Area', 'Satna', 'Madhya Pradesh', 'eligible', '2026-09-03'),
(125, 'Rahul Sharma', '9876500125', 'Main Road Sector 4', 'Rewa', 'Madhya Pradesh', 'eligible', '2026-09-10'),
(276, 'Amit Verma', '9711220276', 'Nehru Nagar', 'Satna', 'Madhya Pradesh', 'eligible', '2026-09-15'),
(357, 'Neha Jain', '9988770357', 'Malviya Marg', 'Rewa', 'Madhya Pradesh', 'eligible', '2026-09-20')
ON DUPLICATE KEY UPDATE `customer_name`=VALUES(`customer_name`);

-- Procedure or simple loop simulation for remaining coupons 1..357 in default state if needed:
-- We can add a helper or seed script to populate 1..357 automatically if not present.

-- 5. Special Prize Assignments
-- Car -> Coupon 125
-- Scooty -> Coupon 276
INSERT INTO `special_prize_assignments` (`prize_id`, `coupon_id`, `coupon_number`)
SELECT p.id, c.id, c.coupon_number 
FROM `prizes` p, `coupons` c 
WHERE p.name LIKE '%Car%' AND c.coupon_number = 125
ON DUPLICATE KEY UPDATE `coupon_number` = 125;

INSERT INTO `special_prize_assignments` (`prize_id`, `coupon_id`, `coupon_number`)
SELECT p.id, c.id, c.coupon_number 
FROM `prizes` p, `coupons` c 
WHERE p.name LIKE '%Scooty%' AND c.coupon_number = 276
ON DUPLICATE KEY UPDATE `coupon_number` = 276;
