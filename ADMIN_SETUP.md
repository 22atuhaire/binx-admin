# Admin Account Setup Guide

## Overview

This guide explains how to create and use admin accounts in the Binx Admin system. Admin accounts have full control over the system, including approving collectors and monitoring all activity.

---

## Creating the First Admin Account

### Using the AdminSeeder

The easiest way to create the first admin account is using the AdminSeeder:

```bash
php artisan db:seed --class=AdminSeeder
```

**Default Credentials:**
- Email: `admin@binx.com`
- Password: `admin123`

⚠️ **IMPORTANT:** Change this password immediately after first login!

### Output Example

```
✓ Admin user created successfully!
  Email: admin@binx.com
  Password: admin123 (CHANGE THIS IMMEDIATELY!)
```

### What the Seeder Does

The AdminSeeder:
- Checks if an admin already exists (prevents duplicates)
- Creates an admin user with role `admin` and status `active`
- Sets email as verified
- Displays credentials in the console

---

## Creating Additional Admin Users

### Method 1: Using Tinker

```bash
php artisan tinker
```

```php
User::create([
    'name' => 'New Admin',
    'email' => 'newadmin@binx.com',
    'password' => Hash::make('secure-password'),
    'role' => User::ROLE_ADMIN,
    'status' => User::STATUS_ACTIVE,
    'email_verified_at' => now(),
]);
```

### Method 2: Using a Factory in Seeder

```php
User::factory()->admin()->create([
    'name' => 'Admin Name',
    'email' => 'admin@example.com',
]);
```

---

## Admin Access Security

### Middleware Protection

All admin routes are protected by **three layers of security**:

1. **`auth` middleware** - Must be logged in
2. **`verified` middleware** - Email must be verified
3. **`admin` middleware** - Must have admin role

### Admin Middleware Implementation

Location: [app/Http/Middleware/IsAdmin.php](app/Http/Middleware/IsAdmin.php)

```php
public function handle(Request $request, Closure $next): Response
{
    if (!$request->user() || !$request->user()->isAdmin()) {
        abort(403, 'Access denied. Admin privileges required.');
    }

    return $next($request);
}
```

### How It Works

- ✅ **Admin users**: Full access to all admin routes
- ❌ **Donors**: Blocked with 403 error
- ❌ **Collectors**: Blocked with 403 error
- ❌ **Guests**: Redirected to login

---

## Admin Routes

All admin routes are prefixed with `/admin` and have the name prefix `admin.`:

| Route | URL | Purpose |
|-------|-----|---------|
| Admin Dashboard | `/admin/dashboard` | Overview and statistics |
| Pending Collectors | `/admin/collectors/pending` | Approve/reject collectors |
| All Users | `/admin/users` | Manage all users |
| Approve Collector | `POST /admin/collectors/{user}/approve` | Approve pending collector |
| Block User | `POST /admin/users/{user}/block` | Block any user |
| Activate User | `POST /admin/users/{user}/activate` | Activate blocked user |

### Route Examples

```php
// Admin dashboard
route('admin.dashboard') // /admin/dashboard

// Pending collectors
route('admin.collectors.pending') // /admin/collectors/pending

// Approve collector
route('admin.collectors.approve', $user) // /admin/collectors/{id}/approve

// All users
route('admin.users.index') // /admin/users
```

---

## Admin Dashboard Features

### Statistics Display

The admin dashboard shows real-time statistics:

- **Total Users**: Count of all users
- **Pending Collectors**: Count of collectors awaiting approval (with quick link)
- **Active Collectors**: Count of approved collectors
- **Total Waste Posts**: Count of all waste items posted

### Quick Actions

- Approve Collectors (links to pending collectors page)
- Manage Users (links to all users page)

### Recent Activity

Table showing last 10 registered users with:
- Name and email
- Role (color-coded badge)
- Status (color-coded badge)
- Registration date

---

## Admin Panel Navigation

Admin users see an additional **"Admin Panel"** link in the navigation menu:

```blade
@if(Auth::user()->isAdmin())
    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
        {{ __('Admin Panel') }}
    </x-nav-link>
@endif
```

This link:
- Only visible to admin users
- Highlights when on any admin route
- Available in both desktop and mobile menus

---

## Approving Collectors

### Workflow

1. New collector registers → status set to `pending`
2. Admin reviews on pending collectors page
3. Admin approves or rejects collector

### Approval Process

**Navigate to:** `/admin/collectors/pending`

**For each pending collector:**
- View name, email, phone, registration date
- Click **Approve** to activate collector
- Click **Reject** to block collector

**Approval Action:**
```php
// Updates collector status from 'pending' to 'active'
$collector->activate();
```

**Result:**
- Collector can now accept collection jobs
- Collector removed from pending list
- Success message displayed

---

## Managing Users

### All Users Page

**Navigate to:** `/admin/users`

**Features:**
- Paginated list of all users (20 per page)
- Filter tabs (All Users, Pending Collectors)
- User information display
- Inline actions

### Available Actions

