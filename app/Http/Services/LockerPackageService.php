<?php

namespace App\Http\Services;

use App\Enums\LockerPackageSource;
use App\Enums\LockerPackageStatus;
use App\Exceptions\LockerNoAvailabilityException;
use App\Http\Support\LockerConfig;
use App\Models\LockerCashMovement;
use App\Models\LockerPackage;
use App\Models\LockerSetting;
use App\Models\LockerStation;
use App\Models\Setting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LockerPackageService
{
    public function getSettings(?LockerStation $station = null): LockerSetting|LockerStation
    {
        return $station ?: LockerSetting::singleton();
    }

    public function settingsFor(?LockerStation $station = null): object
    {
        return $this->getSettings($station);
    }

    public function updateSettings(array $data): LockerSetting
    {
        $settings = LockerSetting::singleton();
        $settings->fill($data);
        $settings->save();

        return $settings;
    }

    public function applySettingsUpdate(array $validated): LockerSetting
    {
        $settings = $this->updateSettings(array_filter([
            'daily_rate' => $validated['daily_rate'] ?? null,
            'max_capacity' => $validated['max_capacity'] ?? null,
            'min_days' => $validated['min_days'] ?? null,
            'max_packages_per_booking' => $validated['max_packages_per_booking'] ?? null,
            'currency' => isset($validated['currency']) ? strtoupper($validated['currency']) : null,
        ], fn ($v) => $v !== null));

        foreach ([
            'locker_online_intake_enabled',
            'locker_notify_staff',
            'locker_staff_notification_email',
            'locker_booking_instructions',
            'locker_agent_monthly_fee',
        ] as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            $value = $key === 'locker_online_intake_enabled' || $key === 'locker_notify_staff'
                ? (filter_var($validated[$key], FILTER_VALIDATE_BOOLEAN) ? '1' : '0')
                : (string) $validated[$key];

            Setting::add($key, $value, Setting::getDataType($key));
        }

        return $settings->fresh();
    }

    public function assertOnlineIntakeAllowed(string $source, ?LockerStation $station = null): void
    {
        if (! in_array($source, ['online', 'api'], true)) {
            return;
        }

        if ($station) {
            if (! $station->online_intake_enabled) {
                throw new InvalidArgumentException('Prenotazione online temporaneamente non disponibile.');
            }

            return;
        }

        if (! LockerConfig::onlineIntakeEnabled()) {
            throw new InvalidArgumentException('Prenotazione online temporaneamente non disponibile.');
        }
    }

    public function generateCode(): string
    {
        $prefix = config('locker.code_prefix', 'LP');

        do {
            $code = $prefix.'-'.strtoupper(Str::random(6));
        } while (LockerPackage::where('code', $code)->exists());

        return $code;
    }

    public function create(array $input, string $source = 'desk', ?LockerStation $station = null): LockerPackage
    {
        $this->assertOnlineIntakeAllowed($source, $station);

        $settings = $this->settingsFor($station);
        $pickupDate = Carbon::parse($input['expected_pickup_date'] ?? $input['booking_date'] ?? today())->startOfDay();

        $availability = $this->getAvailability($pickupDate, $station);
        if ($availability['available_packages'] < 1) {
            throw new LockerNoAvailabilityException(
                "Disponibilità insufficiente: {$availability['available_packages']} posti per {$availability['date']}"
            );
        }

        $normalizedSource = match (strtolower($source)) {
            'online', 'portale' => LockerPackageSource::ONLINE->value,
            'api' => LockerPackageSource::API->value,
            default => LockerPackageSource::DESK->value,
        };

        return LockerPackage::create([
            'station_id' => $station?->id,
            'code' => $this->generateCode(),
            'qr_token' => Str::uuid()->toString(),
            'cliente_id' => $input['cliente_id'] ?? null,
            'recipient_name' => $input['recipient_name'] ?? $input['customer_name'] ?? '',
            'recipient_email' => $input['recipient_email'] ?? $input['customer_email'] ?? null,
            'recipient_phone' => $input['recipient_phone'] ?? $input['customer_phone'] ?? null,
            'sender_name' => $input['sender_name'] ?? null,
            'sender_phone' => $input['sender_phone'] ?? null,
            'carrier' => $input['carrier'] ?? null,
            'tracking_code' => $input['tracking_code'] ?? null,
            'notes' => $input['notes'] ?? null,
            'expected_pickup_date' => $pickupDate,
            'daily_rate' => $settings->daily_rate,
            'status' => LockerPackageStatus::PRENOTATO,
            'source' => $normalizedSource,
        ]);
    }

    public function getAvailability(Carbon $date, ?LockerStation $station = null): array
    {
        $settings = $this->settingsFor($station);
        $day = $date->copy()->startOfDay();

        $query = LockerPackage::query()
            ->whereDate('expected_pickup_date', $day)
            ->whereIn('status', [
                LockerPackageStatus::PRENOTATO,
                LockerPackageStatus::IN_GIACENZA,
            ]);

        $this->scopeStationQuery($query, $station);

        $booked = (int) $query->count();
        $maxCapacity = (int) $settings->max_capacity;

        return [
            'date' => $day->toDateString(),
            'max_capacity' => $maxCapacity,
            'booked_packages' => $booked,
            'available_packages' => max(0, $maxCapacity - $booked),
            'available' => $booked < $maxCapacity,
            'station_id' => $station?->id,
            'station_slug' => $station?->slug,
        ];
    }

    public function getAvailabilityExcluding(Carbon $date, ?LockerPackage $exclude = null, ?LockerStation $station = null): array
    {
        $station = $station ?? $exclude?->station;
        $availability = $this->getAvailability($date, $station);

        if (
            $exclude
            && $exclude->expected_pickup_date
            && $exclude->expected_pickup_date->isSameDay($date)
            && in_array($exclude->status, [LockerPackageStatus::PRENOTATO, LockerPackageStatus::IN_GIACENZA], true)
        ) {
            $availability['available_packages'] += 1;
            $availability['booked_packages'] = max(0, $availability['booked_packages'] - 1);
            $availability['available'] = $availability['available_packages'] > 0;
        }

        return $availability;
    }

    public function scopeStationQuery($query, ?LockerStation $station = null, bool $forListAll = false)
    {
        if ($forListAll) {
            return $query;
        }

        if ($station) {
            return $query->where('station_id', $station->id);
        }

        return $query->whereNull('station_id');
    }

    public function acceptIntake(LockerPackage $package, UploadedFile $photo, ?int $userId = null): LockerPackage
    {
        if (! in_array($package->status, [LockerPackageStatus::PRENOTATO, LockerPackageStatus::NO_SHOW], true)) {
            throw new InvalidArgumentException("Accettazione non consentita per stato {$package->status->value}");
        }

        $maxKb = LockerConfig::maxPhotoKb();
        if ($photo->getSize() > $maxKb * 1024) {
            throw new InvalidArgumentException('Foto troppo grande (max '.($maxKb / 1024).' MB).');
        }

        $path = 'locker/'.$package->id.'/intake.jpg';
        Storage::disk('local')->put($path, file_get_contents($photo->getRealPath()));

        $package->update([
            'status' => LockerPackageStatus::IN_GIACENZA,
            'photo_path' => $path,
            'photo_taken_at' => now(),
            'received_by_user_id' => $userId ?? auth()->id(),
            'received_at' => now(),
        ]);

        $package = $package->fresh();
        Cache::forget($this->pickupCacheKey($package));

        return $package;
    }

    public function findForPickup(string $id, string $token): ?LockerPackage
    {
        return LockerPackage::query()
            ->where('id', $id)
            ->where('qr_token', $token)
            ->first();
    }

    public function pickupCacheKey(LockerPackage $package): string
    {
        return 'locker_pickup:'.$package->id.':'.$package->qr_token;
    }

    public function assertPickupAllowed(LockerPackage $package): void
    {
        if ($package->status !== LockerPackageStatus::IN_GIACENZA) {
            throw new InvalidArgumentException('Ritiro consentito solo per pacchi in giacenza.');
        }

        if (! $package->photo_path) {
            throw new InvalidArgumentException('Pacco non accettato: foto mancante.');
        }
    }

    public function getPickupProgress(LockerPackage $package): array
    {
        $expected = [strtoupper($package->code)];
        $scanned = array_values(array_unique(array_map('strtoupper', Cache::get($this->pickupCacheKey($package), []))));

        return [
            'expected' => [$package->code],
            'scanned' => $scanned,
            'remaining' => in_array(strtoupper($package->code), $scanned, true) ? [] : [$package->code],
            'complete' => in_array(strtoupper($package->code), $scanned, true),
            'pricing' => $this->computeStoragePrice($package),
        ];
    }

    public function scanPickupTag(LockerPackage $package, string $tag): array
    {
        $this->assertPickupAllowed($package);

        $normalized = strtoupper(trim($tag));
        if ($normalized !== strtoupper($package->code)) {
            throw new InvalidArgumentException('Codice pacco non riconosciuto.');
        }

        $cacheKey = $this->pickupCacheKey($package);
        $scanned = array_values(array_unique(Cache::get($cacheKey, [])));
        $scannedUpper = array_map('strtoupper', $scanned);

        if (! in_array($normalized, $scannedUpper, true)) {
            $scanned[] = $package->code;
            Cache::put($cacheKey, $scanned, now()->addHours(4));
        }

        $progress = $this->getPickupProgress($package);

        return [
            'scanned' => $progress['scanned'],
            'remaining' => $progress['remaining'],
            'complete' => $progress['complete'],
            'message' => $progress['complete']
                ? 'Codice pacco scansionato correttamente.'
                : 'Scansiona il codice pacco.',
        ];
    }

    /**
     * @param  list<string>  $scannedTags
     */
    public function completePickup(
        LockerPackage $package,
        string $paymentMethod,
        array $scannedTags,
        string $signatureData,
        string $signerName
    ): array {
        $this->assertPickupAllowed($package);

        $expectedUpper = strtoupper($package->code);
        $submitted = array_values(array_unique(array_map(
            fn (string $tag) => strtoupper(trim($tag)),
            $scannedTags
        )));

        if ($submitted !== [$expectedUpper]) {
            throw new InvalidArgumentException('Scansiona il codice pacco prima di completare il ritiro.');
        }

        if (trim($signerName) === '') {
            throw new InvalidArgumentException('Nome firmatario obbligatorio.');
        }

        $signaturePath = $this->storeSignature($package, $signatureData);

        Cache::forget($this->pickupCacheKey($package));

        return $this->deliver($package, $paymentMethod, $signerName, $signaturePath);
    }

    public function deliverDesk(LockerPackage $package, string $paymentMethod, ?string $signerName = null): array
    {
        $this->assertPickupAllowed($package);

        return $this->deliver($package, $paymentMethod, $signerName ?: $package->recipient_name, null);
    }

    protected function deliver(
        LockerPackage $package,
        string $paymentMethod,
        string $signerName,
        ?string $signaturePath
    ): array {
        if ($package->status !== LockerPackageStatus::IN_GIACENZA) {
            throw new InvalidArgumentException("Consegna non consentita per stato {$package->status->value}");
        }

        if (! $package->received_at) {
            throw new InvalidArgumentException('Accettazione mai effettuata');
        }

        $now = now();
        $pricing = $this->computeStoragePrice($package, $now);

        return DB::transaction(function () use ($package, $paymentMethod, $now, $pricing, $signerName, $signaturePath) {
            $movement = LockerCashMovement::create([
                'locker_package_id' => $package->id,
                'amount' => $pricing['total'],
                'payment_method' => $paymentMethod,
                'currency' => $this->settingsFor($package->station)->currency,
                'recorded_by' => auth()->id(),
                'recorded_at' => $now,
            ]);

            $package->update([
                'status' => LockerPackageStatus::CONSEGNATO,
                'delivered_at' => $now,
                'total_amount' => $pricing['total'],
                'payment_method' => $paymentMethod,
                'cash_movement_id' => $movement->id,
                'signer_name' => $signerName,
                'signature_path' => $signaturePath ?? $package->signature_path,
            ]);

            $package = $package->fresh();

            return [
                'package' => $package,
                'pricing' => $pricing,
            ];
        });
    }

    protected function storeSignature(LockerPackage $package, string $signatureData): string
    {
        if (! preg_match('/^data:image\/(png|jpeg);base64,/', $signatureData)) {
            throw new InvalidArgumentException('Firma non valida.');
        }

        $raw = base64_decode(preg_replace('/^data:image\/(png|jpeg);base64,/', '', $signatureData), true);
        if ($raw === false || strlen($raw) < 100) {
            throw new InvalidArgumentException('Firma non valida.');
        }

        $path = 'locker/'.$package->id.'/signature.png';
        Storage::disk('local')->put($path, $raw);

        return $path;
    }

    public function computeStoragePrice(LockerPackage $package, ?Carbon $deliveredAt = null): array
    {
        if (! $package->received_at) {
            throw new InvalidArgumentException('Accettazione mai effettuata');
        }

        $end = $deliveredAt ?? now();
        $seconds = $package->received_at->diffInSeconds($end);
        $minDays = max(1, (int) $this->settingsFor($package->station)->min_days);
        $days = max($minDays, (int) ceil($seconds / 86400));
        $total = $days * (float) $package->daily_rate;

        return ['days' => $days, 'total' => round($total, 2)];
    }

    public function cancel(LockerPackage $package): LockerPackage
    {
        if ($package->status === LockerPackageStatus::CONSEGNATO) {
            throw new InvalidArgumentException('Impossibile annullare un pacco già consegnato');
        }

        $package->update(['status' => LockerPackageStatus::ANNULLATO]);

        return $package->fresh();
    }

    public function markNoShow(LockerPackage $package): LockerPackage
    {
        if ($package->status !== LockerPackageStatus::PRENOTATO) {
            throw new InvalidArgumentException('No-show consentito solo per prenotazioni attive');
        }

        $package->update(['status' => LockerPackageStatus::NO_SHOW]);

        return $package->fresh();
    }

    public function updatePrenotato(LockerPackage $package, array $data): LockerPackage
    {
        if ($package->status !== LockerPackageStatus::PRENOTATO) {
            throw new InvalidArgumentException('Modifica consentita solo per PRENOTATO');
        }

        $pickupDate = isset($data['expected_pickup_date'])
            ? Carbon::parse($data['expected_pickup_date'])->startOfDay()
            : $package->expected_pickup_date;

        $availability = $this->getAvailabilityExcluding($pickupDate, $package, $package->station);
        if ($availability['available_packages'] < 1) {
            throw new LockerNoAvailabilityException(
                "Disponibilità insufficiente: {$availability['available_packages']} posti per {$availability['date']}"
            );
        }

        $package->update(array_filter([
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_email' => array_key_exists('recipient_email', $data) ? $data['recipient_email'] : null,
            'recipient_phone' => array_key_exists('recipient_phone', $data) ? $data['recipient_phone'] : null,
            'sender_name' => array_key_exists('sender_name', $data) ? $data['sender_name'] : null,
            'sender_phone' => array_key_exists('sender_phone', $data) ? $data['sender_phone'] : null,
            'carrier' => array_key_exists('carrier', $data) ? $data['carrier'] : null,
            'tracking_code' => array_key_exists('tracking_code', $data) ? $data['tracking_code'] : null,
            'expected_pickup_date' => isset($data['expected_pickup_date']) ? $pickupDate : null,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : null,
            'cliente_id' => array_key_exists('cliente_id', $data) ? $data['cliente_id'] : null,
        ], fn ($v) => $v !== null));

        return $package->fresh();
    }

    public function findByCode(string $code): ?LockerPackage
    {
        return LockerPackage::where('code', $code)->first();
    }

    public function findByIdOrCode(string $identifier): ?LockerPackage
    {
        return LockerPackage::query()
            ->where('id', $identifier)
            ->orWhere('code', $identifier)
            ->first();
    }

    public function list(
        array $filters = [],
        int $page = 1,
        int $limit = 25,
        ?LockerStation $station = null,
        bool $adminSeesAll = false
    ): LengthAwarePaginator {
        $query = LockerPackage::query()->orderByDesc('created_at');
        $this->scopeStationQuery($query, $station, $adminSeesAll && $station === null);

        if (! empty($filters['view'])) {
            match ($filters['view']) {
                'giacenza' => $query->where('status', LockerPackageStatus::IN_GIACENZA),
                'prenotati' => $query->where('status', LockerPackageStatus::PRENOTATO),
                'consegnati' => $query->where('status', LockerPackageStatus::CONSEGNATO),
                'annullati' => $query->whereIn('status', [
                    LockerPackageStatus::ANNULLATO,
                    LockerPackageStatus::NO_SHOW,
                ]),
                'oggi' => $query->whereDate('expected_pickup_date', today()),
                default => null,
            };
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['code'])) {
            $query->where('code', 'like', '%'.$filters['code'].'%');
        }

        if (! empty($filters['from'])) {
            $query->whereDate('expected_pickup_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('expected_pickup_date', '<=', $filters['to']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('recipient_name', 'like', "%{$term}%")
                    ->orWhere('recipient_email', 'like', "%{$term}%")
                    ->orWhere('tracking_code', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        return $query->paginate(min(100, max(1, $limit)), ['*'], 'page', max(1, $page));
    }

    public function stats(?Carbon $from = null, ?Carbon $to = null, ?LockerStation $station = null, bool $adminSeesAll = false): array
    {
        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();
        $scoped = fn ($query) => $this->scopeStationQuery($query, $station, $adminSeesAll && $station === null);

        $inStorage = $scoped(LockerPackage::query())->where('status', LockerPackageStatus::IN_GIACENZA)->count();
        $bookedToday = $scoped(LockerPackage::query())->where('status', LockerPackageStatus::PRENOTATO)
            ->whereBetween('expected_pickup_date', [$todayStart, $todayEnd])->count();
        $deliveredToday = $scoped(LockerPackage::query())->where('status', LockerPackageStatus::CONSEGNATO)
            ->whereBetween('delivered_at', [$todayStart, $todayEnd])->count();
        $todayRevenue = (float) $scoped(LockerPackage::query())->where('status', LockerPackageStatus::CONSEGNATO)
            ->whereBetween('delivered_at', [$todayStart, $todayEnd])
            ->sum('total_amount');

        $settings = $this->settingsFor($station);
        $availabilityToday = $this->getAvailability(today(), $station);

        $recent = $scoped(LockerPackage::query())
            ->latest()
            ->limit(5)
            ->get(['id', 'code', 'recipient_name', 'status', 'created_at', 'source']);

        return [
            'kpis' => [
                ['key' => 'giacenza', 'label' => 'In giacenza', 'value' => $inStorage, 'icon' => 'custodia'],
                ['key' => 'booked_today', 'label' => 'Prenotati oggi', 'value' => $bookedToday, 'icon' => 'calendar'],
                ['key' => 'delivered_today', 'label' => 'Consegnati oggi', 'value' => $deliveredToday, 'icon' => 'check'],
                ['key' => 'today_revenue', 'label' => 'Incasso oggi', 'value' => '€'.number_format($todayRevenue, 2, ',', '.'), 'icon' => 'revenue'],
            ],
            'capacity' => [
                'max' => $settings->max_capacity,
                'booked' => $availabilityToday['booked_packages'],
                'available' => $availabilityToday['available_packages'],
                'utilization' => (int) round(($availabilityToday['booked_packages'] / max(1, $settings->max_capacity)) * 100),
            ],
            'pipeline' => [
                'prenotati' => (int) $scoped(LockerPackage::query())->where('status', LockerPackageStatus::PRENOTATO)->count(),
                'giacenza' => $inStorage,
                'consegnati_oggi' => $deliveredToday,
            ],
            'settings' => [
                'dailyRate' => (float) $settings->daily_rate,
                'currency' => $settings->currency,
            ],
            'recent' => $recent,
        ];
    }
}
