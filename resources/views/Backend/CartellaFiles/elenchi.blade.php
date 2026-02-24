@php($canManageFolders = $canManageFolders ?? false)
@php($canDeleteFiles = $canDeleteFiles ?? false)
@php($canUploadFiles = $canUploadFiles ?? false)
@php($stats = $stats ?? ['conteggio_file' => $files->count()])

<div class="d-flex flex-stack flex-wrap gap-3 mb-4">
    <div class="badge badge-lg badge-light-primary">
        <div class="d-flex align-items-center flex-wrap">
            <a class="cartella" href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index']) }}">Root</a>
            @foreach($cartellePrev as $prev)
                <span class="svg-icon svg-icon-2x svg-icon-primary mx-1">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.6343 12.5657L8.45001 16.75C8.0358 17.1642 8.0358 17.8358 8.45001 18.25C8.86423 18.6642 9.5358 18.6642 9.95001 18.25L15.4929 12.7071C15.8834 12.3166 15.8834 11.6834 15.4929 11.2929L9.95001 5.75C9.5358 5.33579 8.86423 5.33579 8.45001 5.75C8.0358 6.16421 8.0358 6.83579 8.45001 7.25L12.6343 11.4343C12.9467 11.7467 12.9467 12.2533 12.6343 12.5657Z" fill="currentColor"/>
                    </svg>
                </span>
                <a class="cartella" href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index'],$prev->id) }}">{{ $prev->nome }}</a>
            @endforeach
        </div>
    </div>

    <div class="badge badge-lg badge-primary">
        <span id="kt_file_manager_items_counter">{{ $stats['conteggio_file'] ?? $files->count() }} file</span>
    </div>
</div>

