-- ============================================
-- SummitSphere Seed Data
-- Version: 1.0
-- Description: Sample data for testing and demonstration
-- ============================================

USE summitsphere;

-- ============================================
-- BRANCHES (5 locations)
-- ============================================
INSERT INTO branch (name, location, contact_phone, email, opening_hour, closing_hour) VALUES
('SummitSphere Downtown', '123 Main Street, Sydney CBD, NSW 2000', '+61-2-9000-1001', 'downtown@summitsphere.com', '08:00:00', '21:00:00'),
('SummitSphere Westfield', '456 Shopping Mall, Parramatta, NSW 2150', '+61-2-9000-1002', 'westfield@summitsphere.com', '09:00:00', '21:00:00'),
('SummitSphere Coastal', '789 Beach Road, Bondi, NSW 2026', '+61-2-9000-1003', 'coastal@summitsphere.com', '07:00:00', '20:00:00'),
('SummitSphere Mountain', '321 Alpine Way, Blue Mountains, NSW 2780', '+61-2-9000-1004', 'mountain@summitsphere.com', '08:30:00', '18:00:00'),
('SummitSphere North', '555 Pacific Highway, Chatswood, NSW 2067', '+61-2-9000-1005', 'north@summitsphere.com', '09:00:00', '20:00:00');

-- ============================================
-- SUPPLIERS (10 vendors)
-- ============================================
INSERT INTO supplier (name, contact_person, contact_email, phone, address, cooperation_status, contract_start_date, contract_end_date) VALUES
('CycleGear Pro', 'James Wilson', 'james@cyclegearpro.com', '+61-3-8000-2001', '100 Industrial Ave, Melbourne VIC 3000', 'Active', '2023-01-15', '2025-01-14'),
('Adventure Outfitters', 'Sarah Chen', 'sarah@adventureoutfitters.com', '+61-7-7000-2002', '200 Outdoor Lane, Brisbane QLD 4000', 'Active', '2022-06-01', '2025-05-31'),
('Summit Equipment Co', 'Michael Brown', 'michael@summitequip.com', '+61-8-6000-2003', '300 Mountain Rd, Perth WA 6000', 'Active', '2023-03-20', '2026-03-19'),
('Coastal Sports Supply', 'Emma Davis', 'emma@coastalsports.com', '+61-2-9000-2004', '400 Harbor St, Sydney NSW 2000', 'Active', '2022-09-10', '2024-09-09'),
('TrailBlazer Imports', 'David Lee', 'david@trailblazer.com', '+61-3-8000-2005', '500 Trade Way, Melbourne VIC 3000', 'Active', '2023-07-01', '2025-06-30'),
('EcoGear Australia', 'Lisa Wang', 'lisa@ecogear.com.au', '+61-2-9000-2006', '600 Green St, Sydney NSW 2000', 'Active', '2024-01-01', '2026-12-31'),
('ProClimb Solutions', 'Robert Taylor', 'robert@proclimb.com', '+61-7-7000-2007', '700 Cliff Rd, Brisbane QLD 4000', 'Active', '2023-04-15', '2025-04-14'),
('WaterSport Wholesale', 'Jennifer Martinez', 'jennifer@watersportwholesale.com', '+61-8-6000-2008', '800 Ocean Dr, Perth WA 6000', 'Inactive', '2021-01-01', '2023-12-31'),
('CampMaster Supplies', 'Andrew Johnson', 'andrew@campmaster.com', '+61-2-9000-2009', '900 Forest Ave, Sydney NSW 2000', 'Active', '2023-08-01', '2025-07-31'),
('FitGear International', 'Michelle Kim', 'michelle@fitgear.com', '+61-3-8000-2010', '1000 Sports Blvd, Melbourne VIC 3000', 'Pending', '2024-06-01', '2026-05-31');

-- ============================================
-- EMPLOYEES (25 staff across branches)
-- ============================================
INSERT INTO employee (branch_id, first_name, last_name, gender, date_of_birth, email, phone, address, role, hire_date, salary, id_card_number) VALUES
-- Branch 1: Downtown (1 Manager, 4 Staff)
(1, 'Thomas', 'Anderson', 'Male', '1985-03-15', 'thomas.anderson@summitsphere.com', '+61-400-100-001', '10 King St, Sydney NSW 2000', 'Manager', '2020-01-15', 85000.00, 'NSW100001'),
(1, 'Emily', 'Parker', 'Female', '1992-07-22', 'emily.parker@summitsphere.com', '+61-400-100-002', '20 Queen St, Sydney NSW 2000', 'Staff', '2021-03-01', 55000.00, 'NSW100002'),
(1, 'Ryan', 'Mitchell', 'Male', '1995-11-08', 'ryan.mitchell@summitsphere.com', '+61-400-100-003', '30 George St, Sydney NSW 2000', 'Staff', '2022-06-15', 52000.00, 'NSW100003'),
(1, 'Sophie', 'Turner', 'Female', '1998-02-28', 'sophie.turner@summitsphere.com', '+61-400-100-004', '40 Pitt St, Sydney NSW 2000', 'Staff', '2023-01-10', 48000.00, 'NSW100004'),
(1, 'Lucas', 'White', 'Male', '1994-09-12', 'lucas.white@summitsphere.com', '+61-400-100-005', '50 Market St, Sydney NSW 2000', 'Staff', '2022-08-20', 50000.00, 'NSW100005'),

-- Branch 2: Westfield (1 Manager, 4 Staff)
(2, 'Jessica', 'Roberts', 'Female', '1983-05-20', 'jessica.roberts@summitsphere.com', '+61-400-200-001', '60 Church St, Parramatta NSW 2150', 'Manager', '2019-06-01', 88000.00, 'NSW200001'),
(2, 'Daniel', 'Clark', 'Male', '1991-12-03', 'daniel.clark@summitsphere.com', '+61-400-200-002', '70 Smith St, Parramatta NSW 2150', 'Staff', '2020-09-15', 54000.00, 'NSW200002'),
(2, 'Olivia', 'Young', 'Female', '1996-04-17', 'olivia.young@summitsphere.com', '+61-400-200-003', '80 Harris St, Parramatta NSW 2150', 'Staff', '2021-11-01', 51000.00, 'NSW200003'),
(2, 'Nathan', 'Harris', 'Male', '1993-08-25', 'nathan.harris@summitsphere.com', '+61-400-200-004', '90 Argyle St, Parramatta NSW 2150', 'Staff', '2022-04-10', 53000.00, 'NSW200004'),
(2, 'Chloe', 'Martin', 'Female', '1997-01-30', 'chloe.martin@summitsphere.com', '+61-400-200-005', '100 Phillip St, Parramatta NSW 2150', 'Staff', '2023-02-20', 49000.00, 'NSW200005'),

-- Branch 3: Coastal (1 Manager, 3 Staff)
(3, 'Benjamin', 'Scott', 'Male', '1984-10-10', 'benjamin.scott@summitsphere.com', '+61-400-300-001', '110 Campbell Pde, Bondi NSW 2026', 'Manager', '2020-03-01', 82000.00, 'NSW300001'),
(3, 'Mia', 'Thompson', 'Female', '1994-06-08', 'mia.thompson@summitsphere.com', '+61-400-300-002', '120 Hall St, Bondi NSW 2026', 'Staff', '2021-07-15', 52000.00, 'NSW300002'),
(3, 'Ethan', 'Garcia', 'Male', '1996-12-20', 'ethan.garcia@summitsphere.com', '+61-400-300-003', '130 Glenayr Ave, Bondi NSW 2026', 'Staff', '2022-10-01', 50000.00, 'NSW300003'),
(3, 'Ava', 'Rodriguez', 'Female', '1999-03-14', 'ava.rodriguez@summitsphere.com', '+61-400-300-004', '140 Curlewis St, Bondi NSW 2026', 'Staff', '2023-04-01', 47000.00, 'NSW300004'),

