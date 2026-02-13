@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')

    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertMessage')
            @include('Backend._components.alertErrori')
            @php($two=false)

            @if (session('status') == 'two-factor-authentication-enabled')
                <div class="alert alert-warning  text-gray-800">
                    Completa la configurazione dell'autenticazione a due fattori scansionando il codice qr con la tua app di autenticazione.
                </div>
                @php($two=true)
            @endif
            @if (session('status') == 'two-factor-authentication-disabled')
                <div class="alert alert-success   text-gray-800">
                    L'autenticazione a due fattori è stata disabilitata correttamente.
                </div>
                @php($two=true)
            @endif
            @if (session('status') == 'two-factor-authentication-confirmed')
                <div class="alert alert-success   text-gray-800">
                    L'autenticazione a due fattori è stata abilitata correttamente.
                </div>
                @php($two=true)

            @endif


            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="myTab">
                <li class="nav-item">
                    <a class="nav-link {{$two?'':'active'}}" data-bs-toggle="tab" href="#tab_dati">Dai utente</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_password">Password</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_email">Email</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{$two?'active':''}}" data-bs-toggle="tab" href="#tab_two_factor">Autenticazione a due fattori</a>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane  {{$two?'':'active show'}} " id="tab_dati" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 px-lg-2 py-lg-2">
                            <h3>Modifica i tuoi dati</h3>
                            <div class="pt-5"></div>
                            <form method="POST" action="{{ action([$controller,'update'],'dati-utente') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                @php($record=Auth::user())
                                @include('Backend._inputs.inputText',['campo'=>'cognome','testo'=>'Cognome','placeholder'=>'Il tuo cognome','required'=>true,'autocomplete'=>'family-name'])
                                @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','placeholder'=>'Il tuo nome','required'=>true,'autocomplete'=>'given-name'])
                                @include('Backend._inputs.inputText',['campo'=>'telefono','testo'=>'Telefono','placeholder'=>'Il tuo numero di telefono','required'=>true])
                                <div class="w-100 text-center">
                                    <button class="btn btn-primary" type="submit">Salva modifiche</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="tab-pane " id="tab_password" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 px-lg-2 py-lg-2">
                            <h3>Modifica la tua password</h3>
                            <div class="pt-5"></div>
                            <form method="POST" action="{{ action([$controller,'update'],'dati-password') }}">
                                @csrf
                                @method('PATCH')
                                @php($record=Auth::user())
                                <div class="row mb-6">
                                    <!--begin::Label-->
                                    <div class="col-lg-4 col-form-label text-lg-end">
                                        <label for="exampleInputPassword3" class="fw-bold fs-6 required">Password attuale</label>
                                    </div>
                                    <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                        <input type="password" class="form-control form-control-solid  @error('password_attuale') is-invalid @enderror"
                                               data-enter-pass=""
                                               id="password_attuale" placeholder="Password attuale" name="password_attuale" required autocomplete="current-password">
                                        @error('password_attuale')
                                        <div class="fv-plugins-message-container invalid-feedback">
                                            {{$message}}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <!--begin::Label-->
                                    <div class="col-lg-4 col-form-label text-lg-end">
                                        <label for="exampleInputPassword3" class="fw-bold fs-6 required">Nuova password </label>
                                    </div>
                                    <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                        <input type="password" class="form-control form-control-solid  @error('password') is-invalid @enderror"
                                               id="password" placeholder="Scegli una password sicura" name="password" required autocomplete="new-password">
                                        <div class="form-text">La password deve essere lunga almeno 8 caratteri</div>
                                        @error('password')
                                        <div class="fv-plugins-message-container invalid-feedback">
                                            {{$message}}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <!--begin::Label-->
                                    <div class="col-lg-4 col-form-label text-lg-end">
                                        <label for="exampleInputPassword3" class="fw-bold fs-6 required">Conferma la password</label>
                                    </div>
                                    <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                        <input type="password" class="form-control form-control-solid" data-enter-pass="La password deve essere lunga almeno 8 caratteri"
                                               id="password_confirmation" placeholder="Ripeti la tua password" name="password_confirmation" required autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="w-100 text-center">
                                    <button class="btn btn-primary" type="submit">Modifica password</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
                <div class="tab-pane " id="tab_email" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 px-lg-2 py-lg-2">
                            <h3>Modifica il tuo indirizzo email</h3>
                            <div class="pt-5"></div>
                            <form method="POST" action="{{ action([$controller,'update'],'dati-email') }}">
                                @csrf
                                @method('PATCH')
                                @include('Backend._inputs.inputText',['campo'=>'email','testo'=>'Email','placeholder'=>'Il tuo indirizzo email','required'=>true,'autocomplete'=>'email'])
                                <div class="w-100 text-center">
                                    <button class="btn btn-primary" type="submit">Modifica email</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
                <div class="tab-pane  {{$two?'active show':''}}" id="tab_two_factor" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 px-lg-2 py-lg-2">
                            <form method="POST" action="/user/two-factor-authentication">
                                @csrf
                                @if(auth()->user()->two_factor_secret)
                                    @method('DELETE')
                                    <div class="pb-5">
                                        {!! Auth::user()->twoFactorQrCodeSvg() !!}
                                    </div>
                                    <h4>Recovery code</h4>
                                    <ul>
                                        @foreach(Auth::user()->recoveryCodes() as $code)
                                            <li>{{$code}}</li>
                                        @endforeach
                                    </ul>
                                    <button class="btn btn-danger">Disabilita autenticazione a due fattori</button>
                                @else
                                    <button class="btn btn-primary">Abilita</button>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('customScript')
    <script type="text/javascript" src="/assets_backend/js-miei/moment_it.js"></script>
    <script>
        $(function () {
            let url = location.href.replace(/\/$/, "");

            if (location.hash) {
                const hash = url.split("#");
                $('#myTab a[href="#' + hash[1] + '"]').tab("show");
            }
            $('#codice_fiscale').maxlength({
                warningClass: "badge badge-danger",
                limitReachedClass: "badge badge-success"
            });
            $('#iban').maxlength({
                warningClass: "badge badge-danger",
                limitReachedClass: "badge badge-success"
            });
            $('#password').maxlength({
                customMaxAttribute: 8,
                warningClass: "badge badge-danger",
                limitReachedClass: "badge badge-success",
                allowOverMax: true
            });
            $('#password_confirmation').maxlength({
                customMaxAttribute: 8,
                warningClass: "badge badge-danger",
                limitReachedClass: "badge badge-success",
                allowOverMax: true
            });
        });
    </script>
@endpush
