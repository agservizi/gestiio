<?php

namespace App\Http\Services;

use App\Enums\LuggageDepositStatus;
use App\Events\LuggageDepositCheckedIn;
use App\Events\LuggageDepositCheckedOut;
use App\Events\LuggageDepositCreated;
use App\Exceptions\LuggageNoAvailabilityException;
use App\Http\Support\LuggageConfig;
use App\Models\LuggageCashMovement;
use App\Models\LuggageDeposit;
use App\Models\LuggageSetting;
use App\Models\Setting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LuggageDepositService
{
    public function getSettings(): LuggageSetting
    {
        return LuggageSetting::singleton();
    }

    public function updateSettings(array $data): LuggageSetting
    {
        $settings = $this->getSettings();
        $settings->fill($data);
        $settings->save();

        return $settings;
    }

    public function applySettingsUpdate(array $validated): LuggageSetting
    {
        $settings = $this->updateSettings(array_filter([
            'daily_rate' => $validated['daily_rate'] ?? null,
            'max_capacity' => $validated['max_capacity'] ?? null,
            'min_days' => $validated['min_days'] ?? null,
            'max_bags_per_booking' => $validated['max_bags_per_booking'] ?? null,
            'currency' => isset($validated['currency']) ? strtoupper($validated['currency']) : null,
        ], fn ($v) => $v !== null));

        foreach ([
            'luggage_online_booking_enabled',
            'luggage_notify_staff',
            'luggage_notify_customer_receipt',
            'luggage_notify_customer_pickup_qr',
            'luggage_staff_notification_email',
            'luggage_booking_instructions',
        ] as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            $value = in_array($key, [
                'luggage_online_booking_enabled',
                'luggage_notify_staff',
                'luggage_notify_customer_receipt',
                'luggage_notify_customer_pickup_qr',
            ], true)
                ? (filter_var($validated[$key], FILTER_VALIDATE_BOOLEAN) ? '1' : '0')
                : (string) $validated[$key];

            Setting::add($key, $value, Setting::getDataType($key));
        }

        return $settings->fresh();
    }

    public function assertOnlineBookingAllowed(string $source): void
    {
        if ($source === 'PORTALE' && ! LuggageConfig::onlineBookingEnabled()) {
            throw new InvalidArgumentException('Prenotazione online temporaneamente non disponibile.');
        }
    }

    public function generateCode(): string
    {
        $prefix = config('luggage.code_prefix', 'LB');

        do {
            $code = $prefix.'-'.strtoupper(Str::random(6));
        } while (LuggageDeposit::where('code', $code)->exists());

        return $code;
    }

    public function create(array $input, string $source = 'SPORTELLO'): LuggageDeposit
    {
        $this->assertOnlineBookingAllowed($source);

        $settings = $this->getSettings();
        $bagCount = max(1, (int) ($input['bag_count'] ?? 1));
        $bookingDate = Carbon::parse($input['booking_date'])->startOfDay();

        if ($bagCount > $settings->max_bags_per_booking) {
            throw new InvalidArgumentException('Numero borse superiore al massimo consentito.');
        }

        $availability = $this->getAvailability($bookingDate);
        if ($availability['available_bags'] < $bagCount) {
            throw new LuggageNoAvailabilityException(
                "Disponibilità insufficiente: {$availability['available_bags']} posti per {$availability['date']}"
            );
        }

        $code = $this->generateCode();
        $bagTags = $this->generateBagTags($code, $bagCount);

        $deposit = LuggageDeposit::create([
            'code' => $code,
            'qr_token' => Str::uuid()->toString(),
            'cliente_id' => $input['cliente_id'] ?? null,
            'customer_name' => $input['customer_name'],
            'customer_email' => $input['customer_email'] ?? null,
            'customer_phone' => $input['customer_phone'] ?? null,
            'bag_count' => $bagCount,
            'bag_tags' => $bagTags,
            'booking_date' => $bookingDate,
            'expected_check_in' => isset($input['expected_check_in']) ? Carbon::parse($input['expected_check_in']) : null,
            'expected_check_out' => isset($input['expected_check_out']) ? Carbon::parse($input['expected_check_out']) : null,
            'notes' => $input['notes'] ?? null,
            'daily_rate' => $settings->daily_rate,
            'status' => LuggageDepositStatus::PRENOTATO,
            'source' => $source,
        ]);

        event(new LuggageDepositCreated($deposit));

        return $deposit;
    }

    public function getAvailability(Carbon $date): array
    {
        $settings = $this->getSettings();
        $day = $date->copy()->startOfDay();

        $bookedBags = (int) LuggageDeposit::query()
            ->whereDate('booking_date', $day)
            ->whereIn('status', [
                LuggageDepositStatus::PRENOTATO,
                LuggageDepositStatus::CHECK_IN,
            ])
            ->sum('bag_count');

        $maxCapacity = $settings->max_capacity;

        return [
            'date' => $day->toDateString(),
            'max_capacity' => $maxCapacity,
            'booked_bags' => $bookedBags,
            'available_bags' => max(0, $maxCapacity - $bookedBags),
            'available' => $bookedBags < $maxCapacity,
        ];
    }

    public function getAvailabilityExcluding(Carbon $date, ?LuggageDeposit $exclude = null): array
    {
        $availability = $this->getAvailability($date);

        if (
            $exclude
            && $exclude->booking_date
            && $exclude->booking_date->isSameDay($date)
            && in_array($exclude->status, [LuggageDepositStatus::PRENOTATO, LuggageDepositStatus::CHECK_IN], true)
        ) {
            $availability['available_bags'] += $exclude->bag_count;
            $availability['booked_bags'] = max(0, $availability['booked_bags'] - $exclude->bag_count);
            $availability['available'] = $availability['available_bags'] > 0;
        }

        return $availability;
    }

    public function generateBagTags(string $code, int $count): array
    {
        $letters = range('A', 'Z');

        return array_map(
            fn (int $i) => $code.'-'.($letters[$i] ?? ($i + 1)),
            range(0, $count - 1)
        );
    }

    public function resolveBagTags(LuggageDeposit $deposit): array
    {
        $tags = $deposit->bag_tags ?? [];

        if ($tags !== []) {
            return $tags;
        }

        return $this->generateBagTags($deposit->code, $deposit->bag_count);
    }

    public function checkIn(LuggageDeposit $deposit, ?array $customBagTags = null): LuggageDeposit
    {
        if (! in_array($deposit->status, [LuggageDepositStatus::PRENOTATO, LuggageDepositStatus::NO_SHOW], true)) {
            throw new InvalidArgumentException("Check-in non consentito per stato {$deposit->status->value}");
        }

        $bagTags = $customBagTags ?: ($deposit->bag_tags ?: $this->generateBagTags($deposit->code, $deposit->bag_count));

        $deposit->update([
            'status' => LuggageDepositStatus::CHECK_IN,
            'checked_in_at' => now(),
            'bag_tags' => $bagTags,
        ]);

        $deposit = $deposit->fresh();
        Cache::forget($this->pickupCacheKey($deposit));
        event(new LuggageDepositCheckedIn($deposit));

        return $deposit;
    }

    public function findForPickup(string $id, string $token): ?LuggageDeposit
    {
        return LuggageDeposit::query()
            ->where('id', $id)
            ->where('qr_token', $token)
            ->first();
    }

    public function pickupCacheKey(LuggageDeposit $deposit): string
    {
        return 'luggage_pickup:'.$deposit->id.':'.$deposit->qr_token;
    }

    public function assertPickupAllowed(LuggageDeposit $deposit): void
    {
        if ($deposit->status !== LuggageDepositStatus::CHECK_IN) {
            throw new InvalidArgumentException('Ritiro consentito solo per depositi in custodia (check-in effettuato).');
        }
    }

    public function getPickupProgress(LuggageDeposit $deposit): array
    {
        $expected = $this->resolveBagTags($deposit);
        $scanned = array_values(array_unique(Cache::get($this->pickupCacheKey($deposit), [])));
        $expectedUpper = array_map('strtoupper', $expected);
        $scannedUpper = array_map('strtoupper', $scanned);

        return [
            'expected' => $expected,
            'scanned' => $scanned,
            'remaining' => array_values(array_filter(
                $expected,
                fn (string $tag) => ! in_array(strtoupper($tag), $scannedUpper, true)
            )),
            'complete' => count($scannedUpper) >= count($expectedUpper)
                && array_diff($expectedUpper, $scannedUpper) === [],
            'pricing' => $this->computeStoragePrice($deposit),
        ];
    }

    public function scanPickupTag(LuggageDeposit $deposit, string $tag): array
    {
        $this->assertPickupAllowed($deposit);

        $normalized = strtoupper(trim($tag));
        $expected = $this->resolveBagTags($deposit);
        $expectedUpper = array_map('strtoupper', $expected);

        if (! in_array($normalized, $expectedUpper, true)) {
            throw new InvalidArgumentException('Tag bagaglio non riconosciuto per questo deposito.');
        }

        $cacheKey = $this->pickupCacheKey($deposit);
        $scanned = array_values(array_unique(Cache::get($cacheKey, [])));
        $scannedUpper = array_map('strtoupper', $scanned);

        if (! in_array($normalized, $scannedUpper, true)) {
            $originalTag = $expected[array_search($normalized, $expectedUpper, true)];
            $scanned[] = $originalTag;
            Cache::put($cacheKey, $scanned, now()->addHours(4));
        }

        $progress = $this->getPickupProgress($deposit);

        return [
            'scanned' => $progress['scanned'],
            'remaining' => $progress['remaining'],
            'complete' => $progress['complete'],
            'message' => $progress['complete']
                ? 'Tutti i bagagli sono stati scansionati.'
                : 'Tag registrato. Scansiona i bagagli rimanenti.',
        ];
    }

    /**
     * @param  list<string>  $scannedTags
     */
    public function completePickup(LuggageDeposit $deposit, string $paymentMethod, array $scannedTags): array
    {
        $this->assertPickupAllowed($deposit);

        $expected = $this->resolveBagTags($deposit);
        $expectedUpper = array_map('strtoupper', $expected);
        $submitted = array_values(array_unique(array_map(
            fn (string $tag) => strtoupper(trim($tag)),
            $scannedTags
        )));

        if (count($submitted) !== count($expectedUpper) || array_diff($expectedUpper, $submitted) !== []) {
            throw new InvalidArgumentException('Scansiona tutti i tag bagaglio prima di completare il ritiro.');
        }

        Cache::forget($this->pickupCacheKey($deposit));

        return $this->checkOut($deposit, $paymentMethod);
    }

    public function computeStoragePrice(LuggageDeposit $deposit, ?Carbon $checkedOutAt = null): array
    {
        if (! $deposit->checked_in_at) {
            throw new InvalidArgumentException('Check-in mai effettuato');
        }

        $end = $checkedOutAt ?? now();
        $seconds = $deposit->checked_in_at->diffInSeconds($end);
        $minDays = max(1, (int) $this->getSettings()->min_days);
        $days = max($minDays, (int) ceil($seconds / 86400));
        $total = $days * $deposit->bag_count * (float) $deposit->daily_rate;

        return ['days' => $days, 'total' => round($total, 2)];
    }

    public function checkOut(LuggageDeposit $deposit, string $paymentMethod = 'Contanti'): array
    {
        if ($deposit->status !== LuggageDepositStatus::CHECK_IN) {
            throw new InvalidArgumentException("Check-out non consentito per stato {$deposit->status->value}");
        }

        if (! $deposit->checked_in_at) {
            throw new InvalidArgumentException('Check-in mai effettuato');
        }

        $now = now();
        $pricing = $this->computeStoragePrice($deposit, $now);

        return DB::transaction(function () use ($deposit, $paymentMethod, $now, $pricing) {
            $movement = LuggageCashMovement::create([
                'luggage_deposit_id' => $deposit->id,
                'amount' => $pricing['total'],
                'payment_method' => $paymentMethod,
                'currency' => $this->getSettings()->currency,
                'recorded_by' => auth()->id(),
                'recorded_at' => $now,
            ]);

            $deposit->update([
                'status' => LuggageDepositStatus::COMPLETATO,
                'checked_out_at' => $now,
                'total_amount' => $pricing['total'],
                'payment_method' => $paymentMethod,
                'cash_movement_id' => $movement->id,
            ]);

            $deposit = $deposit->fresh();
            event(new LuggageDepositCheckedOut($deposit, $pricing['total'], $paymentMethod));

            return [
                'deposit' => $deposit,
                'pricing' => $pricing,
            ];
        });
    }

    public function cancel(LuggageDeposit $deposit): LuggageDeposit
    {
        if ($deposit->status === LuggageDepositStatus::COMPLETATO) {
            throw new InvalidArgumentException('Impossibile annullare un deposito già completato');
        }

        $deposit->update(['status' => LuggageDepositStatus::ANNULLATO]);

        return $deposit->fresh();
    }

    public function markNoShow(LuggageDeposit $deposit): LuggageDeposit
    {
        if ($deposit->status !== LuggageDepositStatus::PRENOTATO) {
            throw new InvalidArgumentException('No-show consentito solo per prenotazioni attive');
        }

        $deposit->update(['status' => LuggageDepositStatus::NO_SHOW]);

        return $deposit->fresh();
    }

    public function updatePrenotato(LuggageDeposit $deposit, array $data): LuggageDeposit
    {
        if ($deposit->status !== LuggageDepositStatus::PRENOTATO) {
            throw new InvalidArgumentException('Modifica consentita solo per PRENOTATO');
        }

        $settings = $this->getSettings();
        $bagCount = array_key_exists('bag_count', $data)
            ? max(1, (int) $data['bag_count'])
            : $deposit->bag_count;
        $bookingDate = isset($data['booking_date'])
            ? Carbon::parse($data['booking_date'])->startOfDay()
            : $deposit->booking_date;

        if ($bagCount > $settings->max_bags_per_booking) {
            throw new InvalidArgumentException('Numero borse superiore al massimo consentito.');
        }

        $availability = $this->getAvailabilityExcluding($bookingDate, $deposit);
        if ($availability['available_bags'] < $bagCount) {
            throw new LuggageNoAvailabilityException(
                "Disponibilità insufficiente: {$availability['available_bags']} posti per {$availability['date']}"
            );
        }

        $deposit->update(array_filter([
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => array_key_exists('customer_email', $data) ? $data['customer_email'] : null,
            'customer_phone' => array_key_exists('customer_phone', $data) ? $data['customer_phone'] : null,
            'bag_count' => array_key_exists('bag_count', $data) ? $bagCount : null,
            'booking_date' => isset($data['booking_date']) ? $bookingDate : null,
            'expected_check_in' => array_key_exists('expected_check_in', $data)
                ? ($data['expected_check_in'] ? Carbon::parse($data['expected_check_in']) : null)
                : null,
            'expected_check_out' => array_key_exists('expected_check_out', $data)
                ? ($data['expected_check_out'] ? Carbon::parse($data['expected_check_out']) : null)
                : null,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : null,
            'cliente_id' => array_key_exists('cliente_id', $data) ? $data['cliente_id'] : null,
        ], fn ($v) => $v !== null));

        return $deposit->fresh();
    }

    public function updateStaffDeposit(LuggageDeposit $deposit, array $data): LuggageDeposit
    {
        if ($deposit->status === LuggageDepositStatus::PRENOTATO) {
            return $this->updatePrenotato($deposit, $data);
        }

        if ($deposit->status === LuggageDepositStatus::NO_SHOW) {
            $deposit->update(array_filter([
                'customer_name' => $data['customer_name'] ?? null,
                'customer_email' => array_key_exists('customer_email', $data) ? $data['customer_email'] : null,
                'customer_phone' => array_key_exists('customer_phone', $data) ? $data['customer_phone'] : null,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : null,
            ], fn ($v) => $v !== null));

            return $deposit->fresh();
        }

        throw new InvalidArgumentException('Modifica consentita solo per prenotazioni attive o no-show.');
    }

    public function verifyByToken(string $token): ?LuggageDeposit
    {
        return LuggageDeposit::query()
            ->where('qr_token', $token)
            ->first([
                'id', 'code', 'customer_name', 'bag_count', 'bag_tags',
                'status', 'checked_in_at', 'checked_out_at', 'booking_date', 'total_amount',
            ]);
    }

    public function findByCode(string $code): ?LuggageDeposit
    {
        return LuggageDeposit::where('code', $code)->first();
    }

    public function findByIdOrCode(string $identifier): ?LuggageDeposit
    {
        return LuggageDeposit::query()
            ->where('id', $identifier)
            ->orWhere('code', $identifier)
            ->first();
    }

    public function list(array $filters = [], int $page = 1, int $limit = 25): LengthAwarePaginator
    {
        $query = LuggageDeposit::query()->orderByDesc('created_at');

        if (! empty($filters['view'])) {
            match ($filters['view']) {
                'attivi' => $query->where('status', LuggageDepositStatus::CHECK_IN),
                'prenotati' => $query->where('status', LuggageDepositStatus::PRENOTATO),
                'completati' => $query->where('status', LuggageDepositStatus::COMPLETATO),
                'annullati' => $query->whereIn('status', [
                    LuggageDepositStatus::ANNULLATO,
                    LuggageDepositStatus::NO_SHOW,
                ]),
                'oggi' => $query->whereDate('booking_date', today()),
                default => null,
            };
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['email'])) {
            $query->where('customer_email', $filters['email']);
        }

        if (! empty($filters['code'])) {
            $query->where('code', 'like', '%'.$filters['code'].'%');
        }

        if (! empty($filters['from'])) {
            $query->whereDate('booking_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('booking_date', '<=', $filters['to']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        return $query->paginate(min(100, max(1, $limit)), ['*'], 'page', max(1, $page));
    }

    public function stats(?Carbon $from = null, ?Carbon $to = null): array
    {
        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();

        $active = LuggageDeposit::where('status', LuggageDepositStatus::CHECK_IN)->count();
        $bookedToday = LuggageDeposit::where('status', LuggageDepositStatus::PRENOTATO)
            ->whereBetween('booking_date', [$todayStart, $todayEnd])->count();
        $completedToday = LuggageDeposit::where('status', LuggageDepositStatus::COMPLETATO)
            ->whereBetween('checked_out_at', [$todayStart, $todayEnd])->count();
        $bagsStored = (int) LuggageDeposit::where('status', LuggageDepositStatus::CHECK_IN)->sum('bag_count');
        $todayRevenue = (float) LuggageDeposit::where('status', LuggageDepositStatus::COMPLETATO)
            ->whereBetween('checked_out_at', [$todayStart, $todayEnd])
            ->sum('total_amount');

        $breakdown = LuggageDeposit::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recent = LuggageDeposit::query()
            ->latest()
            ->limit(5)
            ->get(['id', 'code', 'customer_name', 'bag_count', 'status', 'created_at', 'source']);

        $periodQuery = LuggageDeposit::query()
            ->where('status', LuggageDepositStatus::COMPLETATO);

        if ($from) {
            $periodQuery->whereDate('checked_out_at', '>=', $from);
        }
        if ($to) {
            $periodQuery->whereDate('checked_out_at', '<=', $to);
        }

        $periodRevenue = (float) (clone $periodQuery)->sum('total_amount');
        $periodCompleted = (clone $periodQuery)->count();
        $portalBookings = LuggageDeposit::where('source', 'PORTALE')->count();
        $sportelloBookings = LuggageDeposit::where('source', 'SPORTELLO')->count();
        $settings = $this->getSettings();
        $availabilityToday = $this->getAvailability(today());

        $revenueTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $revenueTrend[] = [
                'date' => $day->toDateString(),
                'label' => $day->isoFormat('ddd'),
                'revenue' => (float) LuggageDeposit::where('status', LuggageDepositStatus::COMPLETATO)
                    ->whereBetween('checked_out_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
                    ->sum('total_amount'),
                'completed' => LuggageDeposit::where('status', LuggageDepositStatus::COMPLETATO)
                    ->whereBetween('checked_out_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
                    ->count(),
            ];
        }

        $upcoming = LuggageDeposit::query()
            ->where('status', LuggageDepositStatus::PRENOTATO)
            ->whereDate('booking_date', '>=', today())
            ->whereDate('booking_date', '<=', today()->addDays(2))
            ->orderBy('booking_date')
            ->limit(6)
            ->get(['id', 'code', 'customer_name', 'bag_count', 'booking_date', 'source']);

        $statusBreakdown = collect($breakdown)->mapWithKeys(function ($count, $status) {
            $enum = LuggageDepositStatus::tryFrom((string) $status);

            return [$status => [
                'count' => (int) $count,
                'label' => $enum?->label() ?? (string) $status,
                'badgeClass' => $enum?->badgeClass() ?? 'badge-light',
            ]];
        });

        return [
            'kpis' => [
                ['key' => 'active', 'label' => 'Depositi attivi', 'value' => $active, 'icon' => 'custodia'],
                ['key' => 'booked_today', 'label' => 'Prenotati oggi', 'value' => $bookedToday, 'icon' => 'calendar'],
                ['key' => 'bags_stored', 'label' => 'Borse in custodia', 'value' => $bagsStored, 'icon' => 'bags'],
                ['key' => 'completed_today', 'label' => 'Completati oggi', 'value' => $completedToday, 'icon' => 'check'],
                ['key' => 'today_revenue', 'label' => 'Incasso oggi', 'value' => '€'.number_format($todayRevenue, 2, ',', '.'), 'icon' => 'revenue'],
                ['key' => 'total', 'label' => 'Totale depositi', 'value' => LuggageDeposit::count(), 'icon' => 'total'],
                ['key' => 'portal_bookings', 'label' => 'Prenotazioni online', 'value' => $portalBookings, 'icon' => 'portal'],
                ['key' => 'period_revenue', 'label' => 'Incasso periodo', 'value' => '€'.number_format($periodRevenue, 2, ',', '.'), 'icon' => 'revenue'],
            ],
            'capacity' => [
                'max' => $settings->max_capacity,
                'booked' => $availabilityToday['booked_bags'],
                'available' => $availabilityToday['available_bags'],
                'utilization' => (int) round(($availabilityToday['booked_bags'] / max(1, $settings->max_capacity)) * 100),
            ],
            'pipeline' => [
                'prenotati' => (int) LuggageDeposit::where('status', LuggageDepositStatus::PRENOTATO)->count(),
                'attivi' => $active,
                'completati_oggi' => $completedToday,
            ],
            'source_split' => [
                'PORTALE' => $portalBookings,
                'SPORTELLO' => $sportelloBookings,
            ],
            'revenue_trend' => $revenueTrend,
            'upcoming' => $upcoming,
            'settings' => [
                'dailyRate' => (float) $settings->daily_rate,
                'currency' => $settings->currency,
            ],
            'status_breakdown' => $statusBreakdown,
            'recent' => $recent,
        ];
    }
}
