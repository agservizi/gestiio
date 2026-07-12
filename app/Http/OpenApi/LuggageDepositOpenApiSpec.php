<?php

namespace App\Http\OpenApi;

class LuggageDepositOpenApiSpec
{
    public static function publicSpec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Deposito Bagagli — API Pubblica',
                'version' => '1.1.0',
                'description' => 'API REST per prenotazioni deposito bagagli da sito web e integrazioni esterne. Autenticazione via header x-api-key (eccetto verify e health).',
            ],
            'servers' => [
                ['url' => url('/api'), 'description' => 'Base API Gestiio'],
            ],
            'tags' => [
                ['name' => 'Prenotazioni'],
                ['name' => 'Disponibilità'],
                ['name' => 'Sistema'],
            ],
            'components' => self::components(),
            'paths' => [
                '/public/deposito-bagagli/health' => [
                    'get' => [
                        'tags' => ['Sistema'],
                        'summary' => 'Health check servizio',
                        'responses' => [
                            '200' => [
                                'description' => 'Servizio operativo',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/SuccessEnvelope']]],
                            ],
                        ],
                    ],
                ],
                '/public/deposito-bagagli/book' => [
                    'post' => [
                        'tags' => ['Prenotazioni'],
                        'summary' => 'Crea prenotazione online',
                        'security' => [['ApiKeyAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/BookingRequest']]],
                        ],
                        'responses' => self::mutationResponses('Deposito creato', 'Deposit'),
                    ],
                ],
                '/public/deposito-bagagli/deposits' => [
                    'get' => [
                        'tags' => ['Prenotazioni'],
                        'summary' => 'Elenco depositi (filtrabile)',
                        'security' => [['ApiKeyAuth' => []]],
                        'parameters' => [
                            ['name' => 'email', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'email']],
                            ['name' => 'code', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['PRENOTATO', 'CHECK_IN', 'COMPLETATO', 'ANNULLATO', 'NO_SHOW']]],
                            ['name' => 'from', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                            ['name' => 'to', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                            ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
                            ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 20, 'maximum' => 100]],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Lista paginata',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/DepositListResponse']]],
                            ],
                            '401' => ['$ref' => '#/components/responses/Unauthorized'],
                        ],
                    ],
                ],
                '/public/deposito-bagagli/deposits/{code}' => [
                    'get' => [
                        'tags' => ['Prenotazioni'],
                        'summary' => 'Dettaglio deposito per codice',
                        'security' => [['ApiKeyAuth' => []]],
                        'parameters' => [['name' => 'code', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]],
                        'responses' => self::singleDepositResponses(),
                    ],
                    'patch' => [
                        'tags' => ['Prenotazioni'],
                        'summary' => 'Modifica prenotazione (solo PRENOTATO)',
                        'security' => [['ApiKeyAuth' => []]],
                        'parameters' => [['name' => 'code', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]],
                        'requestBody' => [
                            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/BookingUpdateRequest']]],
                        ],
                        'responses' => self::mutationResponses('Deposito aggiornato', 'Deposit'),
                    ],
                ],
                '/public/deposito-bagagli/deposits/{code}/cancel' => [
                    'post' => [
                        'tags' => ['Prenotazioni'],
                        'summary' => 'Annulla prenotazione (solo PRENOTATO)',
                        'security' => [['ApiKeyAuth' => []]],
                        'parameters' => [['name' => 'code', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]],
                        'responses' => self::mutationResponses('Prenotazione annullata', 'CancelResult'),
                    ],
                ],
                '/public/deposito-bagagli/availability' => [
                    'get' => [
                        'tags' => ['Disponibilità'],
                        'summary' => 'Disponibilità per data',
                        'security' => [['ApiKeyAuth' => []]],
                        'parameters' => [
                            ['name' => 'date', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'date']],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Disponibilità calcolata',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AvailabilityResponse']]],
                            ],
                            '400' => ['$ref' => '#/components/responses/BadRequest'],
                            '401' => ['$ref' => '#/components/responses/Unauthorized'],
                        ],
                    ],
                ],
                '/public/deposito-bagagli/availability/range' => [
                    'get' => [
                        'tags' => ['Disponibilità'],
                        'summary' => 'Disponibilità per intervallo date',
                        'security' => [['ApiKeyAuth' => []]],
                        'parameters' => [
                            ['name' => 'from', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'date']],
                            ['name' => 'to', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'date']],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Serie giornaliera disponibilità',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AvailabilityRangeResponse']]],
                            ],
                        ],
                    ],
                ],
                '/public/deposito-bagagli/pricing' => [
                    'get' => [
                        'tags' => ['Disponibilità'],
                        'summary' => 'Tariffe e limiti correnti',
                        'security' => [['ApiKeyAuth' => []]],
                        'responses' => [
                            '200' => [
                                'description' => 'Configurazione tariffaria',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/PricingResponse']]],
                            ],
                        ],
                    ],
                ],
                '/public/deposito-bagagli/verify' => [
                    'get' => [
                        'tags' => ['Sistema'],
                        'summary' => 'Verifica QR token (pubblico, senza API key)',
                        'parameters' => [
                            ['name' => 'token', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'uuid']],
                        ],
                        'responses' => self::singleDepositResponses(),
                    ],
                ],
            ],
        ];
    }

    public static function adminSpec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Deposito Bagagli — API Admin',
                'version' => '1.1.0',
                'description' => 'API backoffice protette da sessione Laravel (cookie + CSRF) e middleware 2FA. Richiede ruolo staff.',
            ],
            'servers' => [
                ['url' => url('/api'), 'description' => 'Base API Gestiio'],
            ],
            'tags' => [
                ['name' => 'Depositi'],
                ['name' => 'Azioni'],
                ['name' => 'Report'],
                ['name' => 'Impostazioni'],
            ],
            'components' => self::components(),
            'paths' => [
                '/admin/deposito-bagagli' => [
                    'get' => [
                        'tags' => ['Depositi'],
                        'summary' => 'Elenco depositi admin',
                        'parameters' => [
                            ['name' => 'view', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'q', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'source', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['PORTALE', 'SPORTELLO']]],
                            ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer']],
                            ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AdminDepositListResponse']]]],
                        ],
                    ],
                    'post' => [
                        'tags' => ['Depositi'],
                        'summary' => 'Crea deposito da sportello',
                        'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AdminCreateDepositRequest']]]],
                        'responses' => self::mutationResponses('Deposito creato', 'Deposit'),
                    ],
                ],
                '/admin/deposito-bagagli/{deposit}' => [
                    'get' => ['tags' => ['Depositi'], 'summary' => 'Dettaglio deposito', 'parameters' => [self::depositIdParam()], 'responses' => self::singleDepositResponses()],
                    'patch' => ['tags' => ['Depositi'], 'summary' => 'Aggiorna deposito', 'parameters' => [self::depositIdParam()], 'responses' => self::mutationResponses('Aggiornato', 'Deposit')],
                    'delete' => ['tags' => ['Depositi'], 'summary' => 'Elimina deposito', 'parameters' => [self::depositIdParam()], 'responses' => ['200' => ['description' => 'Eliminato', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/SuccessEnvelope']]]]]],
                ],
                '/admin/deposito-bagagli/{deposit}/actions' => [
                    'post' => [
                        'tags' => ['Azioni'],
                        'summary' => 'Esegue check-in, check-out, cancel o no-show',
                        'parameters' => [self::depositIdParam()],
                        'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/DepositActionRequest']]]],
                        'responses' => self::mutationResponses('Azione completata', 'Deposit'),
                    ],
                ],
                '/admin/deposito-bagagli/stats/overview' => [
                    'get' => [
                        'tags' => ['Report'],
                        'summary' => 'KPI e statistiche dashboard',
                        'parameters' => [
                            ['name' => 'from', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                            ['name' => 'to', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                        ],
                        'responses' => ['200' => ['description' => 'Statistiche', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/StatsResponse']]]]],
                    ],
                ],
                '/admin/deposito-bagagli/settings' => [
                    'get' => ['tags' => ['Impostazioni'], 'summary' => 'Legge tariffe e capacità', 'responses' => ['200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/PricingResponse']]]]]],
                    'post' => ['tags' => ['Impostazioni'], 'summary' => 'Aggiorna tariffe e capacità', 'responses' => ['200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/PricingResponse']]]]]],
                ],
                '/admin/deposito-bagagli/export/csv' => [
                    'get' => ['tags' => ['Report'], 'summary' => 'Export CSV depositi', 'responses' => ['200' => ['description' => 'File CSV', 'content' => ['text/csv' => ['schema' => ['type' => 'string', 'format' => 'binary']]]]]],
                ],
                '/admin/deposito-bagagli/{deposit}/pdf' => [
                    'get' => ['tags' => ['Report'], 'summary' => 'PDF ricevuta', 'parameters' => [self::depositIdParam()], 'responses' => ['200' => ['description' => 'PDF']]],
                ],
                '/admin/deposito-bagagli/{deposit}/pdf/tags' => [
                    'get' => ['tags' => ['Report'], 'summary' => 'PDF tag bagagli', 'parameters' => [self::depositIdParam()], 'responses' => ['200' => ['description' => 'PDF']]],
                ],
                '/admin/deposito-bagagli/{deposit}/pdf/agreement' => [
                    'get' => ['tags' => ['Report'], 'summary' => 'PDF documento firma cliente', 'parameters' => [self::depositIdParam()], 'responses' => ['200' => ['description' => 'PDF']]],
                ],
            ],
        ];
    }

    private static function depositIdParam(): array
    {
        return ['name' => 'deposit', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'ulid']];
    }

    private static function components(): array
    {
        return [
            'securitySchemes' => [
                'ApiKeyAuth' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'x-api-key'],
                'SessionAuth' => ['type' => 'apiKey', 'in' => 'cookie', 'name' => 'laravel_session'],
            ],
            'responses' => [
                'Unauthorized' => ['description' => 'API key mancante o non valida'],
                'BadRequest' => ['description' => 'Parametri non validi'],
                'NotFound' => ['description' => 'Risorsa non trovata'],
            ],
            'schemas' => [
                'SuccessEnvelope' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean', 'example' => true],
                        'data' => ['type' => 'object'],
                        'meta' => ['type' => 'object'],
                    ],
                ],
                'ErrorEnvelope' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean', 'example' => false],
                        'error' => [
                            'type' => 'object',
                            'properties' => [
                                'code' => ['type' => 'string'],
                                'message' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'Deposit' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string'],
                        'code' => ['type' => 'string', 'example' => 'LB-ABC123'],
                        'customerName' => ['type' => 'string'],
                        'customerEmail' => ['type' => 'string', 'nullable' => true],
                        'customerPhone' => ['type' => 'string', 'nullable' => true],
                        'bagCount' => ['type' => 'integer'],
                        'status' => ['type' => 'string'],
                        'statusLabel' => ['type' => 'string'],
                        'bookingDate' => ['type' => 'string', 'format' => 'date'],
                        'dailyRate' => ['type' => 'number'],
                        'totalAmount' => ['type' => 'number', 'nullable' => true],
                        'source' => ['type' => 'string', 'enum' => ['PORTALE', 'SPORTELLO']],
                        'qrToken' => ['type' => 'string', 'format' => 'uuid'],
                        'verifyUrl' => ['type' => 'string', 'format' => 'uri'],
                    ],
                ],
                'BookingRequest' => [
                    'type' => 'object',
                    'required' => ['customerName', 'bookingDate'],
                    'properties' => [
                        'customerName' => ['type' => 'string'],
                        'customerEmail' => ['type' => 'string', 'format' => 'email'],
                        'customerPhone' => ['type' => 'string'],
                        'bagCount' => ['type' => 'integer', 'minimum' => 1],
                        'bookingDate' => ['type' => 'string', 'format' => 'date'],
                        'expectedCheckIn' => ['type' => 'string', 'format' => 'date-time'],
                        'expectedCheckOut' => ['type' => 'string', 'format' => 'date-time'],
                        'notes' => ['type' => 'string'],
                    ],
                ],
                'BookingUpdateRequest' => [
                    'type' => 'object',
                    'properties' => [
                        'customerName' => ['type' => 'string'],
                        'customerEmail' => ['type' => 'string', 'format' => 'email'],
                        'customerPhone' => ['type' => 'string'],
                        'bagCount' => ['type' => 'integer'],
                        'bookingDate' => ['type' => 'string', 'format' => 'date'],
                        'expectedCheckIn' => ['type' => 'string', 'format' => 'date-time'],
                        'expectedCheckOut' => ['type' => 'string', 'format' => 'date-time'],
                        'notes' => ['type' => 'string'],
                    ],
                ],
                'AdminCreateDepositRequest' => [
                    'type' => 'object',
                    'required' => ['customer_name', 'booking_date'],
                    'properties' => [
                        'customer_name' => ['type' => 'string'],
                        'customer_email' => ['type' => 'string'],
                        'customer_phone' => ['type' => 'string'],
                        'bag_count' => ['type' => 'integer'],
                        'booking_date' => ['type' => 'string', 'format' => 'date'],
                        'notes' => ['type' => 'string'],
                        'cliente_id' => ['type' => 'integer'],
                    ],
                ],
                'DepositActionRequest' => [
                    'type' => 'object',
                    'required' => ['action'],
                    'properties' => [
                        'action' => ['type' => 'string', 'enum' => ['check-in', 'check-out', 'cancel', 'no-show']],
                        'bagTags' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'paymentMethod' => ['type' => 'string'],
                    ],
                ],
                'CancelResult' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string'],
                        'code' => ['type' => 'string'],
                        'status' => ['type' => 'string'],
                    ],
                ],
                'DepositListResponse' => [
                    'allOf' => [
                        ['$ref' => '#/components/schemas/SuccessEnvelope'],
                        ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Deposit']]]],
                    ],
                ],
                'AdminDepositListResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean'],
                        'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Deposit']],
                        'meta' => ['type' => 'object', 'properties' => ['page' => ['type' => 'integer'], 'limit' => ['type' => 'integer'], 'total' => ['type' => 'integer']]],
                    ],
                ],
                'AvailabilityResponse' => [
                    'allOf' => [
                        ['$ref' => '#/components/schemas/SuccessEnvelope'],
                        ['type' => 'object', 'properties' => ['data' => ['type' => 'object', 'properties' => [
                            'date' => ['type' => 'string', 'format' => 'date'],
                            'maxCapacity' => ['type' => 'integer'],
                            'bookedBags' => ['type' => 'integer'],
                            'availableBags' => ['type' => 'integer'],
                            'isAvailable' => ['type' => 'boolean'],
                        ]]]],
                    ],
                ],
                'AvailabilityRangeResponse' => [
                    'allOf' => [
                        ['$ref' => '#/components/schemas/SuccessEnvelope'],
                        ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => ['type' => 'object']]]],
                    ],
                ],
                'PricingResponse' => [
                    'allOf' => [
                        ['$ref' => '#/components/schemas/SuccessEnvelope'],
                        ['type' => 'object', 'properties' => ['data' => ['type' => 'object', 'properties' => [
                            'dailyRate' => ['type' => 'number'],
                            'currency' => ['type' => 'string'],
                            'minDays' => ['type' => 'integer'],
                            'maxBagsPerBooking' => ['type' => 'integer'],
                            'maxDailyCapacity' => ['type' => 'integer'],
                        ]]]],
                    ],
                ],
                'StatsResponse' => [
                    'allOf' => [
                        ['$ref' => '#/components/schemas/SuccessEnvelope'],
                        ['type' => 'object', 'properties' => ['data' => ['type' => 'object']]],
                    ],
                ],
            ],
        ];
    }

    private static function singleDepositResponses(): array
    {
        return [
            '200' => [
                'description' => 'Deposito trovato',
                'content' => ['application/json' => ['schema' => ['allOf' => [
                    ['$ref' => '#/components/schemas/SuccessEnvelope'],
                    ['type' => 'object', 'properties' => ['data' => ['$ref' => '#/components/schemas/Deposit']]],
                ]]]],
            ],
            '404' => ['$ref' => '#/components/responses/NotFound'],
        ];
    }

    private static function mutationResponses(string $description, string $schema): array
    {
        return [
            '200' => ['description' => $description, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/SuccessEnvelope']]]],
            '201' => ['description' => $description, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/SuccessEnvelope']]]],
            '400' => ['$ref' => '#/components/responses/BadRequest'],
            '401' => ['$ref' => '#/components/responses/Unauthorized'],
            '409' => ['description' => 'Conflitto di stato o disponibilità'],
        ];
    }
}
