<?php

namespace App\Http\Services;

use App\Models\ChiamataApi;
use App\Models\SpedizioneBrt;
use Illuminate\Support\Facades\Http;

class BrtService
{
    const DEFAULT_BASE_URL = 'https://api.brt.it/rest/v1';
    const DEFAULT_PUDO_URL = 'https://api.brt.it/pudo/v1/open/pickup/get-pudo-by-address';

    protected $userId;
    protected $password;
    protected $baseUrl;
    protected $pudoUrl;
    protected $pudoToken;
    protected $departureDepot;
    protected $senderCustomerCode;
    protected $defaultNetwork;
    protected $defaultServiceType;
    protected $defaultPudoId;
    protected $defaultDeliveryFreightTypeCode;
    protected $labelRequired;
    protected $labelOutputType;
    protected $labelOffsetX;
    protected $labelOffsetY;
    protected $labelBorder;
    protected $labelLogo;
    protected $labelBarcodeRow;

    public function __construct()
    {
        $this->userId = config('services.brt.user');
        $this->password = config('services.brt.password');
        $this->baseUrl = rtrim(config('services.brt.base_url', self::DEFAULT_BASE_URL), '/');
        $this->pudoUrl = $this->normalizePudoUrl((string)config('services.brt.pudo_base_url', self::DEFAULT_PUDO_URL));
        $this->pudoToken = config('services.brt.pudo_auth_token');
        $this->departureDepot = (int)config('services.brt.departure_depot', 122);
        $this->senderCustomerCode = (string)config('services.brt.sender_customer_code', $this->userId);
        $this->defaultNetwork = (string)config('services.brt.default_network', ' ');
        $this->defaultServiceType = (string)config('services.brt.default_service_type', '');
        $this->defaultPudoId = (string)config('services.brt.default_pudo_id', '');
        $this->defaultDeliveryFreightTypeCode = (string)config('services.brt.default_delivery_freight_type_code', 'DAP');
        $this->labelRequired = $this->toBool(config('services.brt.label_required', true));
        $this->labelOutputType = (string)config('services.brt.label_output_type', 'PDF');
        $this->labelOffsetX = config('services.brt.label_offset_x', 0);
        $this->labelOffsetY = config('services.brt.label_offset_y', 0);
        $this->labelBorder = $this->toBool(config('services.brt.label_border', false));
        $this->labelLogo = $this->toBool(config('services.brt.label_logo', false));
        $this->labelBarcodeRow = $this->toBool(config('services.brt.label_barcode_row', false));
    }


    public function shipment(SpedizioneBrt $record)
    {
        $url = $this->baseUrl . '/shipments/shipment';

        $pricingConditionCode = $this->determinePricingConditionCode($record);


        $payload = [
            "account" => [
                "userID" => $this->userId,
                "password" => $this->password,
            ],
            "createData" => [
                "network" => $this->normalizeNetwork($this->defaultNetwork),
                "departureDepot" => $this->departureDepot,
                "senderCustomerCode" => $this->senderCustomerCode,
                "deliveryFreightTypeCode" => $record->tipo_porto ?: $this->defaultDeliveryFreightTypeCode,
                "consigneeCompanyName" => $record->ragione_sociale_destinatario,
                "consigneeAddress" => $record->indirizzo_destinatario,
                "consigneeZIPCode" => $record->cap_destinatario,
                "consigneeCity" => $record->localita_destinazione,
                "consigneeProvinceAbbreviation" => $record->provincia_destinatario,
                "consigneeCountryAbbreviationISOAlpha2" => $record->nazione_destinazione,
                "consigneeContactName" => "",
                "consigneeTelephone" => "",
                "consigneeEMail" => "",
                "consigneeMobilePhoneNumber" => $record->mobile_referente_consegna,
                "isAlertRequired" => 0,
                "consigneeVATNumber" => "",
                "consigneeVATNumberCountryISOAlpha2" => "",
                "consigneeItalianFiscalCode" => "",
                "pricingConditionCode" => $pricingConditionCode,
                "serviceType" => $this->defaultServiceType,
                "insuranceAmount" => 0,
                "insuranceAmountCurrency" => "",
                "senderParcelType" => "",
                "quantityToBeInvoiced" => 0,
                "cashOnDelivery" => $record->contrassegno ?? 0,
                "isCODMandatory" => $record->contrassegno ? 1 : 0,
                "codPaymentType" => "  ",
                "codCurrency" => "",
                "notes" => "",
                "parcelsHandlingCode" => "",
                "deliveryDateRequired" => "",
                "deliveryType" => "",
                "declaredParcelValue" => 0,
                "declaredParcelValueCurrency" => "",
                "particularitiesDeliveryManagementCode" => "",
                "particularitiesHoldOnStockManagementCode" => "",
                "variousParticularitiesManagementCode" => "",
                "particularDelivery1" => "",
                "particularDelivery2" => "",
                "originalSenderCompanyName" => $record->nome_mittente,
                "originalSenderZIPCode" => "",//$record->cap_mittente,
                "originalSenderCountryAbbreviationISOAlpha2" => "",
                "cmrCode" => "",
                "neighborNameMandatoryAuthorization" => "",
                "pinCodeMandatoryAuthorization" => "",
                "packingListPDFName" => "",
                "packingListPDFFlagPrint" => "",
                "packingListPDFFlagEmail" => "",
                "numericSenderReference" => $record->id,
                //"alphanumericSenderReference" => $record->nome_mittente,
               // "actualSender" => Str::limit($record->nome_mittente, 70, ''),
                "numberOfParcels" => $record->numero_pacchi,
                "weightKG" => $record->peso_totale,
                "volumeM3" => $record->volume_totale,
                "pudoId" => $record->pudo_id ?: $this->defaultPudoId,
            ],
            "isLabelRequired" => $this->labelRequired ? 1 : 0,
            "labelParameters" => $this->labelParametersFromConfig(),
        ];

        return $this->requestJson('post', $url, $payload, $record);
    }

