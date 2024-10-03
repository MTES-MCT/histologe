<?php

namespace App\Messenger\Message;

use App\Service\SearchUser;

class UserExportMessage
{
    public function __construct(private SearchUser $searchUser, private string $format)
    {
    }

    public function getSearchUser(): SearchUser
    {
        return $this->searchUser;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
