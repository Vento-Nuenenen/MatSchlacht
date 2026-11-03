<?php

/*
|--------------------------------------------------------------------------
| App Components
|--------------------------------------------------------------------------
|
| Components of the application that can be enabled, disabled and
| configured by administrators under "Einstellungen". The values defined
| here are the defaults; values saved in the "settings" table win.
|
| Supported setting types: "bool", "string", "email_list".
|
*/

return [

    'item_collections' => [
        'label' => 'Kollektionen',
        'description' => 'Vordefinierte Material-Sets, die beim Bestellen als Ganzes hinzugefügt werden können.',
        'enabled' => (bool) env('COMPONENT_ITEM_COLLECTIONS', true),
        'settings' => [],
    ],

    'item_contents' => [
        'label' => 'Inhaltslisten',
        'description' => 'Inhaltslisten für Material inkl. Packliste-PDF.',
        'enabled' => (bool) env('COMPONENT_ITEM_CONTENTS', true),
        'settings' => [],
    ],

    'attachments' => [
        'label' => 'Datei-Anhänge',
        'description' => 'Bilder und PDFs zu Material hochladen und anzeigen. Bestehende Anhänge bleiben beim Deaktivieren erhalten.',
        'enabled' => (bool) env('COMPONENT_ATTACHMENTS', true),
        'settings' => [],
    ],

    'storage' => [
        'label' => 'Lagerhaltung',
        'description' => 'Lagerorte, Lagerräume, Lagerregale, Regalebenen und Lagerplätze mit Bestand pro Platz sowie druckbare Etiketten. Hat ein Material Lagerplätze, ergibt sich sein Bestand aus deren Summe.',
        'enabled' => (bool) env('COMPONENT_STORAGE', false),
        'settings' => [
            'label_caption' => [
                'type' => 'string',
                'label' => 'Fusszeile auf den Etiketten (leer = App-Name)',
                'default' => null,
            ],
        ],
    ],

    'order_notifications' => [
        'label' => 'Mail-Benachrichtigungen',
        'description' => 'E-Mails bei neuen Bestellungen und Statuswechseln.',
        'enabled' => (bool) env('COMPONENT_ORDER_NOTIFICATIONS', true),
        'settings' => [
            'notify_on_created' => [
                'type' => 'bool',
                'label' => 'Bei neuer Bestellung benachrichtigen',
                'default' => true,
            ],
            'notify_on_status_change' => [
                'type' => 'bool',
                'label' => 'Bei Statuswechsel (abgeschickt / angenommen) benachrichtigen',
                'default' => true,
            ],
            'material_responsibles' => [
                'type' => 'email_list',
                'label' => 'Materialverantwortliche (kommagetrennte E-Mail-Adressen, leer = MAIL_MATERIAL_RESPONSIBLES)',
                'default' => null,
            ],
        ],
    ],

    'azure_login' => [
        'label' => 'Azure-Login',
        'description' => 'Anmeldung mit Microsoft-Konto inkl. Azure Group Mappings.',
        'enabled' => (bool) env('COMPONENT_AZURE_LOGIN', true),
        'settings' => [
            'button_label' => [
                'type' => 'string',
                'label' => 'Beschriftung des Login-Buttons',
                'default' => 'Login mit Nünenen Mail',
            ],
            'sync_group_roles' => [
                'type' => 'bool',
                'label' => 'Rollen beim Login anhand der Azure-Gruppen synchronisieren',
                'default' => true,
            ],
        ],
    ],

    'password_login' => [
        'label' => 'Passwort-Login',
        'description' => 'Anmeldung mit E-Mail und Passwort inkl. Passwort-Zurücksetzen.',
        'enabled' => (bool) env('COMPONENT_PASSWORD_LOGIN', true),
        'settings' => [],
    ],

];
