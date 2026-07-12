@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        @foreach(['oggi'=>'Oggi','prenotati'=>'Prenotati','attivi'=>'In custodia','completati'=>'Completati','annullati'=>'Annullati'] as $key=>$label)
            <a href="{{ action([$controller,'index'], ['view'=>$key]) }}"
               class="btn btn-sm {{ ($view ?? 'oggi') === $key ? 'btn-primary' : 'btn-light' }}">{{ $label }}</a>
        @endforeach
        <div class="d-flex align-items-center position-relative ms-md-2" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Cerca codice o cliente">
            <span class="svg-icon svg-icon-3 position-absolute ms-3 mt-n1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path opacity="0.3" d="M14.2928932,16.7071068 C13.9023689,16.3165825 13.9023689,15.6834175 14.2928932,15.2928932 C14.6834175,14.9023689 15.3165825,14.9023689 15.7071068,15.2928932 L19.7071068,19.2928932 C20.0976311,19.6834175 20.0976311,20.3165825 19.7071068,20.7071068 C19.3165825,21.0976311 18.6834175,21.0976311 18.2928932,20.7071068 L14.2928932,16.7071068 Z" fill="#000000"/>
                    <path d="M11,16 C13.7614237,16 16,13.7614237 16,11 C16,8.23857625 13.7614237,6 11,6 C8.23857625,6 6,8.23857625 6,11 C6,13.7614237 8.23857625,16 11,16 Z M11,18 C7.13400675,18 4,14.8659932 4,11 C4,7.13400675 7.13400675,4 11,4 C14.8659932,4 18,7.13400675 18,11 C18,14.8659932 14.8659932,18 11,18 Z" fill="#000000"/>
                </svg>
            </span>
            <input type="text" id="filter_search" class="form-control form-control-sm form-control-solid fw-bold fs-7 w-200px ps-9" placeholder="Ricerca"/>
        </div>
        <a class="btn btn-sm btn-primary fw-bold" href="{{ action([$controller,'create']) }}">Nuovo {{ \App\Models\LuggageDeposit::NOME_SINGOLARE }}</a>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            @include('Backend.LuggageDeposit.tabella')
        </div>
    </div>
@endsection

@push('customScript')
<script>
    window.indexUrl = "{{ action([$controller, 'index']) }}";
    $('#filter_search').on('keyup', debounce(function () {
        $.get(window.indexUrl, { q: this.value, view: '{{ $view ?? 'oggi' }}' }, function (resp) {
            $('#tabella').html(atob(resp.html));
            if (typeof KTMenu !== 'undefined') {
                KTMenu.createInstances();
            }
        });
    }, 300));
</script>
@endpush
