<script setup>
import { useDisplay } from 'vuetify'
const { smAndDown } = useDisplay()
</script>

<template>
  <v-app-bar flat density="comfortable">
    <v-btn
      v-if="smAndDown"
      icon="mdi-menu"
      @click="$emit('toggle-drawer')"
    />

    <v-toolbar-title>Admin panel</v-toolbar-title>
  </v-app-bar>
</template>
