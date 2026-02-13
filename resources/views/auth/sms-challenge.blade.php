@extends('auth._main')
@section('content')
    <div class="w-lg-500px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto" id="put-code">

        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif
        <form class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework" method="POST" action="{{ route('two-factor.login') }}">
            @csrf
            <div class="text-center mb-10">
                <h1 class="text-dark mb-3">Codice di sicurezza</h1>
                <p class="font-size-16 font-weight-medium">Inserisci il codice che ti è stato inviato via sms
                </p>
            </div>

            <!--begin::Input group=-->
            <div class="fv-row mb-8">
                <!--begin::code-->
                <input type="text" placeholder="Codice" name="code" autocomplete="off" class="form-control bg-transparent @error('code') is-invalid @enderror"
                       value="{{ old('code') }}" required/>
                <!--end::code-->
                @error('code')
                <div class="fv-plugins-message-container invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <!--begin::Actions-->
            @if(false)
                <!--begin::Wrapper-->
                <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                    <div></div>
                    <!--begin::Link-->
                    <a href="javascript:void(0)"
                       class="link-primary " id="use-recovery">Usa il recovery code</a>
                    <!--end::Link-->
                </div>
            @endif

            <div class="text-center">
                <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100 mb-5">
                    <span class="indicator-label">Invia</span>
                </button>
            </div>
            <div></div>
        </form>
    </div>
    <div class="w-lg-500px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto" style="display: none;" id="recovery">

        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif
        <form class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework" method="POST" action="{{ route('two-factor.login') }}">
            @csrf
            <div class="text-center mb-10">
                <h1 class="text-dark mb-3">Codice di recovery</h1>
                <p class="font-size-16 font-weight-medium text-start">Inserisci il recovery code
                </p>
            </div>

            <!--begin::Input group=-->
            <div class="fv-row mb-8">
                <!--begin::code-->
                <input type="text" placeholder="Codice di recovery" name="recovery_code" autocomplete="off" class="form-control bg-transparent @error('code') is-invalid @enderror"
                       value="{{ old('recovery_code') }}" required/>
                <!--end::code-->
                @error('recovery_code')
                <div class="fv-plugins-message-container invalid-feedback">{{ $message }}</div>
                @enderror

            </div>
            <!--begin::Actions-->
            <!--begin::Wrapper-->
            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                <div></div>
                <!--begin::Link-->
                <a href="javascript:void(0)"
                   class="link-primary " id="use-code">Usa codice di autorizzazione</a>
                <!--end::Link-->
            </div>


            <div class="text-center">
                <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100 mb-5">
                    <span class="indicator-label">Invia</span>
                </button>
            </div>
            <div></div>
        </form>
    </div>
@endsection
@push('customScript')
    <script>
        $(function () {
            $('#use-recovery').click(function () {
                $('#put-code').hide();
                $('#recovery').show();
            })
            $('#use-code').click(function () {
                $('#recovery').hide();
                $('#put-code').show();

            })
        });
    </script>
@endpush
