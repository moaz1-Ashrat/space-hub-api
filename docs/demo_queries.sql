-- =====================================================================
--  SPACE HUB — LIVE DEMO QUERIES
--  يثبت إن أرقام الـ Dashboard حقيقية من الداتابيز
-- =====================================================================

-- =====================================================================
-- [1] الأرقام الأساسية (يطابق اللي في Dashboard)
-- =====================================================================
SELECT 'Total Users'    AS metric, COUNT(*) AS value FROM users
UNION ALL SELECT 'Total Spaces',     COUNT(*) FROM spaces
UNION ALL SELECT 'Total Bookings',   COUNT(*) FROM bookings
UNION ALL SELECT 'Total Revenue (EGP)',
    ROUND(SUM(total_amount), 2) FROM bookings
    WHERE booking_status IN ('confirmed', 'completed')
UNION ALL SELECT 'Approved Spaces',  COUNT(*) FROM spaces WHERE approval_status = 'approved'
UNION ALL SELECT 'Pending Spaces',   COUNT(*) FROM spaces WHERE approval_status = 'pending'
UNION ALL SELECT 'Rejected Spaces',  COUNT(*) FROM spaces WHERE approval_status = 'rejected';


-- =====================================================================
-- [2] توزيع المستخدمين حسب النوع
-- =====================================================================
SELECT role, COUNT(*) AS count FROM users GROUP BY role ORDER BY count DESC;


-- =====================================================================
-- [3] توزيع حجوزات حسب الحالة (يطابق الـ Donut chart)
-- =====================================================================
SELECT booking_status, COUNT(*) AS count FROM bookings GROUP BY booking_status ORDER BY count DESC;


-- =====================================================================
-- [4] الدخل الشهري (يطابق Revenue Trend Chart)
-- =====================================================================
SELECT
    DATE_FORMAT(booking_date, '%b %Y') AS month,
    COUNT(*) AS bookings,
    ROUND(SUM(total_amount), 2) AS revenue,
    ROUND(SUM(commission_amount), 2) AS commission,
    ROUND(SUM(owner_payout), 2) AS owner_payout
FROM bookings
WHERE booking_status IN ('confirmed', 'completed')
  AND booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(booking_date, '%Y-%m'), DATE_FORMAT(booking_date, '%b %Y')
ORDER BY MIN(booking_date);


-- =====================================================================
-- [5] متوسط الأعمار (يطابق Demographics Section)
-- =====================================================================
SELECT
    role,
    ROUND(AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())), 1) AS avg_age,
    COUNT(*) AS users_count
FROM users
WHERE date_of_birth IS NOT NULL
GROUP BY role;


-- =====================================================================
-- [6] توزيع الفئات العمرية
-- =====================================================================
SELECT
    CASE
        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 25 THEN '18-24'
        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
        ELSE '55+'
    END AS age_group,
    COUNT(*) AS count
FROM users
WHERE date_of_birth IS NOT NULL
GROUP BY age_group
ORDER BY age_group;


-- =====================================================================
-- [7] الكوبونات (يطابق Coupons Analytics)
-- =====================================================================
SELECT
    COUNT(*) AS total_coupons,
    SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) AS used,
    SUM(CASE WHEN is_used = 0 THEN 1 ELSE 0 END) AS available,
    ROUND(SUM(CASE WHEN is_used = 1 THEN discount_value ELSE 0 END), 2) AS total_discounts_given
FROM coupons;


-- =====================================================================
-- [8] إثبات العلاقات (Relationships Proof)
-- =====================================================================
SELECT
    b.id AS booking_id,
    CONCAT(u.first_name, ' ', u.last_name) AS customer,
    s.name AS space_name,
    b.total_amount,
    b.booking_status,
    p.payment_status
FROM bookings b
JOIN users u    ON u.id = b.user_id
JOIN spaces s   ON s.id = b.space_id
JOIN payments p ON p.id = b.payment_id
LIMIT 10;


-- =====================================================================
-- [9] الإيرادات لكل مساحة (Top 5)
-- =====================================================================
SELECT
    s.name AS space_name,
    COUNT(b.id) AS bookings,
    ROUND(SUM(b.total_amount), 2) AS revenue
FROM spaces s
JOIN bookings b ON b.space_id = s.id
WHERE b.booking_status IN ('confirmed', 'completed')
GROUP BY s.id, s.name
ORDER BY revenue DESC
LIMIT 5;


-- =====================================================================
-- [10] التقييمات
-- =====================================================================
SELECT
    COUNT(*) AS total_reviews,
    ROUND(AVG(rating), 2) AS avg_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS five_stars,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS four_stars,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS three_stars
FROM reviews;