<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    /** @use HasFactory<\Database\Factories\JobFactory> */
    use HasFactory;

    protected $fillable = [
        'waste_post_id',
        'collector_id',
        'status',
        'assigned_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the waste post for this job.
     */
    public function wastePost()
    {
        return $this->belongsTo(WastePost::class);
    }

    /**
     * Get the collector assigned to this job.
     */
    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    /**
     * Get the earning record for this job.
     */
    public function earning()
    {
        return $this->hasOne(Earning::class);
    }
}
