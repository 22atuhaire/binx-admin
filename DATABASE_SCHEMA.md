# Binx Admin - Database Schema

## Overview

The database is a **MySQL** database named `binx_admin` running on `127.0.0.1:3306`. It stores all information about users, waste posts, collection jobs, and earnings.

---

## Database Connection

- **Type**: MySQL
- **Host**: 127.0.0.1
- **Port**: 3306
- **Database**: binx_admin
- **Configuration**: `.env` file

### Environment Setup
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=binx_admin
DB_USERNAME=root
DB_PASSWORD=
```

---

## Tables & Schema

### 1. **users** Table
Stores all system users (donors and collectors).

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| `id` | BIGINT | Primary Key, Auto-increment | User ID |
| `name` | VARCHAR(255) | NOT NULL | User full name |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | User email address |
| `role` | ENUM('user', 'collector') | NOT NULL, Default: 'user' | User role |
| `phone` | VARCHAR(255) | NULLABLE | Contact phone number |
| `address` | TEXT | NULLABLE | User address |
| `status` | ENUM('active', 'inactive', 'suspended') | Default: 'active' | Account status |
| `rating` | DECIMAL(3,2) | Default: 0 | User rating (0-5) |
| `email_verified_at` | TIMESTAMP | NULLABLE | Email verification timestamp |
| `password` | VARCHAR(255) | NOT NULL | Hashed password |
| `remember_token` | VARCHAR(100) | NULLABLE | Remember me token |
| `created_at` | TIMESTAMP | NOT NULL | Creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes**: `email` (unique), `role`, `status`

---

### 2. **waste_posts** Table
Stores waste/food posts created by users (donors).

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| `id` | BIGINT | Primary Key, Auto-increment | Post ID |
| `user_id` | BIGINT | Foreign Key → users | Creator user ID |
| `title` | VARCHAR(255) | NOT NULL | Post title |
| `description` | TEXT | NOT NULL | Detailed description |
| `category` | VARCHAR(255) | NOT NULL | Waste category (plastic, organic, etc.) |
| `location` | VARCHAR(255) | NOT NULL | Pickup location |
| `quantity` | VARCHAR(255) | NULLABLE | Quantity (e.g., "10kg") |
| `image_path` | VARCHAR(255) | NULLABLE | Image file path |
| `status` | ENUM('open', 'taken', 'completed') | Default: 'open' | Post status |
| `created_at` | TIMESTAMP | NOT NULL | Creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes**: `user_id`, `status`, `category`
**Cascade Delete**: When user is deleted, all their waste posts are deleted

**Categories**: plastic, paper, metal, glass, organic, electronics, textile

---

### 3. **collection_jobs** Table
Tracks collection jobs assigned to collectors for specific waste posts.

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| `id` | BIGINT | Primary Key, Auto-increment | Job ID |
| `waste_post_id` | BIGINT | Foreign Key → waste_posts | Associated waste post |
| `collector_id` | BIGINT | Foreign Key → users | Assigned collector |
| `status` | ENUM('pending', 'in_progress', 'completed', 'cancelled') | Default: 'pending' | Job status |
| `assigned_at` | TIMESTAMP | NULLABLE | Assignment timestamp |
| `completed_at` | TIMESTAMP | NULLABLE | Completion timestamp |
| `created_at` | TIMESTAMP | NOT NULL | Creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes**: `waste_post_id`, `collector_id`, `status`
**Cascade Delete**: When post or collector is deleted, the job is deleted

---

### 4. **earnings** Table
Records payment/earnings for collectors completing jobs.

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| `id` | BIGINT | Primary Key, Auto-increment | Earning ID |
| `collector_id` | BIGINT | Foreign Key → users | Collector who earned |
| `job_id` | BIGINT | Foreign Key → collection_jobs | Associated job (nullable) |
| `amount` | DECIMAL(10,2) | NOT NULL | Payment amount |
| `description` | VARCHAR(255) | NULLABLE | Payment description |
| `earned_at` | TIMESTAMP | Default: CURRENT_TIMESTAMP | Earning timestamp |
| `created_at` | TIMESTAMP | NOT NULL | Creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes**: `collector_id`, `job_id`
**Cascade Delete**: When job or collector is deleted, earnings are deleted

---

## Data Models & Relationships

### User Model (`App\Models\User`)
```php
// Relationships
- hasMany('wastePosts')      // Posts created by user
- hasMany('jobs', 'collector_id')    // Jobs assigned to collector
- hasMany('earnings', 'collector_id') // Earnings for collector
```

### WastePost Model (`App\Models\WastePost`)
```php
// Relationships
- belongsTo('user')          // Creator of the post
- hasMany('jobs')            // Collection jobs for this post
```

### CollectionJob Model (`App\Models\CollectionJob`)
```php
// Relationships
- belongsTo('wastePost')     // The waste post being collected
- belongsTo('collector', 'collector_id') // Assigned collector
- hasOne('earning')          // Associated earning record
```

### Earning Model (`App\Models\Earning`)
```php
// Relationships
- belongsTo('collector', 'collector_id') // The collector who earned
- belongsTo('job', 'job_id') // The job that generated this earning
```

---

## Database Statistics

| Entity | Count | 
|--------|-------|
| Total Users | 32 |
| Regular Users (Donors) | 15 |
| Collectors | 17 |
| Waste Posts | 28 |
| Collection Jobs | 25 |
| Earnings Records | 15 |
| **Total Earnings** | **$619.39** |

---

## CRUD Operations

### ✅ CREATE (Write)
```php
// Create a user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
    'role' => 'user',
    'phone' => '555-1234',
    'address' => '123 Main St',
    'status' => 'active',
]);

