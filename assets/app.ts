/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/histologe.scss';

import * as Sentry from '@sentry/browser';

import {
    SENTRY_DSN_FRONT,
    SENTRY_ENVIRONMENT,
    SENTRY_TRACES_SAMPLE_RATE
} from'./controllers/environment'

Sentry.init({
    dsn: SENTRY_DSN_FRONT,
    integrations: [Sentry.browserTracingIntegration()],
    environment: SENTRY_ENVIRONMENT,
    tracesSampleRate: SENTRY_TRACES_SAMPLE_RATE,
});

import './vue/index';
import './vue/front-stats';
import './vue/dashboard';
import './vue/signalement-form';
import './vue/signalement-list';

import './controllers/component_search_address';
import './controllers/form_account';
import './controllers/form_helper';
import './controllers/form_nde';
import './controllers/form_notification';
import './controllers/back_partner_view/form_partner';
import './controllers/form_visite';
import './controllers/cookie_banner';
import './controllers/maintenance_banner';
import './controllers/activate_account/activate_account';
import './controllers/back_signalement_view/back_view_signalement';
import './controllers/back_signalement_view/form_edit_modal';
import './controllers/back_signalement_view/form_upload_documents';
import './controllers/back_signalement_view/input_autocomplete_bailleur';
import './controllers/back_signalement_view/form_cloture_modal';
import './controllers/back_signalement_view/form_acceptation_refus';
import './controllers/back_signalement_edit_file/back_signalement_edit_file';
import './controllers/back_signalement_delete_file/back_signalement_delete_file';
import './controllers/front_demande_lien_signalement/front_demande_lien_signalement';
import './controllers/front_suivi_signalement/front_suivi_signalement';
import './controllers/search_filter_form'
import './controllers/back_archived_signalements/back_archived_signalements_reactiver'
