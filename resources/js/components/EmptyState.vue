<template>
    <div class="empty-list">
        <img :src="assetUrl+'/empty.svg'" alt="empty" class="art" />
        <h6><slot /></h6>
        <a v-if="button" :href="link" class="button alt">{{ this.buttonName }}</a>
    </div>
</template>

<script>
    export default {
        name: "EmptyState",
        props: {
            assetUrl: String,
            button: {
                type: Boolean,
                default: false,
            },
            link: String,
            buttonName: String
        }
    }
</script>

<style scoped>

</style>
