<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileAuditLog extends Model
{
    use HasFactory;

    protected $table = 'files_audit_logs';

    protected $fillable = [
        'user_id',
        'file_id',
        'cartella_id',
        'azione',
        'filename_originale',
        'path_filename',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function utente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cartella()
    {
        return $this->belongsTo(CartellaFiles::class, 'cartella_id');
    }
}
