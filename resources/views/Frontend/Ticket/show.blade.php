@extends('Backend._components.modal',['minW'=>'mw-800px','scrollable'=>true,'footer'=>' <button type="button" class="btn btn-light" data-bs-dismiss="modal">Chiudi</button>'])
@section('content')
    <div id="show-ticket">
        @include('Backend._components.alertErrori')
        <div class="d-flex flex-column flex-xl-row p-7">
            <div class="flex-lg-row-fluid me-xl-15 mb-20 mb-xl-0">
                <div class="mb-0">
                    <h1 class="text-gray-800 fw-bold">{{$record->oggetto}}</h1>
                    <div class="d-flex justify-content-between">
                        <div class="">
                                <span class="fw-bold text-muted me-6">Da:
																<a href="#" class="text-muted text-hover-primary">{{$record->utente->nominativo()}}</a></span>
                            <span class="fw-bold text-muted">Creato:
																<span class="fw-bolder text-gray-600 me-1">{{$record->created_at->diffForHumans()}}</span>({{$record->created_at->format('d/m/Y H:i')}})</span>
                        </div>
                        <div class="">
                            @php($messaggio=$record->messaggi[0])
                            @if($messaggio->user_id==Auth::id())
                                @if($messaggio->letto)
                                    <span class="badge badge-light-success fw-bolder my-2">Letto</span>
                                @else
                                    <span class="badge badge-light-primary fw-bolder my-2">Da leggere</span>
                                @endif
                            @else
                                @if(!$messaggio->letto)
                                    <span class="badge badge-light-danger fw-bolder my-2">Nuovo</span>
                                @endif

                            @endif
                        </div>
                    </div>
                    @foreach($record->messaggi as $messaggio)
                        @if($loop->index==0)
                            <!--begin::Details-->
                            <div class="mb-15">
                                <!--begin::Description-->
                                <div class="mb-15 fs-5 fw-normal text-gray-800">

                                    {!! $messaggio->messaggio !!}
                                </div>
                                @if($messaggio->allegati->count())
                                    <p class="fw-normal fs-7 text-gray-700 m-0">
                                        Allegati: @foreach($messaggio->allegati as $allegato)
                                            <a href="{{action([\App\Http\Controllers\Frontend\TicketController::class,'downloadAllegato'],['messaggioId'=>$messaggio->id,'allegatoId'=>$allegato->id])}}"> {{$allegato->filename_originale}}</a>
                                        @endforeach
                                    </p>

                                @endif

                                <!--end::Description-->
                            </div>
                        @else
                            <div class="mb-9">
                                <!--begin::Card-->
                                <div class="card card-bordered w-100">
                                    <!--begin::Body-->
                                    <div class="card-body">
                                        <!--begin::Wrapper-->
                                        <div class="w-100 d-flex flex-stack mb-8">
                                            <!--begin::Container-->
                                            <div class="d-flex align-items-center f">
                                                <!--begin::Author-->
                                                <!--end::Author-->
                                                <!--begin::Info-->
                                                <div class="d-flex flex-column fw-bold fs-5 text-gray-600 text-dark">
                                                    <!--begin::Text-->
                                                    <div class="d-flex align-items-center">
                                                        <!--begin::Username-->
                                                        <a href="#" class="text-gray-800 fw-bolder text-hover-primary fs-5 me-3">
                                                            {{$messaggio->utente->nominativo()}}
                                                        </a>
                                                        <!--end::Username-->
                                                        <span class="m-0"></span>
                                                    </div>
                                                    <!--end::Text-->
                                                    <!--begin::Date-->
                                                    <span class="text-muted fw-bold fs-6">{{$messaggio->created_at->diffForHumans()}} ({{$messaggio->created_at->format('d/m/Y H:i')}})</span>
                                                    <!--end::Date-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Container-->
                                            <!--begin::Actions-->
                                            <div class="m-0">
                                                @if($messaggio->user_id==Auth::id())
                                                    @if($messaggio->letto)
                                                        <span class="badge badge-light-success fw-bolder my-2">Letto</span>
                                                    @else
                                                        <span class="badge badge-light-primary fw-bolder my-2">Da leggere</span>
                                                    @endif
                                                @else
                                                    @if(!$messaggio->letto)
                                                        <span class="badge badge-light-danger fw-bolder my-2">Nuovo</span>
                                                    @endif

                                                @endif
                                            </div>
                                            <!--end::Actions-->
                                        </div>
                                        <!--end::Wrapper-->
                                        <!--begin::Desc-->
                                        <p class="fw-normal fs-5 text-gray-700 m-0">
                                            {!! $messaggio->messaggio !!}
                                        </p>
                                        @if($messaggio->allegati->count())
                                            <p class="fw-normal fs-7 text-gray-700 m-0">
                                                Allegati: @foreach($messaggio->allegati as $allegato)
                                                    <a href="{{action([\App\Http\Controllers\Frontend\TicketController::class,'downloadAllegato'],['messaggioId'=>$messaggio->id,'allegatoId'=>$allegato->id])}}"> {{$allegato->filename_originale}}</a>
                                                @endforeach
                                            </p>

                                        @endif
                                        <!--end::Desc-->
                                    </div>
                                    <!--end::Body-->
                                </div>
                                <!--end::Card-->
                            </div>
                        @endif
                    @endforeach
                    @include('Frontend.Ticket.rispondi')

                </div>
            </div>
        </div>
    </div>
@endsection
