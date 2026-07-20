<?php

return [
    [
        'type' => 'checkbox',
        'name' => 'luggage_online_booking_enabled',
        'label' => 'Prenotazioni online attive',
    ],
    [
        'type' => 'textarea',
        'name' => 'luggage_booking_instructions',
        'label' => 'Istruzioni mostrate in fase di prenotazione',
    ],
    [
        'type' => 'checkbox',
        'name' => 'luggage_notify_customer_booking',
        'label' => 'Invia email conferma prenotazione al cliente (codice)',
    ],
    [
        'type' => 'checkbox',
        'name' => 'luggage_notify_customer_pickup_qr',
        'label' => 'Invia email QR ritiro prima della data di ritiro prevista',
    ],
    [
        'type' => 'number',
        'name' => 'luggage_pickup_qr_hours_before',
        'label' => 'Ore prima del ritiro previsto per inviare il QR',
    ],
    [
        'type' => 'checkbox',
        'name' => 'luggage_notify_customer_thank_you',
        'label' => 'Invia email di ringraziamento al cliente dopo il ritiro',
    ],
    [
        'type' => 'checkbox',
        'name' => 'luggage_notify_staff',
        'label' => 'Invia email conferma prenotazione allo staff (admin + email aggiuntiva)',
    ],
    [
        'type' => 'text',
        'name' => 'luggage_staff_notification_email',
        'label' => 'Email aggiuntiva notifiche staff',
    ],
    [
        'type' => 'number',
        'name' => 'luggage_agent_monthly_fee',
        'label' => 'Canone mensile agente Deposito Bagagli (€)',
    ],
];
