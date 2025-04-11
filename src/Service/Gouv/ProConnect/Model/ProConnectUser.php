<?php

namespace App\Service\Gouv\ProConnect\Model;

use App\Exception\ProConnect\ProConnectException;

class ProConnectUser
{
    public string $uid;
    public string $email;
    public string $givenName;
    public string $usualName;

    /**
     * @throws \Exception
     */
    public function __construct(array $data)
    {
        if (!isset($data['uid'], $data['email'], $data['given_name'], $data['usual_name'])) {
            throw new ProConnectException('Les informations de l\'utilisateur ProConnect sont incomplètes');
        }

        $this->uid = $data['uid'];
        $this->email = $data['email'];
        $this->givenName = $data['given_name'];
        $this->usualName = $data['usual_name'];
    }
}
