<script setup>
import { useAdminStore } from '@/stores/admin';
import { ref, onMounted } from 'vue'
import europeCities from "@/data/country-city.json";
import { VDateInput } from "vuetify/labs/VDateInput";
import { VNumberInput } from "vuetify/labs/VNumberInput";
import { useSearchStore } from '@/stores/search';
import { useDestStore } from '@/stores/destinations';

const admin = useAdminStore();
const search = useSearchStore()
const dest = useDestStore();

onMounted(() => {
  admin.actions.fetchCountries() 
  admin.actions.fetchAllTours()
})

const tab = ref(null);

const items = [
  "Postojeće rute",
  "Dodaj novu rutu",
  "Države i Gradovi",
  "Pretraga"
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
          <v-tabs v-model="admin.tab_tours" align-tabs="title">
            <v-tab
              v-for="item in items"
              :key="item"
              :text="item"
              :value="item"
            ></v-tab>
          </v-tabs>
        </template>
      </v-toolbar>

      <v-tabs-window v-model="admin.tab_tours">
        <v-tabs-window-item v-for="item in items" :key="item" :value="item">
          <v-card flat>
            <v-card-text>
              <div v-if="admin.tab_tours == 'Postojeće rute'">
                <v-data-iterator
                    :items="admin.tours"
                    :items-per-page="10"
                    v-model:page="admin.tourPage"
                  >
                    <template v-slot:default="{ items }">
                      <v-row dense>
                        <v-col
                          v-for="(item, i) in items"
                          :key="item.raw.id"
                          cols="12"
                          sm="6"
                          md="6"
                        >
                          <v-card class="rounded-2xl shadow-md hover:shadow-lg transition-all relative">
                            <v-badge
                              v-if="item.raw.deleted == 1"
                              color="red-darken-3"
                              content="NEAKTIVNA"
                              bordered
                              class="absolute top-2 right-2 ml-3"
                            ></v-badge>
                            <v-card-title class="text-lg font-semibold">
                              {{ item.raw.from_city }} → {{ item.raw.to_city }}
                            </v-card-title>

                            <v-card-subtitle class="text-sm text-gray-600">
                              Polasci: {{admin.formatDepDays(item.raw.departures) }}
                            </v-card-subtitle>

                            <v-card-text class="space-y-2">
                              <div><strong>Vreme polaska:</strong> {{ item.raw.time }}</div>
                              <div><strong>Trajanje:</strong> {{ item.raw.duration }} sati</div>
                              <div><strong>Maksimum mesta:</strong> {{ item.raw.seats }}</div>
                              <div><strong>Cena:</strong> {{ item.raw.price }} €</div>
                            </v-card-text>

                            <v-card-actions>
                              <v-btn color="primary" size="small" @click="admin.showTour(item.raw)">
                                Detalji
                              </v-btn>
                            </v-card-actions>
                          </v-card>
                        </v-col>
                      </v-row>
                    </template>

                    <!-- Pagination -->
                    <template v-slot:footer>
                      <v-pagination
                        v-model="admin.page"
                        :length="admin.tPageCount"
                        total-visible="5"
                      ></v-pagination>
                    </template>
                  </v-data-iterator>

                    <!-- DIALOG to show details -->

                  <v-dialog v-model="admin.manageTourDialog" fullscreen transition="dialog-bottom-transition" persistent>
                    <v-card>
                      <!-- Header -->
                      <v-toolbar color="indigo-darken-4">
                        <v-btn icon @click="admin.manageTourDialog = false">
                          <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        <v-toolbar-title>Ruta: {{ admin.selectedTour?.name }}</v-toolbar-title>
                        <v-spacer></v-spacer>
                      </v-toolbar>

                      <!-- MAIN CONTENT - TOUR DETAILS -->

                      <!--  Details  -->
                      <v-card-text class="pa-4 ">
                        <h3 class="text-center">Detalji rute</h3>
                        <div class="w-100 h-100 d-flex">
                          <div class="w-50 h-100 pa-3 d-flex flex-column justify-space-evenly">
                            <p><strong>Ruta:</strong> {{ admin.selectedTour?.from_city }} → {{ admin.selectedTour?.to_city }}</p>
                            <p><strong>Polasci:</strong> {{ admin.formatDepDays(admin.selectedTour?.departures) }}</p>
                            <p><strong>Vreme Polaska:</strong> {{ admin.selectedTour?.time }}</p>
                            <p><strong>Trajanje:</strong> {{ admin.selectedTour?.duration }}</p>
                            <p><strong>Maksimum mesta:</strong> {{ admin.selectedTour?.seats }}</p>
                            <p><strong>Cena:</strong> {{ admin.selectedTour?.price }} €</p>
                            <div class="w-100 d-flex justify-space-around">
                              <div class="text-center" v-if="admin.selectedTour?.deleted == 1">
                                <h4>Aktiviraj turu</h4>
                                <v-btn
                                  color="green-darken-3"
                                  icon="mdi-check-all"
                                  @click="admin.actions.restoreTour"
                                ></v-btn>
                              </div>
                              <div class="text-center" v-if="admin.selectedTour?.deleted == 0">
                                <h4>Deaktiviraj turu</h4>
                                <v-btn
                                  color="red-darken-3"
                                  icon="mdi-check-all"
                                  @click="admin.actions.removeTour"
                                ></v-btn>
                              </div>
                              <div class="text-center" v-if="admin.selectedTour?.deleted == 1">
                                <h4>Zauvek obriši</h4>
                                <v-btn
                                  color="red-darken-3"
                                  icon="mdi-close-thick"
                                  @click="admin.actions.permanentDeleteTour"
                                ></v-btn>
                              </div>
                            </div>
                          </div>

                          <!--  ACTIONS - tour managing by admin  -->
                          <!--  Update Tour  -->
                          <div class="w-50 h-100 pa-6 mt-3 d-flex flex-column justify-space-around">
                            <div class="h-25 ">
                              <v-select
                                v-model="admin.changeDeps"
                                class="w-75 mt-5"
                                prepend-icon="mdi-calendar-month-outline"
                                clearable
                                chips
                                label="Izmeni dane polaska"
                                :items="tourDays"
                                multiple
                                return-object
                                item-title="day"
                                item-value="id"
                                @click:clear="admin.changeDeps = null"
                              ></v-select>
                              <v-text-field
                                prepend-icon="mdi-clock-time-three-outline"
                                class="w-75 mt-5"
                                v-model="admin.changeTime"
                                label="Vreme polaska"
                                placeholder="hh:mm:ss"
                                clearable
                                hint="Upiši u formatu 08:30:00"
                                persistent-hint
                                :rules="[admin.validateTime]"
                              ></v-text-field>
                            </div>
                            <div class="w-100 h-50">
                              <div class="w-75 d-flex flex-column align-center">
                                <h5>Trajanje u satima</h5>
                                <v-number-input
                                  v-model="admin.changeDuration"
                                  class="w-75 mt-1"
                                  control-variant="split"
                                  :max="33"
                                  :min="1"
                                ></v-number-input>
                              </div>
                              <div class="w-75 d-flex flex-column align-center">
                                <h5>Maksimum putnika</h5>
                                <v-number-input
                                  v-model="admin.changeTourSeats"
                                  class="w-75 mt-1"
                                  control-variant="split"
                                  :max="8"
                                  :min="1"
                                ></v-number-input>
                              </div>
                              <div class="w-75 d-flex flex-column align-center">
                                <h5>Cena u eurima</h5>
                                <v-number-input
                                  v-model="admin.changePrice"
                                  class="w-75 mt-1"
                                  control-variant="split"
                                  :min="30"
                                ></v-number-input>
                              </div>
                              <div class="w-75 d-flex justify-space-around">
                                <v-btn 
                                  variant="elevated" 
                                  color="green-darken-4"
                                  @click="admin.actions.updateTour"
                                  :disabled="!admin.changeDeps || !admin.changeTime || !admin.changeDuration || !admin.changeTourSeats || !admin.changePrice"
                                >Potvrdi</v-btn>
                                <v-btn color="red-darken-3"
                                  @click="admin.actions.clearTourEdit"
                                >Poništi</v-btn>
                              </div>
                              <!-- ACTIONS  -->
                            </div>
                          </div>
                        </div>
                      </v-card-text>

                      <!-- Btn -->
                      <v-card-actions>
                        <v-btn block color="success" @click="admin.manageTourDialog = false">
                          Zatvori
                        </v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-dialog>

                  
              </div>
              <div class="w-100" v-if="admin.tab_tours == 'Dodaj novu rutu'">
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
                          >Dodaj Državu</v-btn>
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
              <!-- Cities & Countries -->
              <div v-if="admin.tab_tours == 'Države i Gradovi'"
                class="d-flex flex-column justify-space-evenly"
                min-height="33rem"
              >
                <div class="w-100 d-flex justify-center">
                  <v-autocomplete
                    class="w-75 text-center"
                    label="Izaberi postojeću državu"
                    clearable
                    :items="admin.dbCountries"
                    item-title="name"
                    item-value="id"
                    return-object
                    v-model="admin.myCountry"
                    @update:model-value="admin.actions.searchByCountry"
                  ></v-autocomplete>
                </div>
                <div v-if="admin.myCountry" class="d-flex flex-column align-center">
                  
                  <v-card class="w-100 d-flex justif-space-evenly align-center" 
                    
                    height="6rem"
                  >
                    <v-img
                      class="d-flex justif-space-evenly align-center"
                      height="100%"
                      contain
                      :src="dest.getCountryImage(admin.myCountry)"
                    >
                      <v-card-title> {{  admin.myCountry?.name  }} </v-card-title>
                    </v-img>
                    <v-btn
                      elevated
                      color="indigo-darken-4"
                      
                      icon="mdi-pencil"
                      @click="admin.countryDialog = true"
                    ></v-btn>
                  </v-card>
                </div>
                <v-spacer></v-spacer>
                <h3 class="text-center mt-3"
                  v-if="admin.citiesByCountry && admin.myCountry"
                > Gradovi države: {{  admin.myCountry?.name  }} </h3>
                <div v-if="admin.citiesByCountry && admin.myCountry" 
                  v-for="c in admin.citiesByCountry.cities"
                  :key="c"
                  class="mt-3"
                >
                  <v-card class="w-100 d-flex justif-space-evenly"
                    height="3rem"
                  >
                    <v-img
                        class="align-start"
                        height="100%"
                        :src="dest.getCityPrimaryImage(c)"
                        cover
                        position="relative"
                    >
                        <v-card-title class="text-start"> {{ c.name }} </v-card-title>
                          <v-badge
                            v-if="c.deleted_city == 1"
                            color="red-darken-3"
                            content="NEAKTIVAN"
                            bordered
                          ></v-badge>
                    </v-img> 
                    <v-btn
                      elevated
                      color="indigo-darken-4"
                      height="100%"
                      icon="mdi-pencil"
                      @click="admin.openCityDialog(c)"
                    ></v-btn>
                  </v-card>
                </div>

                <!--  DIALOG for COUNTRY managing  -->

                <v-dialog v-model="admin.countryDialog" fullscreen transition="dialog-bottom-transition" persistent>
                    <v-card>
                      <!-- Header -->
                      <v-toolbar color="indigo-darken-4">
                        <v-btn icon @click="admin.countryDialog = false">
                          <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        <v-toolbar-title>Država: {{ admin.myCountry?.name }}</v-toolbar-title>
                        <v-spacer></v-spacer>
                      </v-toolbar>

                      <!-- MAIN CONTENT - COUNTRY DETAILS -->

                      <!--  Details  -->
                      <v-card-text class="pa-4 ">
                        <h2 class="text-center">{{ admin.myCountry?.name }}</h2>
                        <div class="w-100 h-100 pa-9 d-flex flex-column align-center">
                          <v-card class="w-50 h-50">
                            <v-img
                              class="text-white d-flex justif-space-evenly"
                              height="100%"
                              cover
                              :src="dest.getCountryImage(admin.myCountry)"
                            >
                            </v-img> 
                          </v-card>
                          <div class="w-50 pa-6 d-flex flex-column align-center">
                            <p class="ma-3">Promeni zastavu:</p>
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
                            style="max-height:6rem; max-width:6rem; border-radius:50%; box-shadow:0 2px 8px rgba(0,0,0,0.3);"
                          />
                          <v-btn color="green-darken-3" class="mb-3"
                            @click="admin.actions.updateCountry"
                            :disabled="!admin.flag"
                          >Promeni zastavu</v-btn>
                          </div>
                        </div>
                        
                        
                      </v-card-text>

                      <!-- Btn -->
                      <v-card-actions>
                        <v-btn block color="success" @click="admin.countryDialog = false">
                          Zatvori
                        </v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-dialog>

                  <!--  DIALOG for CITY managing  -->

                  <v-dialog v-model="admin.cityDialog" fullscreen transition="dialog-bottom-transition" persistent>
                    <v-card>
                      <!-- Header -->
                      <v-toolbar color="indigo-darken-4">
                        <v-btn icon @click="admin.closeCityDialog">
                          <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        <v-toolbar-title>Grad: {{ admin.myCity?.name }}</v-toolbar-title>
                        <v-spacer></v-spacer>
                      </v-toolbar>

                      <!-- MAIN CONTENT - CITY DETAILS -->

                      <!--  Details  -->
                      <v-card-text class="pa-4 ">
                        <h2 class="text-center">{{ admin.myCity?.name }}</h2>
                        <div class="w-100 h-100 pa-9 d-flex flex-column align-center">
                          <h3 v-if="admin.myCityPics.length > 0">Aktivne slike</h3>
                          <v-item-group
                            v-model="admin.selectedPictures"
                            multiple
                            class="d-flex flex-wrap"
                          >
                            
                              <v-item
                                v-for="photo in admin.myCityPics"
                                :key="photo.photo_id"
                                :value="photo.photo_id"
                              >
                                <template #default="{ isSelected, toggle }">
                                  <div class="relative ma-2" @click="toggle" style="cursor: pointer">
                                    
                                    <v-img
                                      :src="photo.file_path"
                                      width="10rem"
                                      height="10rem"
                                      cover
                                      class="rounded-lg"
                                    >

                                    
                                      <div
                                        v-if="isSelected"
                                        class="position-absolute d-flex align-center justify-center"
                                        style="
                                          top: 0;
                                          left: 0;
                                          width: 100%;
                                          height: 100%;
                                          background: rgba(0,0,0,0.4);
                                          border-radius: 12px;
                                        "
                                      >
                                        <v-icon size="36" color="white">mdi-check-circle</v-icon>
                                      </div>
                                    </v-img>
                                  </div>
                                </template>
                              </v-item>
                            
                          </v-item-group>

                          <div v-if="admin.myCityPics.length > 0">
                            <v-btn
                              color="red-darken-4"
                              class="mt-4"
                              :disabled="admin.selectedPictures.length === 0"
                              @click="admin.actions.deleteSelected"
                            >
                              Obriši selektovane ({{ admin.selectedPictures.length }})
                            </v-btn>
                          </div>

                          <!--     NEAKTIVNE SLIKE     -->
                          <h3 v-if="admin.cityDeletedPics.length > 0">Neaktivne slike</h3>
                          <v-item-group
                            v-model="admin.unSelectedPictures"
                            multiple
                            class="d-flex flex-wrap"
                          >
                            
                              <v-item
                                v-for="photo in admin.cityDeletedPics"
                                :key="photo.photo_id"
                                :value="photo.photo_id"
                              >
                                <template #default="{ isSelected, toggle }">
                                  <div class="relative ma-2" @click="toggle" style="cursor: pointer">
                                    
                                    <v-img
                                      :src="photo.file_path"
                                      width="10rem"
                                      height="10rem"
                                      cover
                                      class="rounded-lg"
                                    >

                                    
                                      <div
                                        v-if="isSelected"
                                        class="position-absolute d-flex align-center justify-center"
                                        style="
                                          top: 0;
                                          left: 0;
                                          width: 100%;
                                          height: 100%;
                                          background: rgba(0,0,0,0.4);
                                          border-radius: 12px;
                                        "
                                      >
                                        <v-icon size="36" color="white">mdi-check-circle</v-icon>
                                      </div>
                                    </v-img>
                                  </div>
                                </template>
                              </v-item>
                            
                          </v-item-group>

                          <div v-if="admin.cityDeletedPics.length > 0">
                            <v-btn
                              color="red-darken-4"
                              class="mt-4"
                              :disabled="admin.unSelectedPictures.length === 0"
                              @click="admin.actions.restoreSelected"
                            >
                              Aktiviraj selektovane ({{ admin.unSelectedPictures.length }})
                            </v-btn>
                          </div>
                          
                          <div class="w-75 mt-6 d-flex flex-column align-center">
                            <v-file-input
                              v-model="admin.cityPics"
                              clearable
                              label="Dodaj nove slike"
                              class="w-75 text-center"
                              multiple
                              chips
                              @update:model-value="admin.selectCityPics"
                              @click:clear="admin.clearCityPics"
                            ></v-file-input>
                            <div v-if="admin.cityPreview" :key="admin.cityPreviewKey">
                              <img
                                v-for="p, index in admin.cityPreview"
                                :key="index"
                                :src="p"
                                style="max-height:6rem; max-width:6rem; border-radius:100%; box-shadow:0 2px 8px rgba(0,0,0,0.3);"
                              />
                            </div>
                            
                            <v-btn color="green-darken-3" class="mb-3"
                              :disabled="!admin.cityPics"
                              @click="admin.actions.addCityPics"
                              >Dodaj Slike</v-btn
                            >
                          </div>
                        </div>
                        
                        
                      </v-card-text>

                      <!-- Btn -->
                      <v-card-actions>
                        <v-btn block color="success" @click="admin.closeCityDialog">
                          Zatvori
                        </v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-dialog>

              </div> 
              
              <!--  Search by Filter  -->
              <div v-if="admin.tab_tours == 'Pretraga' && admin.filteredTours">
                <v-data-iterator
                    :items="admin.filteredTours"
                    :items-per-page="10"
                    v-model:page="admin.tourPage"
                  >
                    <template v-slot:default="{ items }">
                      <v-row dense>
                        <v-col
                          v-for="(item, i) in items"
                          :key="item.raw.id"
                          cols="12"
                          sm="6"
                          md="6"
                        >
                          <v-card class="rounded-2xl shadow-md hover:shadow-lg transition-all relative">
                            <v-badge
                              v-if="item.raw.deleted == 1"
                              color="red-darken-3"
                              content="NEAKTIVNA"
                              bordered
                              class="absolute top-2 right-2 ml-3"
                            ></v-badge>
                            <v-card-title class="text-lg font-semibold">
                              {{ item.raw.from_city }} → {{ item.raw.to_city }}
                            </v-card-title>

                            <v-card-subtitle class="text-sm text-gray-600">
                              Polasci: {{admin.formatDepDays(item.raw.departures) }}
                            </v-card-subtitle>

                            <v-card-text class="space-y-2">
                              <div><strong>Vreme polaska:</strong> {{ item.raw.time }}</div>
                              <div><strong>Trajanje:</strong> {{ item.raw.duration }} sati</div>
                              <div><strong>Maksimum mesta:</strong> {{ item.raw.seats }}</div>
                              <div><strong>Cena:</strong> {{ item.raw.price }} €</div>
                            </v-card-text>

                            <v-card-actions>
                              <v-btn color="primary" size="small" @click="admin.showTour(item.raw)">
                                Detalji
                              </v-btn>
                            </v-card-actions>
                          </v-card>
                        </v-col>
                      </v-row>
                    </template>

                    <!-- Pagination -->
                    <template v-slot:footer>
                      <v-pagination
                        v-model="admin.page"
                        :length="admin.tPageCount"
                        total-visible="5"
                      ></v-pagination>
                    </template>
                  </v-data-iterator>

                    <!-- DIALOG to show details -->

                  <v-dialog v-model="admin.manageTourDialog" fullscreen transition="dialog-bottom-transition" persistent>
                    <v-card>
                      <!-- Header -->
                      <v-toolbar color="indigo-darken-4">
                        <v-btn icon @click="admin.manageTourDialog = false">
                          <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        <v-toolbar-title>Ruta: {{ admin.selectedTour?.from_city }} → {{ admin.selectedTour?.to_city }}</v-toolbar-title>
                        <v-spacer></v-spacer>
                      </v-toolbar>

                      <!-- MAIN CONTENT - TOUR DETAILS -->

                      <!--  Details  -->
                      <v-card-text class="pa-4 ">
                        <h3 class="text-center">Detalji rute</h3>
                        <div class="w-100 h-100 d-flex">
                          <div class="w-50 h-100 pa-3 d-flex flex-column justify-space-evenly">
                            <p><strong>Ruta:</strong> {{ admin.selectedTour?.from_city }} → {{ admin.selectedTour?.to_city }}</p>
                            <p><strong>Polasci:</strong> {{ admin.formatDepDays(admin.selectedTour?.departures) }}</p>
                            <p><strong>Vreme Polaska:</strong> {{ admin.selectedTour?.time }}</p>
                            <p><strong>Trajanje:</strong> {{ admin.selectedTour?.duration }}</p>
                            <p><strong>Maksimum mesta:</strong> {{ admin.selectedTour?.seats }}</p>
                            <p><strong>Cena:</strong> {{ admin.selectedTour?.price }} €</p>
                            <div class="w-100 d-flex justify-space-around">
                              <div class="text-center" v-if="admin.selectedTour?.deleted == 1">
                                <h4>Aktiviraj turu</h4>
                                <v-btn
                                  color="green-darken-3"
                                  icon="mdi-check-all"
                                  @click="admin.actions.restoreTour"
                                ></v-btn>
                              </div>
                              <div class="text-center" v-if="admin.selectedTour?.deleted == 0">
                                <h4>Deaktiviraj turu</h4>
                                <v-btn
                                  color="red-darken-3"
                                  icon="mdi-check-all"
                                  @click="admin.actions.removeTour"
                                ></v-btn>
                              </div>
                              <div class="text-center" v-if="admin.selectedTour?.deleted == 1">
                                <h4>Zauvek obriši</h4>
                                <v-btn
                                  color="red-darken-3"
                                  icon="mdi-close-thick"
                                  @click="admin.actions.permanentDeleteTour"
                                ></v-btn>
                              </div>
                            </div>
                          </div>

                          <!--  ACTIONS - tour managing by admin  -->
                          <!--  Update Tour  -->
                          <div class="w-50 h-100 pa-6 mt-3 d-flex flex-column justify-space-around">
                            <div class="h-25 ">
                              <v-select
                                v-model="admin.changeDeps"
                                class="w-75 mt-5"
                                prepend-icon="mdi-calendar-month-outline"
                                clearable
                                chips
                                label="Izmeni dane polaska"
                                :items="tourDays"
                                multiple
                                return-object
                                item-title="day"
                                item-value="id"
                                @click:clear="admin.changeDeps = null"
                              ></v-select>
                              <v-text-field
                                prepend-icon="mdi-clock-time-three-outline"
                                class="w-75 mt-5"
                                v-model="admin.changeTime"
                                label="Vreme polaska"
                                placeholder="hh:mm:ss"
                                clearable
                                hint="Upiši u formatu 08:30:00"
                                persistent-hint
                                :rules="[admin.validateTime]"
                              ></v-text-field>
                            </div>
                            <div class="w-100 h-50">
                              <div class="w-75 d-flex flex-column align-center">
                                <h5>Trajanje u satima</h5>
                                <v-number-input
                                  v-model="admin.changeDuration"
                                  class="w-75 mt-1"
                                  control-variant="split"
                                  :max="33"
                                  :min="1"
                                ></v-number-input>
                              </div>
                              <div class="w-75 d-flex flex-column align-center">
                                <h5>Maksimum putnika</h5>
                                <v-number-input
                                  v-model="admin.changeTourSeats"
                                  class="w-75 mt-1"
                                  control-variant="split"
                                  :max="8"
                                  :min="1"
                                ></v-number-input>
                              </div>
                              <div class="w-75 d-flex flex-column align-center">
                                <h5>Cena u eurima</h5>
                                <v-number-input
                                  v-model="admin.changePrice"
                                  class="w-75 mt-1"
                                  control-variant="split"
                                  :min="30"
                                ></v-number-input>
                              </div>
                              <div class="w-75 d-flex justify-space-around">
                                <v-btn 
                                  variant="elevated" 
                                  color="green-darken-4"
                                  @click="admin.actions.updateTour"
                                  :disabled="!admin.changeDeps || !admin.changeTime || !admin.changeDuration || !admin.changeTourSeats || !admin.changePrice"
                                >Potvrdi</v-btn>
                                <v-btn color="red-darken-3"
                                  @click="admin.actions.clearTourEdit"
                                >Poništi</v-btn>
                              </div>
                              <!-- ACTIONS  -->
                            </div>
                          </div>
                        </div>
                      </v-card-text>

                      <!-- Btn -->
                      <v-card-actions>
                        <v-btn block color="success" @click="admin.manageTourDialog = false">
                          Zatvori
                        </v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-dialog>

                  
              </div>
            </v-card-text>
          </v-card>
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card>
  </div>
</template>
