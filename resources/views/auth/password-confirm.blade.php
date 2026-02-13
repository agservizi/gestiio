@extends('auth._main')
@section('content')
    <div class="w-lg-500px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto">

        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif
        <form class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework" method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <div class="text-center mb-10">
                <h1 class="text-dark mb-3">Conferma la tua password</h1>
                <p class="font-size-16 font-weight-medium text-start">Conferma la tua password per attivare l'autenticazione a due fattori
                </p>
            </div>

            <!--begin::Input group=-->
            <div class="fv-row mb-8">
                <!--begin::password-->
                <input type="text" placeholder="password" name="password" autocomplete="off" class="form-control bg-transparent @error('password') is-invalid @enderror"
                       value="{{ old('password') }}" required/>
                <!--end::password-->
                @error('password')
                <div class="fv-plugins-message-container invalid-feedback">{{ $message }}</div>
                @enderror

            </div>
            <!--begin::Actions-->

            <div class="text-center">
                <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100 mb-5">
                    <span class="indicator-label">Invia</span>
                </button>
            </div>
            <div></div>
        </form>
    </div>
@endsection
