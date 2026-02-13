@extends('auth._main-split')
@section('content')

    <form id="form" class="form w-100" method="POST" action="{{ \Illuminate\Support\Facades\Route::has('login')?route('login'):'' }}">
        @csrf
        <!--begin::Heading-->
        <div class="text-center mb-11">
            <!--begin::Title-->
            <h1 class="text-dark fw-bolder mb-3">Accedi</h1>
            <!--end::Title-->
        </div>
        <!--begin::Heading-->

        <!--begin::Input group=-->
        <div class="fv-row mb-8">
            <!--begin::Email-->
            <input type="text" placeholder="Email o numero di telefono" id="email" name="email" autocomplete="email"
                   class="form-control bg-transparent @error('email') is-invalid @enderror"
                   value="{{ old('email',request()->input('email')) }}" required/>
            <!--end::Email-->
            @error('email')
            <div class="fv-plugins-message-container invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <!--end::Input group=-->
        <div class="fv-row mb-3">
            <!--begin::Password-->
            <input type="password" placeholder="Password" id="password" name="password" autocomplete="off"
                   class="form-control bg-transparent @error('password') is-invalid @enderror"
                   value="{{request()->input('password')}}"
            />
            <!--end::Password-->
            @error('password')
            <div class="fv-plugins-message-container invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <!--end::Input group=-->


        <!--begin::Wrapper-->
        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            <div></div>
            <!--begin::Link-->
            <a href="{{\Illuminate\Support\Facades\Route::has('password.request')? route('password.request') :''}}"
               class="link-primary ">Hai dimenticato la
                password ?</a>
            <!--end::Link-->
        </div>
        <!--end::Wrapper-->
        <div class="mb-10">
            <div class="form-check form-check-custom">
                <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                <label class="form-check-label" for="remember">Ricordami</label>
            </div>
        </div>

        <!--begin::Submit button-->
        <div class="d-grid mb-10">
            <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100 mb-5">
                <span class="indicator-label">Accedi</span>
            </button>
        </div>
        @if(env('APP_NAME')=='gestiio-demo' )
            <div class="separator separator-content my-14">
                <span class="w-325px text-gray-700 fw-semibold fs-5">Dati accesso per test</span>
            </div>
            <table class="table table-row-dashed table-row-gray-300">
                <thead>
                <tr class="fw-bolder fs-6 text-gray-800">
                    <th>utente</th>
                    <th>email</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach(config('configurazione.accessi_test') as $accesso)
                    <tr class="fs-7">
                        <td>{{$accesso['descrizione']}}</td>
                        <td>{{$accesso['email']}}</td>
                        <td>
                            <button type="button" onClick="impostaLogin('{{$accesso['email']}}','{{$accesso['password']}}');">Usa</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </form>

@endsection
@push('customScript')
    <script>
        function impostaLogin(email, password) {
            $('#email').val(email);
            $('#password').val(password);
            $('#form').submit();
        }
    </script>
@endpush
