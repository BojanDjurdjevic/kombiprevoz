<script setup>
import { useAdminStore } from '@/stores/admin';
import { useDisplay } from 'vuetify'

const admin = useAdminStore()
const { smAndDown } = useDisplay()

</script>

<template>
  <v-app-bar flat density="comfortable">
    <v-btn
      v-if="smAndDown"
      icon="mdi-menu"
      @click="admin.toggleDrawer"
    />

    <v-toolbar-title>Admin panel</v-toolbar-title>
    

    <v-btn
      v-if="smAndDown"
      :prepend-icon="admin.filter ? 'mdi-chevron-right' : 'mdi-chevron-left'"
      @click="admin.toggleFilter"
    >Filteri</v-btn>
  </v-app-bar>
</template>