-- Branch 4: Mountain (1 Manager, 3 Staff)
(4, 'William', 'Lewis', 'Male', '1982-08-05', 'william.lewis@summitsphere.com', '+61-400-400-001', '150 Great Western Hwy, Katoomba NSW 2780', 'Manager', '2019-09-15', 80000.00, 'NSW400001'),
(4, 'Isabella', 'Walker', 'Female', '1993-02-18', 'isabella.walker@summitsphere.com', '+61-400-400-002', '160 Katoomba St, Katoomba NSW 2780', 'Staff', '2021-01-10', 51000.00, 'NSW400002'),
(4, 'James', 'Hall', 'Male', '1995-07-27', 'james.hall@summitsphere.com', '+61-400-400-003', '170 Lurline St, Katoomba NSW 2780', 'Staff', '2022-05-20', 49000.00, 'NSW400003'),
(4, 'Charlotte', 'Allen', 'Female', '1998-11-11', 'charlotte.allen@summitsphere.com', '+61-400-400-004', '180 Waratah St, Katoomba NSW 2780', 'Staff', '2023-03-15', 46000.00, 'NSW400004'),

-- Branch 5: North (1 Manager, 4 Staff)
(5, 'Alexander', 'King', 'Male', '1986-04-22', 'alexander.king@summitsphere.com', '+61-400-500-001', '190 Victoria Ave, Chatswood NSW 2067', 'Manager', '2020-07-01', 86000.00, 'NSW500001'),
(5, 'Amelia', 'Wright', 'Female', '1992-09-30', 'amelia.wright@summitsphere.com', '+61-400-500-002', '200 Albert Ave, Chatswood NSW 2067', 'Staff', '2021-04-15', 55000.00, 'NSW500002'),
(5, 'Henry', 'Lopez', 'Male', '1994-01-25', 'henry.lopez@summitsphere.com', '+61-400-500-003', '210 Railway St, Chatswood NSW 2067', 'Staff', '2022-02-01', 52000.00, 'NSW500003'),
(5, 'Grace', 'Hill', 'Female', '1997-06-15', 'grace.hill@summitsphere.com', '+61-400-500-004', '220 Archer St, Chatswood NSW 2067', 'Staff', '2022-09-10', 50000.00, 'NSW500004'),
(5, 'Jack', 'Green', 'Male', '1999-10-05', 'jack.green@summitsphere.com', '+61-400-500-005', '230 Spring St, Chatswood NSW 2067', 'Staff', '2023-05-01', 48000.00, 'NSW500005');

-- ============================================
-- CATEGORIES (Hierarchical structure)
-- ============================================
INSERT INTO category (name, description, parent_category_id) VALUES
-- Parent Categories
('Cycling', 'All cycling related equipment and accessories', NULL),
('Camping & Hiking', 'Outdoor camping and hiking gear', NULL),
('Climbing', 'Rock climbing and mountaineering equipment', NULL),
('Water Sports', 'Swimming, surfing, and water activity gear', NULL),
('Fitness', 'General fitness and exercise equipment', NULL);

-- Child Categories - Cycling
INSERT INTO category (name, description, parent_category_id) VALUES
('Bicycles', 'Complete bicycles for various purposes', 1),
('Helmets', 'Protective cycling helmets', 1),
('Cycling Apparel', 'Clothing for cyclists', 1),
('Bike Accessories', 'Lights, locks, bags, and more', 1),
('Bike Parts', 'Replacement parts and components', 1);

-- Child Categories - Camping & Hiking
INSERT INTO category (name, description, parent_category_id) VALUES
('Tents', 'Camping tents of all sizes', 2),
('Sleeping Gear', 'Sleeping bags and mattresses', 2),
('Backpacks', 'Hiking and camping backpacks', 2),
('Camping Furniture', 'Chairs, tables, and camp kitchen', 2),
('Navigation', 'GPS devices, compasses, and maps', 2);

-- Child Categories - Climbing
INSERT INTO category (name, description, parent_category_id) VALUES
('Climbing Ropes', 'Dynamic and static ropes', 3),
('Harnesses', 'Climbing harnesses and belts', 3),
('Carabiners & Hardware', 'Climbing hardware and protection', 3),
('Climbing Shoes', 'Specialized footwear for climbing', 3);

-- Child Categories - Water Sports
INSERT INTO category (name, description, parent_category_id) VALUES
('Surfboards', 'Surfboards and bodyboards', 4),
('Wetsuits', 'Neoprene suits for water activities', 4),
('Snorkeling', 'Masks, snorkels, and fins', 4),
('Kayaking', 'Kayaks and paddling equipment', 4);

-- Child Categories - Fitness
INSERT INTO category (name, description, parent_category_id) VALUES
('Weights', 'Dumbbells, kettlebells, and barbells', 5),
('Cardio Equipment', 'Jump ropes, resistance bands', 5),
('Yoga & Pilates', 'Mats, blocks, and accessories', 5),
('Sports Nutrition', 'Supplements and hydration', 5);

-- ============================================
-- PRODUCTS (100+ products)
-- ============================================
INSERT INTO product (category_id, supplier_id, name, description, sku, unit_price, cost_price, weight, dimensions, image_url) VALUES
-- Bicycles (Category 6)
(6, 1, 'TrailMaster Mountain Bike', 'Full suspension mountain bike with 29" wheels', 'BIKE-MTN-001', 1299.99, 850.00, 14.5, '180x70x110cm', '/images/products/bike-mtn-001.jpg'),
(6, 1, 'CityRider Hybrid Bike', 'Versatile hybrid bike for city commuting', 'BIKE-HYB-001', 799.99, 520.00, 12.0, '175x65x105cm', '/images/products/bike-hyb-001.jpg'),
(6, 1, 'SpeedPro Road Bike', 'Lightweight carbon road bike', 'BIKE-ROD-001', 2499.99, 1650.00, 8.5, '170x60x95cm', '/images/products/bike-rod-001.jpg'),
(6, 1, 'KidRider Junior Bike', 'Kids bike with training wheels', 'BIKE-KID-001', 249.99, 160.00, 9.0, '120x50x80cm', '/images/products/bike-kid-001.jpg'),

-- Helmets (Category 7)
(7, 1, 'AeroGuard Pro Helmet', 'Premium aerodynamic cycling helmet', 'HELM-PRO-001', 189.99, 95.00, 0.28, '28x22x18cm', '/images/products/helm-pro-001.jpg'),
(7, 1, 'SafeRide Standard Helmet', 'Entry-level safety certified helmet', 'HELM-STD-001', 59.99, 28.00, 0.35, '30x24x20cm', '/images/products/helm-std-001.jpg'),
(7, 1, 'KidSafe Junior Helmet', 'Colorful kids helmet with extra padding', 'HELM-KID-001', 39.99, 18.00, 0.25, '25x20x16cm', '/images/products/helm-kid-001.jpg'),

