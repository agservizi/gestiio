@extends('Backend._layout._main')

@section('content')
    <div class="card mw-800px">
        <div class="card-header">
            <h3 class="card-title">Nuovo {{ \App\Models\LuggageDeposit::NOME_SINGOLARE }}</h3>
        </div>
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="post" action="{{ action([$controller,'store']) }}" id="deposit-create-form">@csrf
                <h4 class="mb-4">Cliente</h4>
                @include('Backend._inputs.inputSelect2', [
                    'campo' => 'cliente_id',
                    'testo' => 'Cliente CRM (opzionale)',
                    'col' => 4,
                    'selected' => \App\Models\Cliente::selected(old('cliente_id')),
                ])
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label required">Nome cliente</label>
                        <input name="customer_name" class="form-control form-control-solid" value="{{ old('customer_name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="customer_email" class="form-control form-control-solid" value="{{ old('customer_email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefono</label>
                        <input name="customer_phone" class="form-control form-control-solid" value="{{ old('customer_phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Numero borse</label>
                        <input type="number" name="bag_count" id="bag_count" class="form-control form-control-solid" value="{{ old('bag_count', 1) }}" min="1" max="{{ $settings->max_bags_per_booking }}">
                    </div>
                </div>

                <div class="separator my-6"></div>
                <h4 class="mb-4">Custodia bagagli</h4>
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label required">Data deposito</label>
                        <input type="date" name="booking_date" id="booking_date" class="form-control form-control-solid" value="{{ old('booking_date', today()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Check-in previsto</label>
                        <input type="date" name="expected_check_in" id="expected_check_in" class="form-control form-control-solid" value="{{ old('expected_check_in', today()->toDateString()) }}">
                        <div class="form-text">Quando il cliente consegna i bagagli in sportello.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Check-out previsto (ritiro)</label>
                        <input type="date" name="expected_check_out" id="expected_check_out" class="form-control form-control-solid" value="{{ old('expected_check_out', today()->addDay()->toDateString()) }}" required>
                        <div class="form-text">Data prevista di ritiro bagagli.</div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light-primary d-flex align-items-center py-4 mb-0">
                            <div>
                                <div class="fw-bold">Stima importo custodia</div>
                                <div class="fs-6" id="pricing-preview">
                                    € {{ number_format($settings->daily_rate, 2, ',', '.') }} × 1 giorno × 1 borsa
                                    = <strong>€ {{ number_format($settings->daily_rate, 2, ',', '.') }}</strong>
                                </div>
                                <div class="text-muted fs-7 mt-1">Tariffa € {{ number_format($settings->daily_rate, 2, ',', '.') }} / borsa / giorno (o frazione).</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note</label>
                        <textarea name="notes" class="form-control form-control-solid" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="mt-6">
                    <button class="btn btn-primary">Crea prenotazione sportello</button>
                    <a href="{{ action([$controller,'index']) }}" class="btn btn-light ms-2">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('customScript')
    <script>
        $('#cliente_id').select2({
            placeholder: 'Collega cliente esistente',
            allowClear: true,
            minimumInputLength: 1,
            width: '100%',
            ajax: {
                url: '{{ action([\App\Http\Controllers\Backend\Select2::class, 'response']) }}?cliente_id',
                dataType: 'json',
                delay: 150,
                data: params => ({ term: params.term }),
                processResults: data => ({ results: data })
            }
        });

        (function () {
            const dailyRate = {{ json_encode((float) $settings->daily_rate) }};
            const minDays = {{ json_encode((int) $settings->min_days) }};
            const bookingDate = document.getElementById('booking_date');
            const checkInDate = document.getElementById('expected_check_in');
            const checkOutDate = document.getElementById('expected_check_out');
            const bagCountInput = document.getElementById('bag_count');
            const preview = document.getElementById('pricing-preview');

            function daysBetween(start, end) {
                if (!start || !end) return minDays;
                const a = new Date(start + 'T00:00:00');
                const b = new Date(end + 'T00:00:00');
                const diff = Math.ceil((b - a) / (1000 * 60 * 60 * 24));
                return Math.max(minDays, diff > 0 ? diff : minDays);
            }

            function formatEuro(value) {
                return value.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function updatePreview() {
                const bags = Math.max(1, parseInt(bagCountInput.value || '1', 10));
                const start = checkInDate.value || bookingDate.value;
                const end = checkOutDate.value || start;
                const days = daysBetween(start, end);
                const total = dailyRate * days * bags;
                preview.innerHTML = `€ ${formatEuro(dailyRate)} × ${days} giorno/i × ${bags} borsa/e = <strong>€ ${formatEuro(total)}</strong>`;
            }

            bookingDate.addEventListener('change', function () {
                if (!checkInDate.value || checkInDate.value < bookingDate.value) {
                    checkInDate.value = bookingDate.value;
                }
                if (!checkOutDate.value || checkOutDate.value < bookingDate.value) {
                    const next = new Date(bookingDate.value + 'T00:00:00');
                    next.setDate(next.getDate() + 1);
                    checkOutDate.value = next.toISOString().slice(0, 10);
                }
                checkOutDate.min = bookingDate.value;
                checkInDate.min = bookingDate.value;
                updatePreview();
            });

            [checkInDate, checkOutDate, bagCountInput].forEach(function (el) {
                el.addEventListener('change', updatePreview);
                el.addEventListener('input', updatePreview);
            });

            checkOutDate.min = bookingDate.value;
            checkInDate.min = bookingDate.value;
            updatePreview();
        })();
    </script>
@endpush
