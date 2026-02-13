@extends('auth._main')
@section('content')
    <div class="w-lg-500px bg-body rounded shadow-sm p-10 mx-auto">
        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <div class="text-center mb-10">
            <h1 class="text-dark mb-3">Registrazione eseguita</h1>
        </div>
        <div class="notice d-flex bg-light-success rounded border-success border border-dashed p-6 mb-8">
            <span class="svg-icon svg-icon-2tx svg-icon-success me-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <path
                                        d="M21,12.0829584 C20.6747915,12.0283988 20.3407122,12 20,12 C16.6862915,12 14,14.6862915 14,18 C14,18.3407122 14.0283988,18.6747915 14.0829584,19 L5,19 C3.8954305,19 3,18.1045695 3,17 L3,8 C3,6.8954305 3.8954305,6 5,6 L19,6 C20.1045695,6 21,6.8954305 21,8 L21,12.0829584 Z M18.1444251,7.83964668 L12,11.1481833 L5.85557487,7.83964668 C5.4908718,7.6432681 5.03602525,7.77972206 4.83964668,8.14442513 C4.6432681,8.5091282 4.77972206,8.96397475 5.14442513,9.16035332 L11.6444251,12.6603533 C11.8664074,12.7798822 12.1335926,12.7798822 12.3555749,12.6603533 L18.8555749,9.16035332 C19.2202779,8.96397475 19.3567319,8.5091282 19.1603533,8.14442513 C18.9639747,7.77972206 18.5091282,7.6432681 18.1444251,7.83964668 Z"
                                        fill="#000000"/>
                                <circle fill="#000000" opacity="0.3" cx="19.5" cy="17.5" r="2.5"/>
                            </svg>
                        </span>
            <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                <div class="mb-3 mb-md-0 fw-bold">
                    <h4 class="text-gray-900 fw-bolder">La tua registrazione è stata effettuata</h4>
                    <div class="fs-6 text-gray-700 pe-7">
                        <p>Grazie per aver effettuato la registrazione Agente su Gestiio!</p>
                    </div>
                </div>

            </div>
        </div>
        <a href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'])}}" class="btn btn-lg btn-success mb-5 w-100">Vai alla tua area riservata</a>
    </div>
    <div class="d-flex align-items-center mt-6">
        @include('auth.footer')
    </div>
@endsection
@push('customScript')
@endpush

