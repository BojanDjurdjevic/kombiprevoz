<script setup>
import { useAdminStore } from '@/stores/admin';
import { ref, onMounted } from 'vue'
import europeCities from "@/data/country-city.json";
import { VDateInput } from "vuetify/labs/VDateInput";
import { VNumberInput } from "vuetify/labs/VNumberInput";
import { useSearchStore } from '@/stores/search';

const admin = useAdminStore();

onMounted(() => {
  admin.actions.fetchCountries() 
})

const tab = ref(null);

const items = [
  "Postojeće rute",
  "Dodaj novu rutu",
  "Države i Gradovi"
];
</script>

<template>
  <div
    class="mt-6 pa-3 w-100 d-flex flex-column align-center"
    v-if="admin.adminView == 'Tours'"
  >
    <v-card class="w-100">
      <v-toolbar>
        <template v-slot>
          <v-tabs v-model="tab" align-tabs="title">
            <v-tab
              v-for="item in items"
              :key="item"
              :text="item"
              :value="item"
            ></v-tab>
          </v-tabs>
        </template>
      </v-toolbar>

      <v-tabs-window v-model="tab">
        <v-tabs-window-item v-for="item in items" :key="item" :value="item">
          <v-card flat>
            <v-card-text>
              <div class="w-100" v-if="tab == 'Dodaj novu rutu'">
                <v-expansion-panels>
                  <v-expansion-panel expand focusable>
                    <v-expansion-panel-title
                      >Dodaj destinaciju</v-expansion-panel-title
                    >
                    <v-expansion-panel-text>
                      <h3>Dodaj novu destinaciju</h3>
                      <div class="d-flex w-100 justify-space-around">
                        <div class="w-50 d-flex flex-column align-center">
                          <v-autocomplete
                            class="w-100 mt-5"
                            prepend-icon="mdi-receipt-text-edit-outline"
                            disabled
                            label="Kontinent: Evropa"
                          ></v-autocomplete>
                          <v-autocomplete
                            v-model="admin.toAddCountry"
                            class="w-100"
                            prepend-icon="mdi-receipt-text-edit-outline"
                            clearable
                            :items="europeCities.map((c) => c.country)"
                            label="Dodaj novu državu"
                          ></v-autocomplete>
                          <v-file-input
                            v-model="admin.flag"
                            clearable
                            label="Dodaj zastavu"
                            accept="image/*"
                            class="w-100"
                            @change="admin.selectFlag"
                            @click:clear="admin.clearFlag"
                          /> <!--
                          <v-img
                            v-if="admin.preview"
                            :key="admin.preview"
                            :src="admin.preview"
                            max-height="250"
                            max-width="250"
                            class="mt-4 rounded-lg elevation-3"
                            cover
                          />-->
                          <img
                            v-if="admin.preview"
                            :src="admin.preview"
                            style="max-height:6rem; max-width:6rem; border-radius:100%; box-shadow:0 2px 8px rgba(0,0,0,0.3);"
                          />
                          <v-btn color="green-darken-3" class="mb-3"
                            @click="admin.actions.addCountry"
                            :disabled="!admin.flag || !admin.toAddCountry"
                          >Dodaj Državu</v-btn
                          >
                        </div>
                        <div class="w-50 d-flex flex-column align-center">
                          <v-autocomplete
                            v-model="admin.selectedCountry"
                            class="w-100 mt-5"
                            prepend-icon="mdi-city-variant"
                            clearable
                            :items="admin.dbCountries"
                            item-value="id"
                            item-title="name"
                            label="Država kojoj grad pripada"
                            return-object
                          ></v-autocomplete>
                          <!-- europeCities.map((c) => c.country) -->
                          <v-autocomplete
                            v-model="admin.selectedCity"
                            class="w-100"
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
                            class="w-100"
                            multiple
                            chips
                            @update:model-value="admin.selectCityPics"
                            @click:clear="admin.clearCityPics"
                          ></v-file-input>
                          <div v-if="admin.cityPreview" :key="admin.cityPreviewKey">
                            <!--
                            <v-img
                              v-for="p, index in admin.cityPreview"
                              :key="index"
                              :src="p"
                              max-height="250"
                              max-width="250"
                              class="mt-4 rounded-lg elevation-3"
                              cover
                            />
                            -->
                            <img
                              v-for="p, index in admin.cityPreview"
                              :key="index"
                              :src="p"
                              style="max-height:6rem; max-width:6rem; border-radius:100%; box-shadow:0 2px 8px rgba(0,0,0,0.3);"
                            />
                          </div>
                          
                          <v-btn color="green-darken-3" class="mb-3"
                            :disabled="!admin.selectedCity || !admin.selectedCountry || !admin.cityPics"
                            @click="admin.actions.addCity"
                            >Dodaj Grad</v-btn
                          >
                        </div>
                      </div>
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>
                <v-expansion-panels mb-3>
                  <v-expansion-panel expand focusable>
                    <v-expansion-panel-title
                      >Dodaj rutu</v-expansion-panel-title
                    >
                    <v-expansion-panel-text>
                      <h3 class="mt-9">Dodaj novu rutu</h3>
                      <div class="w-100 pa-3 d-flex flex-wrap">
                        <v-autocomplete
                          class="w-50 mt-5"
                          prepend-icon="mdi-receipt-text-edit-outline"
                          clearable
                          :items="admin.tours"
                          label="Država polaska"
                        ></v-autocomplete>
                        <v-autocomplete
                          class="w-50 mt-5"
                          prepend-icon="mdi-country"
                          clearable
                          :items="admin.tours"
                          label="Država dolaska"
                        ></v-autocomplete>
                        <v-autocomplete
                          class="w-50 mt-5"
                          prepend-icon="mdi-city-variant"
                          clearable
                          :items="admin.tours"
                          label="Grad polaska"
                        ></v-autocomplete>
                        <v-autocomplete
                          class="w-50 mt-5"
                          prepend-icon="mdi-country"
                          clearable
                          :items="admin.tours"
                          label="Grad dolaska"
                        ></v-autocomplete>
                        <v-select
                          class="w-50 mt-5"
                          prepend-icon="mdi-calendar-month-outline"
                          clearable
                          chips
                          label="Izaberi dane polaska"
                          :items="[
                            'Ponedeljak',
                            'Utorak',
                            'Sreda',
                            'Četvrtak',
                            'Petak',
                            'Subota',
                            'Nedelja',
                          ]"
                          multiple
                        ></v-select>
                      </div>
                      <div class="w-100 d-flex">
                        <div class="w-50 d-flex flex-column align-center">
                          <h5>Trajanje u satima</h5>
                          <v-number-input
                            class="w-75 mt-1"
                            control-variant="split"
                            :max="33"
                            :min="1"
                          ></v-number-input>
                        </div>
                        <div class="w-50 d-flex flex-column align-center">
                          <h5>Maksimum putnika</h5>
                          <v-number-input
                            class="w-75 mt-1"
                            control-variant="split"
                            :max="8"
                            :min="1"
                          ></v-number-input>
                        </div>
                        <div class="w-50 d-flex flex-column align-center">
                          <h5>Cena u eurima</h5>
                          <v-number-input
                            class="w-75 mt-1"
                            control-variant="split"
                            :min="30"
                          ></v-number-input>
                        </div>
                      </div>
                      <v-btn color="green-darken-3" class="">Dodaj Rutu</v-btn>
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>
              </div>
            </v-card-text>
          </v-card>
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card>
  </div>
</template>
