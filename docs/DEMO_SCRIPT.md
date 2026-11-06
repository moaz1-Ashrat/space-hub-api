# Demo Script - 7 minutes

## Setup (30 min before)
Terminal 1: cd C:\GP2\space-hub-api; php artisan serve
Terminal 2: cd C:\GP2\space-hub-frontend; npm run dev
XAMPP: Apache + MySQL running

## Step 1: Login (30s)
- localhost:5173/login
- Email: admin@spacehub.test / Password: root

## Step 2: Dashboard (1min)
Point to: 121 users, 120 spaces, 250 bookings, 799,628 EGP revenue
Say: "Real data from MySQL, not hardcoded"

## Step 3: Analytics (2min)
/admin/analytics
Point to: revenue trend, booking status, growth, demographics, coupons
Say: "Served from MySQL views"

## Step 4: Approve Space (1min)
/admin/spaces -> filter pending -> approve one
Say: "Owner submits, admin reviews"

## Step 5: Database (1min)
phpMyAdmin -> run:
SELECT 'users', COUNT(*) FROM users UNION SELECT 'spaces', COUNT(*) FROM spaces;
Say: "Numbers match dashboard"

## Step 6: Connection (1.5min) [MOST IMPORTANT]
F12 -> Network tab -> click Users
Point to: URL, Authorization header, JSON response
Say: "React calls Laravel via Axios with Bearer token"

## Step 7: Mailtrap (30s)
mailtrap.io -> Sandbox -> show received email

## Key Answers
Q: How connected? -> REST API + Axios + Sanctum tokens
Q: Race conditions? -> DB transaction + lockForUpdate
Q: Auth? -> Sanctum Bearer tokens
Q: ERD vs DB? -> ERD is initial design, DB has optimizations