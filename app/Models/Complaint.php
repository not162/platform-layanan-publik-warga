<?php

namespace App\Models;

use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'is_anonymous',
        'attachment_path',
        'version',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
