@extends('Backend._layout._main')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuovo {{ \App\Models\LockerPackage::NOME_SINGOLARE }}</h3>
        </div>
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="post" action="{{ action([$controller, 'store']) }}" id="package-create-form">@csrf
                <h4 class="mb-4">Destinatario</h4>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label required">Nome destinatario</label>
                        <input name="recipient_name" class="form-control form-control-solid" value="{{ old('recipient_name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email destinatario</label>
                        <input type="email" name="recipient_email" class="form-control form-control-solid" value="{{ old('recipient_email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefono destinatario</label>
                        <input name="recipient_phone" class="form-control form-control-solid" value="{{ old('recipient_phone') }}">
                    </div>
                </div>

                <div class="separator my-6"></div>
                <h4 class="mb-4">Mittente e corriere</h4>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Nome mittente</label>
                        <input name="sender_name" class="form-control form-control-solid" value="{{ old('sender_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefono mittente</label>
                        <input name="sender_phone" class="form-control form-control-solid" value="{{ old('sender_phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Corriere</label>
                        <input name="carrier" class="form-control form-control-solid" value="{{ old('carrier') }}" placeholder="es. BRT, GLS, Amazon">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Codice tracking</label>
                        <input name="tracking_code" class="form-control form-control-solid" value="{{ old('tracking_code') }}">
                    </div>
                </div>

                <div class="separator my-6"></div>
                <h4 class="mb-4">Ritiro</h4>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label required">Data ritiro prevista</label>
                        <input type="date" name="expected_pickup_date" id="expected_pickup_date" class="form-control form-control-solid" value="{{ old('expected_pickup_date', today()->addDay()->toDateString()) }}" required>
                        <div class="form-text">Quando il destinatario ritirerà il pacco in sportello.</div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light-primary d-flex align-items-center py-4 mb-0">
                            <div>
                                <div class="fw-bold">Tariffa giacenza</div>
                                <div class="fs-6">
                                    € {{ number_format($settings->daily_rate, 2, ',', '.') }} / giorno (min. {{ $settings->min_days }} {{ $settings->min_days === 1 ? 'giorno' : 'giorni' }})
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note</label>
                        <textarea name="notes" class="form-control form-control-solid" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="mt-6">
                    <button class="btn btn-primary">Registra pacco</button>
                    <a href="{{ action([$controller, 'index']) }}" class="btn btn-light ms-2">Annulla</a>
                </div>
            </form>
        </div>
    </div>
@endsection
