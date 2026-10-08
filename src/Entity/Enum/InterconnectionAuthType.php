<?php

namespace App\Entity\Enum;

enum InterconnectionAuthType: string
{
    case STATIC_TOKEN = 'static_token';
    case OAUTH2_CLIENT_CREDENTIALS = 'oauth2_client_credentials';
}
