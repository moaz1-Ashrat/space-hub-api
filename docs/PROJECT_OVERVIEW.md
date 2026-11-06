# Space Hub - Project Overview

## What is it?
Marketplace for renting spaces: Coworking, Event Halls, Studios, Entertainment, Offices.

## User Roles
- Customer: Browse, book, review, use coupons
- Space Owner: List spaces, manage availability, view earnings  
- Admin: Approve spaces, manage users, view analytics

## Tech Stack
Backend: Laravel 11, PHP 8.2, MySQL 8, Sanctum
Frontend: React 18, TypeScript, Vite, TanStack Query, TailwindCSS

## Core Features
1. Authentication (Sanctum tokens)
2. Space management + images
3. Availability slots
4. Booking with concurrency safety (lockForUpdate)
5. Payments (manual + Stripe ready)
6. Reviews (1 per customer per space)
7. Coupons (generic + user-specific)
8. Analytics (MySQL views + charts)
9. Notifications (in-app + Mailtrap)