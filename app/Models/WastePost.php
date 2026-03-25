<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WastePost extends Model
{
    /** @use HasFactory<\Database\Factories\WastePostFactory> */
    use HasFactory;

    // Status Constants
    const STATUS_OPEN = 'open';

    const STATUS_PENDING = 'pending';

    const STATUS_TAKEN = 'taken';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_EXPIRED = 'expired';

    const FOOD_WASTE_TYPES = ['cooked', 'vegetables', 'bakery', 'meat', 'mixed'];

    protected $fillable = [
        'user_id',
        'donor_id',
        'collector_id',
        'title',
        'description',
        'category',
        'location',
        'address',
        'quantity',
        'waste_types',
        'notes',
        'pickup_time',
        'instructions',
        'photos',
        'image_path',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'waste_types' => 'array',
            'photos' => 'array',
        ];
    }

    /**
     * Get the user who created this waste post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the job(s) associated with this waste post.
     */
    public function jobs()
    {
        return $this->hasMany(CollectionJob::class);
    }

    /**
     * Get the assigned collector for this waste post.
     */
    public function assignedCollector()
    {
        return $this->hasOneThrough(
            User::class,
            CollectionJob::class,
            'waste_post_id',
            'id',
            'id',
            'collector_id'
        )->latest('collection_jobs.created_at');
    }

    /**
     * Get the latest collection job.
     */
    public function latestJob()
    {
        return $this->hasOne(CollectionJob::class)->latestOfMany();
    }

    // ========== Status Helper Methods ==========

    /**
     * Check if waste post is available.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_PENDING], true);
    }

    /**
     * Check if waste post is assigned.
     */
    public function isTaken(): bool
    {
        return $this->status === self::STATUS_TAKEN;
    }

    /**
     * Check if waste post is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if waste post is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if waste post is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    // ========== Status Management Methods ==========

    /**
     * Mark waste post as cancelled.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Mark waste post as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }
}
