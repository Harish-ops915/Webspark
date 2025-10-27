-- ========================================
-- WEBSPARK TRAFFIC NETWORK DATABASE
-- Complete Setup with Phase 4 & 5 Features
-- ========================================

USE webspark;

-- Drop existing tables if they exist (in proper order due to foreign keys)
DROP TABLE IF EXISTS security_logs;
DROP TABLE IF EXISTS website_analytics;
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS social_analytics;
DROP TABLE IF EXISTS social_accounts;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS credits_log;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS websites;
DROP TABLE IF EXISTS users;

-- ========================================
-- CORE TABLES WITH ENHANCED FEATURES
-- ========================================

-- Enhanced Users Table with Phase 4 & 5 Features
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    credits INT DEFAULT 0,
    subscription VARCHAR(20) DEFAULT 'free',
    country VARCHAR(50),
    niche VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Phase 4: Security Features
    email_verified TINYINT(1) DEFAULT 0,
    verification_code VARCHAR(64),
    reset_token VARCHAR(64),
    reset_expires DATETIME,
    failed_login_attempts INT DEFAULT 0,
    last_login_attempt DATETIME,
    account_locked TINYINT(1) DEFAULT 0,
    
    -- Phase 5: Additional Features
    referral_code VARCHAR(32),
    referred_by INT,
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP,
    timezone VARCHAR(50) DEFAULT 'UTC',
    language_preference VARCHAR(10) DEFAULT 'en',
    avatar_url VARCHAR(255),
    bio TEXT,
    
    -- Foreign Key for referrals (self-reference)
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for better performance
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_subscription (subscription),
    INDEX idx_email_verified (email_verified),
    INDEX idx_referral_code (referral_code),
    INDEX idx_created_at (created_at)
);

-- Enhanced Websites Table
CREATE TABLE websites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    url VARCHAR(500) NOT NULL, -- Increased length for longer URLs
    title VARCHAR(200),
    description TEXT,
    niche VARCHAR(50),
    country VARCHAR(50),
    language VARCHAR(10) DEFAULT 'en',
    status VARCHAR(20) DEFAULT 'pending', -- pending, active, suspended, rejected
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Phase 4: Enhanced tracking
    approval_date DATETIME,
    rejection_reason TEXT,
    last_checked DATETIME,
    
    -- Phase 5: Advanced features
    meta_keywords TEXT,
    category VARCHAR(100),
    target_audience VARCHAR(100),
    monthly_visitors INT DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_session_duration INT DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_niche (niche),
    INDEX idx_country (country),
    INDEX idx_created_at (created_at),
    
    -- Unique constraint on URL
    UNIQUE KEY unique_url (url)
);

-- Enhanced Visits Table with detailed tracking
CREATE TABLE visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    visitor_user_id INT NOT NULL,
    dwell_time INT DEFAULT 0, -- in seconds
    bounce TINYINT(1) DEFAULT 0,
    visit_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Phase 4: Enhanced tracking
    ip_address VARCHAR(45), -- IPv6 support
    user_agent TEXT,
    referrer VARCHAR(500),
    screen_resolution VARCHAR(20),
    
    -- Phase 5: Advanced analytics
    pages_viewed INT DEFAULT 1,
    country_code VARCHAR(2),
    city VARCHAR(100),
    device_type VARCHAR(50), -- desktop, mobile, tablet
    browser VARCHAR(50),
    os VARCHAR(50),
    click_count INT DEFAULT 0,
    scroll_depth DECIMAL(5,2) DEFAULT 0.00, -- percentage
    
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    FOREIGN KEY (visitor_user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_website_id (website_id),
    INDEX idx_visitor_user_id (visitor_user_id),
    INDEX idx_visit_time (visit_time),
    INDEX idx_country_code (country_code),
    INDEX idx_device_type (device_type)
);

-- Enhanced Credits Log
CREATE TABLE credits_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    change_amount INT NOT NULL, -- Can be negative
    balance_after INT NOT NULL, -- Balance after this transaction
    reason VARCHAR(200) NOT NULL,
    reference_id INT, -- For linking to specific transactions
    reference_type VARCHAR(50), -- visit, payment, referral, bonus, admin
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Phase 5: Additional tracking
    admin_user_id INT, -- If changed by admin
    notes TEXT,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_reference_type (reference_type),
    INDEX idx_date (date)
);

