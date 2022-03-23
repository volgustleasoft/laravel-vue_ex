<template>
    <div class="card" :class="inloopStatus(appointment).class" @click="toggleCard">
        <div class="header">
            <span v-if="! listInloopPage || (listInloopPage && currentPage !== 'active')" class="date">{{appointment.stateText}}</span>
            <span>
                {{ listInloopPage ? `${(new Intl.DateTimeFormat('nl-NL', {weekday:'short'})).format(new Date(appointment.datetimeFrom))}, ${(new Intl.DateTimeFormat('en-GB', {month: '2-digit', day: '2-digit', year: 'numeric'})).format(new Date(appointment.datetimeFrom))},` :""}}
                {{ localHours(appointment.datetimeFrom) }} - {{ localHours(appointment.datetimeTo) }}
            </span>
            <i>expand_more</i>
        </div>

        <div class="details">
            <div class="preview icons walkin"></div>

            <div class="details-info">
                <p class="title">{{appointment.countAppointments}} Afspraken</p>
                <p>Inschrijfspreekuur</p>
                <p v-if="appointment.IsManager">Organisator: {{ appointment.careGiver.firstname }} {{ appointment.careGiver.lastname }}</p>
            </div>
        </div>

        <div class="content">
            <ul class="details-with-labels no-border">
                <li>
                    <div class="label">Team</div>
                    <div>{{appointment.team}}</div>
                </li>
                <li>
                    <div class="label">Locatie</div>
                    <a target="_blank" :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(appointment.location)}`">
                        {{ appointment.location }}
                    </a>
                </li>
            </ul>

            <h5 class="section">Afspraken</h5>

            <ul class="details-with-labels time-slots">
                <li v-for="timeslot in timeslots" :key="timeslot.Id">
                    <div class="label">{{localHours(timeslotDateTimeUTC(timeslot.timeslot.StartTime))}}</div>
                    <div v-if="timeslot.question">
                        <div class="time-slot-details">
                            <p class="title">{{timeslot.question.client.firstname}} {{timeslot.question.client.lastname}}</p>

                            <ul class="details-with-labels no-border">
                                <li v-if="timeslot.question.remarks">
                                    <div class="label">Toelichting:</div>
                                    <div class="with-wrap">{{ timeslot.question.remarks }}</div>
                                </li>
                                <li v-if="timeslot.question.cancelReason">
                                    <div class="label">Reden van annuleren:</div>
                                    <div class="with-wrap">{{ timeslot.question.cancelReason }}</div>
                                </li>
                                <li v-if="timeslot.timeslot.State=='failedNoShow'">
                                    <div class="label">Toelichting:</div>
                                    <div class="with-wrap">Nee, de client was niet aanwezig</div>
                                </li>
                                <li>
                                    <div class="label">Categorie:</div>
                                    <div>{{timeslot.question.category}}</div>
                                </li>
                                <li>
                                    <div class="label">Uitleg:</div>
                                    <div class="with-wrap">{{timeslot.question.question}}</div>
                                </li>
                            </ul>

                            <div class="buttons-group">
                                <a class="button outline" @click="showClientInfo(timeslot)">Bekijk contactgegevens</a>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="timeslot.timeslot.IsBlocked == 1" class="red">Niet beschikbaar</div>
                    <div v-else class="gray">Beschikbaar</div>
                </li>
            </ul>

            <div class="buttons-group">
                <a v-if="ifActiveInloop(appointment) && !appointment.IsManager" :href="`/inloop/edit/${appointment.id}`" class="button alt">Bewerk</a>
                <a v-if="! ifActiveInloop(appointment) && shouldReport(appointment) && !appointment.IsManager" :href="`/todolist?id=${appointment.id}`" class="button">Rapporteer Afspraak</a>
            </div>
        </div>

        <client-info :timeSlot="timeSlot" v-if="showModal" @click="hideClientInfo"></client-info>
    </div>
</template>
<script>
const StateColors = {
    new: "orange",
    completed: "green",
    listInloopPageColor: "purple",
}
export default {
    name: "AgendaCardInloop",
    props: {
        appointment: Object,
        listInloopPage: {
            type: Boolean,
            default: false
        },
        currentPage: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            show: false,
            showModal: false,
            timeSlot: false,
        };
    },
    computed: {
        timeslots: function() {
            let prepareTimeslots = [];
            Object.keys(this.appointment.timeslots).forEach(key => {
                prepareTimeslots = prepareTimeslots.concat(this.appointment.timeslots[key]);
            })
            return this.sortByDate(prepareTimeslots);
        }
    },
    methods: {
        toggleCard(event) {
            window.toggleCard(event, this.$el);
        },
        timeslotDateTimeUTC(timeslotTime) {
            var dateTime = new Date();
            var a_time_arr = (timeslotTime.replaceAll(':', ',')).split(',');

            dateTime.setHours(a_time_arr[0], a_time_arr[1]);
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
        inloopStatus(appointment) {
            if(this.listInloopPage && this.currentPage === 'active') {
                return {class: StateColors['listInloopPageColor']};
            } else {
                return {
                    class: this.shouldReport(appointment) === true ? "red" : StateColors[appointment.state]
                }
            }
        },
        shouldReport(appointment) {
            return (appointment.state === 'new' && new Date(appointment.datetimeTo) < new Date())
        },
        hideClientInfo() {
            this.showModal = false;
        },
        showClientInfo(timeslot) {
            this.showModal = true;
            this.timeSlot = timeslot;
        },
        ifActiveInloop(appointment) {
            return new Date(appointment.datetimeTo) > new Date()
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
    }
}
</script>
<style scoped>
</style>
