@php($user = Auth::user())
@php($logins = \App\Models\RegistroLogin::where('user_id', Auth::id())->latest('id')->limit(8)->get())

@push('customCss')
    <style>
        .account-drawer-card {
            border-left: 1px solid #e5e7eb;
        }

        .account-drawer-head {
            border-radius: 0;
            padding: 18px;
            background: linear-gradient(140deg, #111827 0%, #1d4ed8 55%, #38bdf8 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .account-drawer-head::after {
            content: '';
            position: absolute;
            width: 180px;
            height: 180px;
            right: -70px;
            top: -70px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .16);
        }

        .account-pill {
            border-radius: 999px;
            font-size: .72rem;
            padding: 5px 9px;
        }

        .account-kpis {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 14px;
        }

        .account-kpi {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 10px;
            background: #f9fafb;
        }

        .account-kpi .label {
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 4px;
        }

        .account-kpi .value {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }

        .account-tabs .nav-link {
            padding: 7px 10px;
            font-size: .78rem;
        }

        .account-tabs .nav-link.active {
            color: #1d4ed8;
            border-color: #1d4ed8;
        }

        .account-form .form-control {
            border-radius: 9px;
            font-size: .88rem;
        }

        .account-log-table td,
        .account-log-table th {
            font-size: .78rem;
            vertical-align: middle;
        }
    </style>
@endpush

<div class="card shadow-none rounded-0 w-100 account-drawer-card">
    <div class="account-drawer-head">
        <div class="d-flex justify-content-between align-items-start position-relative" style="z-index: 2;">
            <div>
                <h3 class="mb-1 text-white fw-bold">Gestione account</h3>
                <div class="opacity-75 fs-7">{{ $user->nominativo() }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-icon btn-light h-35px w-35px" id="kt_account_close">
                <span class="svg-icon svg-icon-2">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"/>
                        <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"/>
                    </svg>
                </span>
            </button>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 position-relative" style="z-index: 2;">
            <span class="badge bg-light text-dark account-pill">ID: {{ $user->id }}</span>
            @if($user->email_verified_at)
                <span class="badge bg-success account-pill">Email verificata</span>
            @else
                <span class="badge bg-warning text-dark account-pill">Email non verificata</span>
            @endif
        </div>
    </div>

    <div class="card-body" id="kt_account_body">
        @include('Backend._components.alertMessage')
        @include('Backend._components.alertErrori')

        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted fs-7">Aggiorna i tuoi dati direttamente da qui</span>
            <a class="btn btn-sm btn-light-primary" href="{{ action([\App\Http\Controllers\Backend\AreaPersonaleController::class,'exportDatiPersonali']) }}">
                Export
            </a>
        </div>

        <div class="account-kpis">
            <div class="account-kpi">
                <div class="label">Ultimo accesso</div>
                <div class="value">{{ optional($user->ultimo_accesso)->format('d/m H:i') ?: '-' }}</div>
            </div>
            <div class="account-kpi">
                <div class="label">Accessi recenti</div>
                <div class="value">{{ $logins->count() }}</div>
            </div>
        </div>

        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-4 fs-7 account-tabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#drawer_tab_dati">Dati</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#drawer_tab_email">Email</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#drawer_tab_pwd">Password</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#drawer_tab_log">Accessi</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="drawer_tab_dati">
                <form class="account-form" method="POST" action="{{ action([\App\Http\Controllers\Backend\AreaPersonaleController::class,'update'],'dati-utente') }}">
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
                <form class="account-form" method="POST" action="{{ action([\App\Http\Controllers\Backend\AreaPersonaleController::class,'update'],'dati-email') }}">
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
                <form class="account-form" method="POST" action="{{ action([\App\Http\Controllers\Backend\AreaPersonaleController::class,'update'],'dati-password') }}">
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
                    <table class="table table-row-bordered table-sm account-log-table">
                        <thead>
                        <tr class="fw-bolder text-uppercase">
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

        <div class="separator my-5"></div>
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
