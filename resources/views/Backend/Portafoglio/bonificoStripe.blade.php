<div class="recharge-method-head">
    <div>
        <h4>Ricarica con bonifico Stripe</h4>
        <p>Genera coordinate bancarie Stripe e accredita il plafond alla conferma.</p>
    </div>
    <span class="badge badge-light-success">Automatico</span>
</div>
@if(session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
<form method="POST" action="{{action([\App\Http\Controllers\Backend\PaymentController::class,'storeBonificoStripe'])}}"
      class="mt-3">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <div class="row mb-6" id="div_importo_bonifico_stripe">
                <div class="col-lg-4 col-form-label text-lg-end">
                    <label class="form-check-label fw-bold fs-6 required">Importo</label>
                </div>
                <div class="col-lg-8 pt-3">
                    <div class="d-flex flex-wrap">
                        @foreach([20=>importo(20,true),50=>importo(50,true),100=>importo(100,true)] as $key=>$value)
                            <div class="form-check form-check-custom form-check-solid me-10 mb-2">
                                <input class="form-check-input importo-bonifico-stripe" type="radio" value="{{$key}}"
                                       name="importo" id="bonifico_stripe_importo_{{$key}}" required
                                       {{old('importo') == $key ? 'checked' : ''}}>
                                <label class="form-check-label" for="bonifico_stripe_importo_{{$key}}">{!! $value !!}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text fs-5">
                        Il plafond viene accreditato automaticamente quando Stripe conferma il bonifico.
                    </div>
                    <div class="fv-plugins-message-container invalid-feedback">
                        @error('importo')
                        {{$message}}
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="row mb-6" id="div_portafoglio_bonifico_stripe">
                <div class="col-lg-4 col-form-label text-lg-end">
                    <label class="fw-bold fs-6 required" for="portafoglio_bonifico_stripe">Portafoglio</label>
                </div>
                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                    <select id="portafoglio_bonifico_stripe" name="portafoglio" class="form-select form-select-solid"
                            required data-required="1" data-kt-select2="true" data-placeholder="Seleziona"
                            data-minimum-results-for-search="Infinity">
                        <option value="">Seleziona</option>
                        @foreach(\App\Enums\TipiPortafoglioEnum::cases() as $item)
                            <option value="{{$item->value}}" {{old('portafoglio') == $item->value ? 'selected' : ''}}>
                                {{$item->testo()}}
                            </option>
                        @endforeach
                    </select>
                    <div class="fv-plugins-message-container invalid-feedback">
                        @error('portafoglio')
                        {{$message}}
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-2">
        Genera coordinate Stripe
    </button>
</form>
