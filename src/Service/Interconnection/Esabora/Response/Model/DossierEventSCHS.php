<?php

namespace App\Service\Interconnection\Esabora\Response\Model;

use App\Service\Interconnection\Esabora\Response\DossierEventsSCHSResponse;

class DossierEventSCHS
{
    private DossierEventsSCHSResponse $dossierEvents;
    private array $originalData;
    private string $date;
    private string $description;
    private ?string $piecesJointes;
    private int $eventId;

    public function __construct(array $event, DossierEventsSCHSResponse $dossierEvents)
    {
        $this->dossierEvents = $dossierEvents;
        $this->originalData = $event;
        $this->date = $event['columnDataList'][1];
        $this->description = $event['columnDataList'][2];
        $this->piecesJointes = $event['columnDataList'][3];
        $this->eventId = $event['keyDataList'][1];
    }

    public function getDossierEvents(): DossierEventsSCHSResponse
    {
        return $this->dossierEvents;
    }

    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPiecesJointes(): ?string
    {
        return $this->piecesJointes;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }
}
