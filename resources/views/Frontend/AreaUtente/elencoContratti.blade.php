<div id="kt_engage_demos" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="explore" data-kt-drawer-activate="true" data-kt-drawer-overlay="true"
     data-kt-drawer-width="{default:'350px', 'lg': '550px'}" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_engage_demos_toggle"
     data-kt-drawer-close="#kt_engage_demos_close">
    <!--begin::Card-->
    <div class="card shadow-none rounded-0 w-100">
        <!--begin::Header-->
        <div class="card-header" id="kt_engage_demos_header">
            <h3 class="card-title fw-bold text-gray-900">I tuoi contratti</h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-icon btn-active-color-primary h-40px w-40px me-n6" id="kt_engage_demos_close">
                    <!--begin::Svg Icon | path: icons/duotune/arrows/arr061.svg-->
                    <span class="svg-icon svg-icon-2">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"/>
									<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"/>
								</svg>
							</span>
                    <!--end::Svg Icon-->
                </button>
            </div>
        </div>
        <div class="card-body" id="kt_engage_demos_body">
            <div id="kt_explore_scroll" class="scroll-y me-n5 pe-5" data-kt-scroll="true" data-kt-scroll-height="auto" data-kt-scroll-wrappers="#kt_engage_demos_body"
                 data-kt-scroll-dependencies="#kt_engage_demos_header" data-kt-scroll-offset="5px">
                <div class="mb-0">
                    @foreach(\App\Models\ContrattoTelefonia::DelCliente()->get() as $record)
                        <div class="rounded border border-dashed border-gray-300 py-4 px-4 mb-5">
                            <div class="d-flex flex-stack">
                                <div class="d-flex flex-column">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="fs-6 fw-semibold fw-semibold mb-0 me-1"
                                             style="color: {{$record->tipoContratto->gestore->colore_hex}};">{{$record->tipoContratto->nome}}</div>
                                        @if($record->citta)
                                            <i class="text-gray-400 fas fa-location-dot ms-1 fs-7"
                                               data-bs-toggle="popover" data-bs-custom-class="popover-inverse"
                                               data-bs-trigger="hover" data-bs-placement="top" data-bs-content="{{$record->indirizzo.' - '.$record->comune->comuneConTarga()}}"></i>
                                        @endif
                                    </div>
                                    <div class="fs-7 text-muted">Data inserimento: {{$record->data->format('d/m/Y')}}</div>
                                </div>
                                <div class="text-nowrap">
                                    {!! $record->esito->labelStato() !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
