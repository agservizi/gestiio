<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileShareLink extends Model
{
    use HasFactory;

    protected $table = 'files_share_links';

    protected $fillable = [
        'token',
        'file_id',
        'cartella_id',
        'created_by',
        'password_hash',
        'expires_at',
        'max_downloads',
        'download_count',
        'is_active',
        'last_access_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_access_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function cartella()
    {
        return $this->belongsTo(CartellaFiles::class, 'cartella_id');
    }

    public function autore()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    public function isLimitReached(): bool
    {
        return $this->max_downloads !== null && $this->download_count >= $this->max_downloads;
    }
}
