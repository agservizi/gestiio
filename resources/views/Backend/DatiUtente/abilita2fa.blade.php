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


            <div class="row">
                <div class="fs-5">
                    <form method="POST" action="/user/two-factor-authentication">
                        @csrf
                        @if(auth()->user()->two_factor_secret)
                            @method('DELETE')
                            <h4>Scansiona il qrcode con la tua applicazione di autenticazione</h4>
                            <p>Se non ne hai una puoi usare Google Autenticator<br>
                            <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=it&gl=US&pli=1" target="_blank">Android</a> |
                            <a href="https://apps.apple.com/it/app/google-authenticator/id388497605" target="_blank">iOs</a><br>

                            </p>
                            <div class="pb-5">
                                {!! Auth::user()->twoFactorQrCodeSvg() !!}
                            </div>

                            <h4 class="pt-6">Recovery codes</h4>
                            <p class="">Copia questi codici in un posto sicuro, li potrai usare nel caso in cui non riesci ad accedere con il codice generato datta tua applicazione
                                di
                                autenticazione</p>
                            <ul>
                                @foreach(Auth::user()->recoveryCodes() as $code)
                                    <li>{{$code}}</li>
                                @endforeach
                            </ul>
                            <button class="btn btn-danger">Disabilita autenticazione a due fattori</button>
                        @else
                            <h4>E' richiesta l'abilitazione dell'autenticazione a due fattori</h4>
                            <button class="btn btn-primary">Abilita</button>
                        @endif
                    </form>

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