-- Cycling Apparel (Category 8)
(8, 1, 'ProFit Cycling Jersey', 'Breathable moisture-wicking jersey', 'APRL-JRS-001', 89.99, 42.00, 0.18, 'One size', '/images/products/aprl-jrs-001.jpg'),
(8, 1, 'ComfortRide Cycling Shorts', 'Padded cycling shorts', 'APRL-SHT-001', 69.99, 32.00, 0.15, 'Various sizes', '/images/products/aprl-sht-001.jpg'),
(8, 1, 'AllWeather Cycling Jacket', 'Waterproof windbreaker for cyclists', 'APRL-JKT-001', 129.99, 65.00, 0.35, 'Various sizes', '/images/products/aprl-jkt-001.jpg'),

-- Bike Accessories (Category 9)
(9, 1, 'BrightBeam Front Light', 'USB rechargeable 1000 lumen front light', 'ACCS-LGT-001', 49.99, 22.00, 0.12, '10x5x3cm', '/images/products/accs-lgt-001.jpg'),
(9, 1, 'SecureLock U-Lock', 'Heavy duty anti-theft U-lock', 'ACCS-LCK-001', 79.99, 38.00, 1.2, '30x15x4cm', '/images/products/accs-lck-001.jpg'),
(9, 1, 'CarryAll Pannier Bag', 'Waterproof pannier bag set', 'ACCS-BAG-001', 119.99, 58.00, 0.8, '40x30x15cm', '/images/products/accs-bag-001.jpg'),

-- Tents (Category 11)
(11, 2, 'AlpineBase 2-Person Tent', 'Lightweight backpacking tent', 'TENT-2P-001', 299.99, 165.00, 2.2, '220x140x100cm', '/images/products/tent-2p-001.jpg'),
(11, 2, 'FamilyCamp 4-Person Tent', 'Spacious family camping tent', 'TENT-4P-001', 449.99, 250.00, 5.5, '280x240x180cm', '/images/products/tent-4p-001.jpg'),
(11, 2, 'ExpeditionPro 6-Person Tent', 'All-season expedition tent', 'TENT-6P-001', 799.99, 450.00, 8.0, '350x300x200cm', '/images/products/tent-6p-001.jpg'),
(11, 9, 'InstaPitch Pop-up Tent', 'Quick setup pop-up tent', 'TENT-POP-001', 149.99, 75.00, 3.0, '200x150x110cm', '/images/products/tent-pop-001.jpg'),

-- Sleeping Gear (Category 12)
(12, 2, 'ArcticDream -20C Sleeping Bag', 'Cold weather mummy sleeping bag', 'SLEP-BAG-001', 189.99, 95.00, 1.8, '220x80cm', '/images/products/slep-bag-001.jpg'),
(12, 2, 'SummerNight 10C Sleeping Bag', 'Lightweight summer sleeping bag', 'SLEP-BAG-002', 79.99, 38.00, 0.9, '210x75cm', '/images/products/slep-bag-002.jpg'),
(12, 9, 'ComfortRest Air Mattress', 'Self-inflating camping mattress', 'SLEP-MAT-001', 89.99, 42.00, 0.7, '190x60x5cm', '/images/products/slep-mat-001.jpg'),
(12, 9, 'TrekPad Foam Mattress', 'Closed-cell foam sleeping pad', 'SLEP-MAT-002', 39.99, 18.00, 0.4, '180x55x2cm', '/images/products/slep-mat-002.jpg'),

-- Backpacks (Category 13)
(13, 2, 'TrailVenture 65L Backpack', 'Large capacity hiking backpack', 'BKPK-65L-001', 249.99, 135.00, 2.0, '75x35x25cm', '/images/products/bkpk-65l-001.jpg'),
(13, 2, 'DayTripper 30L Backpack', 'Versatile day hiking pack', 'BKPK-30L-001', 129.99, 65.00, 0.9, '55x30x20cm', '/images/products/bkpk-30l-001.jpg'),
(13, 5, 'HydroCarry Hydration Pack', '2L hydration backpack', 'BKPK-HYD-001', 79.99, 38.00, 0.5, '45x25x15cm', '/images/products/bkpk-hyd-001.jpg'),

-- Camping Furniture (Category 14)
(14, 9, 'QuickFold Camp Chair', 'Portable folding camp chair', 'FURN-CHR-001', 49.99, 22.00, 2.5, '90x55x85cm', '/images/products/furn-chr-001.jpg'),
(14, 9, 'CompactTable Folding Table', 'Lightweight aluminum camp table', 'FURN-TBL-001', 69.99, 32.00, 3.0, '120x60x70cm', '/images/products/furn-tbl-001.jpg'),
(14, 9, 'CampKitchen Cooking Set', 'Portable camping cookware set', 'FURN-KIT-001', 89.99, 45.00, 1.5, '30x20x15cm', '/images/products/furn-kit-001.jpg'),

-- Navigation (Category 15)
(15, 5, 'TrekNav GPS Device', 'Handheld hiking GPS with maps', 'NAVI-GPS-001', 349.99, 200.00, 0.2, '12x6x3cm', '/images/products/navi-gps-001.jpg'),
(15, 5, 'ProCompass Navigation Compass', 'Professional orienteering compass', 'NAVI-CMP-001', 39.99, 18.00, 0.05, '10x6x1cm', '/images/products/navi-cmp-001.jpg'),

-- Climbing Ropes (Category 16)
(16, 7, 'DynamicPro 60m Climbing Rope', '10.2mm dynamic climbing rope', 'ROPE-DYN-001', 199.99, 110.00, 4.0, '60m length', '/images/products/rope-dyn-001.jpg'),
(16, 7, 'StaticLine 50m Rope', '11mm static rope for rappelling', 'ROPE-STC-001', 149.99, 80.00, 3.5, '50m length', '/images/products/rope-stc-001.jpg'),

-- Harnesses (Category 17)
(17, 7, 'ClimbSafe Pro Harness', 'Adjustable sport climbing harness', 'HRNS-PRO-001', 129.99, 65.00, 0.4, 'Adjustable', '/images/products/hrns-pro-001.jpg'),
(17, 7, 'AlpineGuard Full Body Harness', 'Full body mountaineering harness', 'HRNS-FUL-001', 189.99, 100.00, 0.7, 'Various sizes', '/images/products/hrns-ful-001.jpg'),

-- Carabiners & Hardware (Category 18)
(18, 7, 'QuickLock Carabiner Set', 'Set of 5 locking carabiners', 'CARB-SET-001', 59.99, 28.00, 0.35, '10cm each', '/images/products/carb-set-001.jpg'),
(18, 7, 'BelaySafe Belay Device', 'Assisted braking belay device', 'HARD-BLY-001', 89.99, 45.00, 0.25, '12x8x4cm', '/images/products/hard-bly-001.jpg'),
(18, 7, 'AnchorPro Protection Set', 'Cam and nut protection set', 'HARD-ANC-001', 299.99, 170.00, 1.2, 'Various', '/images/products/hard-anc-001.jpg'),

-- Climbing Shoes (Category 19)
(19, 7, 'GripMaster Climbing Shoes', 'Aggressive downturned climbing shoes', 'SHOE-CLM-001', 159.99, 85.00, 0.5, 'Various sizes', '/images/products/shoe-clm-001.jpg'),
(19, 7, 'AllRound Climbing Shoes', 'Versatile flat climbing shoes', 'SHOE-CLM-002', 119.99, 60.00, 0.55, 'Various sizes', '/images/products/shoe-clm-002.jpg'),

