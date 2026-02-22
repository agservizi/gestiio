@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($user = Auth::user())
    <div class="card mb-6">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-4">
            <div class="symbol symbol-70px symbol-circle">
                <div class="symbol-label fs-2 fw-bold bg-light-primary text-primary">{{$user->iniziali()}}</div>
            </div>
            <div class="flex-grow-1">
                <h2 class="mb-1">{{$user->nominativo()}}</h2>
                <div class="text-muted mb-2">Gestione dati personali e sicurezza account</div>
                <div class="d-flex flex-wrap gap-2">
                    {!! $user->userLevel(true, $user) !!}
                    <span class="badge badge-light">ID: {{$user->id}}</span>
                    <span class="badge badge-light">Ultimo accesso: {{$user->ultimo_accesso?->format('d/m/Y H:i') ?? '-'}}</span>
                    @if($user->email_verified_at)
                        <span class="badge badge-light-success">Email verificata</span>
                    @else
                        <span class="badge badge-light-warning">Email non verificata</span>
                    @endif
                    @if($user->two_factor_secret)
                        <span class="badge badge-light-success">2FA attiva</span>
                    @else
                        <span class="badge badge-light-danger">2FA non attiva</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

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


            <div class="d-flex justify-content-end mb-4">
                <a class="btn btn-sm btn-light-primary" href="{{action([$controller,'exportDatiPersonali'])}}">
                    Esporta dati personali (JSON)
                </a>
            </div>

            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="myTab">
                <li class="nav-item">
                    <a class="nav-link {{$two?'':'active'}}" data-bs-toggle="tab" href="#tab_dati">Dati utente</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_password">Password</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_email">Email</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_preferenze">Preferenze</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_sicurezza">Sessioni</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_attivita">Attività</a>
                </li>
                @if($user->hasPermissionTo('agente') && $user->agente)
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab_openapi">OpenAPI Visure</a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link {{$two?'active':''}}" data-bs-toggle="tab" href="#tab_two_factor">Autenticazione a due fattori</a>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane  {{$two?'':'active show'}} " id="tab_dati" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 px-lg-2 py-lg-2">
                            <h3 class="mb-1">Dati personali</h3>
                            <div class="text-muted">Mantieni sempre aggiornate le informazioni di contatto.</div>
                            <div class="pt-5"></div>
                            <form method="POST" action="{{ action([$controller,'update'],'dati-utente') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                @php($record=$user)
                                @include('Backend._inputs.inputText',['campo'=>'cognome','testo'=>'Cognome','placeholder'=>'Il tuo cognome','required'=>true,'autocomplete'=>'family-name'])
                                @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','placeholder'=>'Il tuo nome','required'=>true,'autocomplete'=>'given-name'])
                                @include('Backend._inputs.inputText',['campo'=>'telefono','testo'=>'Telefono','placeholder'=>'Il tuo numero di telefono','required'=>true])
                                <div class="row mb-6">
                                    <div class="col-lg-4 col-form-label text-lg-end">
                                        <label class="fw-bold fs-6">Codice agente</label>
                                    </div>
                                    <div class="col-lg-8 fv-row">
                                        <input type="text" class="form-control form-control-solid" value="{{$user->codiceAgente()}}" readonly>
                                    </div>
                                </div>
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
                            <h3 class="mb-1">Sicurezza password</h3>
                            <div class="text-muted">Usa una password robusta e non riutilizzata su altri servizi.</div>
                            <div class="pt-5"></div>
                            <form method="POST" action="{{ action([$controller,'update'],'dati-password') }}">
                                @csrf
                                @method('PATCH')
                                @php($record=$user)
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
                            <h3 class="mb-1">Indirizzo email</h3>
                            <div class="text-muted">L'email è usata per login, notifiche operative e recupero account.</div>
                            <div class="pt-5"></div>
                            <form method="POST" action="{{ action([$controller,'update'],'dati-email') }}">
                                @csrf
                                @method('PATCH')
                                @php($record=$user)
                                @include('Backend._inputs.inputText',['campo'=>'email','testo'=>'Email','placeholder'=>'Il tuo indirizzo email','required'=>true,'autocomplete'=>'email'])
                                @include('Backend._inputs.inputText',['campo'=>'email_confirmation','testo'=>'Conferma email','placeholder'=>'Conferma il tuo indirizzo email','required'=>true,'autocomplete'=>'email'])
                                <div class="w-100 text-center">
                                    <button class="btn btn-primary" type="submit">Modifica email</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
                <div class="tab-pane" id="tab_preferenze" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 px-lg-2 py-lg-2">
                            <h3 class="mb-1">Preferenze notifiche e localizzazione</h3>
                            <div class="text-muted mb-4">Configura come ricevere comunicazioni e il formato di visualizzazione.</div>

                            <form method="POST" action="{{ action([$controller,'update'],'preferenze-notifiche') }}" class="mb-10">
                                @csrf
                                @method('PATCH')
                                <h5 class="mb-4">Notifiche</h5>
                                <div class="form-check form-switch form-check-custom form-check-solid mb-4">
                                    <input class="form-check-input" type="checkbox" id="notifiche_email_ticket" name="notifiche_email_ticket" value="1" {{$user->getExtra('notifiche_email_ticket') !== false ? 'checked' : ''}} />
                                    <label class="form-check-label" for="notifiche_email_ticket">Email su ticket</label>
                                </div>
                                <div class="form-check form-switch form-check-custom form-check-solid mb-4">
                                    <input class="form-check-input" type="checkbox" id="notifiche_email_spedizioni" name="notifiche_email_spedizioni" value="1" {{$user->getExtra('notifiche_email_spedizioni') !== false ? 'checked' : ''}} />
                                    <label class="form-check-label" for="notifiche_email_spedizioni">Email su spedizioni</label>
                                </div>
                                <div class="form-check form-switch form-check-custom form-check-solid mb-4">
                                    <input class="form-check-input" type="checkbox" id="notifiche_email_amministrative" name="notifiche_email_amministrative" value="1" {{$user->getExtra('notifiche_email_amministrative') !== false ? 'checked' : ''}} />
                                    <label class="form-check-label" for="notifiche_email_amministrative">Email amministrative</label>
                                </div>
                                <div class="form-check form-switch form-check-custom form-check-solid mb-6">
                                    <input class="form-check-input" type="checkbox" id="notifiche_browser" name="notifiche_browser" value="1" {{$user->getExtra('notifiche_browser') !== false ? 'checked' : ''}} />
                                    <label class="form-check-label" for="notifiche_browser">Notifiche browser in piattaforma</label>
                                </div>

                                <div class="w-100 text-center">
                                    <button class="btn btn-primary" type="submit">Salva preferenze notifiche</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ action([$controller,'update'],'preferenze-locale') }}">
                                @csrf
                                @method('PATCH')
                                <h5 class="mb-4">Localizzazione</h5>
                                <div class="row mb-6">
                                    <div class="col-lg-4 col-form-label text-lg-end">
                                        <label class="fw-bold fs-6 required" for="fuso_orario">Fuso orario</label>
                                    </div>
                                    <div class="col-lg-8 fv-row">
                                        <select id="fuso_orario" name="fuso_orario" class="form-select form-select-solid @error('fuso_orario') is-invalid @enderror" required>
                                            <option value="Europe/Rome" {{$user->getExtra('fuso_orario') === 'Europe/Rome' || !$user->getExtra('fuso_orario') ? 'selected' : ''}}>Europe/Rome</option>
                                            <option value="UTC" {{$user->getExtra('fuso_orario') === 'UTC' ? 'selected' : ''}}>UTC</option>
                                        </select>
                                        @error('fuso_orario')<div class="invalid-feedback">{{$message}}</div>@enderror
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <div class="col-lg-4 col-form-label text-lg-end">
                                        <label class="fw-bold fs-6 required" for="formato_data">Formato data</label>
                                    </div>
                                    <div class="col-lg-8 fv-row">
                                        <select id="formato_data" name="formato_data" class="form-select form-select-solid @error('formato_data') is-invalid @enderror" required>
                                            <option value="d/m/Y" {{$user->getExtra('formato_data') === 'd/m/Y' || !$user->getExtra('formato_data') ? 'selected' : ''}}>gg/mm/aaaa</option>
                                            <option value="Y-m-d" {{$user->getExtra('formato_data') === 'Y-m-d' ? 'selected' : ''}}>aaaa-mm-gg</option>
                                        </select>
                                        @error('formato_data')<div class="invalid-feedback">{{$message}}</div>@enderror
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <div class="col-lg-4 col-form-label text-lg-end">
                                        <label class="fw-bold fs-6 required" for="formato_numeri_valuta">Formato numeri/valuta</label>
                                    </div>
                                    <div class="col-lg-8 fv-row">
                                        <select id="formato_numeri_valuta" name="formato_numeri_valuta" class="form-select form-select-solid @error('formato_numeri_valuta') is-invalid @enderror" required>
                                            <option value="it_IT" {{$user->getExtra('formato_numeri_valuta') === 'it_IT' || !$user->getExtra('formato_numeri_valuta') ? 'selected' : ''}}>Italiano (1.234,56 €)</option>
                                            <option value="en_US" {{$user->getExtra('formato_numeri_valuta') === 'en_US' ? 'selected' : ''}}>English/US (1,234.56 $)</option>
                                        </select>
                                        @error('formato_numeri_valuta')<div class="invalid-feedback">{{$message}}</div>@enderror
                                    </div>
                                </div>
                                <div class="w-100 text-center">
                                    <button class="btn btn-primary" type="submit">Salva preferenze locali</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="tab_sicurezza" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 px-lg-2 py-lg-2">
                            <h3 class="mb-1">Sessioni attive</h3>
                            <div class="text-muted mb-4">Disconnetti tutte le altre sessioni aperte su altri dispositivi.</div>
                            <form method="POST" action="{{ action([$controller,'update'],'sicurezza-sessioni') }}">
                                @csrf
                                @method('PATCH')
                                <div class="row mb-6">
                                    <div class="col-lg-4 col-form-label text-lg-end">
                                        <label for="password_sessioni" class="fw-bold fs-6 required">Conferma password</label>
                                    </div>
                                    <div class="col-lg-8 fv-row">
                                        <input type="password" class="form-control form-control-solid @error('password_sessioni') is-invalid @enderror" id="password_sessioni" name="password_sessioni" required autocomplete="current-password">
                                        @error('password_sessioni')<div class="invalid-feedback">{{$message}}</div>@enderror
                                    </div>
                                </div>
                                <div class="w-100 text-center">
                                    <button class="btn btn-danger" type="submit">Termina tutte le altre sessioni</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="tab_attivita" role="tabpanel">
                    <div class="row">
                        <div class="col-12 px-lg-2 py-lg-2">
                            <h3 class="mb-1">Attività account</h3>
                            <div class="text-muted mb-4">Ultimi accessi registrati sul tuo account.</div>
                            @php($logins = $recentLogin ?? collect())
                            <div class="table-responsive">
                                <table class="table table-row-bordered">
                                    <thead>
                                    <tr class="fw-bolder fs-7 text-uppercase">
                                        <th>Data/Ora</th>
                                        <th>Esito</th>
                                        <th>IP</th>
                                        <th>Remember me</th>
                                        <th>User agent</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($logins as $login)
                                        <tr>
                                            <td>{{\Carbon\Carbon::parse($login->created_at)->format(config('app.user_date_format', 'd/m/Y') . ' H:i')}}</td>
                                            <td>
                                                @if($login->riuscito)
                                                    <span class="badge badge-light-success">Riuscito</span>
                                                @else
                                                    <span class="badge badge-light-danger">Fallito</span>
                                                @endif
                                            </td>
                                            <td>{{$login->ip}}</td>
                                            <td>{{$login->remember ? 'Sì' : 'No'}}</td>
                                            <td class="text-muted">{{\Illuminate\Support\Str::limit($login->user_agent, 100)}}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Nessuna attività disponibile</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @if($user->hasPermissionTo('agente') && $user->agente)
                    <div class="tab-pane" id="tab_openapi" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8 px-lg-2 py-lg-2">
                                <h3 class="mb-1">Credenziali OpenAPI Visure</h3>
                                <div class="text-muted mb-4">Token personali dell'agente per chiamate visure/catasto e credito separato dal wallet principale.</div>
                                <form method="POST" action="{{ action([$controller,'update'],'openapi-credenziali') }}">
                                    @csrf
                                    @method('PATCH')
                                    @php($record = $user->agente)
                                    @include('Backend._inputs.inputText',[
                                        'campo'=>'openapi_visure_token',
                                        'testo'=>'Token OpenAPI Visure',
                                        'placeholder'=>'Inserisci il bearer token per visure',
                                        'required'=>false
                                    ])
                                    @include('Backend._inputs.inputText',[
                                        'campo'=>'openapi_catasto_token',
                                        'testo'=>'Token OpenAPI Catasto',
                                        'placeholder'=>'Inserisci il bearer token per catasto (opzionale)',
                                        'required'=>false
                                    ])
                                    <div class="w-100 text-center">
                                        <button class="btn btn-primary" type="submit">Salva credenziali OpenAPI</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="tab-pane  {{$two?'active show':''}}" id="tab_two_factor" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 px-lg-2 py-lg-2">
                            <h3 class="mb-1">Autenticazione a due fattori (2FA)</h3>
                            <div class="text-muted mb-4">Aggiungi un secondo livello di sicurezza per proteggere il tuo account.</div>
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
                                    <button class="btn btn-primary">Abilita 2FA</button>
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
