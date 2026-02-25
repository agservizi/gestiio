<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContrattoEnergiaMagicLink extends Model
{
    use HasFactory;

    public const PURPOSE_RICHIESTA_DOCUMENTI = 'richiesta_documenti';

    protected $table = 'contratti_energia_magic_links';

    protected $fillable = [
        'contratto_energia_id',
        'email',
        'purpose',
        'token_hash',
        'expires_at',
        'used_at',
        'used_ip',
        'created_by_user_id',
        'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'meta' => 'array',
    ];

    public function contrattoEnergia()
    {
        return $this->belongsTo(ContrattoEnergia::class, 'contratto_energia_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return !$this->used_at && !$this->isExpired();
    }

    public function markUsed(?string $ip = null): void
    {
        $this->used_at = now();
        $this->used_ip = $ip;
        $this->save();
    }

    public static function createRichiestaDocumentiLink(ContrattoEnergia $contratto, ?int $createdByUserId = null, int $ttlHours = 72): array
    {
        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        $record = self::create([
            'contratto_energia_id' => $contratto->id,
            'email' => (string) $contratto->email,
            'purpose' => self::PURPOSE_RICHIESTA_DOCUMENTI,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours($ttlHours),
            'created_by_user_id' => $createdByUserId,
            'meta' => [
                'gestore_id' => $contratto->gestore_id,
            ],
        ]);

        return [$record, $plainToken];
    }

    public static function findUsableByPlainToken(string $plainToken): ?self
    {
        if (trim($plainToken) === '') {
            return null;
        }

        $hash = hash('sha256', $plainToken);

        /** @var self|null $record */
        $record = self::query()
            ->where('token_hash', $hash)
            ->where('purpose', self::PURPOSE_RICHIESTA_DOCUMENTI)
            ->first();

        if (!$record || !$record->isUsable()) {
            return null;
        }

        return $record;
    }
}
