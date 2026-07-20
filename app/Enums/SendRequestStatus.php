<?php

namespace App\Enums;

enum SendRequestStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case AWAITING_ASSIGNMENT = 'awaiting_assignment';
    case ASSIGNED = 'assigned';
    case TAKEN_IN_CHARGE = 'taken_in_charge';
    case PROCESSING = 'processing';
    case INTEGRATION_REQUIRED = 'integration_required';
    case RESUBMITTED = 'resubmitted';
    case COMPLETED = 'completed';
    case DELIVERED = 'delivered';
    case CLOSED = 'closed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bozza',
            self::SUBMITTED => 'Inviata',
            self::AWAITING_ASSIGNMENT => 'In attesa assegnazione',
            self::ASSIGNED => 'Assegnata',
            self::TAKEN_IN_CHARGE => 'Presa in carico',
            self::PROCESSING => 'In lavorazione',
            self::INTEGRATION_REQUIRED => 'Integrazione richiesta',
            self::RESUBMITTED => 'Reintegrata',
            self::COMPLETED => 'Completata',
            self::DELIVERED => 'Consegnata',
            self::CLOSED => 'Chiusa',
            self::REJECTED => 'Rifiutata',
            self::CANCELLED => 'Annullata',
            self::EXPIRED => 'Scaduta',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT, self::CANCELLED => 'badge-light',
            self::SUBMITTED, self::AWAITING_ASSIGNMENT, self::ASSIGNED => 'badge-light-info',
            self::TAKEN_IN_CHARGE, self::PROCESSING, self::RESUBMITTED => 'badge-light-primary',
            self::INTEGRATION_REQUIRED, self::EXPIRED => 'badge-light-warning',
            self::COMPLETED, self::DELIVERED, self::CLOSED => 'badge-light-success',
            self::REJECTED => 'badge-light-danger',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::CLOSED, self::CANCELLED, self::REJECTED, self::EXPIRED], true);
    }

    public function isEditableByOperator(): bool
    {
        return in_array($this, [self::DRAFT, self::INTEGRATION_REQUIRED], true);
    }
}