-- Surfboards (Category 20)
(20, 4, 'WaveRider Shortboard', '6ft performance shortboard', 'SURF-SHT-001', 549.99, 320.00, 3.2, '183x48x6cm', '/images/products/surf-sht-001.jpg'),
(20, 4, 'BeachCruiser Longboard', '9ft classic longboard', 'SURF-LNG-001', 699.99, 420.00, 6.5, '274x56x8cm', '/images/products/surf-lng-001.jpg'),
(20, 4, 'FoamFun Learner Board', 'Soft foam beginner surfboard', 'SURF-FOM-001', 249.99, 140.00, 4.0, '244x56x10cm', '/images/products/surf-fom-001.jpg'),

-- Wetsuits (Category 21)
(21, 4, 'ThermoFlex 3/2mm Wetsuit', 'Full wetsuit for cool water', 'WETS-32-001', 249.99, 135.00, 1.5, 'Various sizes', '/images/products/wets-32-001.jpg'),
(21, 4, 'SummerSurf 2mm Spring Suit', 'Short wetsuit for warm water', 'WETS-SPR-001', 149.99, 75.00, 0.8, 'Various sizes', '/images/products/wets-spr-001.jpg'),

-- Snorkeling (Category 22)
(22, 4, 'ClearView Mask & Snorkel Set', 'Adult snorkeling set', 'SNRK-SET-001', 59.99, 28.00, 0.5, '45x20x12cm', '/images/products/snrk-set-001.jpg'),
(22, 4, 'AquaFin Swimming Fins', 'Adjustable snorkeling fins', 'SNRK-FIN-001', 49.99, 22.00, 0.8, 'Various sizes', '/images/products/snrk-fin-001.jpg'),

-- Kayaking (Category 23)
(23, 4, 'ExplorerKayak Single', 'Single person recreational kayak', 'KAYK-SNG-001', 799.99, 480.00, 23.0, '300x75x35cm', '/images/products/kayk-sng-001.jpg'),
(23, 4, 'TandemKayak Double', 'Two person touring kayak', 'KAYK-DBL-001', 1199.99, 720.00, 32.0, '400x85x40cm', '/images/products/kayk-dbl-001.jpg'),
(23, 4, 'PaddlePro Kayak Paddle', 'Lightweight aluminum kayak paddle', 'KAYK-PDL-001', 79.99, 38.00, 0.9, '220cm', '/images/products/kayk-pdl-001.jpg'),

-- Weights (Category 24)
(24, 10, 'IronGrip Dumbbell Set', 'Adjustable dumbbell set 5-25kg', 'WGHT-DMB-001', 299.99, 165.00, 50.0, '40x20x20cm', '/images/products/wght-dmb-001.jpg'),
(24, 10, 'KettleFit Kettlebell', '16kg cast iron kettlebell', 'WGHT-KTL-001', 69.99, 35.00, 16.0, '25x20x30cm', '/images/products/wght-ktl-001.jpg'),
(24, 10, 'PowerBar Olympic Barbell', '20kg Olympic barbell', 'WGHT-BAR-001', 249.99, 140.00, 20.0, '220cm', '/images/products/wght-bar-001.jpg'),

-- Cardio Equipment (Category 25)
(25, 10, 'SpeedRope Pro Jump Rope', 'Professional speed jump rope', 'CARD-JRP-001', 29.99, 12.00, 0.2, '300cm', '/images/products/card-jrp-001.jpg'),
(25, 6, 'ResistBand Set', 'Set of 5 resistance bands', 'CARD-RES-001', 39.99, 18.00, 0.3, 'Various', '/images/products/card-res-001.jpg'),
(25, 10, 'BattleRope 12m', 'Heavy battle rope for HIIT', 'CARD-BTL-001', 89.99, 45.00, 12.0, '12m x 38mm', '/images/products/card-btl-001.jpg'),

-- Yoga & Pilates (Category 26)
(26, 6, 'ZenMat Premium Yoga Mat', 'Extra thick eco-friendly yoga mat', 'YOGA-MAT-001', 69.99, 32.00, 1.5, '183x61x0.6cm', '/images/products/yoga-mat-001.jpg'),
(26, 6, 'FlexBlock Cork Yoga Blocks', 'Set of 2 cork yoga blocks', 'YOGA-BLK-001', 34.99, 15.00, 0.8, '23x15x10cm', '/images/products/yoga-blk-001.jpg'),
(26, 6, 'StretchBand Yoga Strap', 'Cotton yoga stretching strap', 'YOGA-STP-001', 14.99, 5.00, 0.2, '250cm', '/images/products/yoga-stp-001.jpg'),

-- Sports Nutrition (Category 27)
(27, 6, 'HydroPro Water Bottle', '1L insulated sports bottle', 'NUTR-BTL-001', 29.99, 12.00, 0.3, '28x8cm', '/images/products/nutr-btl-001.jpg'),
(27, 6, 'EnergyGel Pack', 'Pack of 12 energy gels', 'NUTR-GEL-001', 39.99, 18.00, 0.5, '12 x 35g', '/images/products/nutr-gel-001.jpg'),
(27, 6, 'ProteinShaker Bottle', 'Mixing bottle with storage', 'NUTR-SHK-001', 19.99, 8.00, 0.2, '600ml', '/images/products/nutr-shk-001.jpg');

-- Add more products for variety
INSERT INTO product (category_id, supplier_id, name, description, sku, unit_price, cost_price, weight, dimensions, image_url) VALUES
(10, 1, 'TubeRepair Patch Kit', 'Emergency tire repair kit', 'PART-REP-001', 12.99, 5.00, 0.1, '10x8x2cm', '/images/products/part-rep-001.jpg'),
(10, 1, 'ChainMaster Bike Chain', 'High quality 11-speed chain', 'PART-CHN-001', 39.99, 18.00, 0.25, '116 links', '/images/products/part-chn-001.jpg'),
(10, 1, 'BrakePad Set', 'Disc brake pad set', 'PART-BRK-001', 29.99, 12.00, 0.1, '8x5x1cm', '/images/products/part-brk-001.jpg'),
(9, 1, 'CycleComputer GPS', 'Wireless bike computer with GPS', 'ACCS-CMP-001', 149.99, 75.00, 0.1, '8x5x2cm', '/images/products/accs-cmp-001.jpg'),
(9, 1, 'BikeBottle Sport', '750ml cycling water bottle', 'ACCS-BTL-001', 14.99, 6.00, 0.1, '25x8cm', '/images/products/accs-btl-001.jpg'),
(11, 2, 'StormShield Tarp', 'Heavy duty camping tarp', 'TENT-TRP-001', 79.99, 38.00, 1.5, '300x300cm', '/images/products/tent-trp-001.jpg'),
(14, 9, 'HeadLamp Pro LED', 'Rechargeable 500 lumen headlamp', 'FURN-HLP-001', 44.99, 20.00, 0.1, '8x5x4cm', '/images/products/furn-hlp-001.jpg'),
(14, 9, 'CampStove Portable', 'Compact gas camping stove', 'FURN-STV-001', 59.99, 28.00, 0.8, '15x15x10cm', '/images/products/furn-stv-001.jpg'),
(18, 7, 'ChalkBag Climbing', 'Drawstring chalk bag with belt', 'HARD-CHL-001', 24.99, 10.00, 0.1, '18x12cm', '/images/products/hard-chl-001.jpg'),
(17, 7, 'ClimbGlove Belay Gloves', 'Leather belay/rappel gloves', 'HRNS-GLV-001', 39.99, 18.00, 0.15, 'Various sizes', '/images/products/hrns-glv-001.jpg'),
(21, 4, 'NeoBoots 5mm', 'Neoprene surf booties', 'WETS-BOT-001', 49.99, 22.00, 0.4, 'Various sizes', '/images/products/wets-bot-001.jpg'),
(21, 4, 'SurfGloves 3mm', 'Neoprene surf gloves', 'WETS-GLV-001', 34.99, 15.00, 0.2, 'Various sizes', '/images/products/wets-glv-001.jpg'),
(20, 4, 'BoardBag Travel', 'Padded surfboard travel bag', 'SURF-BAG-001', 129.99, 65.00, 2.0, '200x60x15cm', '/images/products/surf-bag-001.jpg'),
(23, 4, 'LifeVest Adult', 'Coast guard approved life vest', 'KAYK-VES-001', 89.99, 45.00, 0.8, 'Various sizes', '/images/products/kayk-ves-001.jpg'),
(26, 6, 'FoamRoller 45cm', 'High density foam roller', 'YOGA-ROL-001', 29.99, 12.00, 0.5, '45x15cm', '/images/products/yoga-rol-001.jpg'),
(25, 6, 'AgilityCones Set', 'Set of 20 training cones', 'CARD-CON-001', 24.99, 10.00, 0.8, '20 pieces', '/images/products/card-con-001.jpg'),
(24, 10, 'WeightPlate 10kg', 'Olympic weight plate', 'WGHT-PLT-001', 49.99, 25.00, 10.0, '45cm diameter', '/images/products/wght-plt-001.jpg'),
(13, 5, 'TrekPole Carbon', 'Pair of carbon trekking poles', 'BKPK-POL-001', 99.99, 50.00, 0.5, '65-135cm', '/images/products/bkpk-pol-001.jpg'),
(12, 2, 'CampPillow Inflatable', 'Ultralight inflatable pillow', 'SLEP-PIL-001', 24.99, 10.00, 0.1, '40x30x12cm', '/images/products/slep-pil-001.jpg'),
(15, 5, 'EmergencyBeacon PLB', 'Personal locator beacon', 'NAVI-PLB-001', 399.99, 240.00, 0.15, '12x5x3cm', '/images/products/navi-plb-001.jpg');

