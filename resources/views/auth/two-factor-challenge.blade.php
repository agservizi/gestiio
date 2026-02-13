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
                <h1 class="text-dark mb-3">Codice autenticazione a due fattori</h1>
                <p class="font-size-16 font-weight-medium text-start">Inserisci il codice generato dalla tua app
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
            @php($userLoginId=session()->get('user_login_id'))
            <!--begin::Actions-->
            <!--begin::Wrapper-->
            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                @if($userLoginId)
                    @php($user=\App\Models\User::find($userLoginId))
                    @if($user)
                        <div><a href="javascript:invia({{$user->id}});"
                                class="link-primary ">Invia il codice con sms al tuo numero {{$user->telefono}}</a></div>
                    @endif
                @endif
                <!--begin::Link-->
                <a href="javascript:void(0)"
                   class="link-primary " id="use-recovery">Usa il recovery code</a>
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

        function invia(id) {
            $.ajax({
                url: '/send-sms/' + id,
                method: 'POST',
                data: {
                    _token: '{{csrf_token()}}'
                },
                dataType: 'json',
                success: function (response) {
                    Swal.fire(
                        "Fatto!",
                        'Il codice è stato inviato con sms.',
                        "info"
                    )

                },
                error: function (xhr, ajaxOptions, thrownError) {
                    var err = eval("(" + xhr.responseText + ")");
                    Swal.fire(
                        "Errore " + xhr.status,
                        err.message,
                        "error"
                    )
                }

            });

        }
    </script>
@endpush
