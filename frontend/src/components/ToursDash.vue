<script setup>
import { useAdminStore } from '@/stores/admin';
import { ref, onMounted } from 'vue'
import europeCities from "@/data/country-city.json";
import { VDateInput } from "vuetify/labs/VDateInput";
import { VNumberInput } from "vuetify/labs/VNumberInput";
import { useSearchStore } from '@/stores/search';

const admin = useAdminStore();
const search = useSearchStore()

onMounted(() => {
  admin.actions.fetchCountries() 
  admin.actions.fetchAllTours()
})

const tab = ref(null);

const items = [
  "Postojeće rute",
  "Dodaj novu rutu",
  "Države i Gradovi"
];

const tourDays = [
  {id: 0, day: "Nedelja"},
  {id: 1, day: "Ponedeljak"},
  {id: 2, day: "Utorak"},
  {id: 3, day: "Sreda"},
  {id: 4, day: "Četvrtak"},
  {id: 5, day: "Petak"},
  {id: 6, day: "Subota"}
]
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
                          v-model="admin.countryFrom"
                          class="w-50 mt-5"
                          prepend-icon="mdi-receipt-text-edit-outline"
                          clearable
                          :items="admin.dbCountries"
                          label="Država polaska"
                          item-title="name"
                          item-value="id"
                          return-object
                          v-on:update:model-value="val => search.allCities(val.id, true)"
                        ></v-autocomplete>
                        <v-autocomplete
                          v-model="admin.countryTo"
                          class="w-50 mt-5"
                          prepend-icon="mdi-country"
                          clearable
                          :items="admin.dbCountries"
                          label="Država dolaska"
                          item-title="name"
                          item-value="id"
                          return-object
                          v-on:update:model-value="val => search.allCities(val.id, false)"
                        ></v-autocomplete>
                        <v-autocomplete
                          v-model="admin.cityFrom"
                          class="w-50 mt-5"
                          prepend-icon="mdi-city-variant"
                          clearable
                          :rules="[search.rules.required]"
                          :items="search.availableCities"
                          item-title="name"
                          item-value="name"
                          label="Grad polaska"
                          return-object
                        ></v-autocomplete>
                        <v-autocomplete
                          v-model="admin.cityTo"
                          class="w-50 mt-5"
                          prepend-icon="mdi-country"
                          clearable
                          :rules="[search.rules.required]"
                          :items="search.availableCitiesTo"
                          item-title="name"
                          item-value="name"
                          label="Grad dolaska"
                          return-object
                        ></v-autocomplete>
                        <v-select
                          v-model="admin.daysOfTour"
                          class="w-50 mt-5"
                          prepend-icon="mdi-calendar-month-outline"
                          clearable
                          chips
                          label="Izaberi dane polaska"
                          :items="tourDays"
                          multiple
                          return-object
                          item-title="day"
                          item-value="id"
                        ></v-select>
                        <v-text-field
                          prepend-icon="mdi-time"
                          class="w-50 mt-5"
                          v-model="admin.tourTime"
                          label="Vreme polaska"
                          placeholder="hh:mm:ss"
                          hint="Upiši u formatu 08:30:00"
                          persistent-hint
                          :rules="[admin.validateTime]"
                        ></v-text-field>
                      </div>
                      <div class="w-100 d-flex">
                        <div class="w-50 d-flex flex-column align-center">
                          <h5>Trajanje u satima</h5>
                          <v-number-input
                            v-model="admin.hours"
                            class="w-75 mt-1"
                            control-variant="split"
                            :max="33"
                            :min="1"
                          ></v-number-input>
                        </div>
                        <div class="w-50 d-flex flex-column align-center">
                          <h5>Maksimum putnika</h5>
                          <v-number-input
                            v-model="admin.pax"
                            class="w-75 mt-1"
                            control-variant="split"
                            :max="8"
                            :min="1"
                          ></v-number-input>
                        </div>
                        <div class="w-50 d-flex flex-column align-center">
                          <h5>Cena u eurima</h5>
                          <v-number-input
                            v-model="admin.price"
                            class="w-75 mt-1"
                            control-variant="split"
                            :min="30"
                          ></v-number-input>
                        </div>
                      </div>
                      <v-btn color="green-darken-3" class="" :disabled="!admin.cityFrom || !admin.cityTo || !admin.daysOfTour || !admin.hours || !admin.pax || !admin.price || !admin.tourTime"
                        @click="admin.actions.addTour"
                      >Dodaj Rutu</v-btn>
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
