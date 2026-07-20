<?php

namespace App\Models;

use App\Enums\SendApplicantType;
use App\Enums\SendPriority;
use App\Enums\SendRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SendRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'request_number',
        'created_by',
        'updated_by',
        'assigned_supervisor_id',
        'applicant_type',
        'status',
        'priority',
        'prezzo_cliente',
        'prezzo_agente',
        'importo_fornitore',
        'movimento_portafoglio_id',
        'send_notice_identifier',
        'iun',
        'sender_entity',
        'notice_date',
        'received_date',
        'due_date',
        'notice_pages',
        'communication_type',
        'initial_notes',
        'rejection_reason',
        'cancellation_reason',
        'integration_reason',
        'integration_category',
        'integration_due_at',
        'submitted_at',
        'assigned_at',
        'taken_in_charge_at',
        'processing_started_at',
        'completed_at',
        'delivered_at',
        'closed_at',
        'rejected_at',
        'cancelled_at',
        'version',
    ];

    protected $casts = [
        'applicant_type' => SendApplicantType::class,
        'status' => SendRequestStatus::class,
        'priority' => SendPriority::class,
        'notice_date' => 'date',
        'received_date' => 'date',
        'due_date' => 'date',
        'integration_due_at' => 'datetime',
        'submitted_at' => 'datetime',
        'assigned_at' => 'datetime',
        'taken_in_charge_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'closed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_supervisor_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(SendRequestSubject::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SendRequestDocument::class);
    }

    /** Allegato risultato SEND destinato al cliente (visibile ad admin/agente). */
    public function documentsForClient(): HasMany
    {
        return $this->hasMany(SendRequestDocument::class)
            ->where('visibility', 'citizen_receipt');
    }

    public function latestClientDocument(): ?SendRequestDocument
    {
        return $this->documentsForClient()->latest('id')->first();
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(SendRequestChecklistItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(SendRequestStatusHistory::class)->orderByDesc('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SendRequestAssignment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SendRequestNote::class)->orderByDesc('id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(SendRequestConsent::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(SendRequestDelivery::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(SendRequestAuditLog::class)->orderByDesc('id');
    }

    public function subjectByRole(string $role): ?SendRequestSubject
    {
        return $this->subjects->firstWhere('subject_role', $role);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