-- ============================================
-- CUSTOMERS (50 customers)
-- ============================================
INSERT INTO customer (first_name, last_name, gender, email, phone, address, registration_date, membership_level, total_spent) VALUES
('John', 'Smith', 'Male', 'john.smith@email.com', '+61-410-001-001', '1 Customer St, Sydney NSW 2000', '2022-01-15', 'Gold', 7500.00),
('Sarah', 'Johnson', 'Female', 'sarah.johnson@email.com', '+61-410-001-002', '2 Buyer Ave, Melbourne VIC 3000', '2022-02-20', 'Platinum', 15000.00),
('Michael', 'Williams', 'Male', 'michael.williams@email.com', '+61-410-001-003', '3 Shopper Rd, Brisbane QLD 4000', '2022-03-10', 'Silver', 2500.00),
('Emma', 'Brown', 'Female', 'emma.brown@email.com', '+61-410-001-004', '4 Client Way, Perth WA 6000', '2022-04-05', 'Bronze', 500.00),
('David', 'Taylor', 'Male', 'david.taylor@email.com', '+61-410-001-005', '5 Purchase Lane, Adelaide SA 5000', '2022-05-12', 'Gold', 6000.00),
('Olivia', 'Anderson', 'Female', 'olivia.anderson@email.com', '+61-410-001-006', '6 Order St, Hobart TAS 7000', '2022-06-18', 'Silver', 3200.00),
('James', 'Thomas', 'Male', 'james.thomas@email.com', '+61-410-001-007', '7 Sale Blvd, Darwin NT 0800', '2022-07-22', 'Bronze', 800.00),
('Sophia', 'Jackson', 'Female', 'sophia.jackson@email.com', '+61-410-001-008', '8 Deal Cres, Canberra ACT 2600', '2022-08-30', 'Platinum', 12000.00),
('William', 'White', 'Male', 'william.white@email.com', '+61-410-001-009', '9 Bargain Dr, Sydney NSW 2000', '2022-09-14', 'Gold', 5500.00),
('Isabella', 'Harris', 'Female', 'isabella.harris@email.com', '+61-410-001-010', '10 Discount Rd, Melbourne VIC 3000', '2022-10-08', 'Silver', 1800.00),
('Alexander', 'Martin', 'Male', 'alexander.martin@email.com', '+61-410-001-011', '11 Value St, Brisbane QLD 4000', '2022-11-25', 'Bronze', 600.00),
('Mia', 'Thompson', 'Female', 'mia.thompson@email.com', '+61-410-001-012', '12 Savings Ave, Perth WA 6000', '2022-12-03', 'Gold', 8200.00),
('Benjamin', 'Garcia', 'Male', 'benjamin.garcia@email.com', '+61-410-001-013', '13 Offer Way, Adelaide SA 5000', '2023-01-17', 'Platinum', 18000.00),
('Charlotte', 'Martinez', 'Female', 'charlotte.martinez@email.com', '+61-410-001-014', '14 Promo Lane, Hobart TAS 7000', '2023-02-28', 'Silver', 2100.00),
('Henry', 'Robinson', 'Male', 'henry.robinson@email.com', '+61-410-001-015', '15 Special St, Darwin NT 0800', '2023-03-11', 'Bronze', 450.00),
('Amelia', 'Clark', 'Female', 'amelia.clark@email.com', '+61-410-001-016', '16 Clearance Blvd, Canberra ACT 2600', '2023-04-22', 'Gold', 6800.00),
('Sebastian', 'Rodriguez', 'Male', 'sebastian.rodriguez@email.com', '+61-410-001-017', '17 Markdown Cres, Sydney NSW 2000', '2023-05-09', 'Silver', 1500.00),
('Harper', 'Lewis', 'Female', 'harper.lewis@email.com', '+61-410-001-018', '18 Rebate Dr, Melbourne VIC 3000', '2023-06-15', 'Bronze', 350.00),
('Jack', 'Lee', 'Male', 'jack.lee@email.com', '+61-410-001-019', '19 Coupon Rd, Brisbane QLD 4000', '2023-07-20', 'Platinum', 11500.00),
('Evelyn', 'Walker', 'Female', 'evelyn.walker@email.com', '+61-410-001-020', '20 Cashback St, Perth WA 6000', '2023-08-05', 'Gold', 5200.00),
('Aiden', 'Hall', 'Male', 'aiden.hall@email.com', '+61-410-001-021', '21 Loyalty Ave, Adelaide SA 5000', '2023-09-12', 'Silver', 2800.00),
('Abigail', 'Allen', 'Female', 'abigail.allen@email.com', '+61-410-001-022', '22 Reward Way, Hobart TAS 7000', '2023-10-18', 'Bronze', 750.00),
('Lucas', 'Young', 'Male', 'lucas.young@email.com', '+61-410-001-023', '23 Points Lane, Darwin NT 0800', '2023-11-24', 'Gold', 7100.00),
('Emily', 'Hernandez', 'Female', 'emily.hernandez@email.com', '+61-410-001-024', '24 Member St, Canberra ACT 2600', '2023-12-01', 'Silver', 1900.00),
('Mason', 'King', 'Male', 'mason.king@email.com', '+61-410-001-025', '25 VIP Blvd, Sydney NSW 2000', '2024-01-08', 'Bronze', 420.00),
('Ella', 'Wright', 'Female', 'ella.wright@email.com', '+61-410-001-026', '26 Elite Cres, Melbourne VIC 3000', '2024-02-14', 'Platinum', 13500.00),
('Logan', 'Lopez', 'Male', 'logan.lopez@email.com', '+61-410-001-027', '27 Premium Dr, Brisbane QLD 4000', '2024-03-20', 'Gold', 4800.00),
('Avery', 'Hill', 'Female', 'avery.hill@email.com', '+61-410-001-028', '28 Exclusive Rd, Perth WA 6000', '2024-04-05', 'Silver', 1600.00),
('Ethan', 'Scott', 'Male', 'ethan.scott@email.com', '+61-410-001-029', '29 First St, Adelaide SA 5000', '2024-05-11', 'Bronze', 280.00),
('Aria', 'Green', 'Female', 'aria.green@email.com', '+61-410-001-030', '30 Choice Ave, Hobart TAS 7000', '2024-06-17', 'Gold', 5800.00),
('Noah', 'Adams', 'Male', 'noah.adams@email.com', '+61-410-001-031', '31 Select Way, Darwin NT 0800', '2024-07-23', 'Silver', 2200.00),
('Scarlett', 'Baker', 'Female', 'scarlett.baker@email.com', '+61-410-001-032', '32 Pick Lane, Canberra ACT 2600', '2024-08-29', 'Bronze', 550.00),
('Liam', 'Nelson', 'Male', 'liam.nelson@email.com', '+61-410-001-033', '33 Browse St, Sydney NSW 2000', '2024-09-04', 'Platinum', 16000.00),
('Chloe', 'Carter', 'Female', 'chloe.carter@email.com', '+61-410-001-034', '34 Search Blvd, Melbourne VIC 3000', '2024-10-10', 'Gold', 6200.00),
('Jacob', 'Mitchell', 'Male', 'jacob.mitchell@email.com', '+61-410-001-035', '35 Find Cres, Brisbane QLD 4000', '2024-11-15', 'Silver', 1400.00),
('Grace', 'Perez', 'Female', 'grace.perez@email.com', '+61-410-001-036', '36 Discover Dr, Perth WA 6000', '2024-12-01', 'Bronze', 320.00),
('Daniel', 'Roberts', 'Male', 'daniel.roberts@email.com', '+61-410-001-037', '37 Explore Rd, Adelaide SA 5000', '2024-01-20', 'Gold', 7800.00),
('Lily', 'Turner', 'Female', 'lily.turner@email.com', '+61-410-001-038', '38 Adventure St, Hobart TAS 7000', '2024-02-25', 'Platinum', 14500.00),
('Matthew', 'Phillips', 'Male', 'matthew.phillips@email.com', '+61-410-001-039', '39 Journey Ave, Darwin NT 0800', '2024-03-30', 'Silver', 2600.00),
('Zoe', 'Campbell', 'Female', 'zoe.campbell@email.com', '+61-410-001-040', '40 Trek Way, Canberra ACT 2600', '2024-04-15', 'Bronze', 680.00),
('Owen', 'Parker', 'Male', 'owen.parker@email.com', '+61-410-001-041', '41 Trail Lane, Sydney NSW 2000', '2024-05-20', 'Gold', 5400.00),
('Penelope', 'Evans', 'Female', 'penelope.evans@email.com', '+61-410-001-042', '42 Path St, Melbourne VIC 3000', '2024-06-25', 'Silver', 1700.00),
('Ryan', 'Edwards', 'Male', 'ryan.edwards@email.com', '+61-410-001-043', '43 Route Blvd, Brisbane QLD 4000', '2024-07-30', 'Bronze', 390.00),
('Layla', 'Collins', 'Female', 'layla.collins@email.com', '+61-410-001-044', '44 Way Cres, Perth WA 6000', '2024-08-05', 'Platinum', 10500.00),
('Nathan', 'Stewart', 'Male', 'nathan.stewart@email.com', '+61-410-001-045', '45 Road Dr, Adelaide SA 5000', '2024-09-10', 'Gold', 4500.00),
('Hannah', 'Sanchez', 'Female', 'hannah.sanchez@email.com', '+61-410-001-046', '46 Street Rd, Hobart TAS 7000', '2024-10-15', 'Silver', 1300.00),
('Caleb', 'Morris', 'Male', 'caleb.morris@email.com', '+61-410-001-047', '47 Avenue St, Darwin NT 0800', '2024-11-20', 'Bronze', 480.00),
('Nora', 'Rogers', 'Female', 'nora.rogers@email.com', '+61-410-001-048', '48 Boulevard Ave, Canberra ACT 2600', '2024-12-25', 'Gold', 6500.00),
('Isaac', 'Reed', 'Male', 'isaac.reed@email.com', '+61-410-001-049', '49 Crescent Way, Sydney NSW 2000', '2024-01-30', 'Platinum', 17500.00),
('Victoria', 'Cook', 'Female', 'victoria.cook@email.com', '+61-410-001-050', '50 Drive Lane, Melbourne VIC 3000', '2024-02-05', 'Silver', 2000.00);

