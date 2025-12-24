<script setup>
import { useAdminStore } from '@/stores/admin'
import { ref, computed, watch } from 'vue'
import europeCities from "@/data/country-city.json";

const admin = useAdminStore()

// computed za disable dugme
const isCountryDisabled = computed(() => !admin.toAddCountry || !admin.flag)
const isCityDisabled = computed(() => !admin.selectedCountry || !admin.selectedCity || !admin.cityPics)
</script>

<template>
  <v-row class="w-100" dense>
    <!-- Dodaj Državu -->
    <v-col cols="12" md="6">
      <v-expansion-panels elevation="1" multiple>
        <v-expansion-panel>
          <v-expansion-panel-title>Dodaj Državu</v-expansion-panel-title>
          <v-expansion-panel-text>
            <v-autocomplete
              class="w-100 mt-3"
              prepend-icon="mdi-receipt-text-edit-outline"
              disabled
              label="Kontinent: Evropa"
            ></v-autocomplete>

            <v-autocomplete
              v-model="admin.toAddCountry"
              class="w-100 mt-3"
              prepend-icon="mdi-receipt-text-edit-outline"
              clearable
              :items="europeCities.map(c => c.country)"
              label="Dodaj novu državu"
            ></v-autocomplete>

            <v-file-input
              v-model="admin.flag"
              clearable
              label="Dodaj zastavu"
              accept="image/*"
              class="w-100 mt-3"
              @change="admin.selectFlag"
              @click:clear="admin.clearFlag"
            />

            <div v-if="admin.preview" class="mt-3 d-flex justify-center">
              <img
                :src="admin.preview"
                style="max-height:6rem; max-width:6rem; border-radius:100%; box-shadow:0 2px 8px rgba(0,0,0,0.3);"
              />
            </div>

            <v-btn
              color="green-darken-3"
              class="mt-4 mb-2 w-100"
              :disabled="isCountryDisabled"
              @click="admin.actions.addCountry"
            >Dodaj Državu</v-btn>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </v-col>

    <!-- Dodaj Grad -->
    <v-col cols="12" md="6">
      <v-expansion-panels elevation="1" multiple>
        <v-expansion-panel>
          <v-expansion-panel-title>Dodaj Grad</v-expansion-panel-title>
          <v-expansion-panel-text>
            <v-autocomplete
              v-model="admin.selectedCountry"
              class="w-100 mt-3"
              prepend-icon="mdi-city-variant"
              clearable
              :items="admin.dbCountries"
              item-value="id"
              item-title="name"
              label="Država kojoj grad pripada"
              return-object
            ></v-autocomplete>

            <v-autocomplete
              v-model="admin.selectedCity"
              class="w-100 mt-3"
              prepend-icon="mdi-city-variant"
              clearable
              :items="admin.cityOptions"
              :disabled="!admin.selectedCountry"
              label="Dodaj novi grad"
            ></v-autocomplete>

            <v-file-input
              v-model="admin.cityPics"
              clearable
              label="Dodaj slike"
              class="w-100 mt-3"
              multiple
              chips
              @update:model-value="admin.selectCityPics"
              @click:clear="admin.clearCityPics"
            />

            <div v-if="admin.cityPreview" :key="admin.cityPreviewKey" class="mt-3 d-flex flex-wrap gap-2 justify-center">
              <img
                v-for="(p, index) in admin.cityPreview"
                :key="index"
                :src="p"
                style="max-height:6rem; max-width:6rem; border-radius:100%; box-shadow:0 2px 8px rgba(0,0,0,0.3);"
              />
            </div>

            <v-btn
              color="green-darken-3"
              class="mt-4 mb-2 w-100"
              :disabled="isCityDisabled"
              @click="admin.actions.addCity"
            >Dodaj Grad</v-btn>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </v-col>
  </v-row>
</template>


