<template>
    <div v-if="appointment.type === 'inloop'">
        <agenda-card-inloop-report v-if="isReportView"
          :active-event="activeEvent"
          :appointment="appointment"
          @completeinloop="(inloopId) => $emit('completeinloop', inloopId)"
          @click="(data) => $emit('click', data)">
        </agenda-card-inloop-report>
        <agenda-card-inloop v-else :appointment="appointment"></agenda-card-inloop>
    </div>
    <div v-else class="card" :class="getCardClass(appointment)" @click="toggleCard">
        <div class="header">
            <span class="date" v-if="appointment.question.isDC && ! isReportView && appointment.shouldReport">Vandaag</span>
            <span class="date" v-else v-bind:class="isReportView && appointment.question.isDC ? 'simple' : ''">{{ questionStatus(appointment).label }}</span>

            <template v-if="appointment.question.isDC">
                <template v-if="! isReportView">
                    <span>Zo spoedig mogelijk</span>
                </template>
            </template>

            <template v-else>
                <span>
                    {{ showDate ? `${(new Intl.DateTimeFormat('nl-NL', {weekday:'short'})).format(new Date(appointment.datetimeFrom))}, ${(new Intl.DateTimeFormat('en-GB', {month: '2-digit', day: '2-digit', year: 'numeric'})).format(new Date(appointment.datetimeFrom))},` :""}}
                    {{ localHours(appointment.datetimeFrom) }} - {{ localHours(appointment.datetimeTo) }}
                </span>
            </template>

            <i>expand_more</i>
        </div>

        <div class="details">
            <div v-if="appointment.question.type === 'event'" class="preview thumb">
                <img :src="appointment.question.event.image" alt="Afbeelding">
            </div>
            <div v-else :class="`preview icons ${ appointment.question.isDC ? 'directcall' : appointment.question.type }`"></div>

            <div class="details-info">
                <p class="title" v-if="appointment.question.type !== 'event'">{{ appointment.question.client.firstname }} {{ appointment.question.client.lastname }}</p>
                <p class="title" v-else>{{ appointment.question.event.info.EventTitle }}</p>

                <p v-if="appointment.question.type === 'event'">Organisator {{ appointment.question.client.firstname }}  {{ appointment.question.client.lastname }}</p>
                <p v-else-if="appointment.question.isDC">Direct Contact {{ !caregiverAgenda?`met ${ appointment.careGiver.firstname } ${ appointment.careGiver.lastname }`:''}} </p>
                <p v-else>{{ types[appointment.question.type] }} {{ !caregiverAgenda?`met ${ appointment.careGiver.firstname } ${ appointment.careGiver.lastname }`:''}} </p>
            </div>
        </div>

        <ul class="content details-with-labels no-border">
            <li v-if="appointment.report">
                <div class="label">Toelichting:</div>
                <div class="with-wrap">{{ appointment.report }}</div>
            </li>
            <li v-if="appointment.cancelReason">
                <div class="label">Reden van annuleren:</div>
                <div class="with-wrap">{{ appointment.cancelReason }}</div>
            </li>
            <li>
                <div class="label">Team</div>
                <div v-if="appointment.question.type === 'event'">{{appointment.careGiver.careGiverTeam}}</div>
                <div v-else>{{ appointment.question.client.team }}</div>
            </li>
            <li>
                <div class="label">Categorie</div>
                <div>{{ appointment.question.category }}</div>
            </li>

            <li>
                <div class="label" v-if="appointment.question.type === 'event'">Beschrijving</div>
                <div class="label" v-else>Vraag</div>
                <div class="with-wrap">{{ appointment.question.question }}</div>
            </li>
            <li v-if="appointment.selectedRoles && ! appointment.question.isDC">
                <div class="label">Netwerk</div>
                <div>{{ appointment.selectedRoles }}</div>
            </li>
            <li v-else-if="!caregiverAgenda && appointment.question.isDC">
                <div class="label">Telefoonnummer</div>
                <div>{{appointment.question.client.phone}}</div>
            </li>
            <template v-if="appointment.question.type === 'event'">
                <hr>
                <li>
                    <div class="label">Deelnemers</div>
                    <div>
                        <div>{{appointment.question.event.visitors.length}} / {{ appointment.question.event.info.MaxAttendees }} </div>
                        <div v-for="visitor in appointment.question.event.visitors" :key="visitor.Id">{{visitor.Firstname}} {{visitor.Lastname}}</div>
                    </div>
                </li>
                <li>
                    <div class="label">Deelnamekosten</div>
                    <div>
                        <div>{{ `${(new Intl.NumberFormat('de-DE', {style: 'currency', currency: 'EUR'}).format(appointment.question.event.info.Price))}` }} </div>
                    </div>
                </li>
                <li>
                    <div class="label">Adres</div>
                    <a target="_blank" :href="`https://www.google.com/maps/search/?api=1&query=${ encodeURIComponent(appointment.question.event.info.AddressLocation) }`">
                        {{ appointment.question.event.info.AddressLocation }}
                    </a>
                </li>
            </template>
            <template v-else-if="! appointment.question.isDC">
                <hr>
                <li>
                    <div class="label">Telefoonnummer</div>
                    <a :href="`tel:${appointment.question.client.phone}`">{{appointment.question.client.phone}}</a>
                </li>

                <li v-if="appointment.question.client.email">
                    <div class="label">E-mailadres</div>
                    <a :href="`mailto:${appointment.question.client.email}`">{{appointment.question.client.email}}</a>
                </li>
                <li v-if="appointment.question.client.address.street">
                    <div class="label">Adres</div>
                    <a target="_blank" :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${
                      appointment.question.client.address.street
                    } ${appointment.question.client.address.number}, ${appointment.question.client.address.zipcode
                    } ${appointment.question.client.address.city}`)}`">
                        {{ appointment.question.client.address.street }}
                        {{ appointment.question.client.address.number }},
                        {{ appointment.question.client.address.zipcode }}
                        {{ appointment.question.client.address.city }}
                    </a>
                </li>
            </template>

            <li v-if="caregiverAgenda">
                <a v-if="appointment.shouldReport" href="javascript:" @click="$emit('click', appointment)" class="button">Rapporteer Afspraak</a>
                <a v-if="showCancel(appointment) && appointment.question.type === 'event'" href="javascript:" @click="$emit('cancel', appointment)" class="button alt">Annuleer bijeenkomst</a>
                <a v-else-if="showCancel(appointment)" href="javascript:" @click="$emit('cancel', appointment)" class="button alt">Annuleer afspraak</a>
            </li>
        </ul>

        <a v-if="appointment.question.type === 'videocall' && caregiverAgenda && appointment.careGiver.AvailableVideoCallLink" :href="`${appointment.careGiver.VideoCallURLForCG}`" target="_blank" class="footer">
            Open de videobelafspraak
        </a>

        <a v-if="appointment.question.isDC && ! isReportView && caregiverAgenda" :href="`tel: ${appointment.question.client.phone}`" target="_blank" class="footer">
            Bel {{appointment.question.client.phone}}
        </a>
    </div>
