@extends('Backend._layout._main')
@section('toolbar')
    <form method="get" action="{{ action([$controller,'index']) }}">
        <select name="status" class="form-select form-select-sm form-select-solid w-140px" onchange="this.form.submit()">
            <option value="">Tutti gli stati</option>
            @foreach($stati ?? [] as $stato)
                <option value="{{ $stato->value }}" {{ ($filtri['status'] ?? '') === $stato->value ? 'selected' : '' }}>{{ $stato->label() }}</option>
            @endforeach
        </select>
        <select name="anno" class="form-select form-select-sm form-select-solid w-100px" onchange="this.form.submit()">
            <option value="">Anno</option>
            @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}" {{ ($filtri['anno'] ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        @if($orderBy ?? false)
            <input type="hidden" name="orderBy" value="{{ $orderBy }}"/>
        @endif
    </form>

    <div class="d-flex align-items-center position-relative" data-bs-toggle="tooltip" title="{{ $testoCerca }}">
        <span class="svg-icon svg-icon-3 position-absolute ms-3 mt-n1">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path opacity="0.3" d="M14.2928932,16.7071068 C13.9023689,16.3165825 13.9023689,15.6834175 14.2928932,15.2928932 C14.6834175,14.9023689 15.3165825,14.9023689 15.7071068,15.2928932 L19.7071068,19.2928932 C20.0976311,19.6834175 20.0976311,20.3165825 19.7071068,20.7071068 C19.3165825,21.0976311 18.6834175,21.0976311 18.2928932,20.7071068 L14.2928932,16.7071068 Z" fill="#000000"/>
                <path d="M11,16 C13.7614237,16 16,13.7614237 16,11 C16,8.23857625 13.7614237,6 11,6 C8.23857625,6 6,8.23857625 6,11 C6,13.7614237 8.23857625,16 11,16 Z M11,18 C7.13400675,18 4,14.8659932 4,11 C4,7.13400675 7.13400675,4 11,4 C14.8659932,4 18,7.13400675 18,11 C18,14.8659932 14.8659932,18 11,18 Z" fill="#000000"/>
            </svg>
        </span>
        <input type="text" id="filter_search" class="form-control form-control-sm form-control-solid fw-bold fs-7 w-175px ps-9" placeholder="Ricerca" aria-label="Ricerca proforma"/>
        <span id="search-spinner" class="spinner-border spinner-border-sm position-absolute end-0 me-3 d-none" role="status"></span>
    </div>

    @isset($ordinamenti)
        <button type="button" class="btn btn-sm btn-icon btn-light" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" aria-label="Ordinamento">
            <i class="bi bi-sort-down fs-3"></i>
        </button>
        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-200px py-3" data-kt-menu="true">
            <div class="menu-item px-3">
                <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Ordinamento</div>
            </div>
            @foreach($ordinamenti as $key => $ordinamento)
                <div class="menu-item px-3">
                    <a href="{{ request()->fullUrlWithQuery(['orderBy' => $key]) }}" class="menu-link flex-stack px-3">
                        {{ $ordinamento['testo'] }}
                        @if($key == $orderBy)
                            <i class="fas fa-check ms-2 fs-7"></i>
                        @endif
                    </a>
                </div>
            @endforeach
        </div>
    @endisset
@endsection

@section('content')
    @include('Backend._components.alertMessage')
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            @include('Backend.FatturaProforma.tabella')
        </div>
    </div>
@endsection

@push('customScript')
<script>
    if (typeof debounce !== 'function') {
        window.debounce = function (fn, wait) {
            var t;
            return function () {
                var ctx = this, args = arguments;
                clearTimeout(t);
                t = setTimeout(function () { fn.apply(ctx, args); }, wait);
            };
        };
    }
    var indexUrl = '{{ action([$controller, 'index']) }}';
    var filterQuery = @json(request()->except(['cerca', 'page']));
    $(function () {
        const $filterSearch = $('#filter_search');
        const $searchSpinner = $('#search-spinner');
        $filterSearch.on('keyup', debounce(function () {
            $searchSpinner.removeClass('d-none');
            $.get(indexUrl, Object.assign({}, filterQuery, {cerca: this.value}), function (resp) {
                $('#tabella').html(typeof base64_decode === 'function' ? base64_decode(resp.html) : atob(resp.html));
                $searchSpinner.addClass('d-none');
                if (typeof KTMenu !== 'undefined') KTMenu.createInstances();
            });
        }, 300));
    });
</script>
@endpush