-- ============================================
-- INVENTORY (Stock for all products at all branches)
-- ============================================
INSERT INTO inventory (branch_id, product_id, quantity, min_stock_level, max_stock_level, last_restocked)
SELECT
    b.branch_id,
    p.product_id,
    FLOOR(RAND() * 50) + 5 AS quantity,
    10 AS min_stock_level,
    100 AS max_stock_level,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY) AS last_restocked
FROM branch b
CROSS JOIN product p;

-- ============================================
-- CUSTOMER ORDERS (Sample orders)
-- ============================================
INSERT INTO customer_order (customer_id, branch_id, employee_id, order_date, status, subtotal, tax_amount, discount_amount, total_amount, shipping_address) VALUES
(1, 1, 2, '2024-01-15 10:30:00', 'Delivered', 1599.97, 160.00, 160.00, 1599.97, '1 Customer St, Sydney NSW 2000'),
(2, 2, 7, '2024-01-20 14:45:00', 'Delivered', 899.98, 90.00, 135.00, 854.98, '2 Buyer Ave, Melbourne VIC 3000'),
(3, 1, 3, '2024-02-05 09:15:00', 'Delivered', 349.98, 35.00, 17.50, 367.48, '3 Shopper Rd, Brisbane QLD 4000'),
(4, 3, 12, '2024-02-18 16:20:00', 'Delivered', 189.99, 19.00, 0.00, 208.99, '4 Client Way, Perth WA 6000'),
(5, 4, 16, '2024-03-01 11:00:00', 'Delivered', 549.99, 55.00, 55.00, 549.99, '5 Purchase Lane, Adelaide SA 5000'),
(6, 5, 21, '2024-03-15 13:30:00', 'Delivered', 299.99, 30.00, 15.00, 314.99, '6 Order St, Hobart TAS 7000'),
(7, 1, 4, '2024-04-02 10:00:00', 'Delivered', 129.99, 13.00, 0.00, 142.99, '7 Sale Blvd, Darwin NT 0800'),
(8, 2, 8, '2024-04-20 15:45:00', 'Delivered', 799.99, 80.00, 120.00, 759.99, '8 Deal Cres, Canberra ACT 2600'),
(9, 3, 13, '2024-05-08 09:30:00', 'Delivered', 449.99, 45.00, 45.00, 449.99, '9 Bargain Dr, Sydney NSW 2000'),
(10, 4, 17, '2024-05-25 14:00:00', 'Delivered', 159.98, 16.00, 8.00, 167.98, '10 Discount Rd, Melbourne VIC 3000'),
(11, 5, 22, '2024-06-10 11:15:00', 'Delivered', 89.99, 9.00, 0.00, 98.99, '11 Value St, Brisbane QLD 4000'),
(12, 1, 5, '2024-06-28 16:30:00', 'Delivered', 1299.99, 130.00, 130.00, 1299.99, '12 Savings Ave, Perth WA 6000'),
(13, 2, 9, '2024-07-15 10:45:00', 'Delivered', 599.98, 60.00, 90.00, 569.98, '13 Offer Way, Adelaide SA 5000'),
(14, 3, 14, '2024-07-30 13:00:00', 'Shipped', 249.99, 25.00, 12.50, 262.49, '14 Promo Lane, Hobart TAS 7000'),
(15, 4, 18, '2024-08-12 09:00:00', 'Processing', 79.99, 8.00, 0.00, 87.99, '15 Special St, Darwin NT 0800'),
(16, 5, 23, '2024-08-25 14:30:00', 'Delivered', 699.99, 70.00, 70.00, 699.99, '16 Clearance Blvd, Canberra ACT 2600'),
(17, 1, 2, '2024-09-08 11:00:00', 'Delivered', 189.98, 19.00, 9.50, 199.48, '17 Markdown Cres, Sydney NSW 2000'),
(18, 2, 7, '2024-09-22 15:15:00', 'Processing', 59.99, 6.00, 0.00, 65.99, '18 Rebate Dr, Melbourne VIC 3000'),
(19, 3, 12, '2024-10-05 10:30:00', 'Delivered', 1199.99, 120.00, 180.00, 1139.99, '19 Coupon Rd, Brisbane QLD 4000'),
(20, 4, 16, '2024-10-20 14:45:00', 'Delivered', 349.99, 35.00, 35.00, 349.99, '20 Cashback St, Perth WA 6000'),
(1, 1, 3, '2024-11-02 09:15:00', 'Delivered', 499.98, 50.00, 50.00, 499.98, '1 Customer St, Sydney NSW 2000'),
(2, 2, 8, '2024-11-15 13:30:00', 'Shipped', 299.99, 30.00, 45.00, 284.99, '2 Buyer Ave, Melbourne VIC 3000'),
(3, 1, 4, '2024-11-28 16:00:00', 'Pending', 149.99, 15.00, 7.50, 157.49, '3 Shopper Rd, Brisbane QLD 4000');

