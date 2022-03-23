<template>
    <div class="card todo-list red" @click="toggleCard">
        <div class="header">
            <span class="date">Repport in behandeling</span>
            <span>
              {{ `${ (new Intl.DateTimeFormat('nl-NL', { weekday: 'short' })).format(new Date(appointment.datetimeFrom)) }, ${ (new Intl.DateTimeFormat('en-GB', { month: '2-digit', day: '2-digit', year: 'numeric' })).format(new Date(appointment.datetimeFrom)) },` }}
              {{ localHours(appointment.datetimeFrom) }} - {{ localHours(appointment.datetimeTo) }}
            </span>

            <i>expand_more</i>
        </div>

        <div class="details">
            <div class="preview icons walkin"></div>

            <div class="details-info">
                <p class="title">{{ appointment.countAppointments }} Afspraken</p>
                <p>Inschrijfspreekuur</p>
                <p v-if="appointment.IsManager">Organisator: {{ appointment.careGiver.firstname }} {{ appointment.careGiver.lastname }}</p>
            </div>

            <div class="progress">
                <div class="progress-line"><div class="bar" :style="{'width':getReportedPercent(appointment)}"></div></div>
                <p>{{ getCountCompletedReport(appointment) }}/{{ appointment.countAppointments }} Afgerond</p>
            </div>
        </div>

        <ul class="content timetable">
            <li v-if="! appointment.countAppointments">
                <label>Er zijn geen afspraken gemaakt in dit inschrijfspreekuur.</label>
                <button v-if="!appointment.IsManager" class="button" @click="$emit('completeinloop', appointment.id)">Sluit inschrijfspreekuur af</button>
            </li>

            <li v-else v-for="timeslot in timeslots" :key="timeslot.Id">
                <div class="task">
                    <div class="work-time with-wrap">
                        {{localHours(timeslotDateTimeUTC(timeslot.timeslot.StartTime))}} - {{localHours(timeslotDateTimeUTC(timeslot.timeslot.EndTime))}}
                    </div>

                    <div class="performer">
                        <ul class="details-with-labels no-border">
                            <div><h6>{{timeslot.question.client.firstname}} {{timeslot.question.client.lastname}}</h6></div>
                            <div style="white-space: pre-wrap;"><p>{{timeslot.question.question}}</p></div>
                        </ul>
                    </div>
                </div>

                <div class="report" v-if="timeslot.question.state === 'completed' && ['failedNoOther', 'failedNoShow', 'completed'].includes(timeslot.timeslot.State)">
                    <i >check_circle</i>
                    <p>Rapport verzonden</p>
                </div>
                <div class="report" v-else-if="appointment.IsManager">
                    <i style="color: red">remove_circle</i>
                    <p>Nog niet gerapporteerd</p>
                </div>
                <div class="report" v-else>
                    <button class="button" @click="$emit('click', extendTimeslot(timeslot))">Rapporteer afspraak</button>
                </div>
            </li>
        </ul>
    </div>
</template>
<script>
export default {
    name: "AgendaCardInloop",
    props: {
        appointment: Object,
    },
    computed: {
        timeslots: function() {
            let prepareTimeslots = [];

            Object.keys(this.appointment.timeslots).forEach(key => {
                if(this.appointment.timeslots[key].question) {
                    prepareTimeslots = prepareTimeslots.concat(this.appointment.timeslots[key]);
                }
            })

            return this.sortByDate(prepareTimeslots);
        }
    },
    mounted: function () {
        this.$nextTick(function () {
            const url = new URLSearchParams(window.location.search)
            const activeEvent = url.get('id')
            if(activeEvent == this.appointment.id) {
                this.scrollToCurrentInloop(activeEvent);
                document.querySelector("#inloop"+activeEvent+" "+".card.todo-list").click();
            }
        })
    },
    methods: {
        toggleCard(event) {
            window.toggleCard(event, this.$el);
        },
        scrollToCurrentInloop(activeEvent) {
            document.getElementById("inloop"+activeEvent).scrollIntoView(true);
        },
        extendTimeslot(timeslot) {
            timeslot.datetimeFrom = new Date(this.appointment.datetimeFrom);
            return timeslot;
        },
        getReportedPercent(appointment) {
            if(appointment.countAppointments == 0) {
                return '100%';
            }
            return this.getCountCompletedReport(appointment)/appointment.countAppointments*100 + '%';
        },
        getCountCompletedReport(appointment) {
            let count = 0;
            Object.keys(appointment.timeslots).forEach(
                function(key) {
                    let timeslot = appointment.timeslots[key];
                    if(typeof timeslot.question == 'undefined') {
                        return;
                    }

                    if(timeslot.question.state == 'completed' && ['failedNoOther', 'failedNoShow', 'completed'].includes(timeslot.timeslot.State)) {
                        count++;
                    }
                }
            );
            return count;
        },
        timeslotDateTimeUTC(timeslotTime) {
            var dateTime = new Date();
            var a_time_arr = (timeslotTime.replaceAll(':', ',') + ',0').split(',');
            dateTime.setHours(a_time_arr[0], a_time_arr[1], a_time_arr[2]);
            return this.localDateTimeFromUTC(dateTime).toUTCString();
        },
        localHours(date) {
            const dateJs = new Date(date);
            const minutes = `${dateJs.getMinutes()}`.padStart(2,'0');
            return `${dateJs.getHours()}:${minutes}`
        },
        localDateTimeFromUTC(dateTime) {
            return new Date(Date.UTC(dateTime.getFullYear(), dateTime.getMonth(), dateTime.getDate(), dateTime.getHours(), dateTime.getMinutes(), dateTime.getSeconds()));
        },
        hideClientInfo() {
            this.showModal = false;
        },
        showClientInfo(timeslot) {
            this.showModal = true;
            this.timeSlot = timeslot;
        },
        sortByDate(timeslots, order = 'ASC') {
            let operator = (order == "ASC") ? ">" : "<";
            timeslots.sort((a, b) => {
                var a_todayDate = new Date();
                var a_time_arr = (a.timeslot.StartTime.replaceAll(':', ',') + ',0').split(',');
                a_todayDate.setHours(a_time_arr[0], a_time_arr[1], a_time_arr[2])
                var b_todayDate = new Date();
                var b_time_arr = (b.timeslot.StartTime.replaceAll(':', ',') + ',0').split(',');
                b_todayDate.setHours(b_time_arr[0], b_time_arr[1], b_time_arr[2])

                if (operator == ">") {
                    return  a_todayDate < b_todayDate ? -1 : 1;
                } else {
                    return a_todayDate > b_todayDate ? -1 : 1;
                }
            });
            return timeslots;
        }
    },
    data() {
        return {
            showModal: false,
            timeSlot: false
        };
    },
}
</script>
<style scoped>
</style>
