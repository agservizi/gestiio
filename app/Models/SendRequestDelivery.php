<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendRequestDelivery extends Model
{
    protected $fillable = [
        'send_request_id',
        'delivered_by',
        'recipient_type',
        'recipient_name',
        'delivery_method',
        'identification_type',
        'document_verified',
        'delivered_at',
        'documents_summary',
        'confirmation_data',
        'print_done',
        'notes',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'confirmation_data' => 'array',
        'print_done' => 'boolean',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SendRequest::class, 'send_request_id');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }
}
