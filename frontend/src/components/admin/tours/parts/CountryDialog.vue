<script setup>
import { useAdminStore } from '@/stores/admin';
import { useDestStore } from '@/stores/destinations';

const admin = useAdminStore()
const dest = useDestStore()
</script>

<template>
  <v-dialog
    v-model="admin.countryDialog"
    fullscreen
    transition="dialog-bottom-transition"
    persistent
  >
    <v-card class="d-flex flex-column">

      <!-- HEADER -->
      <v-toolbar color="indigo-darken-4">
        <v-btn icon @click="admin.countryDialog = false">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <v-toolbar-title>
          Država: {{ admin.myCountry?.name }}
        </v-toolbar-title>
      </v-toolbar>

      <!-- CONTENT -->
      <v-card-text class="flex-grow-1">
        <v-container fluid>
          <v-row justify="center">
            <v-col cols="12" class="text-center">
              <h2>{{ admin.myCountry?.name }}</h2>
            </v-col>

            <!-- FLAG CARD -->
            <v-col cols="12" sm="8" md="6" lg="4" class="d-flex justify-center">
              <v-card
                class="w-100 d-flex justify-center align-center elevation-4 rounded-xl"
                style="aspect-ratio: 3 / 2;"
              >
                <v-img
                  :src="dest.getCountryImage(admin.myCountry)"
                  cover
                />
              </v-card>
            </v-col>

            <!-- FILE INPUT -->
            <v-col cols="12" sm="8" md="6" class="mt-4 d-flex align-center">
              <v-file-input
                v-model="admin.flag"
                label="Promeni zastavu"
                accept="image/*"
                clearable
                prepend-icon="mdi-image"
                @change="admin.selectFlag"
                @click:clear="admin.clearFlag"
              />
            </v-col>

            <!-- PREVIEW -->
            <v-col cols="12" class="d-flex justify-center mt-4">
              <img
                v-if="admin.preview"
                :src="admin.preview"
                style="
                  max-height: 6rem;
                  max-width: 6rem;
                  border-radius: 50%;
                  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                "
              />
            </v-col>

            <!-- ACTION -->
            <v-col cols="12" class="d-flex justify-center mt-6">
              <v-btn
                color="green-darken-3"
                size="large"
                :disabled="!admin.flag"
                @click="admin.actions.updateCountry"
              >
                Promeni zastavu
              </v-btn>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <!-- FOOTER -->
      <v-card-actions>
        <v-btn block color="success" @click="admin.countryDialog = false">
          Zatvori
        </v-btn>
      </v-card-actions>

    </v-card>
  </v-dialog>
</template>