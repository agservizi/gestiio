@extends('Backend._layout._main')
@section('content')
    <div class="card">
        <div class="card-body">
            @if($success)
                @include('Backend._components.notice',['level'=>'success','titolo'=>'Pagamento avvenuto con successo','testo'=>'Il tuo portafoglio è stato ricaricato'])
            @else
                @include('Backend._components.notice',['level'=>'danger','titolo'=>'Errore nel pagamento','testo'=>'Il pagamento non è andato a buon fine'])

            @endif
        </div>
    </div>
@endsection