</template>

<script>
    const StateColors = {
      created: "orange",
      completed: "green",
      failedNoShow: "purple",
      failedNoOther: "purple"
    }
    export default {
        name: "AgendaCard",
        props: {
            appointment: Object,
            showDate: {
              type: Boolean,
              default: false
            },
            accountType: String,
            activeEvent: String,
            caregiverAgenda: {
                type: Boolean,
                default: false
            },
            isReportView: {
                type: Boolean,
                default: false
            },
        },
        data() {
            return {
                show: false,
                types: {
                    call: 'Belafspraak',
                    videocall: 'Videobelafspraak',
                    visit: 'Huisbezoek'
                }
            };
        },
        computed: {
            dynamicLink: function() {
              return '/unifiedPlatform/caregiver/inloop.php?id=' + this.appointment.id;
            }
        },
        methods: {
            getCardClass(appointment) {
                const prefix = (appointment.question && appointment.question.isDC) ? "pink " : "";

                if (appointment.shouldReport !== true) {
                    if (appointment.state !== "completed") {
                        return prefix + StateColors[appointment.state];
                    }

                    return StateColors[appointment.state];
                }

                return prefix + "red";
            },
            toggleCard(event) {
                window.toggleCard(event, this.$el);
            },
            localHours(date) {
                const dateJs = new Date(date);
                const minutes = `${dateJs.getMinutes()}`.padStart(2,'0');
                return `${dateJs.getHours()}:${minutes}`
            },
            questionStatus(appointment) {
                return {
                    class: this.getCardClass(appointment),
                    label: appointment.question.state
                }
            },
          showCancel(appointment) {
            return (this.accountType==='caregiver' && appointment.state === 'created' && new Date(appointment.datetimeFrom) > new Date())
          }
        }
    }
</script>
<style scoped>
    .slide-enter-active, .slide-leave-active {
        transition: all .3s ease-out;
        overflow: hidden;
    }

    /*
    you set the css property before transition starts
    */
    .slide-enter, .slide-leave-to {
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
        transform: scaleY(0);
    }

    /*
    you set the css property it will be when transition ends
    */
    .slide-enter-to, .slide-leave {
        max-height: 180px;
        padding-top: 16px;
        padding-bottom: 16px;
        transform: scaleY(1);
    }
</style>
