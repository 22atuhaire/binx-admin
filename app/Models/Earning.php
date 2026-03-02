<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Earning extends Model
{
    /** @use HasFactory<\Database\Factories\EarningFactory> */
    use HasFactory;

    protected $fillable = [
        'collector_id',
        'job_id',
        'amount',
        'description',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the collector who earned this.
     */
    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    /**
     * Get the job associated with this earning.
     */
    public function job()
    {
        return $this->belongsTo(CollectionJob::class, 'job_id');
    }
}
