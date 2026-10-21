@extends('Backend._layout._main')
@section('content')
    @php($vecchio = $record->id)
    <div class="card mb-5">
        <div class="card-body">
            <div class="text-muted fs-7 mb-4">
                Premio lump-sum a soglia contratti OK del mese. Separato dalle fasce listino (provvigione) e dal canone cliente.
                <a href="{{ action([\App\Http\Controllers\Backend\ListinoController::class, 'index']) }}">Vai ai listini</a>
            </div>
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{ action([$controller, $vecchio ? 'update' : 'store'], $record->id ?? '') }}" id="form-gara">
                @csrf
                @method($vecchio ? 'PATCH' : 'POST')

                <div class="row mb-4">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText', ['campo' => 'nome', 'testo' => 'Nome gara', 'required' => true])
                    </div>
                    <div class="col-md-3">
                        @include('Backend._inputs.inputSwitch', ['campo' => 'attivo', 'testo' => 'Attiva'])
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label required">Canale</label>
                        <select name="canale" id="canale" class="form-select form-select-solid" required>
                            @foreach(\App\Models\Gara::CANALI as $k => $label)
                                <option value="{{ $k }}" @selected(old('canale', $record->canale) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label required">Anno</label>
                        <input type="number" name="anno" class="form-control form-control-solid" required
                               value="{{ old('anno', $record->anno) }}"/>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label required">Mese</label>
                        <select name="mese" class="form-select form-select-solid" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected((int) old('mese', $record->mese) === $m)>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Ambito</label>
                        <select name="ambito" id="ambito" class="form-select form-select-solid" required>
                            @foreach(\App\Models\Gara::AMBITI as $k => $label)
                                <option value="{{ $k }}" @selected(old('ambito', $record->ambito) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-4 js-ambito-listino" style="{{ old('ambito', $record->ambito) === 'listino' ? '' : 'display:none' }}">
                    <div class="col-md-4">
                        <label class="form-label">Listino telefonia</label>
                        <select name="listino_id" class="form-select form-select-solid">
                            <option value="">—</option>
                            @foreach($listini as $l)
                                <option value="{{ $l->id }}" @selected((string) old('listino_id', $record->listino_id) === (string) $l->id)>{{ $l->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-4 js-ambito-agenti" style="{{ old('ambito', $record->ambito) === 'agenti' ? '' : 'display:none' }}">
                    <div class="col-md-8">
                        <label class="form-label">Affiliati</label>
                        <select name="agente_ids[]" class="form-select form-select-solid" multiple size="8">
                            @php($sel = old('agente_ids', $record->agenti->pluck('id')->all()))
                            @foreach($agentiSelect as $a)
                                <option value="{{ $a->id }}" @selected(in_array($a->id, $sel, false))>
                                    {{ $a->cognome }} {{ $a->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="separator my-6"></div>
                <h4 class="mb-4">Filtri contratti</h4>

                <div class="row mb-4 js-filtri-tel">
                    <div class="col-md-4">
                        <label class="form-label">Gestore telefonia</label>
                        <select name="gestore_id" id="gestore_tel" class="form-select form-select-solid js-gestore-tel">
                            <option value="">Tutti</option>
                            @foreach($gestoriTel as $g)
                                <option value="{{ $g->id }}" @selected((string) old('gestore_id', $record->canale === 'telefonia' ? $record->gestore_id : '') === (string) $g->id)>{{ $g->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Offerta telefonia</label>
                        <select name="telefonia_offerta_id" class="form-select form-select-solid">
                            <option value="">Tutte</option>
                            @foreach($offerteTel as $o)
                                <option value="{{ $o->id }}" data-gestore="{{ $o->gestore_id }}"
                                    @selected((string) old('telefonia_offerta_id', $record->telefonia_offerta_id) === (string) $o->id)>
                                    {{ $o->nome }}
                                    @if($o->prezzo_mese)
                                        — {{ number_format($o->prezzo_mese, 2, ',', '.') }} €/mese
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-4 js-filtri-en" style="display:none">
                    <div class="col-md-4">
                        <label class="form-label">Gestore energia</label>
                        <select name="gestore_id_en" id="gestore_en" class="form-select form-select-solid js-gestore-en">
                            <option value="">Tutti</option>
                            @foreach($gestoriEn as $g)
                                <option value="{{ $g->id }}" @selected((string) old('gestore_id', $record->canale === 'energia' ? $record->gestore_id : '') === (string) $g->id)>{{ $g->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo attivazione</label>
                        <input type="text" name="tipo_attivazione" class="form-control form-control-solid"
                               value="{{ old('tipo_attivazione', $record->tipo_attivazione) }}"
                               placeholder="es. switch, nuova_attivazione"/>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Business / Consumer</label>
                        <select name="is_business" class="form-select form-select-solid">
                            <option value="" @selected(old('is_business', $record->is_business) === null || old('is_business', $record->is_business) === '')>Tutti</option>
                            <option value="1" @selected((string) old('is_business', $record->is_business === null ? '' : (int) $record->is_business) === '1')>Solo business</option>
                            <option value="0" @selected((string) old('is_business', $record->is_business === null ? '' : (int) $record->is_business) === '0')>Solo consumer</option>
                        </select>
                    </div>
                </div>

                <div class="separator my-6"></div>
                <h4 class="mb-4">Soglie premio</h4>
                <div id="repeater-soglie">
                    <div data-repeater-list="soglie">
                        @if(($soglie ?? collect())->isEmpty())
                            @include('Backend.Gara.repeaterSoglia', ['record' => new \App\Models\GaraSoglia(['da_valore' => 1, 'premio' => 0])])
                        @else
                            @foreach($soglie as $s)
                                @include('Backend.Gara.repeaterSoglia', ['record' => $s])
                            @endforeach
                        @endif
                    </div>
                    <button data-repeater-create class="btn btn-primary btn-sm" type="button"><i class="fa fa-plus"></i> Aggiungi soglia</button>
                </div>

                <div class="row mt-8">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary" type="submit">{{ $vecchio ? 'Salva modifiche' : 'Crea gara' }}</button>
                    </div>
                    @if($vecchio && !empty($eliminabile))
                        <div class="col-md-4 text-end">
                            <a class="btn btn-danger" id="elimina" href="{{ action([$controller, 'destroy'], $record->id) }}">Elimina</a>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
@push('customScript')
    <script src="/assets_backend/plugins/custom/formrepeater/formrepeater.bundle.js"></script>
    <script>
        $(function () {
            if (typeof eliminaHandler === 'function') {
                eliminaHandler('Questa gara verrà eliminata definitivamente');
            }

            function syncCanale() {
                var c = $('#canale').val();
                $('.js-filtri-tel').toggle(c === 'telefonia');
                $('.js-filtri-en').toggle(c === 'energia');
                // un solo gestore_id nel POST
                if (c === 'energia') {
                    $('.js-gestore-tel').prop('disabled', true).attr('name', 'gestore_id_tel_disabled');
                    $('.js-gestore-en').prop('disabled', false).attr('name', 'gestore_id');
                } else {
                    $('.js-gestore-en').prop('disabled', true).attr('name', 'gestore_id_en_disabled');
                    $('.js-gestore-tel').prop('disabled', false).attr('name', 'gestore_id');
                }
            }

            function syncAmbito() {
                var a = $('#ambito').val();
                $('.js-ambito-listino').toggle(a === 'listino');
                $('.js-ambito-agenti').toggle(a === 'agenti');
            }

            $('#canale').on('change', syncCanale);
            $('#ambito').on('change', syncAmbito);
            syncCanale();
            syncAmbito();

            $('#repeater-soglie').repeater({
                show: function () { $(this).slideDown(); },
                hide: function (deleteElement) { $(this).slideUp(deleteElement); }
            });
        });
    </script>
@endpush
