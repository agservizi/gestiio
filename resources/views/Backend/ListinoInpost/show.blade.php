@extends('Backend._layout._main')

@section('content')
    <div class="card">
        <div class="card-body">
            @php
                $label = match($record->package_type) {
                    'small' => 'Piccolo (S)',
                    'medium' => 'Medio (M)',
                    'large' => 'Grande (L)',
                    default => strtoupper((string) $record->package_type),
                };
            @endphp
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'package_type','testo'=>'Package','valore'=>$label])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'locker_point','testo'=>'Locker o punto di ritiro','valore'=>importo($record->locker_point,true)])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'home_delivery','testo'=>'Indirizzo del destinatario','valore'=>importo($record->home_delivery,true)])
                </div>
            </div>
        </div>
    </div>
@endsection
