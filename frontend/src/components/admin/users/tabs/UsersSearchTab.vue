<script setup>
import HistoryDialog from "@/components/profile/HistoryDialog.vue";
import { useAdminStore } from "@/stores/admin";

const admin = useAdminStore();
</script>

<template>
  <div v-if="admin.userByAdmin">
    <v-card class="w-full pa-3 mt-3">
      <v-card-title class="text-center">Korisnik: {{ admin.userByAdmin?.name }}</v-card-title>
      <v-card-subtitle class="text-center">Status korisnika: {{ admin.userByAdmin?.status }}</v-card-subtitle>
      <v-card-text>
        <p>Grad: {{ admin.userByAdmin?.city }}</p>
        <p>Adresa: {{ admin.userByAdmin?.address }}</p>
        <p>Email: {{ admin.userByAdmin?.email }}</p>
        <p>Telefon: {{ admin.userByAdmin?.phone }}</p>
      </v-card-text>
      <v-card-actions class="justify-space-between">
        <v-btn color="indigo-darken-4" prepend-icon="mdi-pencil-circle" @click="admin.actions.openUserEditDialog">Uredi</v-btn>
        <HistoryDialog v-if="admin.userLogs.length > 0"/>
      </v-card-actions>
    </v-card>
  </div>
</template>
