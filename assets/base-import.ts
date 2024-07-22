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
