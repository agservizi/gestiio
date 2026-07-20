@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <a href="{{ action([$controller, 'index']) }}" class="btn btn-sm btn-light">Elenco</a>
        <a href="{{ action([$controller, 'dashboard']) }}" class="btn btn-sm btn-light">Dashboard</a>
    </div>
@endsection

@section('content')
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $isEdit = (bool) $record;
        $action = $isEdit ? action([$controller, 'update'], $record) : action([$controller, 'store']);
        $applicant = old('applicant_type', $record?->applicant_type?->value ?? 'destinatario');
        $subj = fn($role, $field) => old("subjects.$role.$field", $record?->subjectByRole($role)?->{$field});
        $checkDone = $record?->checklistItems?->where('completed', true)->pluck('code')->all() ?? [];
        $operatorDocs = $record?->documents?->filter(fn($d) => $d->visibility !== 'citizen_receipt' && ! in_array($d->category?->value ?? (string) $d->category, ['risultato', 'ricevuta'], true)) ?? collect();
    @endphp

    <form method="post" action="{{ $action }}" id="send-form">
        @csrf

        <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
            <div>
                <div class="fw-bold fs-5">Importo per il cliente: {!! importo($prezzoCliente ?? 5, true) !!}</div>
                <div class="fs-7 mt-1">Da comunicare / applicare al cittadino allo sportello.</div>
            </div>
            <div class="text-end">
                <div class="fs-7 text-muted">Addebito plafond servizi</div>
                <div class="fw-bold">{!! importo($prezzoAgente ?? 4, true) !!}</div>
                @if(!($record ?? null))
                    <div class="fs-8 text-muted">Disponibili: {!! importo($portafoglioServizi ?? 0, true) !!}</div>
                @endif
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">1. Nominativo</h3></div>
            <div class="card-body row g-5">
                <div class="col-md-6">
                    <label class="form-label required">Tipologia richiedente</label>
                    <select name="applicant_type" id="applicant_type" class="form-select" required>
                        @foreach($applicantTypes as $at)
                            <option value="{{ $at->value }}" @selected($applicant === $at->value)>{{ $at->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        Verifica allo sportello: avviso SEND, documento d’identità, CF/tessera sanitaria;
                        in caso di delega anche delega + documenti del delegato; per imprese poteri di rappresentanza.
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-5 send-section" data-roles="destinatario,delegato">
            <div class="card-header"><h3 class="card-title">Destinatario persona fisica</h3></div>
            <div class="card-body row g-5">
                <input type="hidden" name="subjects[destinatario][subject_type]" value="persona">
                <div class="col-md-3"><label class="form-label">Nome</label><input class="form-control" name="subjects[destinatario][first_name]" value="{{ $subj('destinatario','first_name') }}"></div>
                <div class="col-md-3"><label class="form-label">Cognome</label><input class="form-control" name="subjects[destinatario][last_name]" value="{{ $subj('destinatario','last_name') }}"></div>
                <div class="col-md-3"><label class="form-label">Codice fiscale</label><input class="form-control" name="subjects[destinatario][tax_code]" value="{{ $subj('destinatario','tax_code') }}"></div>
                <div class="col-md-3"><label class="form-label">Telefono</label><input class="form-control" name="subjects[destinatario][phone]" value="{{ $subj('destinatario','phone') }}"></div>
            </div>
        </div>

        <div class="card mb-5 send-section" data-roles="delegato,delegato_impresa">
            <div class="card-header"><h3 class="card-title">Delegato</h3></div>
            <div class="card-body row g-5">
                <input type="hidden" name="subjects[delegato][subject_type]" value="persona">
                <div class="col-md-3"><label class="form-label">Nome</label><input class="form-control" name="subjects[delegato][first_name]" value="{{ $subj('delegato','first_name') }}"></div>
                <div class="col-md-3"><label class="form-label">Cognome</label><input class="form-control" name="subjects[delegato][last_name]" value="{{ $subj('delegato','last_name') }}"></div>
                <div class="col-md-3"><label class="form-label">Codice fiscale</label><input class="form-control" name="subjects[delegato][tax_code]" value="{{ $subj('delegato','tax_code') }}"></div>
                <div class="col-md-3"><label class="form-label">Rapporto</label><input class="form-control" name="subjects[delegato][relationship]" value="{{ $subj('delegato','relationship') }}"></div>
            </div>
        </div>

        <div class="card mb-5 send-section" data-roles="impresa,delegato_impresa">
            <div class="card-header"><h3 class="card-title">Impresa</h3></div>
            <div class="card-body row g-5">
                <input type="hidden" name="subjects[impresa][subject_type]" value="impresa">
                <div class="col-md-4"><label class="form-label">Denominazione</label><input class="form-control" name="subjects[impresa][business_name]" value="{{ $subj('impresa','business_name') }}"></div>
                <div class="col-md-4"><label class="form-label">Partita IVA</label><input class="form-control" name="subjects[impresa][vat_number]" value="{{ $subj('impresa','vat_number') }}"></div>
                <div class="col-md-4"><label class="form-label">CF impresa</label><input class="form-control" name="subjects[impresa][tax_code]" value="{{ $subj('impresa','tax_code') }}"></div>
            </div>
        </div>

        <div class="card mb-5 send-section" data-roles="impresa,delegato_impresa">
            <div class="card-header"><h3 class="card-title">Rappresentante</h3></div>
            <div class="card-body row g-5">
                <input type="hidden" name="subjects[rappresentante][subject_type]" value="persona">
                <div class="col-md-4"><label class="form-label">Nome</label><input class="form-control" name="subjects[rappresentante][first_name]" value="{{ $subj('rappresentante','first_name') }}"></div>
                <div class="col-md-4"><label class="form-label">Cognome</label><input class="form-control" name="subjects[rappresentante][last_name]" value="{{ $subj('rappresentante','last_name') }}"></div>
                <div class="col-md-4"><label class="form-label">Codice fiscale</label><input class="form-control" name="subjects[rappresentante][tax_code]" value="{{ $subj('rappresentante','tax_code') }}"></div>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">2. Checklist documenti</h3></div>
            <div class="card-body">
                <div class="row g-3" id="checklist-box">
                    @foreach($checklistLabels as $code => $label)
                        <div class="col-md-6 checklist-item" data-code="{{ $code }}">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="checklist[]" value="{{ $code }}"
                                    @checked(in_array($code, old('checklist', $checkDone), true))>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">3. Consensi e dichiarazioni</h3></div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" name="consents[identita_verificata]" value="1" @checked(old('consents.identita_verificata') || $record?->consents?->firstWhere('consent_type','identita_verificata')?->accepted)>
                        <span class="form-check-label">Confermo di aver verificato l’identità del richiedente e la corrispondenza dei documenti.</span>
                    </label>
                </div>
                <div class="col-12">
                    <label class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" name="consents[privacy]" value="1" required @checked(old('consents.privacy') || $record?->consents?->firstWhere('consent_type','privacy')?->accepted)>
                        <span class="form-check-label">Presa visione informativa privacy e autorizzazione al trattamento per la gestione della richiesta. <span class="text-danger">*</span></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">4. Allegati operatore</h3></div>
            <div class="card-body">
                @if($isEdit && $operatorDocs->isNotEmpty())
                    <div class="mb-4">
                        <div class="fw-semibold mb-2">Allegati già caricati</div>
                        @foreach($operatorDocs as $doc)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>{{ $doc->category->label() }} — {{ $doc->original_name }}</span>
                                <a href="{{ action([$controller, 'downloadDocument'], [$record, $doc]) }}">Scarica</a>
                            </div>
                        @endforeach
                    </div>
                @endif

                <input type="hidden" name="upload_uid" id="send_upload_uid" value="{{ $uploadUid ?? '' }}">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Categoria documento</label>
                        <select id="send_dropzone_category" class="form-select">
                            @foreach($documentCategories as $cat)
                                @if(! in_array($cat->value, ['risultato', 'ricevuta'], true))
                                    <option value="{{ $cat->value }}" @selected($cat->value === 'avviso_send')>{{ $cat->label() }}</option>
                                @endif
                            @endforeach
                        </select>
                        <div class="form-text">Avviso SEND, identità, CF, deleghe e altri documenti operatore.</div>
                    </div>
                </div>
                <div class="dropzone gestiio-dropzone" id="send_dropzone">
                    <div class="dz-message needsclick">
                        <span class="gestiio-dropzone-icon">
                            <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h3>Trascina i file qui o clicca per selezionarli</h3>
                            <span>Allegati per il supervisore (max 10 file).</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-light-info mb-4">
            La pratica parte subito verso il supervisore se checklist, consensi e allegati sono completi.
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Salva e invia</button>
            @if($isEdit)
                <a href="{{ action([$controller, 'show'], $record) }}" class="btn btn-light">Torna al dettaglio</a>
            @endif
        </div>
    </form>

    <script>
        (function () {
            const requiredByType = @json(collect($applicantTypes)->mapWithKeys(fn($t) => [$t->value => $t->requiredChecklistCodes()])->all());
            const always = ['identita_verificata', 'privacy'];
            const select = document.getElementById('applicant_type');
            function sync() {
                const type = select.value;
                document.querySelectorAll('.send-section').forEach(el => {
                    const roles = (el.dataset.roles || '').split(',');
                    el.style.display = roles.includes(type) ? '' : 'none';
                });
                const req = (requiredByType[type] || []).concat(always);
                document.querySelectorAll('.checklist-item').forEach(el => {
                    el.style.display = req.includes(el.dataset.code) ? '' : 'none';
                });
            }
            select.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection

@include('Backend._components.dropzoneUx')

@push('customScript')
    <script>
        $(function () {
            initGestiioDropzone('#send_dropzone', {
                uploadUrl: "{{ action([$controller, 'uploadAllegato']) }}",
                deleteUrl: "{{ action([$controller, 'deleteAllegato']) }}",
                csrfToken: "{{ csrf_token() }}",
                maxFiles: 10,
                maxFilesize: {{ max(1, (int) ceil(((int) config('send.max_upload_kb', 20480)) / 1024)) }},
                existingFiles: @json(\App\Models\SendRequestDocument::forBlade($uploadUid ?? null, $record->id ?? null)),
                sendingData: {
                    upload_uid: function () { return $('#send_upload_uid').val() || ''; },
                    send_uuid: @json($record?->uuid),
                    category: function () { return $('#send_dropzone_category').val() || 'altro'; }
                }
            });
        });
    </script>
@endpush
