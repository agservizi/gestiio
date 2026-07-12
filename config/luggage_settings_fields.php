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
        'name' => 'luggage_notify_staff',
        'label' => 'Notifica staff su nuova prenotazione',
    ],
    [
        'type' => 'checkbox',
        'name' => 'luggage_notify_customer_receipt',
        'label' => 'Invia ricevuta email al cliente al check-out',
    ],
    [
        'type' => 'checkbox',
        'name' => 'luggage_notify_customer_pickup_qr',
        'label' => 'Invia QR ritiro al cliente dopo il check-in',
    ],
    [
        'type' => 'text',
        'name' => 'luggage_staff_notification_email',
        'label' => 'Email aggiuntiva notifiche staff',
    ],
];