-- ========================================
-- PHASE 5: NEW TABLES
-- ========================================

-- Referrals System
CREATE TABLE referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    bonus_credits INT DEFAULT 5,
    referrer_bonus INT DEFAULT 5,
    status VARCHAR(20) DEFAULT 'completed', -- pending, completed, cancelled
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Prevent duplicate referrals
    UNIQUE KEY unique_referral (referrer_id, referred_id),
    
    -- Indexes
    INDEX idx_referrer_id (referrer_id),
    INDEX idx_referred_id (referred_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Payment System
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    plan_type VARCHAR(50), -- premium, pro, credits_50, credits_200, etc.
    payment_method VARCHAR(20), -- stripe, paypal
    
    -- Payment processor details
    stripe_payment_id VARCHAR(100),
    stripe_customer_id VARCHAR(100),
    paypal_payment_id VARCHAR(100),
    paypal_payer_id VARCHAR(100),
    
    status VARCHAR(20) DEFAULT 'pending', -- pending, completed, failed, refunded, cancelled
    failure_reason TEXT,
    
    -- Metadata
    credits_added INT DEFAULT 0,
    subscription_start DATETIME,
    subscription_end DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    INDEX idx_plan_type (plan_type),
    INDEX idx_created_at (created_at)
);

-- Social Media Accounts
CREATE TABLE social_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform VARCHAR(50) NOT NULL, -- facebook, twitter, instagram, linkedin, youtube
    account_id VARCHAR(100) NOT NULL, -- Platform-specific ID
    account_username VARCHAR(100),
    account_name VARCHAR(200),
    
    -- OAuth tokens
    access_token TEXT,
    refresh_token TEXT,
    token_expires DATETIME,
    
    -- Account metrics
    followers_count INT DEFAULT 0,
    following_count INT DEFAULT 0,
    posts_count INT DEFAULT 0,
    
    status VARCHAR(20) DEFAULT 'connected', -- connected, disconnected, error
    last_sync DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Unique constraint per user per platform
    UNIQUE KEY unique_user_platform (user_id, platform),
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_platform (platform),
    INDEX idx_status (status)
);

-- Social Media Analytics
CREATE TABLE social_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    social_account_id INT,
    platform VARCHAR(50) NOT NULL,
    metric_name VARCHAR(100) NOT NULL, -- impressions, reach, engagement, clicks, etc.
    metric_value INT DEFAULT 0,
    metric_date DATE NOT NULL,
    
    -- Additional context
    post_id VARCHAR(100), -- If metric is post-specific
    campaign_id VARCHAR(100), -- If metric is campaign-specific
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE CASCADE,
    
    -- Unique constraint to prevent duplicates
    UNIQUE KEY unique_metric_date (user_id, platform, metric_name, metric_date, post_id, campaign_id),
    
    -- Indexes
    INDEX idx_user_platform (user_id, platform),
    INDEX idx_metric_date (metric_date),
    INDEX idx_platform_metric (platform, metric_name)
);

-- Website Analytics (Aggregated Daily Stats)
CREATE TABLE website_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    date DATE NOT NULL,
    
    -- Traffic metrics
    visits_count INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    page_views INT DEFAULT 0,
    total_dwell_time INT DEFAULT 0, -- in seconds
    
    -- Engagement metrics
    bounce_count INT DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_session_duration DECIMAL(8,2) DEFAULT 0.00,
    
    -- Traffic sources
    direct_traffic INT DEFAULT 0,
    referral_traffic INT DEFAULT 0,
    social_traffic INT DEFAULT 0,
    search_traffic INT DEFAULT 0,
    
    -- Device breakdown
    desktop_visits INT DEFAULT 0,
    mobile_visits INT DEFAULT 0,
    tablet_visits INT DEFAULT 0,
    
    -- Geographic data
    top_country VARCHAR(50),
    country_stats JSON, -- Store country breakdown as JSON
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    
    -- Unique constraint per website per date
    UNIQUE KEY unique_website_date (website_id, date),
    
    -- Indexes
    INDEX idx_website_date (website_id, date),
    INDEX idx_date (date)
);

