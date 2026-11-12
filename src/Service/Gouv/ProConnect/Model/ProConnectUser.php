<?php

namespace App\Service\Gouv\ProConnect\Model;

use App\Exception\ProConnect\ProConnectException;

class ProConnectUser
{
    public string $uid;
    public string $sub;
    public string $email;
    public ?string $siret = null;
    public ?string $organizationalUnit = null;
    public ?string $chorusdtMatricule = null;
    public ?string $fullname = null;

    /**
     * @param array<string, mixed> $data
     *
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
        $this->siret = $data['siret'] ?? null;
        $this->organizationalUnit = $data['organizational_unit'] ?? null;
        $this->chorusdtMatricule = $data['chorusdt:matricule'] ?? null;
        $lastName = $data['usual_name'] ?? $data['family_name'] ?? '';
        $fullname = trim(sprintf('%s %s', $data['given_name'] ?? '', $lastName));
        $this->fullname = '' !== $fullname ? $fullname : null;
    }
}
