@php
    $tagTitle = 'Registrazione agente AG SERVIZI | Gestiio';
    $metaDescription = 'Crea il tuo account agente Gestiio per collaborare con AG SERVIZI e gestire pratiche, contratti, ticket, documenti e plafond operativo.';
    $metaKeywords = 'registrazione agente AG SERVIZI, collabora con AG SERVIZI, portale agenti, Gestiio';
    $robotsMeta = 'index, follow';
    $canonicalUrl = url('/register');
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => 'AG SERVIZI',
                'url' => url('/'),
                'logo' => url('/loghi/logo.png'),
            ],
            [
                '@type' => 'WebPage',
                '@id' => url('/register').'#webpage',
                'url' => url('/register'),
                'name' => $tagTitle,
                'description' => $metaDescription,
                'inLanguage' => 'it-IT',
            ],
        ],
    ];
@endphp

@extends('auth._main')


@section('content')
    @php($nuovo=$record->id)
    <div class="w-lg-1000px bg-body rounded shadow-sm p-10 mx-auto">
        @include('Backend._components.alertErrori')
        <form method="POST" action="{{action([\App\Http\Controllers\RegistratiController::class,'post'])}}" enctype="multipart/form-data">
            <div class="text-center mb-10">
                <div class="text-primary fw-bold text-uppercase fs-8 mb-3">Collabora con AG SERVIZI</div>
                <h1 class="text-dark mb-3">Crea il tuo account agente</h1>
                <div class="text-gray-600 fw-semibold fs-5 mx-auto" style="max-width: 720px">
                    Entra nel portale Gestiio per gestire pratiche, contratti, ticket, documenti e plafond con un flusso operativo tracciato.
                </div>
                <div class="text-gray-400 fw-bold fs-4 mt-4">Hai gia un account?
                    <a href="{{route('login')}}" class="link-primary fw-bolder">Accedi</a></div>
            </div>
            @csrf
            @method('POST')
            <input type="hidden" name="nazione" value="IT" id="nazione">
            <div class="alert alert-primary d-flex align-items-center p-5 mb-8">
                <div>
                    <div class="fw-bold mb-1">Cosa succede dopo la registrazione</div>
                    <div>Il tuo account viene creato, AG SERVIZI riceve la notifica e puoi accedere all'area operativa con le funzioni disponibili per il tuo profilo.</div>
                </div>
            </div>
            <h5>Dati anagrafici</h5>
            <div class="row">
                <div class="col-md-6">
                    @include("Backend._inputs.inputText",["campo"=>"nome","testo"=>"Nome","required"=>true,"autocomplete"=>"off"])
                </div>
                <div class="col-md-6">
                    @include("Backend._inputs.inputText",["campo"=>"cognome","testo"=>"Cognome","required"=>true,"autocomplete"=>"off"])
                </div>
                <div class="col-md-6">
                    @include("Backend._inputs.inputText",["campo"=>"codice_fiscale","testo"=>"Codice Fiscale","required"=>true,"autocomplete"=>"off"])
                </div>
            </div>
            <h5 class="mt-8">Dati accesso</h5>

            <div class="row">
                <div class="col-md-6">
                    @include("Backend._inputs.inputText",["campo"=>"email","testo"=>"Email","required"=>true,"autocomplete"=>"off"])
                </div>
                <div class="col-md-6">
                    @include("Backend._inputs.inputText",["campo"=>"telefono","testo"=>"Cellulare","required"=>true,"autocomplete"=>"off"])
                </div>

            </div>
            <div class="row">
                <div class="col-md-6">
                    @include("Backend._inputs.inputPassword",["campo"=>"password","testo"=>"Password","required"=>true,"autocomplete"=>"password"])
                </div>
                <div class="col-md-6">
                    @include("Backend._inputs.inputPasswordConfirm",["testo"=>"Conferma password"])

                </div>
            </div>
            <h5 class="mt-8">Dati azienda</h5>

            <div class="row">

                <div class="col-md-6">
                    @include("Backend._inputs.inputText",["campo"=>"partita_iva","testo"=>"Partita IVA","required"=>false,"autocomplete"=>"off"])
                </div>

                <div class="col-md-12">
                    @include("Backend._inputs.inputText",["campo"=>"ragione_sociale","testo"=>"Ragione Sociale","required"=>false,"autocomplete"=>"off",'col'=>2])
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    @include("Backend._inputs.inputSelect2",["campo"=>"citta","testo"=>"Città","required"=>false,"autocomplete"=>"off",'selected'=>\App\Models\Comune::selected(old('citta',$record->citta))])
                </div>
                <div class="col-md-6">
                    @include("Backend._inputs.inputText",["campo"=>"cap","testo"=>"CAP","required"=>true,"autocomplete"=>"off"])
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @include("Backend._inputs.inputText",["campo"=>"indirizzo","testo"=>"Indirizzo","required"=>true,"autocomplete"=>"off",'col'=>2])
                </div>
            </div>
            @if(false)
                <div class="row">
                    <div class="col-md-6">
                        @include("Backend._inputs.inputText",["campo"=>"pec","testo"=>"PEC","required"=>false,"autocomplete"=>"off"])
                    </div>
                    <div class="col-md-6">
                        @include("Backend._inputs.inputText",["campo"=>"codice_destinatario","testo"=>"Codice Destinatario","required"=>false,"autocomplete"=>"off"])
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-md-6">
                    @include("Backend._inputs.inputText",["campo"=>"iban","testo"=>"Iban","required"=>false,"autocomplete"=>"off"])
                </div>

                <div class="col-md-6">
                    @include("Backend._inputs.inputFile",["campo"=>"visura_camerale","testo"=>"Visura camerale","required"=>false,"autocomplete"=>"off",'immagine'=>null])
                </div>
            </div>
            <div class="text-center py-8">
                <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary mb-5 px-10">
                    <span class="indicator-label">Crea account agente</span>
                </button>
                <div class="text-gray-500 fs-7">Registrandoti accetti i termini della piattaforma Gestiio di AG SERVIZI.</div>
            </div>
        </form>
    </div>
    <div class="d-flex align-items-center mt-6">
        @include('auth.footer')
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


            $('#open').click(function () {
                $('#change').trigger('click');
            });
            impostaCampiCitta();

            function impostaCampiCitta() {
                const nazione = $('#nazione').val();
                if (nazione === 'IT') {
                    $('#div_citta').show();
                    $('#citta').prop('required', true);
                    $('#label_citta').addClass('required');
                    $('#div_citta_estera').hide();
                    $('#citta_estera').prop('required', false);
                    $('#label_citta_estera').removeClass('required');

                } else {
                    $('#div_citta_estera').show();
                    $('#citta_estera').prop('required', true);
                    $('#label_citta_estera').addClass('required');

                    $('#div_citta').hide();
                    $('#citta').prop('required', false);
                    $('#label_citta').removeClass('required');

                }
            }


            $('#citta').select2({
                placeholder: 'Seleziona una città',
                minimumInputLength: 3,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/select2front?citta",
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

            }).on('select2:open', function () {
                select2Focus($(this));
            });

        });

        function select2Focus(obj) {
            var id = obj.attr('id');
            id = "select2-" + id + "-results";
            var input = $("[aria-controls='" + id + "']");
            setTimeout(function () {
                input.delay(100).focus().select();
            }, 100);

        }
    </script>
@endpush

