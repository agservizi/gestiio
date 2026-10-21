<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppVersionRelease extends Model
{
    protected $table = 'app_version_releases';

    protected $fillable = [
        'version',
        'title',
        'notes',
        'is_major',
        'user_id',
        'released_at',
    ];

    protected $casts = [
        'is_major' => 'boolean',
        'released_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return 'v'.$this->version.($this->title ? ' — '.$this->title : '');
    }
}
