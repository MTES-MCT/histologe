<?php

namespace App\Service\Esabora\Response\Model;

class DossierEventSCHS
{
    private DossierEventsSCHS $dossierEvents;
    private array $originalData;
    private string $date;
    private string $description;
    private ?string $piecesJointes;
    private int $eventId;

    public function __construct(array $event, DossierEventsSCHS $dossierEvents)
    {
        $this->dossierEvents = $dossierEvents;
        $this->originalData = $event;
        $this->date = $event['columnDataList'][1];
        $this->description = $event['columnDataList'][2];
        $this->piecesJointes = $event['columnDataList'][3];
        $this->eventId = $event['keyDataList'][1];
    }

    public function getDossierEvents(): DossierEventsSCHS
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