**For Pending Collectors:**
- ✅ Approve → Activates collector
- ❌ Block → Blocks collector permanently

**For Active Users:**
- ❌ Block → Suspends user access

**For Blocked Users:**
- ✅ Activate → Restores user access

**For Admin Users:**
- No actions (admins cannot be blocked)

---

## Admin Privileges

### What Admins CAN Do

✅ Access admin dashboard  
✅ View all users and their details  
✅ Approve pending collectors  
✅ Block any user (except other admins)  
✅ Activate blocked users  
✅ View all waste posts  
✅ View all collection jobs  
✅ Monitor system statistics  

### What Admins CANNOT Do

❌ Delete users (not implemented yet)  
❌ Create waste posts (donors only)  
❌ Accept collection jobs (collectors only)  
❌ Modify other admins' status  

---

## Security Best Practices

### Password Management

1. **Change default password immediately**
   ```
   Default: admin123
   Required: Strong password (8+ chars, mixed case, numbers, symbols)
   ```

2. **Use password reset if forgotten**
   ```bash
   php artisan tinker
   ```
   ```php
   $admin = User::where('email', 'admin@binx.com')->first();
   $admin->password = Hash::make('new-secure-password');
   $admin->save();
   ```

### Account Protection

- ✅ Enable two-factor authentication (future enhancement)
- ✅ Use unique email addresses for each admin
- ✅ Regularly review admin access logs
- ✅ Remove unused admin accounts

### Role Protection

Admins are **always active** by design:

```php
// In User model
const ROLE_ADMIN = 'admin';
const STATUS_ACTIVE = 'active';

// Admin factory
User::factory()->admin()->create(); // Always creates active admin
```

---

## Testing Admin Access

### Comprehensive Test Suite

Location: [tests/Feature/AdminAccessTest.php](tests/Feature/AdminAccessTest.php)

**10 tests covering:**

✅ Guests cannot access admin routes  
✅ Donors cannot access admin routes  
✅ Collectors cannot access admin routes  
✅ Admins can access admin dashboard  
✅ Admins can access pending collectors page  
✅ Admins can access all users page  
✅ Admins can approve pending collectors  
✅ Admins can block users  
✅ Admins can activate blocked users  
✅ Non-admins cannot approve collectors  

### Running Tests

```bash
# Run all admin access tests
php artisan test tests/Feature/AdminAccessTest.php --compact

# Expected output
Tests:   10 passed (25 assertions)
Duration: 4.95s
```

---

## Troubleshooting

### Cannot Login to Admin Account

**Problem:** Login fails with correct credentials

**Solutions:**
1. Verify email is verified:
   ```php
   $admin = User::where('email', 'admin@binx.com')->first();
   $admin->email_verified_at = now();
   $admin->save();
   ```

2. Check role and status:
   ```php
   $admin->role; // Should be 'admin'
   $admin->status; // Should be 'active'
   ```

### Getting 403 Error on Admin Routes

**Problem:** "Access denied. Admin privileges required."

**Solutions:**
1. Verify user has admin role:
   ```php
   Auth::user()->isAdmin(); // Should return true
   ```

2. Check middleware is registered:
   ```php
   // In bootstrap/app.php
   $middleware->alias(['admin' => \App\Http\Middleware\IsAdmin::class]);
   ```

### Admin Link Not Showing in Navigation

**Problem:** Admin panel link not visible in menu

**Solutions:**
1. Clear view cache:
   ```bash
   php artisan view:clear
   ```

2. Verify user is logged in and is admin:
   ```php
   Auth::check(); // Should be true
   Auth::user()->isAdmin(); // Should be true
   ```

---

## Production Deployment

### Before Going Live

1. **Remove test admin account**
   ```sql
   DELETE FROM users WHERE email = 'admin@binx.com';
   ```

2. **Create production admin**
   ```bash
   php artisan db:seed --class=AdminSeeder
   ```

3. **Change password immediately**
   - Login with default credentials
   - Navigate to profile
   - Update password

4. **Secure environment variables**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```

### Post-Deployment

- Test admin login
- Verify all admin routes accessible
- Test collector approval workflow
- Review user management functionality

---

## Quick Reference

### Login Credentials (Default)

```
URL: /login
Email: admin@binx.com
Password: admin123
```

### Key Commands

```bash
# Create first admin
php artisan db:seed --class=AdminSeeder

# Test admin access
php artisan test tests/Feature/AdminAccessTest.php --compact

# Reset admin password
php artisan tinker
> $admin = User::where('email', 'admin@binx.com')->first();
> $admin->password = Hash::make('new-password');
> $admin->save();
```

### Important Routes

```
/admin/dashboard - Main admin panel
/admin/collectors/pending - Approve collectors
/admin/users - Manage all users
```

---

## Summary

✅ **Admin account created** with AdminSeeder  
✅ **Admin routes secured** with auth + admin middleware  
✅ **Admin dashboard implemented** with statistics  
✅ **Collector approval system** working  
✅ **User management** functional  
✅ **10 comprehensive tests** passing  

The system is now ready for admin control, collector approval, and system monitoring.
