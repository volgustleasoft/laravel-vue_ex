<template>
    <div class="modal" v-if="isModalVisible">
        <section class="box">
            <h3>{{ title }}</h3>

            <div class="content">
                <p><slot /></p>
            </div>

            <div class="buttons-group">
                <button v-for="button in buttons" :key="button.label" @click="emitClick" :class="button.class!==undefined?`button ${button.class}`:'button'">{{ button.label }}</button>
            </div>
        </section>
    </div>
</template>

<script>
    export default {
        name: "Modal",
        props: {
            buttons: Array,
            title: String,
        },
        data() {
            return {
                isModalVisible: true
            }
        },
        methods: {
            emitClick(e) {
                this.$emit('click',e.target.innerHTML)
            },
            emitClose() {
                this.isModalVisible = false;
                // this.Agenda.cancelAppointment('close');
            }
        }
    }
</script>

<style scoped>
    .modal {
        z-index: 1000;
    }
</style>