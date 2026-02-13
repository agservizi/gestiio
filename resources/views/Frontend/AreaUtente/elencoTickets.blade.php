    <!--begin::Card-->
    <div class="card shadow-none rounded-0 w-100">
        <!--begin::Header-->
        <div class="card-header" id="kt_help_header">
            <h5 class="card-title fw-semibold text-gray-600">I tuoi tickets</h5>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-icon explore-btn-dismiss me-n5" id="kt_help_close">
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
        <!--end::Header-->
        <!--begin::Body-->
        <div class="card-body" id="kt_help_body">
            <!--begin::Content-->
            <div id="kt_help_scroll" class="hover-scroll-overlay-y" data-kt-scroll="true" data-kt-scroll-height="auto" data-kt-scroll-wrappers="#kt_help_body"
                 data-kt-scroll-dependencies="#kt_help_header" data-kt-scroll-offset="5px">
                @foreach(\App\Models\Ticket::where('user_id',Auth::id())->get() as $record)

                    <!--begin::Link-->
                    <div class="d-flex align-items-center mb-7">
                        <!--begin::Icon-->
                        {!! $record->labelBoxStatoTicket() !!}
                        <!--end::Icon-->
                        <!--begin::Info-->
                        <div class="d-flex flex-stack flex-grow-1 ms-4 ms-lg-6">
                            <!--begin::Wrapper-->
                            <div class="d-flex flex-column me-2 me-lg-5">
                                <!--begin::Title-->
                                <span>{{$record->uidTicket()}}</span>
                                <a href="https://preview.keenthemes.com/html/metronic/docs" class="text-dark text-hover-primary fw-bold fs-6 fs-lg-4 mb-1">{{$record->oggetto}}</a>
                                <!--end::Title-->
                                <div class="fs-6 fs-lg-6">{!! $record->labelTipoTicket() !!}</div>
                                <!--begin::Description-->
                                <div class="text-muted fw-semibold fs-7 fs-lg-6">Ultimo aggiornamento: {{$record->updated_at->format('d/m/Y')}}</div>
                                <!--end::Description-->
                            </div>
                            <!--end::Wrapper-->
                            <a data-target="kt_modal" data-toggle="modal-ajax"
                               class="btn btn-sm btn-light btn-active-light-primary"
                               href="{{action([\App\Http\Controllers\Frontend\TicketController::class,'show'],$record->id)}}">Vedi</a>
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::Link-->
                @endforeach

                <!--end::Info-->
            </div>
            <!--end::Link-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Body-->
</div>
<!--end::Card-->
