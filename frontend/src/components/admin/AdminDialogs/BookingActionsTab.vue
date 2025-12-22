<script setup>
import { useAdminStore } from '@/stores/admin'

const admin = useAdminStore()
</script>

<template>
  <v-container fluid class="pa-3">

    <v-card variant="outlined">
      <v-card-title>Administrativne akcije</v-card-title>

      <v-card-text class="d-flex flex-column gap-4">

        <v-btn
          block
          color="indigo-darken-3"
          prepend-icon="mdi-eye"
          :href="'http://localhost:8080/' + admin.selected?.voucher"
          target="_blank"
        >
          Pregledaj vaučer
        </v-btn>

        <v-btn
          block
          color="green-darken-3"
          prepend-icon="mdi-email-arrow-right"
          @click="admin.actions.resendVoucher"
        >
          Pošalji vaučer
        </v-btn>

        <v-divider />

        <v-btn
          v-if="!admin.selected?.deleted"
          block
          color="red-darken-3"
          prepend-icon="mdi-close-thick"
          @click="admin.cancelDialog = true"
        >
          Otkaži rezervaciju
        </v-btn>

        <v-btn
          v-else
          block
          color="green-darken-3"
          prepend-icon="mdi-check-all"
          @click="admin.restoreDialog = true"
        >
          Aktiviraj rezervaciju
        </v-btn>

      </v-card-text>
    </v-card>

  </v-container>
</template>
