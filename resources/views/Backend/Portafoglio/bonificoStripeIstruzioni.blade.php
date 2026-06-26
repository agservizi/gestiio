@extends('Backend._layout._main')
@section('content')
    @php
        $hostedUrl = data_get($istruzioni, 'hosted_instructions_url');
        $reference = data_get($istruzioni, 'reference');
        $amountRemaining = data_get($istruzioni, 'amount_remaining', (int) round($importo * 100));
        $currency = strtoupper(data_get($istruzioni, 'currency', 'eur'));
        $addresses = data_get($istruzioni, 'financial_addresses', []);
    @endphp
    <div class="card">
        <div class="card-body">
            <h4>Coordinate bonifico Stripe</h4>
            <div class="alert alert-primary mt-3" role="alert">
                Importo da trasferire: <strong>{{importo($amountRemaining / 100, true)}} {{$currency}}</strong>
                @if($reference)
                    <br>Riferimento/causale: <strong>{{$reference}}</strong>
                @endif
            </div>

            @foreach($addresses as $address)
                @if(data_get($address, 'type') === 'iban')
                    <div class="border rounded p-4 mb-4">
                        <div><strong>IBAN:</strong> {{data_get($address, 'iban.iban')}}</div>
                        <div><strong>BIC:</strong> {{data_get($address, 'iban.bic')}}</div>
                        <div><strong>Banca:</strong> {{data_get($address, 'iban.bank_name')}}</div>
                        <div><strong>Intestatario:</strong> {{data_get($address, 'iban.account_holder_name')}}</div>
                        <div><strong>Reti supportate:</strong> {{implode(', ', data_get($address, 'supported_networks', []))}}</div>
                    </div>
                @endif
            @endforeach

            @if($hostedUrl)
                <a href="{{$hostedUrl}}" target="_blank" rel="noopener" class="btn btn-primary">
                    Apri istruzioni Stripe
                </a>
            @endif
            <a href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'create'])}}" class="btn btn-light ms-2">
                Torna alla ricarica
            </a>
        </div>
    </div>
@endsection
