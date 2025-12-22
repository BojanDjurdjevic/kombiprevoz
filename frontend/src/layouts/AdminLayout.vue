<script setup>
import { ref } from 'vue'
import { useDisplay } from 'vuetify'
import AdminSidebar from '@/components/admin/AdminSidebar.vue'
import AdminTopBar from '@/components/admin/AdminTopBar.vue';
import { useAdminStore } from '@/stores/admin';
/*
const { mdAndUp } = useDisplay()
const drawer = ref(mdAndUp.value) */
const admin = useAdminStore()

const drawer = ref(true)

function toggleDrawer() {
  drawer.value = !drawer.value
}
</script>

<template>
  <v-layout class="admin-layout">
    <AdminSidebar v-model="admin.drawer" />

    <v-main class="admin-main">
        <AdminTopBar @toggle-drawer="admin.toggleDrawer" />
        <slot />
    </v-main>
  </v-layout>
</template>

<style scoped>
.admin-main {
  min-height: 100vh;
}
</style>
