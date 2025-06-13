<?php

namespace App\Service\Interconnection\Esabora\Response\Model;

class DossierEventSCHS
{
    public const int SAS_REFERENCE_DOSSIER = 0;
    public const int EVT_DATE = 1;
    public const int EVT_PRESENTATION = 2;
    public const int EVT_DOCUMENTS = 3;
    public const int EVT_LIBELLE = 4;

    private ?int $searchId = null;
    private ?string $documentTypeName = null;
    private ?int $eventId = null;
    private ?string $sasReference = null;
    /** @var array<mixed> */
    private array $originalData = [];
    private ?string $date = null;
    private ?string $presentation = null;
    private ?string $documents = null;
    private ?string $libelle = null;

    /**
     * @param array<mixed> $event
     */
    public function __construct(array $event)
    {
        if (!empty($event)) {
            $this->originalData = $event;
            $this->eventId = $event['keyDataList'][1];
            $this->searchId = $event['searchId'];
            $this->documentTypeName = $event['documentTypeName'];
            $data = $event['columnDataList'] ?? null;
            if (null !== $data) {
                $this->sasReference = $data[self::SAS_REFERENCE_DOSSIER];
                $this->date = $data[self::EVT_DATE];
                $this->presentation = $data[self::EVT_PRESENTATION];
                $this->documents = $data[self::EVT_DOCUMENTS];
                $this->libelle = $data[self::EVT_LIBELLE];
            }
        }
    }

    public function getSasReference(): ?string
    {
        return $this->sasReference;
    }

    /**
     * @return array<mixed>
     */
    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getPresentation(): ?string
    {
        return $this->presentation;
    }

    public function getDocuments(): ?string
    {
        return $this->documents;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function getSearchId(): ?int
    {
        return $this->searchId;
    }

    public function getDocumentTypeName(): ?string
    {
        return $this->documentTypeName;
    }
}
