<script setup>
import { useAdminStore } from '@/stores/admin'
import { useDisplay } from 'vuetify'

const admin = useAdminStore()
const { smAndDown } = useDisplay()
</script>

<template>
  <component
    :is="smAndDown ? 'v-bottom-sheet' : 'v-dialog'"
    v-model="admin.cancelDialog"
  >
    <v-card>

      <v-card-title class="text-red">
        Potvrda otkazivanja
      </v-card-title>

      <v-card-text>
        Da li ste sigurni da želite da otkažete ovu vožnju?
      </v-card-text>

      <v-card-actions class="justify-end">
        <v-btn variant="text" @click="admin.cancelDialog = false">
          Odustani
        </v-btn>
        <v-btn
          color="red-darken-3"
          @click="admin.actions.confirmCancelBookingItem"
        >
          Potvrdi
        </v-btn>
      </v-card-actions>

    </v-card>
  </component>
</template>