    public function confirm(SpedizioneBrt $record)
    {
        $url = $this->baseUrl . '/shipments/shipment';

        $payload = [
            "account" => [
                "userID" => $this->userId,
                "password" => $this->password,
            ],
            "confirmData" => [
                "senderCustomerCode" => $this->senderCustomerCode,
                "numericSenderReference" => $record->id,
            ]
        ];

        return $this->requestJson('put', $url, $payload, $record);
    }

    public function routing(SpedizioneBrt $record)
    {
        $url = $this->baseUrl . '/shipments/routing';

        $payload = [
            "account" => [
                "userID" => $this->userId,
                "password" => $this->password,
            ],
            "routingData" => [
                "network" => $this->normalizeNetwork($this->defaultNetwork),
                "departureDepot" => $this->departureDepot,
                "senderCustomerCode" => $this->senderCustomerCode,
                "deliveryFreightTypeCode" => $record->tipo_porto ?: $this->defaultDeliveryFreightTypeCode,
                "consigneeCompanyName" => $record->ragione_sociale_destinatario,
                "consigneeAddress" => $record->indirizzo_destinatario,
                "consigneeZIPCode" => $record->cap_destinatario,
                "consigneeCity" => $record->localita_destinazione,
                "consigneeProvinceAbbreviation" => $record->provincia_destinatario,
                "consigneeCountryAbbreviationISOAlpha2" => $record->nazione_destinazione,
                "numberOfParcels" => $record->numero_pacchi,
                "weightKG" => $record->peso_totale,
                "volumeM3" => $record->volume_totale,
            ]
        ];

        return $this->requestJson('put', $url, $payload, $record);
    }

    public function delete(SpedizioneBrt $record)
    {
        $url = $this->baseUrl . '/shipments/delete';


        $payload = [
            "account" => [
                "userID" => $this->userId,
                "password" => $this->password,
            ],
            "deleteData" => [
                "senderCustomerCode" => $this->senderCustomerCode,
                "numericSenderReference" => $record->id,
               // "alphanumericSenderReference" => $record->nome_mittente,
            ]
        ];

        return $this->requestJson('put', $url, $payload, $record);
    }


    public function parcelId($parcelId)
    {
        $url = $this->baseUrl . '/tracking/parcelID/' . $parcelId;

        return $this->requestJson('get', $url, [], null, [
            "userID" => $this->userId,
            "password" => $this->password
        ]);
    }


    public function pudo($countryCode, $city, $zipCode)
    {
        $url = $this->pudoUrl;
        $dati = [
            'zipCode' => $zipCode,
            'city' => $city,
            'countryCode' => $countryCode
        ];

        $headers = [];
        if ($this->pudoToken) {
            $headers['X-API-Auth'] = $this->pudoToken;
        }

        return $this->requestJson('get', $url, $dati, null, $headers);

    }

    protected function requestJson(string $method, string $url, array $payload = [], ?SpedizioneBrt $record = null, array $headers = [])
    {
        $log = new ChiamataApi();
        $log->servizio = 'brt';
        $log->url = $url;
        $log->request = $payload;
        $log->method = $method;
        if ($record) {
            $log->service_id = $record->id;
            $log->service_type = get_class($record);
        }
        $log->save();

        $request = Http::withHeaders($headers);

        if ($method === 'get') {
            $res = $request->get($url, $payload);
        } else {
            $res = $request->{$method}($url, $payload);
        }

        $json = $res->json();
        $log->status = $res->status();
        $log->response = is_array($json) ? $json : ['raw' => $res->body()];
        $log->save();

        return is_array($json) ? $json : ['raw' => $res->body()];
    }

    protected function normalizePudoUrl(string $url): string
    {
        $url = rtrim($url, '/');
        if (str_ends_with($url, '/get-pudo-by-address')) {
            return $url;
        }

        return $url . '/get-pudo-by-address';
    }

    protected function determinePricingConditionCode(SpedizioneBrt $record): string
    {
        $fallback = (string)config('services.brt.pricing_condition_code', config('services.brt.pricing', '360'));
        $italia = (string)config('services.brt.pricing_condition_code_italia', $fallback ?: '360');
        $pudo = (string)config('services.brt.pricing_condition_code_pudo', $italia);
        $europe = (string)config('services.brt.pricing_condition_code_europe', $fallback ?: '390');
        $swiss = (string)config('services.brt.pricing_condition_code_swiss', $europe);

        if ($record->nazione_destinazione === 'IT') {
            return $record->pudo_id ? $pudo : $italia;
        }

        if ($record->nazione_destinazione === 'CH') {
            return $swiss;
        }

        return $europe;
    }

    protected function labelParametersFromConfig(): array
    {
        return [
            'outputType' => $this->labelOutputType,
            'offsetX' => $this->labelOffsetX === '' ? 0 : (int)$this->labelOffsetX,
            'offsetY' => $this->labelOffsetY === '' ? 0 : (int)$this->labelOffsetY,
            'isBorderRequired' => $this->boolToApiFlag($this->labelBorder),
            'isLogoRequired' => $this->boolToApiFlag($this->labelLogo),
            'isBarcodeControlRowRequired' => $this->boolToApiFlag($this->labelBarcodeRow),
        ];
    }

    protected function boolToApiFlag(bool $value): string
    {
        return $value ? '1' : '0';
    }

    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    protected function normalizeNetwork(string $network): string
    {
        $network = trim($network);
        if ($network === '' || strtoupper($network) === 'ITALIA') {
            return ' ';
        }

        return $network;
    }


}
