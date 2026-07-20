@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <a href="{{ action([$controller, 'dashboard']) }}" class="btn btn-sm btn-light">Dashboard</a>
        @can('create', \App\Models\SendRequest::class)
            <a href="{{ action([$controller, 'create']) }}" class="btn btn-sm btn-primary">Nuova</a>
        @endcan
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-5">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Ricerca</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Codice, nominativo, CF, IUN">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stato</label>
                    <select name="status" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Priorità</label>
                    <select name="priority" class="form-select">
                        <option value="">Tutte</option>
                        @foreach($priorities as $pr)
                            <option value="{{ $pr->value }}" @selected(($filters['priority'] ?? '') === $pr->value)>{{ $pr->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipologia</label>
                    <select name="applicant_type" class="form-select">
                        <option value="">Tutte</option>
                        @foreach($applicantTypes as $at)
                            <option value="{{ $at->value }}" @selected(($filters['applicant_type'] ?? '') === $at->value)>{{ $at->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Dal</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Al</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100" type="submit">Filtra</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-row-bordered align-middle gs-3">
                <thead>
                <tr class="fw-bold text-muted">
                    <th>Codice</th>
                    <th>Stato</th>
                    <th>Priorità</th>
                    <th>Cliente</th>
                    <th>Tipologia</th>
                    <th>Soggetto</th>
                    <th>Avviso</th>
                    <th>Creata</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($records as $row)
                    @php
                        $dest = $row->subjects->firstWhere('subject_role', 'destinatario')
                            ?? $row->subjects->firstWhere('subject_role', 'impresa')
                            ?? $row->subjects->first();
                        $name = $dest?->displayName() ?: '—';
                        $cf = $dest?->tax_code ? $audit->mask($dest->tax_code) : '—';
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $row->request_number }}</td>
                        <td><span class="badge {{ $row->status->badgeClass() }}">{{ $row->status->label() }}</span></td>
                        <td><span class="badge {{ $row->priority->badgeClass() }}">{{ $row->priority->label() }}</span></td>
                        <td class="fw-bold text-primary">{!! importo($row->prezzo_cliente ?? 5, true) !!}</td>
                        <td>{{ $row->applicant_type->label() }}</td>
                        <td>{{ $name }} <span class="text-muted fs-8">{{ $cf }}</span></td>
                        <td>{{ $row->send_notice_identifier ?: ($row->iun ?: '—') }}</td>
                        <td>{{ $row->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-end text-nowrap">
                            <div class="btn-group" role="group">
                                @can('downloadClientDocument', $row)
                        @php $clientDocCount = (int) ($row->documents_for_client_count ?? $row->documentsForClient->count()); @endphp
                        @if($clientDocCount === 1)
                            @php $onlyClientDoc = $row->documentsForClient->first(); @endphp
                            <a href="{{ action([$controller, 'downloadDocument'], [$row, $onlyClientDoc]) }}"
                               class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                               data-bs-toggle="tooltip" title="Scarica allegato SEND">
                                <span class="svg-icon svg-icon-muted svg-icon-1">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17 11H7C6.4 11 6 10.6 6 10V9C6 8.4 6.4 8 7 8H17C17.6 8 18 8.4 18 9V10C18 10.6 17.6 11 17 11ZM22 5V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4V5C2 5.6 2.4 6 3 6H21C21.6 6 22 5.6 22 5Z" fill="currentColor"/>
                                        <path opacity="0.3" d="M21 16H3C2.4 16 2 15.6 2 15V14C2 13.4 2.4 13 3 13H21C21.6 13 22 13.4 22 14V15C22 15.6 21.6 16 21 16ZM18 20V19C18 18.4 17.6 18 17 18H7C6.4 18 6 18.4 6 19V20C6 20.6 6.4 21 7 21H17C17.6 21 18 20.6 18 20Z" fill="currentColor"/>
                                    </svg>
                                </span>
                            </a>
                        @elseif($clientDocCount > 1)
                            <button type="button"
                                    class="btn btn-icon btn-sm btn-light btn-active-light-primary position-relative"
                                    data-bs-toggle="modal"
                                    data-bs-target="#clientDocsModal{{ $row->id }}"
                                    title="Scarica allegati SEND ({{ $clientDocCount }})">
                                <span class="svg-icon svg-icon-muted svg-icon-1">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17 11H7C6.4 11 6 10.6 6 10V9C6 8.4 6.4 8 7 8H17C17.6 8 18 8.4 18 9V10C18 10.6 17.6 11 17 11ZM22 5V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4V5C2 5.6 2.4 6 3 6H21C21.6 6 22 5.6 22 5Z" fill="currentColor"/>
                                        <path opacity="0.3" d="M21 16H3C2.4 16 2 15.6 2 15V14C2 13.4 2.4 13 3 13H21C21.6 13 22 13.4 22 14V15C22 15.6 21.6 16 21 16ZM18 20V19C18 18.4 17.6 18 17 18H7C6.4 18 6 18.4 6 19V20C6 20.6 6.4 21 7 21H17C17.6 21 18 20.6 18 20Z" fill="currentColor"/>
                                    </svg>
                                </span>
                                <span class="position-absolute top-0 start-100 translate-middle badge badge-circle badge-primary">{{ $clientDocCount }}</span>
                            </button>
                            <div class="modal fade" id="clientDocsModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Allegati SEND — {{ $row->request_number }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                                        </div>
                                        <div class="modal-body">
                                            @foreach($row->documentsForClient as $clientDoc)
                                                <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                                    <div>
                                                        <div class="fw-semibold">{{ $clientDoc->original_name }}</div>
                                                        <div class="text-muted fs-7">{{ number_format($clientDoc->size/1024,1) }} KB</div>
                                                    </div>
                                                    @can('downloadDocument', $clientDoc)
                                                        <a class="btn btn-sm btn-primary" href="{{ action([$controller, 'downloadDocument'], [$row, $clientDoc]) }}">Scarica</a>
                                                    @endcan
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                                @endcan
                                @can('claim', $row)
                                    <form method="post" action="{{ action([$controller, 'claim'], $row) }}" class="d-inline">@csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Assegna a me</button>
                                    </form>
                                @endcan
                                <a class="btn btn-sm btn-light" href="{{ action([$controller, 'show'], $row) }}">Apri</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-10">Nessuna richiesta trovata.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $records->links() }}
        </div>
    </div>
@endsection
