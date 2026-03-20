@extends('Backend._layout._main')
@section('content')
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'store'])}}">
                @csrf
                @if(Auth::user()->hasAnyPermission(['admin']))
                    @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                @else
                    <input type="hidden" name="agente_id" value="{{old('agente_id',$record->agente_id)}}">
                @endif
                @include('Backend._inputs.inputText',['campo'=>'customer_reference','testo'=>'Customer reference'])
                @include('Backend._inputs.inputText',['campo'=>'receiver_name','testo'=>'Nome destinatario reso','required'=>true])
                @include('Backend._inputs.inputText',['campo'=>'receiver_email','testo'=>'Email destinatario reso'])
                @include('Backend._inputs.inputText',['campo'=>'receiver_phone','testo'=>'Telefono destinatario reso'])
                @include('Backend._inputs.inputTextButton',['campo'=>'point_id','testo'=>'Punto InPost','testoButton'=>'Cerca'])
                @include('Backend._inputs.inputText',['campo'=>'point_label','testo'=>'Descrizione punto'])
                @include('Backend._inputs.inputTextArea',['campo'=>'payload_json','testo'=>'Override payload JSON','placeholder'=>'{ \"customField\": \"value\" }'])
                <div class="text-center"><button class="btn btn-primary" type="submit">Crea reso InPost</button></div>
            </form>
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        $(function () {
            if ($('#agente_id').length && $('#agente_id').is('select')) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }
            $(document).on('click', '#button-point_id', function (e) {
                e.preventDefault();
                var url = '{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['inpost_points'])}}?nazione=IT';
                apriModal(url);
            });
        });
    </script>
@endpush
