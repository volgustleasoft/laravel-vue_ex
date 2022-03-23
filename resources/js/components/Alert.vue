<template>
    <div v-if="showMessage" :class="`${message.type} message card`" >{{ message.text }}</div>
</template>

<script>
export default {
    name: "Alert",
    props: {
        message: Object
    },

    data() {
        return {
            showMessage:  false
        }
    },
    methods: {
        clearSession() {
            axios.post('/event/ajaxPostHandler', {
                action: 'clearSession'
            })
        }
    },
    watch: {
        message(value) {
            if (value) {
                this.showMessage = true;
                setTimeout(()=>{
                    this.showMessage=false;
                    this.clearSession();
                }, 3000)
            }
        }
    }
}
</script>
<style scoped>
</style>
