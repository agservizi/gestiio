@php($user = Auth::user())
@php($logins = \App\Models\RegistroLogin::where('user_id', Auth::id())->latest('id')->limit(8)->get())

<div class="card shadow-none rounded-0 w-100">
    <div class="card-header" id="kt_account_header">
        <h3 class="card-title fw-bold text-gray-900">Gestione account</h3>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-icon btn-active-color-primary h-40px w-40px me-n6" id="kt_account_close">
                <span class="svg-icon svg-icon-2">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"/>
                        <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"/>
                    </svg>
                </span>
            </button>
        </div>
    </div>

    <div class="card-body" id="kt_account_body">
        @include('Backend._components.alertMessage')
        @include('Backend._components.alertErrori')

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="text-muted fs-7">Profilo: {{ $user->nominativo() }}</div>
            <a class="btn btn-sm btn-light-primary" href="{{ action([\App\Http\Controllers\Backend\AreaPersonaleController::class,'exportDatiPersonali']) }}">
                Export
            </a>
        </div>

        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-7">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#drawer_tab_dati">Dati</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#drawer_tab_email">Email</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#drawer_tab_pwd">Password</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#drawer_tab_log">Accessi</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="drawer_tab_dati">
                <form method="POST" action="{{ action([\App\Http\Controllers\Backend\AreaPersonaleController::class,'update'],'dati-utente') }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label required">Nome</label>
                        <input type="text" name="nome" class="form-control form-control-solid" required value="{{ old('nome', $user->nome) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Cognome</label>
                        <input type="text" name="cognome" class="form-control form-control-solid" required value="{{ old('cognome', $user->cognome) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Telefono</label>
                        <input type="text" name="telefono" class="form-control form-control-solid" required value="{{ old('telefono', $user->telefono) }}">
                    </div>
                    <div class="text-end"><button class="btn btn-primary btn-sm" type="submit">Salva dati</button></div>
                </form>
            </div>

            <div class="tab-pane fade" id="drawer_tab_email">
                <form method="POST" action="{{ action([\App\Http\Controllers\Backend\AreaPersonaleController::class,'update'],'dati-email') }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control form-control-solid" required value="{{ old('email', $user->email) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Conferma email</label>
                        <input type="email" name="email_confirmation" class="form-control form-control-solid" required value="{{ old('email_confirmation', $user->email) }}">
                    </div>
                    <div class="text-end"><button class="btn btn-primary btn-sm" type="submit">Aggiorna email</button></div>
                </form>
            </div>

            <div class="tab-pane fade" id="drawer_tab_pwd">
                <form method="POST" action="{{ action([\App\Http\Controllers\Backend\AreaPersonaleController::class,'update'],'dati-password') }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label required">Password attuale</label>
                        <input type="password" name="password_attuale" class="form-control form-control-solid" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Nuova password</label>
                        <input type="password" name="password" class="form-control form-control-solid" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Conferma password</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-solid" required autocomplete="new-password">
                    </div>
                    <div class="text-end"><button class="btn btn-primary btn-sm" type="submit">Aggiorna password</button></div>
                </form>
            </div>

            <div class="tab-pane fade" id="drawer_tab_log">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-sm">
                        <thead>
                        <tr class="fw-bolder fs-8 text-uppercase">
                            <th>Data/Ora</th>
                            <th>Esito</th>
                            <th>IP</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logins as $login)
                            <tr>
                                <td>{{ optional($login->created_at)->format('d/m H:i') }}</td>
                                <td>{!! $login->riuscito ? '<span class="badge badge-light-success">OK</span>' : '<span class="badge badge-light-danger">KO</span>' !!}</td>
                                <td>{{ $login->ip ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">Nessun accesso recente.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="separator my-6"></div>
        <a href="/logout" class="btn btn-danger w-100">Logout</a>
    </div>
</div>

@push('customScript')
    <script>
        $(function () {
            @if($errors->any() || session()->has('alertMessage'))
            const accountToggle = document.getElementById('kt_account_toggle');
            if (accountToggle) {
                accountToggle.click();
            }
            @endif
        });
    </script>
@endpush