-- ============================================
-- ORDER ITEMS
-- ============================================
INSERT INTO order_item (order_id, product_id, quantity, unit_price, discount_percent, subtotal) VALUES
-- Order 1
(1, 1, 1, 1299.99, 0, 1299.99),
(1, 5, 1, 189.99, 0, 189.99),
(1, 11, 1, 49.99, 0, 49.99),
-- Order 2
(2, 2, 1, 799.99, 0, 799.99),
(2, 8, 1, 89.99, 0, 89.99),
-- Order 3
(3, 14, 1, 299.99, 0, 299.99),
(3, 11, 1, 49.99, 0, 49.99),
-- Order 4
(4, 5, 1, 189.99, 0, 189.99),
-- Order 5
(5, 41, 1, 549.99, 0, 549.99),
-- Order 6
(6, 14, 1, 299.99, 0, 299.99),
-- Order 7
(7, 22, 1, 129.99, 0, 129.99),
-- Order 8
(8, 2, 1, 799.99, 0, 799.99),
-- Order 9
(9, 15, 1, 449.99, 0, 449.99),
-- Order 10
(10, 6, 1, 59.99, 0, 59.99),
(10, 8, 1, 89.99, 0, 89.99),
-- Order 11
(11, 9, 1, 69.99, 0, 69.99),
-- Order 12
(12, 1, 1, 1299.99, 0, 1299.99),
-- Order 13
(13, 43, 1, 249.99, 0, 249.99),
(13, 44, 1, 149.99, 0, 149.99),
(13, 45, 1, 59.99, 0, 59.99),
-- Order 14
(14, 42, 1, 249.99, 0, 249.99),
-- Order 15
(15, 56, 1, 29.99, 0, 29.99),
(15, 57, 1, 39.99, 0, 39.99),
-- Order 16
(16, 42, 1, 699.99, 0, 699.99),
-- Order 17
(17, 5, 1, 189.99, 0, 189.99),
-- Order 18
(18, 45, 1, 59.99, 0, 59.99),
-- Order 19
(19, 50, 1, 1199.99, 0, 1199.99),
-- Order 20
(20, 23, 1, 249.99, 0, 249.99),
(20, 25, 1, 79.99, 0, 79.99),
-- Order 21
(21, 32, 1, 199.99, 0, 199.99),
(21, 34, 1, 129.99, 0, 129.99),
(21, 36, 1, 59.99, 0, 59.99),
-- Order 22
(22, 14, 1, 299.99, 0, 299.99),
-- Order 23
(23, 16, 1, 149.99, 0, 149.99);

-- ============================================
-- PAYMENTS
-- ============================================
INSERT INTO payment (order_id, payment_date, amount, payment_method, status, transaction_reference) VALUES
(1, '2024-01-15 10:35:00', 1599.97, 'Credit Card', 'Completed', 'TXN-2024-001001'),
(2, '2024-01-20 14:50:00', 854.98, 'PayPal', 'Completed', 'TXN-2024-001002'),
(3, '2024-02-05 09:20:00', 367.48, 'Debit Card', 'Completed', 'TXN-2024-001003'),
(4, '2024-02-18 16:25:00', 208.99, 'Credit Card', 'Completed', 'TXN-2024-001004'),
(5, '2024-03-01 11:05:00', 549.99, 'Bank Transfer', 'Completed', 'TXN-2024-001005'),
(6, '2024-03-15 13:35:00', 314.99, 'Credit Card', 'Completed', 'TXN-2024-001006'),
(7, '2024-04-02 10:05:00', 142.99, 'Cash', 'Completed', 'TXN-2024-001007'),
(8, '2024-04-20 15:50:00', 759.99, 'Credit Card', 'Completed', 'TXN-2024-001008'),
(9, '2024-05-08 09:35:00', 449.99, 'PayPal', 'Completed', 'TXN-2024-001009'),
(10, '2024-05-25 14:05:00', 167.98, 'Debit Card', 'Completed', 'TXN-2024-001010'),
(11, '2024-06-10 11:20:00', 98.99, 'Cash', 'Completed', 'TXN-2024-001011'),
(12, '2024-06-28 16:35:00', 1299.99, 'Credit Card', 'Completed', 'TXN-2024-001012'),
(13, '2024-07-15 10:50:00', 569.98, 'PayPal', 'Completed', 'TXN-2024-001013'),
(14, '2024-07-30 13:05:00', 262.49, 'Credit Card', 'Completed', 'TXN-2024-001014'),
(15, '2024-08-12 09:05:00', 87.99, 'Cash', 'Completed', 'TXN-2024-001015'),
(16, '2024-08-25 14:35:00', 699.99, 'Credit Card', 'Completed', 'TXN-2024-001016'),
(17, '2024-09-08 11:05:00', 199.48, 'Debit Card', 'Completed', 'TXN-2024-001017'),
(18, '2024-09-22 15:20:00', 65.99, 'Cash', 'Completed', 'TXN-2024-001018'),
(19, '2024-10-05 10:35:00', 1139.99, 'Credit Card', 'Completed', 'TXN-2024-001019'),
(20, '2024-10-20 14:50:00', 349.99, 'PayPal', 'Completed', 'TXN-2024-001020'),
(21, '2024-11-02 09:20:00', 499.98, 'Credit Card', 'Completed', 'TXN-2024-001021'),
(22, '2024-11-15 13:35:00', 284.99, 'Debit Card', 'Completed', 'TXN-2024-001022');

