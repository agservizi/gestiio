@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        @include('Backend._components.ricercaIndex')
        @isset($ordinamenti)
            <div class="me-4 d-none d-md-block">
                <button class="btn btn-sm btn-icon bg-body btn-color-gray-700 btn-active-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
                        data-kt-menu-flip="top-end">
                    <i class="bi bi-sort-down fs-3"></i>
                </button>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-200px py-3" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Ordinamento</div>
                    </div>
                    @foreach($ordinamenti as $key=>$ordinamento)
                        <div class="menu-item px-3">
                            <a href="{{Request::url()}}?orderBy={{$key}}" class="menu-link flex-stack px-3">{{$ordinamento['testo']}}
                                @if($key==$orderBy)
                                    <i class="fas fa-check ms-2 fs-7"></i>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endisset
            <a class="btn btn-sm btn-success fw-bold d-none d-md-block me-4" data-targetZ="kt_modal" data-toggleZ="modal-ajax" href="{{action([\App\Http\Controllers\Backend\EsportaController::class,'esporta'],'clienti')}}">Esporta csv</a>
            <a class="btn btn-sm btn-primary fw-bold" data-targetZ="kt_modal" data-toggleZ="modal-ajax" href="{{action([$controller,'create'])}}"><span class="d-md-none">+</span><span
                        class="d-none d-md-block">{{$testoNuovo}}</span></a>
    </div>
@endsection
@section('content')
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            @include('Backend.Cliente.tabella')
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';

        $(function () {
            searchHandler();
        });
    </script>
@endpush
