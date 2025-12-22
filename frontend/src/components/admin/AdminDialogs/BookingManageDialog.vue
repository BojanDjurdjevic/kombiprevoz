<script setup>
import { useAdminStore } from '@/stores/admin'
import { useDisplay } from 'vuetify'

import BookingDetailsTab from './BookingDetailsTab.vue'
import BookingEditTab from './BookingEditTab.vue'
import BookingActionsTab from './BookingActionsTab.vue'

const admin = useAdminStore()
const { smAndDown } = useDisplay()
</script>

<template>
  <v-dialog
    v-model="admin.manageDialog"
    :fullscreen="smAndDown"
    transition="dialog-bottom-transition"
    persistent
  >
    <v-card>

      <!-- HEADER -->
      <v-toolbar color="indigo-darken-4">
        <v-btn icon @click="admin.manageDialog = false">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <v-toolbar-title>
          Rezervacija #{{ admin.selected?.code }}
        </v-toolbar-title>
      </v-toolbar>

      <!-- TABS -->
      <v-tabs v-model="admin.manageTab" grow>
        <v-tab value="details">Detalji</v-tab>
        <v-tab value="edit">Izmena</v-tab>
        <v-tab value="actions">Akcije</v-tab>
      </v-tabs>

      <!-- CONTENT -->
      <v-window v-model="admin.manageTab">
        <v-window-item value="details">
          <BookingDetailsTab />
        </v-window-item>

        <v-window-item value="edit">
          <BookingEditTab />
        </v-window-item>

        <v-window-item value="actions">
          <BookingActionsTab />
        </v-window-item>
      </v-window>

    </v-card>
  </v-dialog>
</template>
