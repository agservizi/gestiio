<?php

return [
    [
        'type' => 'checkbox',
        'name' => 'locker_online_intake_enabled',
        'label' => 'Prenotazioni online attive',
    ],
    [
        'type' => 'textarea',
        'name' => 'locker_booking_instructions',
        'label' => 'Istruzioni mostrate in fase di prenotazione',
    ],
    [
        'type' => 'checkbox',
        'name' => 'locker_notify_staff',
        'label' => 'Invia email notifica staff su nuova prenotazione',
    ],
    [
        'type' => 'text',
        'name' => 'locker_staff_notification_email',
        'label' => 'Email aggiuntiva notifiche staff',
    ],
    [
        'type' => 'number',
        'name' => 'locker_agent_monthly_fee',
        'label' => 'Canone mensile agente Locker Point (€)',
    ],
];
