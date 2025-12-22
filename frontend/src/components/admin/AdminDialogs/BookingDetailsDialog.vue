<script setup>
import { useAdminStore } from '@/stores/admin'
import { useDisplay } from 'vuetify'

import BookingEditTab from './BookingEditTab.vue'
import BookingActionsTab from './BookingActionsTab.vue'
import BookingDetailsTab from './BookingDetailsTab.vue'

const admin = useAdminStore()
const { smAndDown } = useDisplay()

const tab = ref('details')
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

      <!-- DESKTOP TABS -->
      <v-tabs
        v-if="!smAndDown"
        v-model="tab"
        grow
      >
        <v-tab value="details">Detalji</v-tab>
        <v-tab value="edit">Izmena</v-tab>
        <v-tab value="actions">Akcije</v-tab>
      </v-tabs>

      <!-- CONTENT -->
      <v-card-text>

        <!-- DESKTOP -->
        <v-window v-if="!smAndDown" v-model="tab">
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

        <!-- MOBILE -->
        <v-expansion-panels v-else multiple>
          <v-expansion-panel title="Detalji">
            <BookingDetailsTab />
          </v-expansion-panel>

          <v-expansion-panel title="Izmena">
            <BookingEditTab />
          </v-expansion-panel>

          <v-expansion-panel title="Akcije">
            <BookingActionsTab />
          </v-expansion-panel>
        </v-expansion-panels>

      </v-card-text>

      <v-card-actions>
        <v-btn block color="success" @click="admin.manageDialog = false">
          Zatvori
        </v-btn>
      </v-card-actions>

    </v-card>
  </v-dialog>
</template>
