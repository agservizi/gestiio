@extends('Backend._layout._main')
@section('content')
    <div class="card">
        <div class="card-body">

            <div class="row">
                <div class="col-md-8 col-md-offset-2">

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="post" action="{{ route('settings.store') }}" class="form-horizontal" role="form">
                        {!! csrf_field() !!}

                        @if(count(config('setting_fields', [])) )

                            @foreach(config('setting_fields') as $section => $fields)
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <i class="{{ \Illuminate\Support\Arr::get($fields, 'icon', 'glyphicon glyphicon-flash') }}"></i>
                                        <h4> {{ $fields['title'] }}</h4>
                                    </div>

                                    <p class="fw-bold">{{ $fields['desc'] }}</p>

                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-7  col-md-offset-2">
                                                @foreach($fields['elements'] as $field)
                                                    @includeIf('Backend.Setting.Fields.' . $field['type'] )
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            <!-- end panel for {{ $fields['title'] }} -->
                            @endforeach

                        @endif

                        <div class="row m-b-md">
                            <div class="col-md-12">
                                <button class="btn-primary btn">
                                    Salva impostazioni
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('customScript')
    <script src="/assets_backend/js-miei/autoNumeric.js"></script>

    <script>

        $(function () {
            autonumericImporto('importo');
        });
    </script>
@endpush
