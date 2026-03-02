# User Roles and Statuses

## Overview

The Binx Admin system implements a **role-based access control** system with three distinct user roles and three status levels. This critical foundation ensures proper permissions and workflow management throughout the application.

---

## User Roles

### 1. **Admin** 
- **Value**: `admin`
- **Constant**: `User::ROLE_ADMIN`
- **Purpose**: System administrators with full access
- **Default Status**: Always `active`
- **Permissions**: Full system access, manage all users, approve collectors

### 2. **Donor**
- **Value**: `donor`
- **Constant**: `User::ROLE_DONOR`
- **Purpose**: Users who post waste/food items for collection
- **Default Status**: `active` (immediately upon registration)
- **Permissions**: Create waste posts, view collectors, rate collectors

### 3. **Collector**
- **Value**: `collector`
- **Constant**: `User::ROLE_COLLECTOR`
- **Purpose**: Users who collect waste/food items from donors
- **Default Status**: `pending` (requires admin approval)
- **Permissions**: View waste posts, accept collection jobs, earn money

---

## User Status Levels

### 1. **Pending** 
- **Value**: `pending`
- **Constant**: `User::STATUS_PENDING`
- **Description**: User account awaiting approval
- **Applied To**: New collectors before admin approval
- **Can Access**: Limited (profile setup only)

### 2. **Active**
- **Value**: `active`
- **Constant**: `User::STATUS_ACTIVE`
- **Description**: User account is fully operational
- **Applied To**: Admins (always), donors (on registration), collectors (after approval)
- **Can Access**: Full role-specific permissions

### 3. **Blocked**
- **Value**: `blocked`
- **Constant**: `User::STATUS_BLOCKED`
- **Description**: User account is suspended/banned
- **Applied To**: Any user who violates terms or by admin action
- **Can Access**: None (cannot log in)

---

## Role Rules

### ✅ Critical Rules

1. **Admin is always active**
   - When an admin user is created, status is automatically set to `active`
   - Admins cannot be set to `pending`

2. **Collectors start as pending**
   - New collector registrations are set to `pending` status
   - Requires admin approval to become `active`
   - Must pass verification before collecting waste

3. **Donors become active immediately**
   - Donor accounts are immediately set to `active` upon registration
   - Can start posting waste items right away

---

## Database Schema

### Users Table - Role & Status Fields

```sql
role ENUM('admin', 'donor', 'collector') NOT NULL DEFAULT 'donor'
status ENUM('pending', 'active', 'blocked') NOT NULL DEFAULT 'pending'
```

**Indexes**: `role`, `status` (for efficient queries)

---

## User Model Methods

### Role Check Methods

```php
// Check if user is admin
$user->isAdmin(): bool

// Check if user is donor
$user->isDonor(): bool

// Check if user is collector
$user->isCollector(): bool
```

### Status Check Methods

```php
// Check if user is pending
$user->isPending(): bool

// Check if user is active
$user->isActive(): bool

// Check if user is blocked
$user->isBlocked(): bool
```

### Status Management Methods

```php
// Activate a user
$user->activate(): void

// Block a user
$user->block(): void

// Set user as pending
$user->setPending(): void
```

---

## Usage Examples

### Creating Users with Roles

```php
// Create admin (always active)
$admin = User::factory()->admin()->create([
    'name' => 'John Admin',
    'email' => 'admin@example.com',
]);
// Result: role=admin, status=active

// Create donor (immediately active)
$donor = User::factory()->donor()->create([
    'name' => 'Jane Donor',
    'email' => 'donor@example.com',
]);
// Result: role=donor, status=active

// Create collector (starts pending)
$collector = User::factory()->collector()->create([
    'name' => 'Bob Collector',
    'email' => 'collector@example.com',
]);
// Result: role=collector, status=pending
```

### Checking User Role and Status

```php
if ($user->isAdmin()) {
    // User is admin - grant full access
}

if ($user->isCollector() && $user->isActive()) {
    // Approved collector - can accept jobs
}

if ($user->isDonor() && $user->isBlocked()) {
    // Blocked donor - deny access
}
```

### Managing User Status

```php
// Approve a pending collector
$collector = User::find($collectorId);
if ($collector->isCollector() && $collector->isPending()) {
    $collector->activate();
    // Now collector can start accepting jobs
}

// Block a problematic user
$user = User::find($userId);
$user->block();
// User can no longer access the system

// Restore a blocked user
$user->activate();
```

---

## Factory States

The UserFactory provides convenient methods for creating users with specific roles and statuses:

```php
// Admin (always active)
User::factory()->admin()->create()

// Donor (always active)
User::factory()->donor()->create()

// Collector (always pending)
User::factory()->collector()->create()

// Active collector (for testing)
User::factory()->collector()->active()->create()

// Blocked user (any role)
User::factory()->blocked()->create()

// Custom combinations
User::factory()->donor()->blocked()->create()
```

---

## Workflow Examples

### Collector Registration & Approval

```php
// Step 1: Collector registers
$collector = User::create([
    'name' => 'New Collector',
    'email' => 'collector@example.com',
    'password' => bcrypt('password'),
    'role' => User::ROLE_COLLECTOR,
    // Status automatically set to 'pending'
]);

// Step 2: Admin reviews collector profile
// ... verification process ...

// Step 3: Admin approves collector
$collector->activate();

// Now collector can accept jobs
if ($collector->isActive()) {
    // Allow job acceptance
}
```

### Donor Instant Access

