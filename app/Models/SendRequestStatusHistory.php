<?php

namespace App\Models;

use App\Enums\SendRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendRequestStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'send_request_status_history';

    protected $fillable = [
        'send_request_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'from_status' => SendRequestStatus::class,
        'to_status' => SendRequestStatus::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SendRequest::class, 'send_request_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
