<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WastePost extends Model
{
    /** @use HasFactory<\Database\Factories\WastePostFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'location',
        'quantity',
        'image_path',
        'status',
    ];

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
}
