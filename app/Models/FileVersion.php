<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    use HasFactory;

    protected $table = 'files_versions';

    protected $fillable = [
        'file_id',
        'versione',
        'filename_originale',
        'path_filename',
        'dimensione_file',
        'tipo_file',
        'categoria_documentale',
        'tags_documentali',
        'ocr_testo',
        'created_by',
    ];

    protected $casts = [
        'tags_documentali' => 'array',
    ];

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function autore()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
