<script setup>
import { useAdminStore } from "@/stores/admin";
import { ref } from "vue";

const admin = useAdminStore();
</script>

<template>
  <div
    class="mt-6 pa-3 w-100 d-flex flex-column align-center"
    v-if="admin.adminView == 'Users'"
  >
    <v-card class="w-100">
      <v-toolbar>
        <template v-slot>
          <v-tabs v-model="admin.tab_users" align-tabs="title">
            <v-tab
              v-for="item in admin.items_users"
              :key="item"
              :text="item"
              :value="item"
            ></v-tab>
          </v-tabs>
        </template>
      </v-toolbar>

      <v-tabs-window v-model="admin.tab_users">
        <v-tabs-window-item v-for="item in admin.items_users" :key="item" :value="item">
          <v-card flat>
            <v-card-text>
              <div v-if="item == 'Pretraga'">
                Pretraga 
              </div>
              <div v-if="item == 'Kreiraj novog korisnika'">
                Novi user 
              </div>
            </v-card-text>
          </v-card>
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card>
  </div>
</template>
