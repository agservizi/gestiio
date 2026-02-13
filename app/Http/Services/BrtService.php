<?php

namespace App\Http\Services;

use App\Models\ChiamataApi;
use App\Models\SpedizioneBrt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BrtService
{
    const URL = 'https://api.brt.it/rest/v1';
    const USER_ID = 1020110;
    const PASSWORD = "brt1454st";

    protected $userId;
    protected $password;

    public function __construct()
    {
        $this->userId = config('services.brt.user');
        $this->password = config('services.brt.password');
    }


    public function shipment(SpedizioneBrt $record)
    {
        $url = self::URL . '/shipments/shipment';

        $pricingConditionCode = config('services.brt.pricing');
        if ($pricingConditionCode != "000") {
            $pricingConditionCode = "360";
            if ($record->nazione_destinazione !== 'IT') {
                //estero
                $pricingConditionCode = "390";
            } else if ($record->pudo_id) {
                //Italia pudo
                $pricingConditionCode = "363";
            }
        }


        $jayParsedAry = [
            "account" => [
                "userID" => $this->userId,
                "password" => $this->password,
            ],
            "createData" => [
                "network" => " ",
                "departureDepot" => 122,
                "senderCustomerCode" => $this->userId,
                "deliveryFreightTypeCode" => $record->tipo_porto,
                "consigneeCompanyName" => $record->ragione_sociale_destinatario,
                "consigneeAddress" => $record->indirizzo_destinatario,
                "consigneeZIPCode" => $record->cap_destinatario,
                "consigneeCity" => $record->localita_destinazione,
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
                "serviceType" => "",
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
                "pudoId" => $record->pudo_id,
            ],
            "isLabelRequired" => 1,
            "labelParameters" => [
                "outputType" => "PDF",
                "offsetX" => 0,
                "offsetY" => 0,
                "isBorderRequired" => "0",
                "isLogoRequired" => "0",
                "isBarcodeControlRowRequired" => "0"
            ]
        ];

        $log = new ChiamataApi();
        $log->servizio = 'brt';
        $log->url = $url;
        $log->request = $jayParsedAry;
        $log->method = 'post';
        $log->service_id = $record->id;
        $log->service_type = get_class($record);
        $log->save();


        $res = Http::post($url, $jayParsedAry);
        $log->status = $res->status();
        \Log::debug($res->reason());
        $log->response = $res->json();
        $log->save();

        return $res->json();
    }

    public function delete(SpedizioneBrt $record)
    {
        $url = self::URL . '/shipments/delete';


        $jayParsedAry = [
            "account" => [
                "userID" => $this->userId,
                "password" => $this->password,
            ],
            "deleteData" => [
                "senderCustomerCode" => $this->userId,
                "numericSenderReference" => $record->id,
               // "alphanumericSenderReference" => $record->nome_mittente,
            ]
        ];

        $log = new ChiamataApi();
        $log->servizio = 'brt';
        $log->url = $url;
        $log->request = $jayParsedAry;
        $log->method = 'put';
        $log->service_id = $record->id;
        $log->service_type = get_class($record);
        $log->save();

        $res = Http::put($url, $jayParsedAry);
        $log->status = $res->status();
        \Log::debug($res->reason());
        $log->response = $res->json();
        $log->save();

        return $res->json();
    }


    public function parcelId($parcelId)
    {
        $url = self::URL . '/tracking/parcelID/' . $parcelId;

        $log = new ChiamataApi();
        $log->servizio = 'brt';
        $log->url = $url;
        $log->request = [];
        $log->method = 'get';
        $log->save();
        $res = Http::withHeaders([
            "userID" => $this->userId,
            "password" => $this->password
        ])->get($url);
        $log->status = $res->status();
        $log->response = $res->json();
        $log->save();

        return $res->json();
    }


    public function pudo($countryCode, $city, $zipCode)
    {
        $url = 'https://api.brt.it/pudo/v1/open/pickup/get-pudo-by-address';
        $dati = [
            'zipCode' => $zipCode,
            'city' => $city,
            'countryCode' => $countryCode
        ];

        $log = new ChiamataApi();
        $log->servizio = 'brt';
        $log->url = $url;
        $log->request = $dati;
        $log->method = 'get';
        $log->save();
        $res = Http::withHeaders(['X-API-Auth' => '548391ec-e75a-4a9a-a9a2-72ae305519ea'])->get($url, $dati);
        $log->status = $res->status();
        $log->response = $res->json();
        $log->save();

        return $res->json();

    }


}
