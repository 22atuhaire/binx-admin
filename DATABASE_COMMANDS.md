# Database Management Commands

Quick reference for common database operations in the Binx Admin system.

## Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Reset database and run all migrations
php artisan migrate:fresh

# Rollback last batch
php artisan migrate:rollback

# Reset and seed database (fresh data)
php artisan migrate:fresh --seed
```

## Seeding Database

```bash
# Seed database with test data
php artisan db:seed

# Seed with specific seeder
php artisan db:seed --class=DataSeeder
```

## Database Shell Access

```bash
# Access MySQL interactive shell (if installed)
mysql -h 127.0.0.1 -u root binx_admin

# Query example
SELECT * FROM users WHERE role = 'collector';
```

## Tinker (PHP Artisan Shell)

```bash
# Start interactive PHP shell
php artisan tinker

# Inside tinker:
> User::count()
> WastePost::with('user')->get()
> CollectionJob::where('status', 'completed')->count()
> Earning::sum('amount')
```

## Testing

```bash
# Run all database integration tests
php artisan test tests/Feature/DatabaseIntegrationTest.php --compact

# Run with detailed output
php artisan test tests/Feature/DatabaseIntegrationTest.php

# Run all tests
php artisan test --compact
```

## Viewing Database Schema

```bash
# Inside tinker:
> Schema::getTables()
> Schema::getColumns('users')
> Schema::getIndexes('collection_jobs')
```

## Common Queries

### User Operations
```php
# Create user
User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
    'role' => 'user',
]);

# Get all collectors
User::where('role', 'collector')->get()

# Get user with relationships
User::with(['wastePosts', 'jobs', 'earnings'])->find($id)
```

### Waste Post Operations
```php
# Get all open posts
WastePost::where('status', 'open')->get()

# Get posts by category
WastePost::where('category', 'organic')->get()

# Get posts with jobs
WastePost::with('jobs')->get()
```

### Collection Job Operations
```php
# Get pending jobs
CollectionJob::where('status', 'pending')->get()

# Get completed jobs with earning
CollectionJob::where('status', 'completed')
    ->with('earning')
    ->get()

# Get collector's jobs
User::find($collectorId)->jobs()->get()
```

### Earnings Operations
```php
# Get total earnings
Earning::sum('amount')

# Get collector's earnings
Earning::where('collector_id', $id)->sum('amount')

# Get average earning
Earning::avg('amount')
```

## Backup & Restore

```bash
# Create database backup
mysqldump -h 127.0.0.1 -u root binx_admin > backup.sql

# Restore from backup
mysql -h 127.0.0.1 -u root binx_admin < backup.sql
```

## Database Information

```php
# Inside tinker:

# Get connection info
> config('database.connections.mysql')

# Get current database
> DB::connection()->getDatabaseName()

# Get table count
> DB::select('SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = ?', [DB::connection()->getDatabaseName()])
```

## Status Enum Values

### User Status
- `active`: User account is active
- `inactive`: User account is inactive
- `suspended`: User account is suspended

### Waste Post Status
- `open`: Post is available for collection
- `taken`: Collection job assigned to post
- `completed`: Post collection completed

### Collection Job Status
- `pending`: Job assigned, awaiting action
- `in_progress`: Collector working on job
- `completed`: Job completed successfully
- `cancelled`: Job cancelled

### User Role
- `user`: Regular user (waste/food donor)
- `collector`: Collector (picks up waste/food)

## Performance Tips

1. **Always use eager loading** to prevent N+1 queries:
   ```php
   User::with(['wastePosts', 'jobs'])->get()
   ```

2. **Use pagination for large datasets**:
   ```php
   WastePost::paginate(15)
   ```

3. **Use aggregation functions** for calculations:
   ```php
   Earning::sum('amount')  // Instead of fetching all and summing in PHP
   ```

4. **Use indexes** for frequently searched columns (already done in migrations)

5. **Remember to use transactions** for multi-step operations:
   ```php
   DB::transaction(function () {
       // Multiple database operations
   });
   ```
