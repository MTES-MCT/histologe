<?php

namespace App\EventSubscriber;

use App\Entity\File;
use App\Event\FileUploadedEvent;
use App\Manager\SuiviManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class FileUploadedSubscriber implements EventSubscriberInterface
{
    public function __construct(private SuiviManager $suiviManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FileUploadedEvent::NAME => 'onFileUploaded',
        ];
    }

    public function onFileUploaded(FileUploadedEvent $event): void
    {
        $files = $event->getFiles();
        $user = $event->getUser();

        $files = array_map(function ($file) {
            return $file['file'];
        }, $files);

        $signalement = $event->getSignalement();
        $filesFiltered = $signalement->getFiles()->filter(function (File $file) use ($files) {
            return in_array($file->getFilename(), $files, true);
        });

        $event->setFilesPushed($filesFilteredArray = $filesFiltered->toArray());
        $this->suiviManager->createInstanceForFilesSignalement($user, $signalement, $filesFilteredArray);
    }
}