<div class="table-responsive">
    <table id="kt_file_manager_list" data-kt-filemanager-table="files" class="table align-middle table-row-dashed fs-6">
        <thead>
        <tr class="text-start text-gray-900 fw-bold fs-7 text-uppercase gs-0">
            <th class="w-35px">
                <div class="form-check form-check-sm form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" id="select-files-page"/>
                </div>
            </th>
            <th class="min-w-250px">Nome</th>
            <th class="min-w-80px">Tipo</th>
            <th class="min-w-140px">Categoria</th>
            <th class="min-w-160px">Tag</th>
            <th class="min-w-10px">Dimensione</th>
            <th class="min-w-125px">Caricato il</th>
            <th class="w-125px"></th>
        </tr>
        </thead>
        <tbody class="fw-semibold text-gray-600">
        @forelse($cartelle as $cartella)
            <tr>
                <td></td>
                <td data-order="account">
                    <div class="d-flex align-items-center">
                        <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M10 4H21C21.6 4 22 4.4 22 5V7H10V4Z" fill="currentColor"></path>
                                <path d="M9.2 3H3C2.4 3 2 3.4 2 4V19C2 19.6 2.4 20 3 20H21C21.6 20 22 19.6 22 19V7C22 6.4 21.6 6 21 6H12L10.4 3.60001C10.2 3.20001 9.7 3 9.2 3Z" fill="currentColor"></path>
                            </svg>
                        </span>
                        <a href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index'],$cartella->id) }}"
                           class="cartella text-gray-800 text-hover-primary">{{ $cartella->nome }}</a>
                    </div>
                </td>
                <td><span class="badge badge-light-primary">cartella</span></td>
                <td>-</td>
                <td>-</td>
                <td>{{ $cartella->files_count }} file</td>
                <td>-</td>
                <td class="text-end" data-kt-filemanager-table="action_dropdown">
                    @if($canManageFolders)
                        <div class="d-inline-flex align-items-center justify-content-end gap-2 text-nowrap">
                            <button type="button" class="btn btn-sm btn-icon btn-light-info folder-move" data-folder-id="{{ $cartella->id }}" title="Sposta">
                                <span class="svg-icon svg-icon-4 m-0">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L15 5H13V9H11V5H9L12 2Z" fill="currentColor"/>
                                        <path d="M22 12L19 15V13H15V11H19V9L22 12Z" fill="currentColor"/>
                                        <path d="M12 22L9 19H11V15H13V19H15L12 22Z" fill="currentColor"/>
                                        <path d="M2 12L5 9V11H9V13H5V15L2 12Z" fill="currentColor"/>
                                    </svg>
                                </span>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-light-warning folder-visibility" data-folder-id="{{ $cartella->id }}" data-folder-roles="{{ implode(',', (array)($cartella->visibilita_ruoli ?? [])) }}" title="Visibilità">
                                <span class="svg-icon svg-icon-4 m-0">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.3" d="M2 12C4.5 7.5 8 5 12 5C16 5 19.5 7.5 22 12C19.5 16.5 16 19 12 19C8 19 4.5 16.5 2 12Z" fill="currentColor"/>
                                        <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                    </svg>
                                </span>
                            </button>
                            <a href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class,'edit'],['cartellaId'=>$cartellaId,'cartella'=>$cartella->id]) }}"
                               class="btn btn-sm btn-icon btn-light-primary" data-target="kt_modal" data-toggle="modal-ajax" title="Modifica">
                                <span class="svg-icon svg-icon-4 m-0">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.3" d="M3 17.25V21H6.75L17.81 9.94L14.06 6.19L3 17.25Z" fill="currentColor"/>
                                        <path d="M20.71 7.04C21.1 6.65 21.1 6.02 20.71 5.63L18.37 3.29C17.98 2.9 17.35 2.9 16.96 3.29L15.13 5.12L18.88 8.87L20.71 7.04Z" fill="currentColor"/>
                                    </svg>
                                </span>
                            </a>
                        </div>
                    @endif
                </td>
            </tr>
        @empty
        @endforelse

        @forelse($files as $record)
            @php($tags = is_array($record->tags_documentali) ? $record->tags_documentali : (json_decode($record->tags_documentali ?? '[]', true) ?: []))
            <tr id="file_{{ $record->id }}">
                <td>
                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input file-select" type="checkbox" value="{{ $record->id }}"/>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z" fill="currentColor"/>
                                <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <a href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class,'download'],$record->id) }}"
                           class="text-gray-800 text-hover-primary">{{ $record->filename_originale }}</a>
                    </div>
                    <div class="text-muted fs-8">#{{ $record->id }} | v{{ (int)($record->versione ?? 1) }}</div>
                    @if($record->expires_at)
                        <div class="fs-8 {{ $record->expires_at->isPast() ? 'text-danger' : 'text-warning' }}">
                            Scadenza: {{ $record->expires_at->format('d/m/Y') }}
                        </div>
                    @endif
                </td>
                <td><span class="badge badge-light">{{ strtoupper($record->tipo_file) }}</span></td>
                <td>
                    @if($record->categoria_documentale)
                        <span class="badge badge-light-info">{{ $record->categoria_documentale }}</span>
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if(count($tags))
                        @foreach(array_slice($tags, 0, 3) as $tag)
                            <span class="badge badge-light me-1">{{ $tag }}</span>
                        @endforeach
                    @else
                        -
                    @endif
                </td>
                <td>{{ \App\humanFileSize($record->dimensione_file) }}</td>
                <td>{{ $record->created_at->format('d/m/Y H:i') }}</td>
                <td class="text-end" data-kt-filemanager-table="action_dropdown">
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                        <a href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class,'preview'],$record->id) }}" target="_blank"
                           class="btn btn-sm btn-light-info">Anteprima</a>
                        <a href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class,'download'],$record->id) }}"
                           class="btn btn-sm btn-light-primary">Scarica</a>
                        @if($canManageFolders)
                            <button type="button" class="btn btn-sm btn-light-dark file-rename"
                                    data-file-id="{{ $record->id }}"
                                    data-file-name="{{ e($record->filename_originale) }}">Rinomina</button>
                            <button type="button" class="btn btn-sm btn-light-secondary file-move"
                                    data-file-id="{{ $record->id }}">Sposta</button>
                            <button type="button" class="btn btn-sm btn-light-warning file-expiry"
                                    data-file-id="{{ $record->id }}"
                                    data-file-expiry="{{ $record->expires_at?->format('Y-m-d') }}">Scadenza</button>
                            <button type="button" class="btn btn-sm btn-light-success file-share"
                                    data-file-id="{{ $record->id }}">Share</button>
                        @endif
                        @if($canUploadFiles)
                            <button type="button" class="btn btn-sm btn-light-primary file-version"
                                    data-file-id="{{ $record->id }}">Nuova v.</button>
                            <button type="button" class="btn btn-sm btn-light-warning file-rollback"
                                    data-file-id="{{ $record->id }}">Rollback</button>
                        @endif
                        @if($canDeleteFiles)
                            <a href="{{ action([\App\Http\Controllers\Backend\CartellaFilesController::class,'cancellaFile'],['id'=>$record->id]) }}"
                               class="elimina-file btn btn-sm btn-light-danger">Elimina</a>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
        @endforelse

        @if($cartelle->isEmpty() && $files->isEmpty())
            <tr>
                <td colspan="8" class="text-center py-10 text-muted">Nessun risultato con i filtri correnti.</td>
            </tr>
        @endif
        </tbody>
    </table>
</div>
