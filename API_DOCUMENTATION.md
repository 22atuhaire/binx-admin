# Binx Admin API Documentation

## Overview
The Binx waste collection platform provides a comprehensive REST API for mobile and web clients to interact with the system. All API endpoints are prefixed with `/api` and use JSON for request/response bodies.

## Base URL
```
http://localhost:8000/api
```

## Authentication
The API uses **Laravel Sanctum** for token-based authentication. After login/registration, clients receive a bearer token that must be included in all subsequent requests:

```
Authorization: Bearer {token}
```

## Response Format
All API responses follow this structure:

**Success Response:**
```json
{
  "data": { ... },
  "message": "Success message",
  "meta": { ... }
}
```

**Error Response:**
```json
{
  "message": "Error message",
  "errors": { ... }
}
```

---

## Authentication Endpoints

### 1. Register User
**POST** `/auth/register`

Create a new user account (Donor or Collector).

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "role": "donor",
  "phone": "+256700000000",
  "location": "Kampala, Uganda"
}
```

**Response:** `201 Created`
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "donor",
    "status": "pending"
  },
  "token": "1|abc...xyz"
}
```

---

### 2. Login
**POST** `/auth/login`

Authenticate user and receive API token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123!"
}
```

**Response:** `200 OK`
```json
{
  "message": "Login successful",
  "user": { ... },
  "token": "1|abc...xyz"
}
```

---

### 3. Logout
**POST** `/auth/logout`

Revoke current authentication token.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`
```json
{
  "message": "Logout successful"
}
```

---

### 4. Refresh Token
**POST** `/auth/refresh`

Get a new authentication token (revokes old one).

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`
```json
{
  "message": "Token refreshed successfully",
  "token": "2|new...token"
}
```

---

### 5. Get Current User
**GET** `/auth/me`

Get authenticated user's profile information.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+256700000000",
    "location": "Kampala, Uganda",
    "role": "donor",
    "status": "active",
    "suspended": false
  }
}
```

---

## Waste Posts Endpoints

### 1. Get Available Waste Posts
**GET** `/waste-posts`

List all available waste posts (status = "open") with optional filtering.

**Query Parameters:**
- `per_page` - Results per page (default: 20)
- `page` - Page number (default: 1)
- `category` - Filter by waste type (e.g., "plastic", "paper")
- `location` - Filter by location (partial match)
- `search` - Search by title or description

**Example:**
```
GET /waste-posts?category=plastic&location=Kampala&per_page=10
```

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "title": "Recyclable Plastics",
      "description": "Clean plastic bottles",
      "category": "plastic",
      "quantity": "50 kg",
      "location": "Kampala, Uganda",
      "status": "open",
      "image_path": "http://.../image.jpg",
      "donor": { ... },
      "assigned_collector": null,
      "created_at": "2026-03-06T10:00:00Z"
    }
  ],
  "meta": {
    "total": 45,
    "per_page": 20,
    "current_page": 1,
    "last_page": 3
  }
}
```

---

### 2. Get Single Waste Post
**GET** `/waste-posts/{id}`

Get detailed information about a specific waste post.

**Example:**
```
GET /waste-posts/1
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "title": "Recyclable Plastics",
    "description": "Clean plastic bottles",
    "category": "plastic",
    "quantity": "50 kg",
    "location": "Kampala, Uganda",
    "status": "open",
    "image_path": "http://.../image.jpg",
    "donor": {
      "id": 2,
      "name": "Jane Donor",
      "email": "jane@example.com",
      "phone": "+256701111111",
      "location": "Kampala, Uganda"
    },
    "assigned_collector": null,
    "created_at": "2026-03-06T10:00:00Z"
  }
}
```

---

### 3. Create Waste Post
**POST** `/waste-posts`

Create new waste post (Donors only).

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "title": "Recyclable Plastics",
  "description": "Clean plastic bottles ready for collection",
  "category": "plastic",
  "quantity": "50 kg",
  "location": "Kampala, Uganda"
}
```

**Response:** `201 Created`

---

### 4. Update Waste Post
**PUT** `/waste-posts/{id}`

Update waste post (Only open posts, Donor only).

**Headers:** `Authorization: Bearer {token}`

**Request Body:** Same as Create (all fields optional)

---

### 5. Delete Waste Post
**DELETE** `/waste-posts/{id}`

Delete waste post.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`

---

### 6. Claim Waste Post
**POST** `/waste-posts/{id}/claim`

Claim/assign waste post to collector (Collectors only).

**Headers:** `Authorization: Bearer {token}`

**Response:** `201 Created`
```json
{
  "message": "Waste post claimed successfully",
  "data": {
    "job_id": 5,
    "waste_post": { ... }
  }
}
```

---

### 7. Get Waste Categories
**GET** `/categories`

Get all available waste categories for filtering.

**Response:** `200 OK`
```json
{
  "categories": ["plastic", "paper", "metal", "glass", "organic"]
}
```

---

### 8. Get Locations
**GET** `/locations`

Get common/available locations for autocomplete.

**Response:** `200 OK`
```json
{
  "locations": ["Kampala, Uganda", "Entebbe, Uganda", ...]
}
```

---

## Collection Jobs Endpoints

### 1. Get My Jobs
**GET** `/jobs`

List all jobs assigned to authenticated collector.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` - Filter by status (pending, in_progress, completed, all)
- `per_page` - Results per page (default: 20)

**Response:** `200 OK`

---

### 2. Get Job Details
**GET** `/jobs/{id}`

Get detailed information about specific job.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`

---

### 3. Update Job Status
**PUT** `/jobs/{id}/status`

Update job status (State machine validation enforced).

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "status": "in_progress"
}
```

**Valid Transitions:**
- `pending` → `in_progress`, `cancelled`
- `in_progress` → `completed`, `cancelled`
- `completed` → (no transitions)
- `cancelled` → (no transitions)

---

### 4. Complete Job
**POST** `/jobs/{id}/complete`

Mark job as completed.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`

---

## Profile Endpoints

### 1. Get Profile
**GET** `/profile`

Get authenticated user's profile.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`

---

### 2. Update Profile
**PUT** `/profile`

Update user profile information.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "name": "John Updated",
  "phone": "+256700000000",
  "location": "Entebbe, Uganda",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

All fields are optional. Password changes require confirmation.

---

### 3. Get Earnings (Collectors only)
**GET** `/profile/earnings`

Get collector earnings and statistics.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`
```json
{
  "data": [ ... earnings records ... ],
  "stats": {
    "total_earnings": 150000,
    "completed_jobs": 15,
    "average_per_job": 10000
  },
  "meta": { ... pagination ... }
}
```

---

### 4. Get History
**GET** `/profile/history`

Get user history (jobs for collectors, posts for donors).

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`

---

## Error Responses

### Validation Errors
**Status:** `422 Unprocessable Entity`

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Unauthorized
**Status:** `401 Unauthorized`

```json
{
  "message": "Unauthenticated."
}
```

### Forbidden
**Status:** `403 Forbidden`

```json
{
  "message": "Only collectors can claim waste posts"
}
```

### Not Found
**Status:** `404 Not Found`

```json
{
  "message": "No query results found"
}
```

---

## Testing
Run API tests with:
```bash
php artisan test tests/Feature/Api/ --compact
```

All 15 authentication tests are included and passing.

---

## Rate Limiting
(To be implemented)

---

## Versioning
(To be implemented - API v1 planned)
