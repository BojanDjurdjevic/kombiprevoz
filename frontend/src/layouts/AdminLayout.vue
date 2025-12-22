<script setup>
import { ref } from 'vue'
import { useDisplay } from 'vuetify'
import AdminSidebar from '@/components/admin/AdminSidebar.vue'
import AdminTopBar from '@/components/admin/AdminTopBar.vue';
/*
const { mdAndUp } = useDisplay()
const drawer = ref(mdAndUp.value) */
const drawer = ref(true)

function toggleDrawer() {
  drawer.value = !drawer.value
}
</script>

<template>
  <v-layout class="admin-layout">
    <AdminSidebar v-model="drawer" />

    <v-main class="admin-main">
        <AdminTopBar @toggle-drawer="toggleDrawer" />
        <slot />
    </v-main>
  </v-layout>
</template>

<style scoped>
.admin-main {
  min-height: 100vh;
}
</style>
