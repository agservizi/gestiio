@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'store'])}}">
                @csrf
                @method('POST')
                <h4>Invia a {{$contatti}} contatti</h4>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextAreaCol',['campo'=>'testo','testo'=>'Testo','required'=>true])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSwitch',['campo'=>'test','testo'=>'Test a '.Auth::user()->telefono])
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit" id="submit">Invia</button>
                    </div>
                </div>
            </form>
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