// Create a waste post
$post = $user->wastePosts()->create([
    'title' => 'Extra vegetables',
    'description' => 'Fresh vegetables...',
    'category' => 'organic',
    'location' => '456 Food St',
    'quantity' => '20kg',
    'status' => 'open',
]);

// Create a collection job
$job = CollectionJob::create([
    'waste_post_id' => $post->id,
    'collector_id' => $collector->id,
    'status' => 'pending',
    'assigned_at' => now(),
]);

// Create an earning record
$earning = Earning::create([
    'collector_id' => $collector->id,
    'job_id' => $job->id,
    'amount' => 35.50,
    'description' => 'Payment for waste collection',
    'earned_at' => now(),
]);
```

### ✅ READ (Read)
```php
// Get user with all relationships
$user = User::with(['wastePosts', 'jobs', 'earnings'])->find($id);

// Get collection job with eager loading
$job = CollectionJob::with('wastePost', 'collector', 'earning')->find($id);

// Query statistics
$totalEarnings = Earning::sum('amount');
$avgEarning = Earning::avg('amount');
$completedJobs = CollectionJob::where('status', 'completed')->count();
```

### ✅ UPDATE (Write)
```php
// Update user profile
$user->update([
    'phone' => '555-9999',
    'rating' => 4.5,
]);

// Update waste post status
$post->update(['status' => 'taken']);

// Update job status
$job->update([
    'status' => 'completed',
    'completed_at' => now(),
]);
```

### ✅ DELETE (Write)
```php
// Delete a waste post
$post->delete();

// Delete a collector (cascades to jobs and earnings)
$user->delete();
```

---

## Database Constraints

### Foreign Key Constraints
- `waste_posts.user_id` → `users.id` (CASCADE DELETE)
- `collection_jobs.waste_post_id` → `waste_posts.id` (CASCADE DELETE)
- `collection_jobs.collector_id` → `users.id` (CASCADE DELETE)
- `earnings.collector_id` → `users.id` (CASCADE DELETE)
- `earnings.job_id` → `collection_jobs.id` (CASCADE DELETE)

### Unique Constraints
- `users.email` (UNIQUE)

### Check Constraints (via ENUM)
- `users.role`: 'user' or 'collector'
- `users.status`: 'active', 'inactive', or 'suspended'
- `waste_posts.status`: 'open', 'taken', or 'completed'
- `collection_jobs.status`: 'pending', 'in_progress', 'completed', or 'cancelled'

---

## Migrations

Location: `database/migrations/`

| Migration | Purpose |
|-----------|---------|
| `0001_01_01_000000_create_users_table.php` | Create users table |
| `0001_01_01_000001_create_cache_table.php` | Create cache table |
| `0001_01_01_000002_create_jobs_table.php` | Create Laravel jobs table |
| `2026_03_02_165623_create_waste_posts_table.php` | Create waste posts table |
| `2026_03_02_165704_add_fields_to_users_table.php` | Add role, phone, address fields to users |
| `2026_03_02_170025_create_collection_jobs_table.php` | Create collection jobs table |
| `2026_03_02_170026_create_earnings_table.php` | Create earnings table |

---

## Seeding

Location: `database/seeders/`

### DataSeeder.php
Creates realistic test data:
- 13 regular users (donors)
- 15 collectors
- 26 waste posts
- 23 collection jobs
- 13 earning records

**Run migrations and seeding:**
```bash
php artisan migrate:fresh --seed
```

---

## Testing

Location: `tests/Feature/DatabaseIntegrationTest.php`

**14 automated tests** verify:
- ✅ User creation (donors and collectors)
- ✅ Waste post creation and updates
- ✅ Collection job assignment
- ✅ Earning records
- ✅ Relationship loading
- ✅ Cascade delete functionality
- ✅ Aggregation queries

**Run tests:**
```bash
php artisan test tests/Feature/DatabaseIntegrationTest.php --compact
```

---

## Best Practices Implemented

1. **Migrations**: All schema changes tracked in version control
2. **Relationships**: Proper Eloquent relationships for efficient querying
3. **Eager Loading**: Prevents N+1 query problems
4. **Cascade Delete**: Data integrity maintained through foreign keys
5. **Type Casting**: Proper data type validation
6. **Timestamps**: All records have created_at and updated_at
7. **Factories**: Consistent test data generation
8. **Seeders**: Reliable data population
9. **Tests**: Automated verification of all CRUD operations
10. **Environment Config**: Sensitive data in .env file

---

## Performance Considerations

- **Indexes**: Applied to foreign keys and frequently queried columns
- **Eager Loading**: Use `with()` to prevent N+1 queries
- **Pagination**: Implement for large result sets
- **Aggregations**: Use database aggregation functions (SUM, AVG, COUNT)
- **Connection Pooling**: MySQL connection reuse enabled

---

## Status

✅ **DATABASE FULLY OPERATIONAL**

- All tables created and verified
- All relationships working correctly
- All CRUD operations tested and passing
- 32 users with test data present
- Ready for API development and mobile app integration

---

## Next Steps

1. Build API controllers and routes
2. Create API Resources for JSON responses
3. Implement authentication system
4. Create job assignment logic
5. Build earnings calculation service
6. Connect to mobile app for data synchronization