```php
// Donor registers and immediately active
$donor = User::create([
    'name' => 'New Donor',
    'email' => 'donor@example.com',
    'password' => bcrypt('password'),
    'role' => User::ROLE_DONOR,
    // Status automatically set to 'active'
]);

// Donor can immediately create waste posts
if ($donor->isActive()) {
    $donor->wastePosts()->create([
        'title' => 'Extra vegetables',
        // ...
    ]);
}
```

### Blocking Problematic Users

```php
// Admin blocks a user for violations
$user = User::find($userId);
$user->block();

// User attempts to access system
if ($user->isBlocked()) {
    // Deny access, show "Account suspended" message
    abort(403, 'Your account has been suspended.');
}
```

---

## Query Examples

### Get All Pending Collectors (For Admin Approval)

```php
$pendingCollectors = User::where('role', User::ROLE_COLLECTOR)
    ->where('status', User::STATUS_PENDING)
    ->get();
```

### Get Active Donors

```php
$activeDonors = User::where('role', User::ROLE_DONOR)
    ->where('status', User::STATUS_ACTIVE)
    ->get();
```

### Get All Admins

```php
$admins = User::where('role', User::ROLE_ADMIN)->get();
// All admins are always active
```

### Get Blocked Users

```php
$blockedUsers = User::where('status', User::STATUS_BLOCKED)->get();
```

---

## Statistics

After seeding, the database contains:

| Role | Status | Count |
|------|--------|-------|
| Admin | Active | 3 |
| Donor | Active | 20 |
| Collector | Pending | 18 |
| Collector | Active | 9 |
| **Total Users** | | **50** |

---

## Testing

The system includes 18 comprehensive tests covering:

✅ Admin role creation and status rules  
✅ Donor role creation and status rules  
✅ Collector role creation and status rules  
✅ Role helper methods  
✅ Status helper methods  
✅ User activation workflow  
✅ User blocking workflow  
✅ Status transitions  
✅ Database integrity  

**Run tests:**
```bash
php artisan test tests/Feature/UserRolesAndStatusesTest.php --compact
```

**Expected Output:**
```
✓ 18 tests passed (58 assertions)
```

---

## Permissions Matrix

| Action | Admin | Donor (Active) | Donor (Blocked) | Collector (Active) | Collector (Pending) |
|--------|-------|----------------|-----------------|--------------------|--------------------|
| Login | ✅ | ✅ | ❌ | ✅ | ⚠️ Limited |
| Create waste post | ❌ | ✅ | ❌ | ❌ | ❌ |
| View waste posts | ✅ | ✅ | ❌ | ✅ | ⚠️ View only |
| Accept collection job | ❌ | ❌ | ❌ | ✅ | ❌ |
| Earn money | ❌ | ❌ | ❌ | ✅ | ❌ |
| Rate collectors | ❌ | ✅ | ❌ | ❌ | ❌ |
| Approve collectors | ✅ | ❌ | ❌ | ❌ | ❌ |
| Block users | ✅ | ❌ | ❌ | ❌ | ❌ |
| View all users | ✅ | ❌ | ❌ | ❌ | ❌ |
| Manage system | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## Best Practices

### 1. Always Check Both Role and Status

```php
// ❌ BAD - Only checking role
if ($user->isCollector()) {
    // Allow job acceptance
}

// ✅ GOOD - Check both role and status
if ($user->isCollector() && $user->isActive()) {
    // Allow job acceptance only for active collectors
}
```

### 2. Use Constants Instead of Strings

```php
// ❌ BAD - Hardcoded strings
$user->update(['role' => 'admin']);

// ✅ GOOD - Use constants
$user->update(['role' => User::ROLE_ADMIN]);
```

### 3. Use Helper Methods

```php
// ❌ BAD - Direct property access
if ($user->status === 'active') { }

// ✅ GOOD - Use helper methods
if ($user->isActive()) { }
```

### 4. Handle Status Transitions Properly

```php
// Approve collector with notification
if ($collector->isPending()) {
    $collector->activate();
    
    // Send approval notification
    $collector->notify(new CollectorApprovedNotification());
}
```

---

## API Integration

When exposing user data to APIs, **always** include role and status:

```json
{
  "id": 1,
  "name": "John Collector",
  "email": "collector@example.com",
  "role": "collector",
  "status": "pending",
  "phone": "555-1234",
  "created_at": "2026-03-02T10:00:00Z"
}
```

Mobile apps should check `status` before allowing actions:

```javascript
// Example mobile app logic
if (user.role === 'collector' && user.status === 'active') {
    // Show "Accept Job" button
} else if (user.role === 'collector' && user.status === 'pending') {
    // Show "Awaiting Approval" message
}
```

---

## Future Enhancements

Considerations for future development:

1. **Email Verification**: Require email verification before activation
2. **Role Permissions**: Fine-grained permission system (e.g., Laravel Sanctum abilities)
3. **Audit Log**: Track role/status changes with timestamps
4. **Auto-Approval**: Automatic collector approval after document verification
5. **Suspended Status**: Temporary suspension (different from blocked)
6. **Role Change**: Allow role transitions with proper validation

---

## Summary

✅ **3 Roles**: Admin, Donor, Collector  
✅ **3 Statuses**: Pending, Active, Blocked  
✅ **Critical Rules Enforced**:
   - Admins always active
   - Collectors start pending
   - Donors active immediately

✅ **18 Tests Passing**  
✅ **Helper Methods Available**  
✅ **Database Constraints Applied**  
✅ **Ready for Permission-Based Features**

This role and status system forms the foundation for all permission-based features in the Binx Admin application.
