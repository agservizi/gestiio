<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendRequestSubject extends Model
{
    protected $fillable = [
        'send_request_id',
        'subject_role',
        'subject_type',
        'first_name',
        'last_name',
        'business_name',
        'tax_code',
        'vat_number',
        'birth_date',
        'birth_place',
        'address',
        'email',
        'phone',
        'document_type',
        'document_number',
        'document_issued_by',
        'document_issue_date',
        'document_expiry_date',
        'representative_role',
        'relationship',
        'delegation_date',
        'delegation_expiry',
        'pec',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'document_issue_date' => 'date',
        'document_expiry_date' => 'date',
        'delegation_date' => 'date',
        'delegation_expiry' => 'date',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SendRequest::class, 'send_request_id');
    }

    public function displayName(): string
    {
        if ($this->subject_type === 'impresa' || $this->business_name) {
            return (string) $this->business_name;
        }

        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }
}
