# Frontend-Backend Connection

## Architecture
React (5173) <--REST API/JSON--> Laravel (8000) <--> MySQL

## Frontend: Axios Client
File: src/lib/axios.ts

    const apiClient = axios.create({
      baseURL: 'http://127.0.0.1:8000/api/v1',
    });
    
    apiClient.interceptors.request.use((config) => {
      const token = localStorage.getItem('auth_token');
      if (token) config.headers.Authorization = 'Bearer ' + token;
      return config;
    });

## Backend: Routes
File: routes/api.php

    Route::prefix('v1')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::apiResource('bookings', BookingController::class);
        });
    });

## Authentication Flow
1. POST /api/v1/auth/login {email, password}
2. Response: {data: {user, token}}
3. Frontend stores token in localStorage
4. Every request: Authorization: Bearer <token>
5. Laravel Sanctum validates against personal_access_tokens table

## Response Format
{
  "data": [...],
  "links": {first, last, prev, next},
  "meta": {current_page, last_page, per_page, total}
}

## CORS (config/cors.php)
'allowed_origins' => ['http://localhost:5173'],
'supports_credentials' => true,

## Demo (Chrome DevTools)
1. F12 -> Network tab
2. Login
3. See: POST /api/v1/auth/login -> 200
4. See: Request Headers include Authorization: Bearer