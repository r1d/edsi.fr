<?php
declare(strict_types=1);

/**
 * Copier ce fichier vers config.php (à la racine du projet, hors document root)
 * et renseigner la clé API Brevo.
 */
return [
    'brevo' => [
        'api_key' => 'xkeysib-REMPLACER',
        /** Optionnel : URL de base si Brevo change d’hôte (défaut : https://api.brevo.com/v3) */
        // 'api_base_url' => 'https://api.brevo.com/v3',
    ],
    'contact_mail' => [
        'from_email' => 'hello@edsi.fr',
        'from_name' => 'EDSI',
        'to_email' => 'hello@edsi.fr',
        /** Utilisé dans le corps du message (« depuis … ») */
        'site_label' => 'edsi.fr',
        /** Objet de l’e-mail reçu (le sujet saisi par l’utilisateur est dans le corps) */
        'notification_subject' => 'Nouveau message depuis edsi.fr',
    ],
];
