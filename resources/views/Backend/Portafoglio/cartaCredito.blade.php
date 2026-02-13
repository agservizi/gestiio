<h4>Ricarica con carta di credito</h4>
@if(session('message'))
    <div class="alert alert-success" role="alert">{{ session('message') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
<form method="POST" action="{{action([\App\Http\Controllers\Backend\PaymentController::class,'storePagamento'])}}"
      class="card-form mt-3 mb-3">
    @csrf
    <div class="row">
        <div class="col-md-12">
            @include('Backend._inputs.inputRadioH',['campo'=>'importo','testo'=>'Importo','required'=>true,'help'=>'Per ogni transazione verrà applicata una commissione di €1,00','array' => [20=>\App\importo(20,true),50=>\App\importo(50,true),100=>\App\importo(100,true)]])
        </div>
        <div class="col-md-12">
            @include('Backend._inputs.inputSelect2Enum',['campo'=>'portafoglio','testo'=>'Portafoglio','required'=>true,'cases'=>\App\Enums\TipiPortafoglioEnum::class])
        </div>
    </div>


    <div class="row">
        <div class="col-md-12">

            <div class="row mb-6">
                <div class="col-lg-4 col-form-label text-lg-end">
                    <label class="fw-bold fs-6  required " for="card_holder_name">Titolare della carta</label>
                </div>
                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                    <input id="card_holder_name" class="StripeElement mb-3 form-control form-control-solid"
                           name="card_holder_name" placeholder="Card holder name"
                           required
                           value="{{\Illuminate\Support\Facades\Auth::user()->nominativo()}}">
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">


            <div class="row mb-6">
                <div class="col-lg-4 col-form-label text-lg-end">
                    <label class="fw-bold fs-6  required " for="card_holder_name">Dati carta</label>

                </div>
                <div class="col-lg-8 fv-row fv-plugins-icon-container">


                    <input type="hidden" name="payment_method" class="payment-method">

                    <div id="card-element"></div>
                    <div id="card-errors" role="alert"></div>
                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary pay">
                            Conferma il pagamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

