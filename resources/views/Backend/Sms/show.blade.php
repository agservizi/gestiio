@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    <div class="card">
        <div class="card-body">


<pre>
    {!! print_r($record->response) !!}
</pre>
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

        });
    </script>
@endpush
