<?php

namespace App\Entity\Enum;

enum InterconnectionAuthType: string
{
    case STATIC_TOKEN = 'static_token';
    case OAUTH2_CLIENT_CREDENTIALS = 'oauth2_client_credentials';

    public function label(): string
    {
        return match ($this) {
            self::STATIC_TOKEN => 'Token statique',
            self::OAUTH2_CLIENT_CREDENTIALS => 'Connexion OAuth2',
        };
    }
}
