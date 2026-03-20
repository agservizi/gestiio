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
                @include('Backend._inputs.inputText',['campo'=>'pickup_date','testo'=>'Data ritiro','required'=>true,'placeholder'=>'YYYY-MM-DD'])
                @include('Backend._inputs.inputText',['campo'=>'contact_name','testo'=>'Contatto','required'=>true])
                @include('Backend._inputs.inputText',['campo'=>'contact_email','testo'=>'Email'])
                @include('Backend._inputs.inputText',['campo'=>'contact_phone','testo'=>'Telefono','required'=>true])
                @include('Backend._inputs.inputText',['campo'=>'street','testo'=>'Via','required'=>true])
                @include('Backend._inputs.inputText',['campo'=>'building_number','testo'=>'Numero civico'])
                @include('Backend._inputs.inputText',['campo'=>'post_code','testo'=>'CAP','required'=>true])
                @include('Backend._inputs.inputText',['campo'=>'city','testo'=>'Citta','required'=>true])
                @include('Backend._inputs.inputText',['campo'=>'country_code','testo'=>'Nazione','required'=>true])
                @include('Backend._inputs.inputText',['campo'=>'parcel_count','testo'=>'Numero colli','required'=>true])
                @include('Backend._inputs.inputTextArea',['campo'=>'note','testo'=>'Nota'])
                @include('Backend._inputs.inputTextArea',['campo'=>'payload_json','testo'=>'Override payload JSON','placeholder'=>'{ \"customField\": \"value\" }'])
                <div class="text-center"><button class="btn btn-primary" type="submit">Crea ritiro InPost</button></div>
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
        });
    </script>
@endpush
