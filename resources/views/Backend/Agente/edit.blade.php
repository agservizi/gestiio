@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id?'PATCH':'POST')
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'cognome','testo'=>'Cognome','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputPasswordShow',['campo'=>'password','testo'=>'Password','required'=>$record->id==null,'autocomplete'=>'off','help'=>'Inserisci una password per modificare la password dell\'agente'])
                    </div>
                    <div class="col-md-6">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'email','testo'=>'Email','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'telefono','testo'=>'Telefono','required'=>true,'autocomplete'=>'off','help'=>'Se non presente il prefisso internazionale, verrà aggiunto +39. Eventuali spazi verranno rimossi in modo automatico'])
                    </div>
                </div>
                @include('Backend.Agente.editDatiAgente',['record'=>$record->agente??new \App\Models\Agente()])

                @if(count($ruoli))
                    <div class="row mb-6">
                        <div class="col-lg-4 col-form-label text-lg-end">
                            <label class="fw-bold fs-6  required ">Ruolo</label><br>
                        </div>
                        <div class="col-lg-8 fv-row fv-plugins-icon-container">
                            @foreach($ruoli as $ruolo=>$testo)
                                @php($ruolo=$testo)
                                <div class="form-check form-check-custom form-check-solid my-3">
                                    <input class="form-check-input ruolo" name="ruolo" type="radio" id="radio{{$ruolo}}"
                                           value="{{$ruolo}}"
                                           {{$record->hasPermissionTo($ruolo)?'checked':''}} required/>
                                    <label class="form-check-label" for="radio{{$ruolo}}">{{$testo}}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @includeWhen(false,'Backend._inputs.inputSwitch',['campo'=>'operatori-vedi_tutti','testo'=>'Vedi tutti gli operatori','required'=>false,'help'=>'Vede tutti gli operatori'])
                @endif
                <div class="row mb-6" id="div_vedi">
                    <div class="col-lg-4 col-form-label text-lg-end">
                        <label class="fw-bold fs-6">Servizi</label><br>
                    </div>
                    <div class="col-lg-8 fv-row fv-plugins-icon-container">
                        @foreach(\Spatie\Permission\Models\Permission::where('id','>',3)->where('name','<>','operatore')->get()->pluck('name')->toArray() as $ruolo=>$testo)
                            @php($ruolo=$testo)
                            <div class="form-check form-check-custom form-check-solid my-3">
                                <input class="form-check-input" name="vedi[]" type="checkbox" id="radio{{$ruolo}}"
                                       value="{{$ruolo}}" {{$record->hasPermissionTo($ruolo)?'checked':''}}/>
                                <label class="form-check-label" for="radio{{$ruolo}}">{{$testo}}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if(!$record->id)
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                        <!--begin::Icon-->
                        <!--begin::Svg Icon | path: assets/media/icons/duotone/Communication/Mail-notification.svg-->
                        <span class="svg-icon svg-icon-2tx svg-icon-primary me-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24"
                                 version="1.1">
                                <path
                                        d="M21,12.0829584 C20.6747915,12.0283988 20.3407122,12 20,12 C16.6862915,12 14,14.6862915 14,18 C14,18.3407122 14.0283988,18.6747915 14.0829584,19 L5,19 C3.8954305,19 3,18.1045695 3,17 L3,8 C3,6.8954305 3.8954305,6 5,6 L19,6 C20.1045695,6 21,6.8954305 21,8 L21,12.0829584 Z M18.1444251,7.83964668 L12,11.1481833 L5.85557487,7.83964668 C5.4908718,7.6432681 5.03602525,7.77972206 4.83964668,8.14442513 C4.6432681,8.5091282 4.77972206,8.96397475 5.14442513,9.16035332 L11.6444251,12.6603533 C11.8664074,12.7798822 12.1335926,12.7798822 12.3555749,12.6603533 L18.8555749,9.16035332 C19.2202779,8.96397475 19.3567319,8.5091282 19.1603533,8.14442513 C18.9639747,7.77972206 18.5091282,7.6432681 18.1444251,7.83964668 Z"
                                        fill="#000000"/>
                                <circle fill="#000000" opacity="0.3" cx="19.5" cy="17.5" r="2.5"/>
                            </svg>
                        </span>
                        <!--end::Svg Icon-->
                        <!--end::Icon-->
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                            <!--begin::Content-->
                            <div class="mb-3 mb-md-0 fw-bold">
                                <h4 class="text-gray-900 fw-bolder">Invio email</h4>
                                <div class="fs-6 text-gray-700 pe-7">Verrà inviata una mail all'indirizzo dell'agente
                                    con i dati per accedere.
                                </div>
                            </div>
                            <!--end::Content-->
                            <!--begin::Action-->
                            <button class="btn btn-primary mt-3"
                                    type="submit">{{$record->id?'Salva modifiche':'Crea'}}</button>
                            <!--end::Action-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                @else
                    <div class="row">
                        <div class="col-md-4 offset-md-4">
                            <button class="btn btn-primary mt-3"
                                    type="submit">{{$record->id?'Salva modifiche':'Crea'}}</button>
                        </div>
                        <div class="col-md-4 text-end">
                            @if($eliminabile===true)
                                <a class="btn btn-danger mt-3" id="elimina"
                                   href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                            @elseif(is_string($eliminabile))
                                <span data-bs-toggle="tooltip" title="{{$eliminabile}}">
                                    <a class="btn btn-danger mt-3 disabled" href="javascript:void(0)">Elimina</a>
                                </span>
                            @endif
                            <a class="btn btn-danger mt-3 azione"
                               href="{{action([$controller,'azioni'],['id'=>$record->id,'azione'=>'sospendi'])}}">Sospendi</a>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        $(function () {

            $('#partita_iva').blur(function () {

                if ($('#ragione_sociale').val() !== '') {
                    return;
                }

                var url = "{{action([\App\Http\Controllers\RegistratiController::class,'verificaPIvaEu'])}}";
                $.ajax(url,
                    {
                        dataType: 'json',
                        method: 'POST',
                        data: {
                            'partita_iva': $('#partita_iva').val(),
                            '_token': '{{csrf_token()}}'
                        },
                        success: function (resp) {
                            if (resp.success) {
                                $('#ragione_sociale').val(resp.res.name);
                            }
                        }
                    });

            });

            eliminaHandler('Questa voce verrà eliminata definitivamente');
            $('.azione').click(function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                ajaxAzione(url);
            });
            mostraNascondiVedi($('.ruolo:checked').val());

            $('.ruolo').change(function () {
                mostraNascondiVedi($(this).val());
            });
            $('#citta').select2({
                placeholder: 'Seleziona una citta',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/backend/select2?citta",
                    dataType: 'json',
                    data: function (term, page) {
                        return {
                            term: term.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    }
                }
            }).on('select2:select', function (e) {
                // Access to full data
                $("#cap").val(e.params.data.cap);
            });

            function mostraNascondiVedi(ruolo) {
                if (ruolo === 'supervisore' || ruolo === 'agente'|| ruolo === 'operatore') {
                    $('#div_vedi').show();
                } else {
                    $('#div_vedi').hide();
                }
            }
        });
    </script>
@endpush
