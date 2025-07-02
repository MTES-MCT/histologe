<?php

namespace App\Service\DashboardWidget;

/**
 * @deprecated This class will be removed in the next major release.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
class WidgetType
{
    public const string WIDGET_TYPE_DATA_KPI = 'data-kpi';
    public const string WIDGET_TYPE_AFFECTATION_PARTNER = 'affectations-partenaires';
    public const string WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI = 'signalements-acceptes-sans-suivi';
    public const string WIDGET_TYPE_SIGNALEMENT_TERRITOIRE = 'signalements-territoires';
    public const string WIDGET_TYPE_ESABORA_EVENTS = 'esabora-evenements';
}
