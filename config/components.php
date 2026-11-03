<?php

use App\Enums\FileStorageProvider;

/*
|--------------------------------------------------------------------------
| App Components
|--------------------------------------------------------------------------
|
| Components of the application that can be enabled, disabled and
| configured by administrators under "Einstellungen". The values defined
| here are the defaults; values saved in the "settings" table win.
|
| Supported setting types: "bool", "integer", "string", "email_list", "color", "select"
| (with "options") and "secret" (stored encrypted). Settings with a "group" are
| only shown while the provider select of the component has that value.
| Components with "toggleable" => false are always enabled. Settings with
| "hidden" => true are not shown in the form, e.g. OAuth refresh tokens.
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

    'procurement' => [
        'label' => 'Beschaffung',
        'description' => 'Beschaffungen pro Material erfassen: Datum, Menge, Lieferant, Preis, Beleg-Nr. und Garantie.',
        'enabled' => (bool) env('COMPONENT_PROCUREMENT', false),
        'settings' => [],
    ],

    'maintenance' => [
        'label' => 'Wartung',
        'description' => 'Wartungsprotokoll und Wartungsintervalle pro Material mit Übersicht der fälligen Wartungen.',
        'enabled' => (bool) env('COMPONENT_MAINTENANCE', false),
        'settings' => [
            'reminders' => [
                'type' => 'bool',
                'label' => 'Wöchentliche Erinnerungsmail für fällige Wartungen senden',
                'default' => true,
            ],
            'reminder_days_ahead' => [
                'type' => 'integer',
                'label' => 'Als fällig gilt eine Wartung so viele Tage im Voraus',
                'default' => 30,
            ],
            'reminder_recipients' => [
                'type' => 'email_list',
                'label' => 'Empfänger der Erinnerung (leer = Materialverantwortliche)',
                'default' => null,
            ],
        ],
    ],

    'appearance' => [
        'label' => 'Erscheinungsbild',
        'description' => 'Farbe der App. Alle Abstufungen für den hellen und den dunklen Modus werden daraus berechnet.',
        'enabled' => true,
        'toggleable' => false,
        'settings' => [
            'primary_color' => [
                'type' => 'color',
                'label' => 'Hauptfarbe',
                'default' => env('APP_PRIMARY_COLOR', '#b1138b'),
            ],
        ],
    ],

    'file_storage' => [
        'label' => 'Dateispeicher',
        'description' => 'Wo hochgeladene Dateien (Material-Bilder, Belege, Wartungsfotos) gespeichert werden. Zugangsdaten werden verschlüsselt gespeichert; leere Passwort-Felder behalten den gespeicherten Wert. Bestehende Dateien zieht beim Wechsel der Befehl «php artisan attachments:transfer» um.',
        'enabled' => true,
        'toggleable' => false,
        'settings' => [
            'provider' => [
                'type' => 'select',
                'label' => 'Speicher-Provider',
                'default' => env('ATTACHMENTS_PROVIDER', 's3'),
                'options' => FileStorageProvider::options(),
            ],

            's3_key' => ['type' => 'string', 'group' => 's3', 'label' => 'Access Key ID', 'default' => env('AWS_ACCESS_KEY_ID')],
            's3_secret' => ['type' => 'secret', 'group' => 's3', 'label' => 'Secret Access Key', 'default' => env('AWS_SECRET_ACCESS_KEY')],
            's3_region' => ['type' => 'string', 'group' => 's3', 'label' => 'Region (z. B. eu-central-1, bei R2 «auto»)', 'default' => env('AWS_DEFAULT_REGION', 'eu-central-1')],
            's3_bucket' => ['type' => 'string', 'group' => 's3', 'label' => 'Bucket', 'default' => env('AWS_BUCKET')],
            's3_endpoint' => ['type' => 'string', 'group' => 's3', 'label' => 'Endpoint (leer = Amazon S3; z. B. für MinIO, Cloudflare R2, Wasabi, Infomaniak)', 'default' => env('AWS_ENDPOINT')],
            's3_path_style' => ['type' => 'bool', 'group' => 's3', 'label' => 'Path-Style-Endpoint verwenden (meist bei MinIO nötig)', 'default' => (bool) env('AWS_USE_PATH_STYLE_ENDPOINT', false)],

            'sftp_host' => ['type' => 'string', 'group' => 'sftp', 'label' => 'Host', 'default' => env('ATTACHMENTS_SFTP_HOST')],
            'sftp_port' => ['type' => 'integer', 'group' => 'sftp', 'label' => 'Port', 'default' => 22, 'rules' => ['required', 'integer', 'min:1', 'max:65535']],
            'sftp_username' => ['type' => 'string', 'group' => 'sftp', 'label' => 'Benutzername', 'default' => env('ATTACHMENTS_SFTP_USERNAME')],
            'sftp_password' => ['type' => 'secret', 'group' => 'sftp', 'label' => 'Passwort (oder Passphrase des Schlüssels)', 'default' => env('ATTACHMENTS_SFTP_PASSWORD')],
            'sftp_private_key' => ['type' => 'secret', 'group' => 'sftp', 'multiline' => true, 'label' => 'Privater Schlüssel (optional, statt Passwort)', 'default' => null],
            'sftp_root' => ['type' => 'string', 'group' => 'sftp', 'label' => 'Verzeichnis auf dem Server', 'default' => '/matschlacht'],

            'ftp_host' => ['type' => 'string', 'group' => 'ftp', 'label' => 'Host', 'default' => env('ATTACHMENTS_FTP_HOST')],
            'ftp_port' => ['type' => 'integer', 'group' => 'ftp', 'label' => 'Port', 'default' => 21, 'rules' => ['required', 'integer', 'min:1', 'max:65535']],
            'ftp_username' => ['type' => 'string', 'group' => 'ftp', 'label' => 'Benutzername', 'default' => env('ATTACHMENTS_FTP_USERNAME')],
            'ftp_password' => ['type' => 'secret', 'group' => 'ftp', 'label' => 'Passwort', 'default' => env('ATTACHMENTS_FTP_PASSWORD')],
            'ftp_root' => ['type' => 'string', 'group' => 'ftp', 'label' => 'Verzeichnis auf dem Server', 'default' => '/matschlacht'],
            'ftp_ssl' => ['type' => 'bool', 'group' => 'ftp', 'label' => 'Verschlüsselt (FTPS / FTP über TLS)', 'default' => true],
            'ftp_passive' => ['type' => 'bool', 'group' => 'ftp', 'label' => 'Passiver Modus', 'default' => true],

            'sharepoint_drive_id' => ['type' => 'string', 'group' => 'sharepoint', 'label' => 'Drive-ID der Dokumentbibliothek bzw. des OneDrive', 'default' => env('ATTACHMENTS_SHAREPOINT_DRIVE_ID')],
            'sharepoint_folder' => ['type' => 'string', 'group' => 'sharepoint', 'label' => 'Ordner in der Bibliothek', 'default' => 'MatSchlacht'],

            'nextcloud_url' => ['type' => 'string', 'group' => 'nextcloud', 'label' => 'Adresse der Nextcloud (z. B. https://cloud.example.ch)', 'default' => env('ATTACHMENTS_NEXTCLOUD_URL'), 'rules' => ['nullable', 'url:https,http', 'max:255']],
            'nextcloud_username' => ['type' => 'string', 'group' => 'nextcloud', 'label' => 'Benutzername', 'default' => env('ATTACHMENTS_NEXTCLOUD_USERNAME')],
            'nextcloud_password' => ['type' => 'secret', 'group' => 'nextcloud', 'label' => 'App-Passwort (Einstellungen › Sicherheit › Geräte & Sitzungen)', 'default' => env('ATTACHMENTS_NEXTCLOUD_PASSWORD')],
            'nextcloud_folder' => ['type' => 'string', 'group' => 'nextcloud', 'label' => 'Ordner', 'default' => 'MatSchlacht'],

            'dropbox_app_key' => ['type' => 'string', 'group' => 'dropbox', 'label' => 'App Key', 'default' => env('ATTACHMENTS_DROPBOX_APP_KEY')],
            'dropbox_app_secret' => ['type' => 'secret', 'group' => 'dropbox', 'label' => 'App Secret', 'default' => env('ATTACHMENTS_DROPBOX_APP_SECRET')],
            'dropbox_folder' => ['type' => 'string', 'group' => 'dropbox', 'label' => 'Ordner (bei App-Ordner-Zugriff relativ zum App-Ordner)', 'default' => 'MatSchlacht'],
            'dropbox_refresh_token' => ['type' => 'secret', 'group' => 'dropbox', 'hidden' => true, 'label' => 'Refresh Token', 'default' => null],

            'google_client_id' => ['type' => 'string', 'group' => 'google_drive', 'label' => 'OAuth Client-ID', 'default' => env('ATTACHMENTS_GOOGLE_CLIENT_ID')],
            'google_client_secret' => ['type' => 'secret', 'group' => 'google_drive', 'label' => 'OAuth Client-Secret', 'default' => env('ATTACHMENTS_GOOGLE_CLIENT_SECRET')],
            'google_folder' => ['type' => 'string', 'group' => 'google_drive', 'label' => 'Ordner in «Meine Ablage» (wird angelegt)', 'default' => 'MatSchlacht'],
            'google_refresh_token' => ['type' => 'secret', 'group' => 'google_drive', 'hidden' => true, 'label' => 'Refresh Token', 'default' => null],
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
