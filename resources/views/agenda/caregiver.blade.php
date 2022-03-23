<x-layout>
    <x-slot name="app_test"></x-slot>
    <x-slot name="rtestheader">
        <rtest-header
            :manager-teams="false"
            :filter-type="[]"
        >Mijn Agenda
        </rtest-header>
    </x-slot>
    <div class="wrap">
        <alert :message="message"></alert>
    </div>
    <div class="wrap">
        <agenda
            type="caregiver"
            v-on:save-report="saveReport"
            v-on:picker-date="pickerDate"
            v-model="selectedDate"
            :appointments="appointments"
            :inloops="inloops"
            v-on:canceled-appointment="replaceAppointments"
            :attributes="attributes[selectedUser] === undefined?[]:attributes[selectedUser]"
            asseturl="{{$assetUrl}}"
        ></agenda>
    </div>
</x-layout>
<script>
    const CurrentPage = '{{ $currentFilterChoice }}';
    const message = JSON.parse('{!! $message !!}');
    const currentFilterChoices = [{value: 'active', label: 'Gepland', selected: false}, {
        value: 'past',
        label: 'In afwachting',
        selected: false
    }];
</script>
<script>
    window.onload = function () {
        setTimeout(function () {
            const messageCards = document.querySelectorAll('.card.message.success');

            for (let index = 0; index < messageCards.length; index++) {
                messageCards[index].style.display = 'none';
            }
        }, 3000);
    };
</script>
<script>
    window.addEventListener("DOMContentLoaded", (event) => {
        new Vue({
            delimiters: ['${', '}'],
            el: '#app',
            vuetify: new Vuetify(),
            data() {
                return {
                    selectedDate: (new Date(Date.now() - (new Date()).getTimezoneOffset() * 60000)).toISOString().substr(0, 10),
                    loading: false,
                    attributes: {
                        0: []
                    },
                    appointments: [],
                    message: {},
                    inloops: [],
                    callData: '',
                    dataMonths: {},
                    searchFilter: [
                        {
                            label: 'Alle',
                            value: 0,
                            selected: true
                        }
                    ],
                    selectedUser: 0
                }
            },
            mounted() {
                window.jQuery('#app').prop('style', '');
                this.loadData(this.selectedDate);
            },
            watch: {
                selectedDate: function (value) {
                    this.loadData(value)
                }
            },
            methods: {
                pickerDate(cal) {
                    this.callData = cal;

                    axios.get(`/agenda/ajaxHandler?action=getDatesForMonth&date=${cal}`)
                        .then((response) => {
                            const attributes = [];
                            for (const index in response.data.dates) {
                                attributes.push(response.data.dates[index])
                            }
                            this.attributes = attributes
                            this.dataMonths[`${cal}`] = attributes
                        });
                },
                saveReport({data, closeForm}) {
                    axios.post('/agenda/caregiver/reportAppointment', {
                        data: data
                    })
                        .then((response) => {
                            if (response.data.success) {
                                closeForm()
                                this.loadData(this.selectedDate)
                            }
                        })
                },
                loadData(date) {
                    const DateForm = new Date(date);
                    this.loading = true;
                    const year = new Intl.DateTimeFormat('en', {year: 'numeric'}).format(DateForm)
                    const month = new Intl.DateTimeFormat('en', {month: '2-digit'}).format(DateForm)
                    const day = new Intl.DateTimeFormat('en', {day: '2-digit'}).format(DateForm)
                    axios.get(`/agenda/ajaxHandler?action=getCaregiverAppointmentForDate&selectedUser=${this.selectedUser}&date=${year}-${month}-${day}`)
                        .then((response) => {
                            this.appointments = response.data.appointments;
                            this.inloops = response.data.inloopEvents;
                            this.loading = false
                        })
                },
                replaceAppointments(appointments) {
                    this.appointments = appointments
                }
            }
        })
    })
</script>
