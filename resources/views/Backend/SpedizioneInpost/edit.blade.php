@extends('Backend._layout._main')

@section('content')
    <div class="row">
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    @include('Backend._components.alertErrori')
                    @php
                        $originCountry = \App\Models\Nazione::find(config('services.inpost.default_country', 'IT'));
                        $destinationCountry = \App\Models\Nazione::find(old('nazione_destinazione', $record->nazione_destinazione ?: 'IT'));
                    @endphp
                    <form method="POST" action="{{$record->id ? action([\App\Http\Controllers\Backend\SpedizioneInpostController::class,'update'],$record->id) : action([\App\Http\Controllers\Backend\SpedizioneInpostController::class,'store'])}}" id="form-inpost">
                        @csrf
                        @method($record->id ? 'PATCH' : 'POST')

                        @if(Auth::user()->hasAnyPermission(['admin']))
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="agente_id" id="agente_id" value="{{old('agente_id',$record->agente_id)}}">
                        @endif

                        <input type="hidden" id="delivery_type" name="delivery_type" value="{{old('delivery_type',$record->delivery_type ?: 'point')}}">

                        <div class="inpost-section-card mb-8">
                            <div class="inpost-section-head">
                                <h3 class="inpost-section-title">Direzione</h3>
                                <p class="inpost-section-text">Seleziona i paesi di origine e destinazione per iniziare la spedizione.</p>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="inpost-field-label required">Da</label>
                                    <div class="inpost-country-display">
                                        <span class="inpost-flag" aria-hidden="true">🇮🇹</span>
                                        <span>{{$originCountry?->langIT ?? 'Italia'}}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputSelect2',['campo'=>'nazione_destinazione','testo'=>'A','required'=>true,'selected'=>\App\Models\Nazione::selected(old('nazione_destinazione',$record->nazione_destinazione ?: 'IT'))])
                                </div>
                            </div>
                        </div>

                        <div class="inpost-section-card mb-8">
                            <div class="inpost-section-head">
                                <h3 class="inpost-section-title">Note e tag</h3>
                                <p class="inpost-section-text">Lascia una nota interna e usa i tag per identificare la spedizione.</p>
                            </div>
                            <div class="mb-6">
                                <textarea name="altri_dati[internal_note]" class="form-control form-control-solid form-control-lg" rows="3" placeholder="Lascia una nota">{{old('altri_dati.internal_note', $record->altri_dati['internal_note'] ?? '')}}</textarea>
                            </div>
                            <div class="mb-2">
                                <input type="text" name="altri_dati[tags]" class="form-control form-control-solid form-control-lg" value="{{old('altri_dati.tags', $record->altri_dati['tags'] ?? '')}}" placeholder="Aggiungi tag (separati da virgole)">
                            </div>
                            <div class="text-muted fs-7">Nessun tag. Aggiungi tag per identificare facilmente le spedizioni.</div>
                        </div>

                        <div class="inpost-section-card mb-8">
                            <div class="inpost-section-head">
                                <h3 class="inpost-section-title">Consegna a</h3>
                                <p class="inpost-section-text">
                                    Paese di destinazione selezionato:
                                    <span class="fw-semibold">{{$destinationCountry?->langIT ?? 'Italia'}}</span>
                                </p>
                            </div>
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="inpost-choice-card {{old('delivery_type',$record->delivery_type) !== 'address' ? 'is-active' : ''}}" data-delivery-card="point">
                                        <input type="radio" class="d-none delivery-choice" name="delivery_type_choice" value="point" {{old('delivery_type',$record->delivery_type) !== 'address' ? 'checked' : ''}}>
                                        <span class="inpost-package-radio"></span>
                                        <span class="inpost-choice-title">Locker o punto di ritiro</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="inpost-choice-card {{old('delivery_type',$record->delivery_type) === 'address' ? 'is-active' : ''}}" data-delivery-card="address">
                                        <input type="radio" class="d-none delivery-choice" name="delivery_type_choice" value="address" {{old('delivery_type',$record->delivery_type) === 'address' ? 'checked' : ''}}>
                                        <span class="inpost-package-radio"></span>
                                        <span class="inpost-choice-title">Indirizzo del destinatario</span>
                                    </label>
                                </div>
                            </div>

                            <div class="row" id="point-search-row">
                                <div class="col-md-9">
                                    @include('Backend._inputs.inputTextButton',['campo'=>'punto_inpost_id','testo'=>'Locker o punto di ritiro','testoButton'=>'Mappa','classe'=>'cerca'])
                                </div>
                                <div class="col-md-3">
                                    @include('Backend._inputs.inputText',['campo'=>'punto_inpost_label','testo'=>'Dettaglio punto'])
                                </div>
                            </div>

                            <div id="address-destination-row">
                                <div class="row">
                                    <div class="col-md-8">
                                        @include('Backend._inputs.inputText',['campo'=>'indirizzo_destinatario','testo'=>'Indirizzo destinatario'])
                                    </div>
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'provincia_destinatario','testo'=>'Provincia'])
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'cap_destinatario','testo'=>'CAP / ZIP'])
                                    </div>
                                    <div class="col-md-4">
                                        @include('Backend._inputs.inputText',['campo'=>'localita_destinazione','testo'=>'Localita / City'])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="inpost-section-card mb-8">
                            <div class="inpost-section-head">
                                <h3 class="inpost-section-title">Invia da</h3>
                                <p class="inpost-section-text">Inserisci i dati del mittente usati per la spedizione.</p>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    @include('Backend._inputs.inputText',['campo'=>'nome_mittente','testo'=>'Mittente','required'=>true])
                                </div>
                                <div class="col-md-4">
                                    @include('Backend._inputs.inputText',['campo'=>'email_mittente','testo'=>'Email mittente'])
                                </div>
                                <div class="col-md-4">
                                    @include('Backend._inputs.inputText',['campo'=>'mobile_mittente','testo'=>'Telefono mittente'])
                                </div>
                            </div>
                        </div>

                        <div class="inpost-section-card mb-8">
                            <div class="inpost-section-head">
                                <h3 class="inpost-section-title">Destinatario</h3>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    @include('Backend._inputs.inputText',['campo'=>'ragione_sociale_destinatario','testo'=>'Destinatario','required'=>true])
                                </div>
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'email_destinatario','testo'=>'Email destinatario'])
                                </div>
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'mobile_referente_consegna','testo'=>'Numero di telefono','required'=>true])
                                </div>
                            </div>
                        </div>

                        @include('Backend.SpedizioneInpost.repeaterColli')

                        <input type="hidden" name="numero_pacchi" id="numero_pacchi" value="{{old('numero_pacchi',$record->numero_pacchi)}}">
                        <input type="hidden" name="peso_totale" id="peso_totale" value="{{old('peso_totale',$record->peso_totale)}}">
                        <input type="hidden" name="volume_totale" id="volume_totale" value="{{old('volume_totale',$record->volume_totale)}}">

                        <div class="row">
                            <div class="col-md-4 offset-md-4 text-center">
                                <button class="btn btn-primary mt-3" type="submit">{{$record->id ? 'Salva modifiche' : 'Crea spedizione InPost'}}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush">
                <div class="card-header"><h3 class="card-title">Riepilogo</h3></div>
                <div class="card-body">
                    <div class="bg-gray-100 mb-6 fw-bold">
                        Pacchi
                        <div id="numero_pacchi_dx" class="fw-bolder min-h-15px fs-4">{{\App\intero($record->numero_pacchi,true)}}</div>
                    </div>
                    <div class="bg-gray-100 mb-6 fw-bold">
                        Peso totale
                        <div id="peso_totale_dx" class="fw-bolder min-h-15px fs-4">{{$record->peso_totale}}</div>
                    </div>
                    <div class="bg-gray-100 mb-6 fw-bold">
                        Volume totale
                        <div id="volume_totale_span" class="fw-bolder min-h-15px fs-4">{{$record->volume_totale}}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('customCss')
    <style>
        .inpost-section-card {
            background: #fff;
            border: 1px solid #e1e3ea;
            border-radius: 14px;
            padding: 1.75rem;
        }

        .inpost-section-head {
            margin-bottom: 1.25rem;
        }

        .inpost-section-title {
            font-size: 2rem;
            font-weight: 700;
            color: #20242c;
            margin-bottom: .45rem;
        }

        .inpost-section-text {
            color: #5f6672;
            margin-bottom: 0;
        }

        .inpost-country-display {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-height: 54px;
            padding: .95rem 1rem;
            border: 1px solid #cfd3da;
            border-radius: 8px;
            background: #fff;
            font-size: 1.05rem;
        }

        .inpost-flag {
            font-size: 1.45rem;
            line-height: 1;
        }

        .inpost-choice-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
            min-height: 88px;
            padding: 1rem 1.25rem;
            border: 1px solid #cfd3da;
            background: #fff;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .inpost-choice-card.is-active {
            border-color: #f7c600;
            box-shadow: inset 0 0 0 2px #f7c600;
        }

        .inpost-choice-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #20242c;
        }

        .inpost-field-label {
            display: block;
            margin-bottom: .5rem;
            font-weight: 600;
            color: #4a5160;
        }

        .inpost-package-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            min-height: 110px;
            padding: 1rem 1.25rem;
            border: 1px solid #d7d7d7;
            background: #fff;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .inpost-package-card.is-active {
            border-color: #f7c600;
            box-shadow: inset 0 0 0 2px #f7c600;
        }

        .inpost-package-card.is-custom .inpost-package-title,
        .inpost-package-card.is-custom .inpost-package-subtitle {
            color: #b5b5c3;
        }

        .inpost-package-radio {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 2px solid #8a8d93;
            flex: 0 0 26px;
            position: relative;
            background: #fff;
        }

        .inpost-package-card.is-active .inpost-package-radio::after {
            content: "";
            position: absolute;
            inset: 5px;
            border-radius: 50%;
            background: #2f2f2f;
        }

        .inpost-package-content {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
        }

        .inpost-package-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #222;
        }

        .inpost-package-subtitle {
            color: #6c6f75;
            line-height: 1.45;
        }

        .inpost-package-icon {
            flex: 0 0 56px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endpush

@push('customScript')
    <script>
        $(function () {
            if ($('#agente_id').length && $('#agente_id').is('select')) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }
            if ($('#nazione_destinazione').length) {
                select2UniversaleBackend('nazione_destinazione', 'una nazione', 1);
            }

            var packagePresets = {
                small: {altezza: 8, larghezza: 38, profondita: 64, peso_reale: 25},
                medium: {altezza: 19, larghezza: 38, profondita: 64, peso_reale: 25},
                large: {altezza: 41, larghezza: 38, profondita: 64, peso_reale: 25}
            };

            $(document).on('keyup change', '.ricalcola', aggiornaRiepilogo);
            $(document).on('change', '.package-choice', handlePackageChoice);
            $(document).on('change', '.delivery-choice', handleDeliveryChoice);
            togglePointFields();
            syncPackageFields();
            aggiornaRiepilogo();

            $(document).on('click', '#button-punto_inpost_id', function (e) {
                e.preventDefault();
                var nazione = $('#nazione_destinazione').val();
                var cap = $('#cap_destinatario').val();
                var citta = $('#localita_destinazione').val();
                var url = '{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['inpost_points'])}}?nazione=' + encodeURIComponent(nazione || '') + '&cap=' + encodeURIComponent(cap || '') + '&citta=' + encodeURIComponent(citta || '');
                apriModal(url);
            });

            function togglePointFields() {
                var isPoint = $('#delivery_type').val() === 'point';
                $('#point-search-row').toggle(isPoint);
                $('#address-destination-row').toggle(!isPoint);

                $('#indirizzo_destinatario').prop('required', !isPoint);
                $('#cap_destinatario').prop('required', !isPoint);
                $('#localita_destinazione').prop('required', !isPoint);
                $('#punto_inpost_id').prop('required', isPoint);

                $('#label_indirizzo_destinatario').toggleClass('required', !isPoint);
                $('#label_cap_destinatario').toggleClass('required', !isPoint);
                $('#label_localita_destinazione').toggleClass('required', !isPoint);
                $('#label_punto_inpost_id').toggleClass('required', isPoint);
            }

            function handleDeliveryChoice() {
                $('[data-delivery-card]').removeClass('is-active');
                $('[data-delivery-card="' + $(this).val() + '"]').addClass('is-active');
                $('#delivery_type').val($(this).val());
                togglePointFields();
            }

            function handlePackageChoice() {
                $('[data-package-card]').removeClass('is-active');
                $('[data-package-card="' + $(this).val() + '"]').addClass('is-active');
                syncPackageFields();
                aggiornaRiepilogo();
            }

            function syncPackageFields() {
                var selected = $('.package-choice:checked').val() || 'small';
                var preset = packagePresets[selected] || null;
                var isCustom = selected === 'custom';

                $('#custom-package-fields').toggle(isCustom);

                if (!isCustom && preset) {
                    $('#dati_colli_0_altezza').val(preset.altezza);
                    $('#dati_colli_0_larghezza').val(preset.larghezza);
                    $('#dati_colli_0_profondita').val(preset.profondita);
                    $('#dati_colli_0_peso_reale').val(preset.peso_reale);
                } else {
                    $('#dati_colli_0_altezza').val($('#custom_altezza').val());
                    $('#dati_colli_0_larghezza').val($('#custom_larghezza').val());
                    $('#dati_colli_0_profondita').val($('#custom_profondita').val());
                    $('#dati_colli_0_peso_reale').val($('#custom_peso_reale').val());
                }
            }

            function aggiornaRiepilogo() {
                syncPackageFields();

                var conteggioColli = 1;
                var volumeTotale = 0;
                var pesoTotale = 0;
                var larghezza = parseNumero($('#dati_colli_0_larghezza').val());
                var altezza = parseNumero($('#dati_colli_0_altezza').val());
                var profondita = parseNumero($('#dati_colli_0_profondita').val());
                var pesoReale = parseNumero($('#dati_colli_0_peso_reale').val());
                var volume = larghezza / 100 * altezza / 100 * profondita / 100;
                var pesoVolumetrico = larghezza * altezza * profondita / 4000;

                volumeTotale += volume;
                pesoTotale += (pesoReale > pesoVolumetrico ? pesoReale : pesoVolumetrico);
                $('#dati_colli_0_peso_volumetrico').val(pesoVolumetrico.toFixed(1).replace('.', ','));

                $('#numero_pacchi').val(conteggioColli);
                $('#numero_pacchi_dx').text(conteggioColli);
                $('#peso_totale').val(new Intl.NumberFormat('it-IT', {minimumFractionDigits: 1, maximumFractionDigits: 1}).format(pesoTotale));
                $('#peso_totale_dx').text(new Intl.NumberFormat('it-IT', {minimumFractionDigits: 1, maximumFractionDigits: 1}).format(pesoTotale));
                $('#volume_totale').val(new Intl.NumberFormat('it-IT', {minimumFractionDigits: 3, maximumFractionDigits: 3}).format(volumeTotale));
                $('#volume_totale_span').text(new Intl.NumberFormat('it-IT', {minimumFractionDigits: 3, maximumFractionDigits: 3}).format(volumeTotale));
            }

            function parseNumero(value) {
                if (typeof value === 'number') {
                    return isNaN(value) ? 0 : value;
                }

                var normalized = String(value || '')
                    .replace(/\./g, '')
                    .replace(',', '.')
                    .replace(/[^0-9.\-]/g, '');
                var parsed = parseFloat(normalized);

                return isNaN(parsed) ? 0 : parsed;
            }
        });
    </script>
@endpush
