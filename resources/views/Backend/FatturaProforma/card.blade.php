<div id="" class="app-container container-xxl">
    <div class="card">
        <div class="card-body py-20">
            <div class="mw-lg-950px mx-auto w-100">
                <div class="d-flex justify-content-between flex-column flex-sm-row mb-19">
                    <div class="">
                        @isset($view)
                            <img alt="Logo" src="/loghi/logo-aziendale.png" class="h-60px"/>
                        @else
                            <img alt="Logo" src="{{ public_path('/loghi/logo-aziendale.png') }}" class="h-60px"/>
                        @endif
                        <div class="ms-2 fw-bolder fs-2 text-gray-800 mt-2">
                            <div>Gestiio</div>
                        </div>
                    </div>
                    <div class="text-sm-end">
                        <div class="text-sm-end mt-2">
                            <div class="text-gray-800 fs-1 fw-bolder">PRO FORMA #{{ $record->numero }}</div>
                            <div class="text-gray-700 fs-2 fw-bold">Data: {{ $record->data->format('d/m/Y') }}</div>
                            @if(method_exists($record, 'statusLabel'))
                                <div class="text-gray-600 fs-6 mt-1">Stato: {{ $record->statusLabel() }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pb-12">
                    <div class="d-flex flex-column gap-7 gap-md-10">
                        <div class="separator"></div>

                        <div class="d-flex flex-column flex-sm-row gap-7 gap-md-10 fw-bold">
                            <div class="flex-root d-flex flex-column">
                                <span class="text-gray-700">Spett.le</span>
                                <span class="fs-6">
                                    {{ $record->intestazione->denominazione }}
                                    @if($record->intestazione->codice_fiscale)
                                        <br/>{{ $record->intestazione->codice_fiscale }}
                                    @endif
                                    @if($record->intestazione->indirizzo)
                                        <br/>{{ $record->intestazione->indirizzo }}
                                    @endif
                                    @if($record->intestazione->cap || $record->intestazione->citta)
                                        <br/>{{ trim(($record->intestazione->cap ?? '').' '.($record->intestazione->citta ?? '')) }}
                                    @endif
                                    @if($record->intestazione->nazione)
                                        <br/>{{ $record->intestazione->nazione }}
                                    @endif
                                </span>
                            </div>
                            <div class="flex-root d-flex flex-column">
                                @if($record->periodoLabel())
                                    <span class="text-gray-700">Periodo di competenza</span>
                                    <span class="fs-6">{{ $record->periodoLabel() }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-between flex-column">
                            <div class="table-responsive border-bottom mb-9">
                                <table class="table table-row-dashed fs-6 gy-5 mb-0">
                                    <thead>
                                    <tr class="border-bottom fs-6 fw-bold text-gray-700">
                                        <th class="min-w-175px pb-2">Descrizione</th>
                                        <th class="min-w-80px text-end pb-2">Quantità</th>
                                        <th class="min-w-100px text-end pb-2">Importo</th>
                                    </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600">
                                    @foreach($record->righe as $riga)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $riga->descrizione }}</div>
                                                @if($riga->dettaglio)
                                                    <div class="fs-7 text-gray-700">{{ $riga->dettaglio }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ $riga->quantita }}</td>
                                            <td class="text-end">{{ importo($riga->imponibile) }}</td>
                                        </tr>
                                    @endforeach

                                    <tr>
                                        <td colspan="2" class="text-end">Imponibile</td>
                                        <td class="text-end">{{ importo($record->totale_imponibile) }}</td>
                                    </tr>
                                    @if((float)$record->aliquota_iva > 0)
                                        <tr>
                                            <td colspan="2" class="text-end">IVA {{ $record->aliquota_iva }}%</td>
                                            <td class="text-end">{{ importo(calcolaImposta($record->totale_imponibile, $record->aliquota_iva)) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td colspan="2" class="fs-3 text-dark fw-bold text-end">Totale</td>
                                        <td class="text-dark fs-3 fw-bolder text-end">{{ importo($record->totale_con_iva) }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
