@extends('auth._main-split')
@section('content')

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif
    <form class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework" method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="text-center mb-10">
            <h1 class="text-dark mb-3">Hai dimenticato la password</h1>
            <p class="font-size-16 font-weight-medium text-start">Inserisci l'email del tuo account.<br>
                Riceverai una mail con un collegamento per reimpostare la password<br>
            </p>
        </div>

        <!--begin::Input group=-->
        <div class="fv-row mb-8">
            <!--begin::Email-->
            <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent @error('email') is-invalid @enderror" value="{{ old('email') }}" required/>
            <!--end::Email-->
            @error('email')
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


@endsection
