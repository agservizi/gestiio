@extends('Backend._layout._main')

@section('content')
    @include('Backend._components.alertMessage')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-6">
        <div class="text-muted fs-7">
            Conferma attivazione bonus referral · scatta solo con ≥2 energia OK + ≥1 telefonia OK (esito liquidabile) · ricontrollo soglia in conferma · non mescolato al plafond
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach([
                'pending' => ['Pending', 'warning', $counts['pending'] ?? 0],
                'credited' => ['Accreditati', 'success', $counts['credited'] ?? 0],
                'cancelled' => ['Annullati', 'danger', $counts['cancelled'] ?? 0],
                'all' => ['Tutti', 'primary', null],
            ] as $key => $meta)
                <a href="{{ action([\App\Http\Controllers\Backend\AdminReferralController::class, 'index'], ['stato' => $key]) }}"
                   class="btn btn-sm btn-light-{{ $meta[1] }} {{ ($stato ?? '') === $key ? 'active' : '' }}">
                    {{ $meta[0] }}@if($meta[2] !== null) ({{ $meta[2] }})@endif
                </a>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body pt-6">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gs-0 gy-3">
                    <thead>
                    <tr class="fw-bold text-muted">
                        <th>Invitante</th>
                        <th>Nuovo affiliato</th>
                        <th>Importo</th>
                        <th>Stato</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($bonuses as $b)
                        <tr>
                            <td>
                                @if($b->referrer)
                                    <a href="{{ action([\App\Http\Controllers\Backend\AgenteController::class, 'show'], $b->referrer_user_id) }}"
                                       class="fw-bold text-gray-900 text-hover-primary">
                                        {{ trim(($b->referrer->nome ?? '').' '.($b->referrer->cognome ?? '')) ?: $b->referrer->email }}
                                    </a>
                                    <div class="text-muted fs-8">{{ $b->referrer->codiceAgente() }}</div>
                                @else
                                    #{{ $b->referrer_user_id }}
                                @endif
                            </td>
                            <td>
                                @if($b->referred)
                                    <a href="{{ action([\App\Http\Controllers\Backend\AgenteController::class, 'show'], $b->referred_user_id) }}"
                                       class="fw-bold text-gray-900 text-hover-primary">
                                        {{ trim(($b->referred->nome ?? '').' '.($b->referred->cognome ?? '')) ?: $b->referred->email }}
                                    </a>
                                    <div class="text-muted fs-8">
                                        {{ $b->referred->email }}
                                        @if($b->referred->email_verified_at)
                                            · <span class="text-success">email ok</span>
                                        @else
                                            · <span class="text-warning">email da confermare</span>
                                        @endif
                                    </div>
                                @else
                                    #{{ $b->referred_user_id }}
                                @endif
                            </td>
                            <td class="fw-bold">{{ number_format((float) $b->importo, 2, ',', '.') }} €</td>
                            <td>
                                <span class="badge badge-light-{{ $b->stato === 'credited' ? 'success' : ($b->stato === 'cancelled' ? 'danger' : 'warning') }}">
                                    {{ $b->stato }}
                                </span>
                            </td>
                            <td>{{ optional($b->created_at)?->format('d/m/Y H:i') }}</td>
                            <td class="text-end text-nowrap">
                                @if($b->stato === 'pending')
                                    <form method="POST" action="{{ action([\App\Http\Controllers\Backend\AdminReferralController::class, 'credit'], $b->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"
                                                onclick="return confirm('Confermare accredito bonus referral? (non va sul plafond)')">
                                            Conferma attivazione
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ action([\App\Http\Controllers\Backend\AdminReferralController::class, 'cancel'], $b->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light-danger"
                                                onclick="return confirm('Annullare questo bonus?')">
                                            Annulla
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Nessun bonus in questo filtro.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $bonuses->links() }}</div>
        </div>
    </div>
@endsection
