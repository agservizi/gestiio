@extends('Backend._layout._main')
@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <div class="row">
                <div class="col-md-6 fs-5">
                    <h4>Ricarica con bonifico</h4>
                    <span class="fw-bold">Iban: <span class="fw-normal">IT59W3606401600I08442881218</span></span><br>
                    <span class="fw-bold">Intestato a: <span class="fw-normal">AG SERVIZI VIA PLINIO 72 DI CAVALIERE CARMINE</span></span><br>
                    <span class="fw-bold">Indicare nella causale: <span class="fw-normal">Ricarica plafond per servizi</span></span><br>
                </div>
                <div class="col-md-6 pt-sm-8 pt-md-0">
                    @includeWhen(true,'Backend.Portafoglio.cartaCredito')
                </div>
            </div>
        </div>
    </div>
    <div class="row g-5">
        <div class="col-md-7 col-lg-8">
        </div>
    </div>
@endsection
@push('customCss')
    <style>
        .StripeElement {
            box-sizing: border-box;
            height: 40px;
            padding: 10px 12px;
            border: 1px solid transparent;
            border-radius: 4px;
            background-color: white;
            box-shadow: 0 1px 3px 0 #e6ebf1;
            -webkit-transition: box-shadow 150ms ease;
            transition: box-shadow 150ms ease;
        }

        .StripeElement--focus {
            box-shadow: 0 1px 3px 0 #cfd7df;
        }

        .StripeElement--invalid {
            border-color: #fa755a;
        }

        .StripeElement--webkit-autofill {
            background-color: #fefde5 !important;
        }
    </style>
@endpush
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>
    <script src="https://js.stripe.com/v3/"></script>

    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            autonumericImporto('autonumericImporto');
        });


    </script>
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        let stripe = Stripe("{{ env('STRIPE_KEY') }}")
        let elements = stripe.elements()
        let style = {
            base: {
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        }
        let card = elements.create('card', {style: style})
        card.mount('#card-element')
        let paymentMethod = null;
        $('.card-form').on('submit', function (e) {
            $('button.pay').attr('disabled', true)
            if (paymentMethod) {
                return true
            }
            stripe.confirmCardSetup(
                "{{ $intent->client_secret }}",
                {
                    payment_method: {
                        card: card,
                        billing_details: {name: $('.card_holder_name').val()}
                    }
                }
            ).then(function (result) {
                if (result.error) {
                    $('#card-errors').text(result.error.message)
                    $('button.pay').removeAttr('disabled')
                } else {
                    paymentMethod = result.setupIntent.payment_method;
                    $('.payment-method').val(paymentMethod)
                    $('.card-form').submit()
                }
            })
            return false
        })
    </script>
@endpush
