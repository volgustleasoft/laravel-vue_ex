/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

import ElementUI from 'element-ui'
import locale from 'element-ui/lib/locale/lang/nl'


window.Vue = require('vue').default;

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))
Vue.component('rtest-header', require('./components/rtestHeader.vue').default);
Vue.component('alert', require('./components/Alert.vue').default);
Vue.component('empty-state', require('./components/EmptyState.vue').default);
Vue.component('agenda-card-inloop', require('./components/AgendaCardInloop.vue').default);
Vue.component('agenda-card-inloop-report', require('./components/AgendaCardInloopReport.vue').default);
Vue.component('person-card', require('./components/PersonCard.vue').default);
Vue.component('edit-inloop-timeslots', require('./components/EditInloopTimeslots.vue').default);
Vue.component('client-info', require('./components/ClientInfo.vue').default);
Vue.component('open-question', require('./components/OpenQuestion').default)
Vue.component('modal', require('./components/Modal.vue').default);
Vue.component('agenda', require('./components/Agenda.vue').default);
Vue.component('agenda-card', require('./components/AgendaCard.vue').default);
Vue.component('report-form', require('./components/ReportForm.vue').default);
Vue.component('checkup-card', require('./components/CheckupCard.vue').default);


/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */
window.addEventListener("DOMContentLoaded", (event) => {
    if(jQuery('#app').length) {
        window.Vue.use(ElementUI, { locale })
    }
});
