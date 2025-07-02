<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Entity\Enum\InterfacageType;
use App\Entity\JobEvent;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetDataManagerInterface;
use App\Service\DashboardWidget\WidgetType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @deprecated This class will be removed in the next major release.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
class EsaboraEventWidgetLoader extends AbstractWidgetLoader
{
    protected ?string $widgetType = WidgetType::WIDGET_TYPE_ESABORA_EVENTS;

    public function __construct(
        protected ParameterBagInterface $parameterBag,
        protected WidgetDataManagerInterface $widgetDataManager,
    ) {
        parent::__construct($this->parameterBag);
    }

    public function load(Widget $widget): void
    {
        parent::load($widget);
        $data = $this->widgetDataManager->findLastJobEventByInterfacageType(
            InterfacageType::ESABORA->value,
            $this->widgetParameter['data'],
            $widget->getTerritories()
        );
        foreach ($data as $key => $event) {
            $data[$key]['response'] = null;
            if (JobEvent::STATUS_FAILED === $event['status']) {
                $data[$key]['response'] = $this->normalizeErrorMessage($event);
            }
        }
        $widget->setData($data);
    }

    /**
     * @param array<string, mixed> $event
     */
    private function normalizeErrorMessage(array $event): string
    {
        if (!isset($event['response'])) {
            return 'Réponse vide';
        }

        $response = json_decode($event['response'], true);
        if (!$response) {
            return 'Réponse vide';
        }
        if (isset($response['message']) && !\is_array($response['message'])) {
            return $response['message'];
        }
        if (isset($response['message']) && \is_array($response['message'])) {
            return $this->normalizeErrorList($response['message']);
        }
        if (isset($response['errorReason'])) {
            $errorReason = json_decode($response['errorReason'], true);

            if (isset($errorReason['message']) && !\is_array($errorReason['message'])) {
                return $errorReason['message'].' ('.$response['statusCode'].')';
            }
            if (isset($errorReason['message']) && \is_array($errorReason['message'])) {
                return $this->normalizeErrorList($errorReason['message']);
            }
            if (isset($errorReason['nbResults']) && 0 === $errorReason['nbResults']) {
                return 'Le dossier n\'a pas été trouvé';
            }
        }

        return 'SAS Etat : '.$response['sasEtat'].' ('.$response['statusCode'].')';
    }

    /**
     * @param array<int, array{error: string, fieldName: string}> $messages
     *
     * @return string
     */
    private function normalizeErrorList(array $messages)
    {
        $html = '';
        foreach ($messages as $msg) {
            $html .= $msg['error'].' ('.$msg['fieldName'].')<br>';
        }

        return substr($html, 0, -4);
    }
}
