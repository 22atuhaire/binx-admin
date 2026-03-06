<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // Role Constants
    const ROLE_ADMIN = 'admin';

    const ROLE_DONOR = 'donor';

    const ROLE_COLLECTOR = 'collector';

    // Status Constants
    const STATUS_PENDING = 'pending';

    const STATUS_ACTIVE = 'active';

    const STATUS_BLOCKED = 'blocked';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'status',
        'rating',
        'id_document',
        'rejection_reason',
        'suspended_at',
        'suspension_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Get all waste posts created by this user.
     */
    public function wastePosts()
    {
        return $this->hasMany(WastePost::class);
    }

    /**
     * Get all collection jobs assigned to this collector.
     */
    public function jobs()
    {
        return $this->hasMany(CollectionJob::class, 'collector_id');
    }

    /**
     * Get all earnings for this collector.
     */
    public function earnings()
    {
        return $this->hasMany(Earning::class, 'collector_id');
    }

    // ========== Role Helper Methods ==========

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is a donor.
     */
    public function isDonor(): bool
    {
        return $this->role === self::ROLE_DONOR;
    }

    /**
     * Check if user is a collector.
     */
    public function isCollector(): bool
    {
        return $this->role === self::ROLE_COLLECTOR;
    }

    // ========== Status Helper Methods ==========

    /**
     * Check if user status is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if user status is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user status is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    // ========== Status Management Methods ==========

    /**
     * Activate a user (set status to active).
     */
    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Block a user (set status to blocked).
     */
    public function block(): void
    {
        $this->update(['status' => self::STATUS_BLOCKED]);
    }

    /**
     * Set user as pending.
     */
    public function setPending(): void
    {
        $this->update(['status' => self::STATUS_PENDING]);
    }

    /**
     * Suspend a collector temporarily.
     */
    public function suspend(string $reason): void
    {
        $this->update([
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Reactivate a suspended collector.
     */
    public function reactivate(): void
    {
        $this->update([
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    /**
     * Check if collector is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Alias for jobs relationship (for API compatibility).
     */
    public function collectorJobs()
    {
        return $this->hasMany(CollectionJob::class, 'collector_id');
    }
}
