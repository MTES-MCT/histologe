/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/histologe.scss';
import './styles/tooltip.scss';

// start the Stimulus application
// import './bootstrap';

import './vue/index';
import './vue/front-stats';
import './vue/dashboard';
import './vue/signalement-form';

import './controllers/form_account';
import './controllers/form_helper';
import './controllers/form_nde';
import './controllers/form_notification';
import './controllers/form_partner';
import './controllers/form_visite';
import './controllers/view_signalement';