-- Email Logs
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    email_type VARCHAR(50) NOT NULL, -- verification, password_reset, welcome, payment_confirmation, etc.
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    template_used VARCHAR(100),
    
    status VARCHAR(20) DEFAULT 'sent', -- sent, failed, bounced, opened, clicked
    error_message TEXT,
    
    -- Email tracking
    opened_at DATETIME,
    clicked_at DATETIME,
    unsubscribed_at DATETIME,
    
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_user_email (user_id),
    INDEX idx_email_type (email_type),
    INDEX idx_status (status),
    INDEX idx_sent_date (sent_at)
);

-- Security Logs
CREATE TABLE security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_type VARCHAR(50) NOT NULL, -- login_attempt, failed_login, password_reset, account_locked, etc.
    ip_address VARCHAR(45),
    user_agent TEXT,
    success TINYINT(1) DEFAULT 0,
    
    -- Additional context
    details JSON, -- Store additional event details as JSON
    risk_score INT DEFAULT 0, -- 0-100 security risk score
    country_code VARCHAR(2),
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_user_security (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
);

-- ========================================
-- SAMPLE DATA - ENHANCED
-- ========================================

-- Insert Enhanced Sample Users
INSERT INTO users (username, email, password, credits, subscription, country, niche, email_verified, referral_code, timezone, language_preference, bio) VALUES 
('johndoe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 125, 'premium', 'Australia', 'Technology', 1, 'REF_JOHN_2025', 'Australia/Sydney', 'en', 'Tech enthusiast and blogger from Sydney. Love exploring new technologies and sharing insights.'),
('janedoe', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 50, 'free', 'USA', 'Health', 1, 'REF_JANE_2025', 'America/New_York', 'en', 'Fitness coach and wellness advocate. Helping people live healthier lives through proper nutrition and exercise.'),
('techguru', 'tech@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 200, 'pro', 'UK', 'Technology', 1, 'REF_TECH_2025', 'Europe/London', 'en', 'Senior software developer with 10+ years experience. Passionate about clean code and mentoring others.'),
('healthpro', 'health@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 75, 'premium', 'Canada', 'Health', 1, 'REF_HEALTH_2025', 'America/Toronto', 'en', 'Licensed nutritionist and personal trainer. Specializing in holistic health approaches.'),
('bizexpert', 'biz@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 100, 'free', 'India', 'Business', 1, 'REF_BIZ_2025', 'Asia/Kolkata', 'en', 'Serial entrepreneur and business consultant. Helping startups scale and optimize their operations.'),
('travelguru', 'travel@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 80, 'premium', 'Germany', 'Travel', 1, 'REF_TRAVEL_2025', 'Europe/Berlin', 'de', 'Professional travel blogger and photographer. Exploring the world one country at a time.'),
('foodie123', 'food@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 60, 'free', 'France', 'Food', 1, 'REF_FOOD_2025', 'Europe/Paris', 'fr', 'Chef and culinary artist. Sharing authentic recipes and cooking techniques from around the world.');

-- Insert Enhanced Sample Websites
INSERT INTO websites (user_id, url, title, description, niche, country, language, status, meta_keywords, category, monthly_visitors, bounce_rate, avg_session_duration) VALUES 
(1, 'https://techblog.com', 'Tech Blog Australia', 'Latest technology news, reviews, and insights from Australia', 'Technology', 'Australia', 'en', 'active', 'technology, australia, tech news, gadgets, software', 'Blog', 15000, 35.50, 180),
(1, 'https://myblog.com', 'John\'s Personal Blog', 'Personal thoughts and experiences in the tech world', 'Technology', 'Australia', 'en', 'active', 'personal blog, technology, programming, reviews', 'Personal', 8500, 42.30, 145),
(2, 'https://healthtips.net', 'Health Tips Daily', 'Daily health tips and wellness advice for better living', 'Health', 'USA', 'en', 'active', 'health tips, wellness, fitness, nutrition', 'Health & Wellness', 22000, 28.40, 210),
(2, 'https://fitness-guide.com', 'Complete Fitness Guide', 'Comprehensive fitness guides and workout routines', 'Health', 'USA', 'en', 'active', 'fitness, workout, exercise, health', 'Fitness', 18500, 31.20, 195),
(3, 'https://techinsights.co.uk', 'Tech Insights UK', 'Deep dives into emerging technologies and trends', 'Technology', 'UK', 'en', 'active', 'technology insights, UK tech, innovation, startups', 'Technology News', 35000, 25.10, 240),
(4, 'https://wellness-center.ca', 'Canadian Wellness Center', 'Holistic health and wellness resource for Canadians', 'Health', 'Canada', 'en', 'active', 'wellness, canada, holistic health, nutrition', 'Health Center', 12000, 33.80, 165),
(5, 'https://businessinsights.co', 'Business Insights Hub', 'Strategic business insights and entrepreneurship tips', 'Business', 'India', 'en', 'active', 'business, entrepreneurship, startup, strategy', 'Business', 28000, 38.60, 175),
(5, 'https://startup-tips.com', 'Startup Success Tips', 'Essential tips and resources for startup founders', 'Business', 'India', 'en', 'active', 'startup, tips, entrepreneur, business', 'Startup', 16500, 41.20, 155),
(6, 'https://wanderlust-travels.de', 'Wanderlust Travels', 'Inspiring travel stories and destination guides', 'Travel', 'Germany', 'de', 'active', 'travel, wanderlust, destinations, adventure', 'Travel Blog', 45000, 22.30, 280),
(7, 'https://authentic-recipes.fr', 'Authentic French Recipes', 'Traditional and modern French cuisine recipes', 'Food', 'France', 'fr', 'active', 'french cuisine, recipes, cooking, food', 'Food Blog', 31000, 29.70, 220);

-- Insert Sample Visits with Enhanced Data
INSERT INTO visits (website_id, visitor_user_id, dwell_time, bounce, ip_address, user_agent, device_type, browser, os, pages_viewed, country_code, city, click_count, scroll_depth) VALUES 
(1, 2, 180, 0, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'desktop', 'Chrome', 'Windows', 3, 'US', 'New York', 8, 75.50),
(1, 3, 240, 0, '10.0.0.50', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', 'desktop', 'Safari', 'macOS', 4, 'GB', 'London', 12, 89.20),
(2, 4, 150, 0, '172.16.0.75', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101', 'desktop', 'Firefox', 'Windows', 2, 'CA', 'Toronto', 5, 65.30),
(3, 1, 320, 0, '203.0.113.45', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15', 'mobile', 'Safari', 'iOS', 5, 'AU', 'Sydney', 15, 92.10),
(4, 5, 90, 1, '198.51.100.25', 'Mozilla/5.0 (Android 11; Mobile; rv:68.0) Gecko/68.0 Firefox/88.0', 'mobile', 'Firefox', 'Android', 1, 'IN', 'Mumbai', 2, 35.80),
(5, 1, 280, 0, '203.0.113.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'desktop', 'Chrome', 'Windows', 6, 'AU', 'Sydney', 18, 95.60),
(6, 3, 200, 0, '10.0.0.50', 'Mozilla/5.0 (iPad; CPU OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15', 'tablet', 'Safari', 'iPadOS', 3, 'GB', 'London', 9, 78.40),
(7, 2, 165, 0, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'desktop', 'Edge', 'Windows', 2, 'US', 'New York', 7, 82.30);

-- Insert Enhanced Credits Log
INSERT INTO credits_log (user_id, change_amount, balance_after, reason, reference_type) VALUES 
(1, 10, 10, 'Registration bonus', 'bonus'),
(1, 100, 110, 'Upgraded to Premium plan', 'payment'),
(1, 2, 112, 'Website visit completed', 'visit'),
(1, 5, 117, 'Referral bonus earned', 'referral'),
(1, 8, 125, 'Daily login bonus', 'bonus'),
(2, 10, 10, 'Registration bonus', 'bonus'),
(2, 1, 11, 'Website visit completed', 'visit'),
(2, -1, 10, 'Received website visit', 'visit'),
(2, 40, 50, 'Referral bonuses accumulated', 'referral'),
(3, 10, 10, 'Registration bonus', 'bonus'),
(3, 500, 510, 'Upgraded to Pro plan', 'payment'),
(3, -310, 200, 'Used credits for traffic exchange', 'visit'),
(4, 10, 10, 'Registration bonus', 'bonus'),
(4, 100, 110, 'Upgraded to Premium plan', 'payment'),
(4, -35, 75, 'Website traffic campaigns', 'visit'),
(5, 10, 10, 'Registration bonus', 'bonus'),
(5, 15, 25, 'Referral bonuses', 'referral'),
(5, 75, 100, 'Contest winner bonus', 'bonus'),
(6, 10, 10, 'Registration bonus', 'bonus'),
(6, 100, 110, 'Upgraded to Premium plan', 'payment'),
(6, -30, 80, 'Traffic exchange usage', 'visit'),
(7, 10, 10, 'Registration bonus', 'bonus'),
(7, 50, 60, 'Referral program earnings', 'referral');

-- Insert Sample Referrals
INSERT INTO referrals (referrer_id, referred_id, bonus_credits, referrer_bonus, status, completed_at) VALUES 
(1, 4, 5, 5, 'completed', '2025-01-15 10:30:00'),
(1, 6, 5, 5, 'completed', '2025-02-01 14:20:00'),
(2, 7, 5, 5, 'completed', '2025-02-10 09:15:00'),
(3, 5, 5, 5, 'completed', '2025-01-28 16:45:00'),
(1, 2, 5, 5, 'completed', '2025-01-10 11:00:00');

-- Insert Sample Payments
INSERT INTO payments (user_id, amount, plan_type, payment_method, stripe_payment_id, status, credits_added, subscription_start, subscription_end) VALUES 
(1, 19.00, 'premium', 'stripe', 'pi_1ABC123DEF456GHI', 'completed', 100, '2025-02-01 00:00:00', '2025-03-01 00:00:00'),
(3, 49.00, 'pro', 'stripe', 'pi_2DEF456GHI789JKL', 'completed', 500, '2025-01-15 00:00:00', '2025-02-15 00:00:00'),
(4, 19.00, 'premium', 'stripe', 'pi_3GHI789JKL012MNO', 'completed', 100, '2025-02-15 00:00:00', '2025-03-15 00:00:00'),
(6, 19.00, 'premium', 'stripe', 'pi_4JKL012MNO345PQR', 'completed', 100, '2025-01-20 00:00:00', '2025-02-20 00:00:00'),
(2, 5.00, 'credits_50', 'stripe', 'pi_5MNO345PQR678STU', 'completed', 50, NULL, NULL);

-- Insert Sample Social Accounts
INSERT INTO social_accounts (user_id, platform, account_id, account_username, account_name, followers_count, following_count, posts_count, status, last_sync) VALUES 
(1, 'twitter', '123456789', 'johndoe_tech', 'John Doe - Tech Blogger', 5420, 892, 1250, 'connected', '2025-10-13 09:00:00'),
(1, 'facebook', '987654321', 'johndoe.tech', 'John Doe Tech Blog', 3200, 450, 380, 'connected', '2025-10-13 08:30:00'),
(2, 'instagram', '456789123', 'jane_health_tips', 'Jane Doe - Health Coach', 12500, 1200, 890, 'connected', '2025-10-13 10:15:00'),
(3, 'linkedin', '789123456', 'techguru-uk', 'Tech Guru - Senior Developer', 8900, 2100, 145, 'connected', '2025-10-13 07:45:00'),
(6, 'instagram', '321654987', 'wanderlust_travels_de', 'Wanderlust Travels', 28400, 1800, 1520, 'connected', '2025-10-13 11:20:00'),
(7, 'youtube', '654987321', 'authentic_french_cooking', 'Authentic French Recipes', 15600, 890, 245, 'connected', '2025-10-13 09:30:00');

-- Insert Sample Social Analytics
INSERT INTO social_analytics (user_id, social_account_id, platform, metric_name, metric_value, metric_date) VALUES 
-- John Doe Twitter Analytics
(1, 1, 'twitter', 'impressions', 45200, '2025-10-12'),
(1, 1, 'twitter', 'engagements', 1820, '2025-10-12'),
(1, 1, 'twitter', 'profile_visits', 340, '2025-10-12'),
(1, 1, 'twitter', 'mentions', 28, '2025-10-12'),
-- Jane Instagram Analytics  
(2, 3, 'instagram', 'impressions', 89500, '2025-10-12'),
(2, 3, 'instagram', 'reach', 12400, '2025-10-12'),
(2, 3, 'instagram', 'profile_visits', 890, '2025-10-12'),
(2, 3, 'instagram', 'website_clicks', 156, '2025-10-12'),
-- Tech Guru LinkedIn Analytics
(3, 4, 'linkedin', 'impressions', 15600, '2025-10-12'),
(3, 4, 'linkedin', 'clicks', 420, '2025-10-12'),
(3, 4, 'linkedin', 'reactions', 89, '2025-10-12'),
(3, 4, 'linkedin', 'comments', 34, '2025-10-12');

-- Insert Sample Website Analytics
INSERT INTO website_analytics (website_id, date, visits_count, unique_visitors, page_views, total_dwell_time, bounce_count, bounce_rate, avg_session_duration, direct_traffic, referral_traffic, social_traffic, desktop_visits, mobile_visits, tablet_visits, top_country) VALUES 
(1, '2025-10-12', 145, 98, 320, 18900, 32, 22.07, 130.34, 45, 62, 38, 89, 42, 14, 'Australia'),
(2, '2025-10-12', 89, 67, 178, 12600, 28, 31.46, 141.57, 28, 35, 26, 58, 23, 8, 'Australia'),
(3, '2025-10-12', 234, 156, 520, 35600, 41, 17.52, 152.14, 89, 98, 47, 145, 67, 22, 'United Kingdom'),
(4, '2025-10-12', 167, 124, 298, 22400, 45, 26.95, 134.13, 52, 71, 44, 102, 48, 17, 'Canada'),
(5, '2025-10-12', 201, 145, 445, 28900, 58, 28.86, 143.78, 67, 89, 45, 125, 58, 18, 'India');

-- Insert Sample Email Logs
INSERT INTO email_logs (user_id, email_type, recipient_email, subject, template_used, status, opened_at, clicked_at) VALUES 
(1, 'welcome', 'john@example.com', 'Welcome to Webspark!', 'welcome_template', 'opened', '2025-01-10 11:30:00', '2025-01-10 11:32:00'),
(2, 'verification', 'jane@example.com', 'Verify your email address', 'email_verification', 'clicked', '2025-01-12 09:15:00', '2025-01-12 09:16:00'),
(3, 'payment_confirmation', 'tech@example.com', 'Payment Successful - Pro Plan', 'payment_success', 'opened', '2025-01-15 14:20:00', NULL),
(4, 'password_reset', 'health@example.com', 'Reset your password', 'password_reset', 'clicked', '2025-02-01 16:45:00', '2025-02-01 16:47:00'),
(5, 'referral_bonus', 'biz@example.com', 'You earned referral credits!', 'referral_earned', 'opened', '2025-01-28 18:30:00', NULL);

-- Insert Sample Security Logs
INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, success, details, country_code) VALUES 
(1, 'login_success', '203.0.113.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 1, '{"login_method": "password", "remember_me": true}', 'AU'),
(2, 'failed_login', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 0, '{"reason": "invalid_password", "attempts": 1}', 'US'),
(3, 'password_reset_requested', '10.0.0.50', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', 1, '{"email": "tech@example.com"}', 'GB'),
(4, 'login_success', '172.16.0.75', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15', 1, '{"login_method": "password", "device": "mobile"}', 'CA'),
(1, 'account_settings_changed', '203.0.113.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 1, '{"changed_fields": ["country", "niche"]}', 'AU');

-- ========================================
-- PERFORMANCE INDEXES & OPTIMIZATIONS
-- ========================================

-- Additional performance indexes
ALTER TABLE users ADD INDEX idx_last_active (last_active);
ALTER TABLE visits ADD INDEX idx_dwell_time (dwell_time);
ALTER TABLE visits ADD INDEX idx_bounce (bounce);
ALTER TABLE payments ADD INDEX idx_stripe_payment_id (stripe_payment_id);
ALTER TABLE social_analytics ADD INDEX idx_metric_value (metric_value);
ALTER TABLE website_analytics ADD INDEX idx_visits_count (visits_count);

-- ========================================
-- TRIGGERS FOR AUTOMATION
-- ========================================

-- Trigger to update user's last_active on any activity
DELIMITER //
CREATE TRIGGER update_user_last_active 
AFTER INSERT ON visits 
FOR EACH ROW 
BEGIN
    UPDATE users SET last_active = NOW() WHERE id = NEW.visitor_user_id;
END//
DELIMITER ;

-- Trigger to update credits balance in credits_log
DELIMITER //
CREATE TRIGGER update_credits_balance 
BEFORE INSERT ON credits_log 
FOR EACH ROW 
BEGIN
    DECLARE current_balance INT DEFAULT 0;
    SELECT credits INTO current_balance FROM users WHERE id = NEW.user_id;
    SET NEW.balance_after = current_balance + NEW.change_amount;
END//
DELIMITER ;

-- ========================================
-- VIEWS FOR COMMON QUERIES
-- ========================================

-- Create view for user statistics
CREATE VIEW user_statistics AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.subscription,
    u.credits,
    u.created_at,
    COUNT(DISTINCT w.id) as total_websites,
    COUNT(DISTINCT v.id) as total_visits_made,
    COUNT(DISTINCT v2.id) as total_visits_received,
    COALESCE(r.total_referrals, 0) as total_referrals,
    COALESCE(p.total_spent, 0) as total_spent
FROM users u
LEFT JOIN websites w ON u.id = w.user_id
LEFT JOIN visits v ON u.id = v.visitor_user_id
LEFT JOIN visits v2 ON w.id = v2.website_id
LEFT JOIN (
    SELECT referrer_id, COUNT(*) as total_referrals 
    FROM referrals 
    WHERE status = 'completed' 
    GROUP BY referrer_id
) r ON u.id = r.referrer_id
LEFT JOIN (
    SELECT user_id, SUM(amount) as total_spent 
    FROM payments 
    WHERE status = 'completed' 
    GROUP BY user_id
) p ON u.id = p.user_id
GROUP BY u.id;

-- Create view for website performance
CREATE VIEW website_performance AS
SELECT 
    w.id,
    w.url,
    w.title,
    w.niche,
    w.country,
    w.user_id,
    u.username as owner,
    COUNT(DISTINCT v.id) as total_visits,
    AVG(v.dwell_time) as avg_dwell_time,
    (COUNT(CASE WHEN v.bounce = 0 THEN 1 END) * 100.0 / COUNT(v.id)) as engagement_rate,
    SUM(v.pages_viewed) as total_page_views
FROM websites w
JOIN users u ON w.user_id = u.id
LEFT JOIN visits v ON w.id = v.website_id
GROUP BY w.id;

-- ========================================
-- INITIAL DATA VERIFICATION
-- ========================================

-- Show table sizes
SELECT 
    'users' as table_name, COUNT(*) as record_count FROM users
UNION ALL
SELECT 'websites', COUNT(*) FROM websites
UNION ALL  
SELECT 'visits', COUNT(*) FROM visits
UNION ALL
SELECT 'referrals', COUNT(*) FROM referrals
UNION ALL
SELECT 'payments', COUNT(*) FROM payments
UNION ALL
SELECT 'social_accounts', COUNT(*) FROM social_accounts
UNION ALL
SELECT 'social_analytics', COUNT(*) FROM social_analytics
UNION ALL
SELECT 'website_analytics', COUNT(*) FROM website_analytics
UNION ALL
SELECT 'email_logs', COUNT(*) FROM email_logs
UNION ALL
SELECT 'security_logs', COUNT(*) FROM security_logs
UNION ALL
SELECT 'credits_log', COUNT(*) FROM credits_log;

-- ========================================
-- DATABASE SETUP COMPLETE
-- Ready for Phase 4 & 5 Features!
-- ========================================