-- ============================================
-- PURCHASE ORDERS (Orders to suppliers)
-- ============================================
INSERT INTO purchase_order (supplier_id, branch_id, employee_id, order_date, expected_delivery, status, total_amount, notes) VALUES
(1, 1, 1, '2024-01-10', '2024-01-20', 'Received', 15000.00, 'Monthly bike stock replenishment'),
(2, 2, 6, '2024-01-15', '2024-01-25', 'Received', 8500.00, 'Camping gear for summer season'),
(7, 3, 11, '2024-02-01', '2024-02-15', 'Received', 5200.00, 'Climbing equipment restock'),
(4, 4, 15, '2024-02-20', '2024-03-05', 'Received', 12000.00, 'Water sports inventory'),
(1, 5, 20, '2024-03-10', '2024-03-20', 'Received', 9800.00, 'Cycling accessories bulk order'),
(9, 1, 1, '2024-04-05', '2024-04-15', 'Received', 6500.00, 'Camping furniture stock'),
(6, 2, 6, '2024-05-01', '2024-05-12', 'Received', 4200.00, 'Eco-friendly products'),
(5, 3, 11, '2024-06-15', '2024-06-28', 'Shipped', 7800.00, 'Hiking gear for winter'),
(7, 4, 15, '2024-07-20', '2024-08-01', 'Confirmed', 3500.00, 'Climbing shoes restock'),
(4, 5, 20, '2024-08-25', '2024-09-08', 'Submitted', 11000.00, 'Surfing season preparation');

-- ============================================
-- SHIPMENTS
-- ============================================
INSERT INTO shipment (supplier_id, branch_id, shipment_date, expected_arrival, actual_arrival, status, tracking_number, total_cost) VALUES
(1, 1, '2024-01-12', '2024-01-18', '2024-01-17', 'Delivered', 'SHIP-2024-001', 15000.00),
(2, 2, '2024-01-18', '2024-01-24', '2024-01-24', 'Delivered', 'SHIP-2024-002', 8500.00),
(7, 3, '2024-02-05', '2024-02-12', '2024-02-13', 'Delivered', 'SHIP-2024-003', 5200.00),
(4, 4, '2024-02-25', '2024-03-03', '2024-03-02', 'Delivered', 'SHIP-2024-004', 12000.00),
(1, 5, '2024-03-15', '2024-03-20', '2024-03-19', 'Delivered', 'SHIP-2024-005', 9800.00),
(9, 1, '2024-04-08', '2024-04-14', '2024-04-14', 'Delivered', 'SHIP-2024-006', 6500.00),
(6, 2, '2024-05-05', '2024-05-11', '2024-05-10', 'Delivered', 'SHIP-2024-007', 4200.00),
(5, 3, '2024-06-20', '2024-06-27', NULL, 'In Transit', 'SHIP-2024-008', 7800.00),
(7, 4, '2024-07-25', '2024-08-02', NULL, 'Pending', 'SHIP-2024-009', 3500.00);

-- ============================================
-- REVIEWS
-- ============================================
INSERT INTO review (customer_id, product_id, order_id, rating, title, comment, is_verified_purchase, is_approved) VALUES
(1, 1, 1, 5, 'Amazing Mountain Bike!', 'This bike exceeded my expectations. Great suspension and handles rough terrain perfectly.', TRUE, TRUE),
(1, 5, 1, 4, 'Good quality helmet', 'Fits well and feels safe. Ventilation could be better.', TRUE, TRUE),
(2, 2, 2, 5, 'Perfect for commuting', 'Love this hybrid bike. Smooth ride and very comfortable for daily commutes.', TRUE, TRUE),
(3, 14, 3, 4, 'Solid tent for the price', 'Easy to set up and kept us dry during rain. Good value.', TRUE, TRUE),
(5, 41, 5, 5, 'Best surfboard I have owned', 'Amazing performance. Catches waves easily and very responsive.', TRUE, TRUE),
(6, 14, 6, 4, 'Great lightweight tent', 'Perfect for backpacking. Very compact when packed.', TRUE, TRUE),
(8, 2, 8, 5, 'Excellent bike!', 'Smooth gears and comfortable seat. Highly recommend for city riding.', TRUE, TRUE),
(9, 15, 9, 5, 'Premium quality tent', 'Worth every penny. Stood up to strong winds without any issues.', TRUE, TRUE),
(12, 1, 12, 5, 'Dream bike', 'Finally got my dream mountain bike. Performance is outstanding!', TRUE, TRUE),
(16, 42, 16, 4, 'Great for beginners', 'Soft foam board is perfect for learning. Very forgiving.', TRUE, TRUE),
(19, 50, 19, 5, 'Incredible kayak', 'Tracks beautifully and very stable. Perfect for touring.', TRUE, TRUE),
(20, 23, 20, 4, 'Comfortable pack', 'Good support and lots of pockets. Hip belt could be more padded.', TRUE, TRUE),
(4, 5, 4, 3, 'Decent helmet', 'Does the job but nothing special. A bit heavy.', TRUE, TRUE),
(7, 22, 7, 4, 'Reliable backpack', 'Durable and fits a lot. Straps are comfortable.', TRUE, TRUE),
(10, 6, 10, 5, 'Best budget helmet', 'Great protection at an affordable price. Highly recommend!', TRUE, TRUE);

-- ============================================
-- USERS (Authentication accounts)
-- ============================================
-- Note: Passwords are 'password123' hashed with PHP password_hash()
-- In production, use proper password hashing
INSERT INTO user (username, password_hash, role, employee_id, supplier_id, customer_id) VALUES
-- Manager accounts
('thomas.anderson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 1, NULL, NULL),
('jessica.roberts', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 6, NULL, NULL),
('benjamin.scott', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 11, NULL, NULL),
('william.lewis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 15, NULL, NULL),
('alexander.king', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 20, NULL, NULL),
-- Staff accounts
('emily.parker', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 2, NULL, NULL),
('ryan.mitchell', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 3, NULL, NULL),
('daniel.clark', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 7, NULL, NULL),
('mia.thompson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 12, NULL, NULL),
('isabella.walker', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 16, NULL, NULL),
('amelia.wright', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 21, NULL, NULL),
-- Supplier accounts
('supplier.cyclegear', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supplier', NULL, 1, NULL),
('supplier.adventure', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supplier', NULL, 2, NULL),
('supplier.summit', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supplier', NULL, 3, NULL),
('supplier.coastal', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supplier', NULL, 4, NULL),
('supplier.proclimb', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supplier', NULL, 7, NULL),
-- Customer accounts
('john.smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer', NULL, NULL, 1),
('sarah.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer', NULL, NULL, 2),
('michael.williams', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer', NULL, NULL, 3),
('emma.brown', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer', NULL, NULL, 4),
('david.taylor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer', NULL, NULL, 5);

SELECT 'Seed data inserted successfully!' AS Status;
SELECT CONCAT('Branches: ', COUNT(*)) AS Summary FROM branch;
SELECT CONCAT('Suppliers: ', COUNT(*)) AS Summary FROM supplier;
SELECT CONCAT('Employees: ', COUNT(*)) AS Summary FROM employee;
SELECT CONCAT('Categories: ', COUNT(*)) AS Summary FROM category;
SELECT CONCAT('Products: ', COUNT(*)) AS Summary FROM product;
SELECT CONCAT('Customers: ', COUNT(*)) AS Summary FROM customer;
SELECT CONCAT('Orders: ', COUNT(*)) AS Summary FROM customer_order;
SELECT CONCAT('Users: ', COUNT(*)) AS Summary FROM user;
