@extends('Backend._layout._main')
@section('content')
    @php($vecchio=$record->id)
    <div class="card recharge-page">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <div class="recharge-heading mb-8">
                <div>
                    <div class="text-uppercase fw-semibold text-primary fs-8 mb-2">Plafond agente</div>
                    <h3 class="mb-1">Scegli come ricaricare</h3>
                    <div class="text-muted fs-6">Carta immediata, bonifico Stripe con accredito automatico alla conferma dell'incasso.</div>
                </div>
            </div>
            <div class="row g-8 align-items-stretch">
                <div class="col-xl-6">
                    <section class="recharge-method h-100">
                        @include('Backend.Portafoglio.bonificoStripe')
                    </section>
                </div>
                <div class="col-xl-6">
                    <section class="recharge-method h-100">
                        @if(($intent ?? null) && ($stripePublicKey ?? null))
                            @include('Backend.Portafoglio.cartaCredito')
                        @else
                            <div class="recharge-method-head">
                                <div>
                                    <h4>Ricarica con carta</h4>
                                    <p>Pagamento immediato tramite Stripe.</p>
                                </div>
                                <span class="badge badge-light-warning">Non disponibile</span>
                            </div>
                            <div class="alert alert-warning mt-6" role="alert">
                                {{ $stripeErrore ?? 'Ricarica con carta momentaneamente non disponibile.' }}
                            </div>
                        @endif
                    </section>
                </div>
            </div>
            <div class="recharge-note mt-8">
                <span class="fw-semibold">Accredito:</span>
                carta subito dopo il pagamento; bonifico dopo conferma Stripe.
            </div>
        </div>
    </div>
@endsection
@push('customCss')
    <style>
        .recharge-page {
            --recharge-border: #e7eef7;
            --recharge-soft: #f6f9fc;
            --recharge-text: #162033;
            --recharge-muted: #7e8aa6;
        }

        .recharge-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--recharge-border);
        }

        .recharge-method {
            padding: 1.75rem;
            border: 1px solid var(--recharge-border);
            border-radius: 8px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        }

        .recharge-method-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            min-height: 68px;
            padding-bottom: 1.25rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--recharge-border);
        }

        .recharge-method-head h4 {
            margin: 0 0 .35rem;
            color: var(--recharge-text);
            font-size: 1.15rem;
            font-weight: 700;
        }

        .recharge-method-head p {
            margin: 0;
            color: var(--recharge-muted);
            font-size: .95rem;
            line-height: 1.45;
        }

        .recharge-page .row.mb-6 {
            margin-bottom: 1.35rem !important;
        }

        .recharge-page .col-form-label {
            padding-top: .7rem;
        }

        .recharge-page .form-text {
            color: var(--recharge-muted);
            font-size: .9rem !important;
            line-height: 1.45;
        }

        .recharge-page .form-select,
        .recharge-page .form-control,
        .recharge-page .select2-container--bootstrap5 .select2-selection {
            min-height: 44px;
            border-radius: 6px;
            background-color: var(--recharge-soft);
            border-color: transparent;
        }

        .recharge-page .select2-container--bootstrap5 .select2-selection--single .select2-selection__rendered {
            color: #3b4663;
            line-height: 44px;
            padding-left: 1rem;
        }

        .recharge-page .select2-container--bootstrap5 .select2-selection--single .select2-selection__arrow {
            height: 44px;
            right: .85rem;
        }

        .select2-dropdown {
            border: 1px solid var(--recharge-border, #e7eef7) !important;
            border-radius: 8px !important;
            box-shadow: 0 14px 36px rgba(22, 32, 51, .12);
            overflow: hidden;
        }

        .select2-results__option {
            min-height: 40px;
            padding: .7rem 1rem;
        }

        .select2-results__option--highlighted {
            background-color: #eef7ff !important;
            color: #009ef7 !important;
        }

        .select2-results__option[aria-selected="true"] {
            background-color: #e8f3ff !important;
            color: #162033 !important;
            font-weight: 600;
        }

        .recharge-note {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            color: #526079;
            background: #f7f9fc;
            border: 1px solid var(--recharge-border);
        }

        .StripeElement {
            box-sizing: border-box;
            height: 44px;
            padding: 10px 12px;
            border: 1px solid transparent;
            border-radius: 6px;
            background-color: var(--recharge-soft);
            box-shadow: none;
            -webkit-transition: box-shadow 150ms ease;
            transition: box-shadow 150ms ease;
        }

        .StripeElement--focus {
            box-shadow: 0 0 0 3px rgba(0, 158, 247, .12);
        }

        .StripeElement--invalid {
            border-color: #fa755a;
        }

        .StripeElement--webkit-autofill {
            background-color: #fefde5 !important;
        }

        @media (max-width: 767.98px) {
            .recharge-method {
                padding: 1.25rem;
            }

            .recharge-method-head {
                flex-direction: column;
                min-height: auto;
            }

            .recharge-page .col-form-label {
                text-align: left !important;
                padding-bottom: .35rem;
            }
        }
    </style>
@endpush
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
            autonumericImporto('autonumericImporto');
        });
    </script>
    @if(($intent ?? null) && ($stripePublicKey ?? null))
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            const stripePublicKey = @json($stripePublicKey);
            let stripe = Stripe(stripePublicKey)
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
                            billing_details: {name: $('#card_holder_name').val()}
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
    @endif
@endpush
