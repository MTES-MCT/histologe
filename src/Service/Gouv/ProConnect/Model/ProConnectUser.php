<?php

namespace App\Service\Gouv\ProConnect\Model;

use App\Exception\ProConnect\ProConnectException;

class ProConnectUser
{
    public string $uid;
    public string $sub;
    public string $email;

    /**
     * @throws \Exception
     */
    public function __construct(array $data)
    {
        if (!isset($data['sub'], $data['uid'], $data['email'])) {
            throw new ProConnectException('Les informations de l\'utilisateur ProConnect sont incomplètes');
        }

        $this->sub = $data['sub'];
        $this->uid = $data['uid'];
        $this->email = $data['email'];
    }
}
