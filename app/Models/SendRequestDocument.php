<?php

namespace App\Models;

use App\Enums\SendDocumentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SendRequestDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'send_request_id',
        'upload_uid',
        'category',
        'visibility',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'size',
        'hash',
        'uploaded_by',
        'antivirus_status',
        'expires_at',
    ];

    protected $casts = [
        'category' => SendDocumentCategory::class,
        'expires_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SendRequest::class, 'send_request_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Payload mock-file per Dropzone (create/edit). */
    public static function forBlade(?string $uploadUid, ?int $sendRequestId = null): array
    {
        $q = static::query()->whereNull('deleted_at');
        if ($sendRequestId) {
            $q->where('send_request_id', $sendRequestId);
        } elseif ($uploadUid) {
            $q->where('upload_uid', $uploadUid)->whereNull('send_request_id');
        } else {
            return [];
        }

        return $q->orderBy('id')->get()->map(fn (self $doc) => [
            'id' => $doc->id,
            'filename_originale' => $doc->original_name,
            'path_filename' => $doc->stored_name,
            'dimensione_file' => (int) $doc->size,
            'category' => $doc->category?->value ?? (string) $doc->category,
        ])->all();
    }
}
