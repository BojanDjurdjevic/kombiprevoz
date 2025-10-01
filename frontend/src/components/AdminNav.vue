<script setup>
import { ref, computed } from "vue";
import { VDateInput } from "vuetify/labs/VDateInput";
import { VNumberInput } from "vuetify/labs/VNumberInput";
import { useUserStore } from "@/stores/user";
import { useAdminStore } from "@/stores/admin";
import { useTourStore } from "@/stores/tours";
import europeCities from "@/data/country-city.json";
const user = useUserStore();
const admin = useAdminStore();
const tours = useTourStore();

const tab = ref(null);

const items = [
  "Postojeće rute",
  "Dodaj novu rutu",
  "Države i Gradovi"
];

const tab_bookings = ref(null);

const items_bookings = [
  "Pretraga",
  "U narednih 24h",
  "U narednih 48h"
];
</script>

<template>
  <v-card class="h-100">
    <v-layout class="h-100">
      <v-navigation-drawer expand-on-hover permanent rail>
        <v-list>
          <v-list-item :subtitle="user.user.email" :title="user.user.name">
            <template v-slot:prepend>
              <v-avatar color="green-darken-3" size="38">
                <span class="text-white font-weight-bold">
                  {{ user.user.initials }}
                </span>
              </v-avatar>
            </template>
          </v-list-item>
        </v-list>

        <v-divider></v-divider>

        <v-list density="compact" nav>
          <v-list-item
            prepend-icon="mdi-book-open-variant"
            title="Rezervacije"
            value="Rezervacije"
            @click="admin.adminView = 'Bookings'"
          ></v-list-item>
          <v-list-item
            prepend-icon="mdi-account-multiple"
            title="Korisnici"
            value="Korisnici"
            @click="admin.adminView = 'Users'"
          ></v-list-item>
          <v-list-item
            prepend-icon="mdi-car"
            title="Vožnje"
            value="Vožnje"
            @click="admin.adminView = 'Tours'"
          ></v-list-item>
          <v-list-item
            prepend-icon="mdi-star"
            title="Vozači"
            value="Vozači"
            @click="admin.adminView = 'Drivers'"
          ></v-list-item>
        </v-list>
      </v-navigation-drawer>

      <v-main class="d-flex">
        <v-container class="h-100 w-75 pa-3 d-flex flex-column align-center">
          <div class="w-100 text-center">
            <h1 class="mt-3">{{ admin.adminView }}</h1>
            <v-divider class="w-100"></v-divider>
          </div>
          <!--  BOOKINGS  -->

          <div 
            class="mt-6 pa-3 w-100 d-flex flex-column align-center"
            v-if="admin.adminView == 'Bookings'"
          >
            <v-card class="w-100">
              <v-toolbar 
              >
                <template v-slot>
                  <v-tabs v-model="tab_bookings" align-tabs="title" >
                    <v-tab
                      v-for="item in items_bookings"
                      :key="item"
                      :text="item"
                      :value="item"
                    ></v-tab>
                  </v-tabs>
                </template>
              </v-toolbar>

              <v-tabs-window v-model="tab_bookings">
                <v-tabs-window-item
                  v-for="item in items_bookings"
                  :key="item"
                  :value="item"
                >
                  <v-card flat>
                    <v-card-text>
                      <div class="w-100" v-if="tab_bookings == 'Pretraga'">
                        Pretraga
                      </div>
                      <div class="w-100" v-if="tab_bookings == 'U narednih 24h'">
                        24 H
                      </div>
                      <div class="w-100" v-if="tab_bookings == 'U narednih 48h'">
                        48 H
                      </div>
                    </v-card-text>
                  </v-card>
                </v-tabs-window-item>
              </v-tabs-window>
            </v-card>
          </div>

          <!--  USERS  -->

          <div
            class="mt-6 pa-3 w-100 d-flex flex-column align-center"
            v-if="admin.adminView == 'Users'"
          >
            Users
          </div>

          <!--   TOURS   -->
          <div
            class="mt-6 pa-3 w-100 d-flex flex-column align-center"
            v-if="admin.adminView == 'Tours'"
          >
            <v-card class="w-100">
              <v-toolbar 
              >
                <template v-slot>
                  <v-tabs v-model="tab" align-tabs="title" >
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
                <v-tabs-window-item
                  v-for="item in items"
                  :key="item"
                  :value="item"
                >
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
                                <div
                                  class="w-50 d-flex flex-column align-center"
                                >
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
                                    clearable
                                    label="Dodaj zastavu"
                                    class="w-100"
                                  ></v-file-input>
                                  <v-btn color="green-darken-3" class="mb-3"
                                    >Dodaj Državu</v-btn
                                  >
                                </div>
                                <div
                                  class="w-50 d-flex flex-column align-center"
                                >
                                  <v-autocomplete
                                    v-model="admin.selectedCountry"
                                    class="w-100 mt-5"
                                    prepend-icon="mdi-city-variant"
                                    clearable
                                    :items="europeCities.map((c) => c.country)"
                                    label="Država kojoj grad pripada"
                                  ></v-autocomplete>
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
                                    clearable
                                    label="Dodaj slike"
                                    class="w-100"
                                    multiple
                                    chips
                                  ></v-file-input>
                                  <v-btn color="green-darken-3" class="mb-3"
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
                                <div
                                  class="w-50 d-flex flex-column align-center"
                                >
                                  <h5>Trajanje u satima</h5>
                                  <v-number-input
                                    class="w-75 mt-1"
                                    control-variant="split"
                                    :max="33"
                                    :min="1"
                                  ></v-number-input>
                                </div>
                                <div
                                  class="w-50 d-flex flex-column align-center"
                                >
                                  <h5>Maksimum putnika</h5>
                                  <v-number-input
                                    class="w-75 mt-1"
                                    control-variant="split"
                                    :max="8"
                                    :min="1"
                                  ></v-number-input>
                                </div>
                                <div
                                  class="w-50 d-flex flex-column align-center"
                                >
                                  <h5>Cena u eurima</h5>
                                  <v-number-input
                                    class="w-75 mt-1"
                                    control-variant="split"
                                    :min="30"
                                  ></v-number-input>
                                </div>
                              </div>
                              <v-btn color="green-darken-3" class=""
                                >Dodaj Rutu</v-btn
                              >
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

          <!--  DRIVERS  -->

          <div
            class="mt-6 pa-3 w-100 d-flex flex-column align-center"
            v-if="admin.adminView == 'Drivers'"
          >
            Drivers
          </div>
        </v-container>
        <v-divider vertical></v-divider>

        <!--   FILTERS   -->

        <v-container
          class="w-25 pa-3 d-flex flex-column justify-space-between align-center"
        >
          <div class="w-100 text-center">
            <h1 class="mt-3">Filters</h1>
            <v-divider class="w-100"></v-divider>
          </div>
          <v-container
            class="w-100 h-100 d-flex flex-column justify-space-between align-center"
            v-if="admin.adminView == 'Bookings'"
          >
            <div class="pa-6 w-100 h-100 d-flex flex-column">
              <div class="ma-4">
                <v-text-field
                  prepend-icon="mdi-book-open-variant"
                  v-model="admin.bCode"
                  label="Broj rezervacije"
                  clearable
                ></v-text-field>
              </div>
              <div class="ma-4">
                <v-date-input
                  v-model="admin.depDay.date"
                  label="Datum vožnje"
                ></v-date-input>
              </div>
              <div class="ma-4">
                <v-select
                  prepend-icon="mdi-highway"
                  clearable
                  :items="admin.tours"
                  item-title="name"
                  item-value="id"
                  label="Izaberi Rutu"
                  v-model="admin.tourID"
                  return-object
                ></v-select>
              </div>
              <div class="ma-4">
                <v-text-field
                  prepend-icon="mdi-email"
                  v-model="admin.usrEmail"
                  label="Email korisnika"
                  clearable
                  type="email"
                ></v-text-field>
              </div>
            </div>
            <div
              class="w-100 h-25 d-flex flex-column align-center justify-space-evenly"
            >
              <v-btn
                class="w-75"
                prepend-icon="mdi-magnify"
                color="green-darken-3"
                >Traži</v-btn
              >
              <v-btn
                class="w-75"
                prepend-icon="mdi-close-circle-multiple"
                color="red-darken-3"
                >Obriši sve</v-btn
              >
            </div>
          </v-container>

          <!--    USERS    -->

          <v-container
            class="w-100 h-100 d-flex flex-column justify-space-between align-center"
            v-if="admin.adminView == 'Users'"
          >
            <div class="pa-6 w-100 h-100 d-flex flex-column">
              <div class="ma-4">
                <v-text-field
                  prepend-icon="mdi-email"
                  v-model="admin.usrEmail"
                  label="Email korisnika"
                  clearable
                  type="email"
                ></v-text-field>
              </div>
            </div>
            <div
              class="w-100 h-25 d-flex flex-column align-center justify-space-evenly"
            >
              <v-btn
                class="w-75"
                prepend-icon="mdi-magnify"
                color="green-darken-3"
                >Traži</v-btn
              >
              <v-btn
                class="w-75"
                prepend-icon="mdi-close-circle-multiple"
                color="red-darken-3"
                >Obriši sve</v-btn
              >
            </div>
          </v-container>

          <!--    TOURS    -->

          <v-container
            class="w-100 h-100 d-flex flex-column justify-space-between align-center"
            v-if="admin.adminView == 'Tours'"
          >
            <div class="pa-6 w-100 h-25 d-flex flex-column">
              <div class="ma-4">
                <v-select
                  prepend-icon="mdi-highway"
                  clearable
                  :items="admin.tours"
                  item-title="name"
                  item-value="id"
                  label="Izaberi Rutu"
                  v-model="admin.tourID"
                  return-object
                ></v-select>
              </div>
            </div>
            <div
              class="w-100 h-75 d-flex flex-column align-center justify-center"
            >
              <v-btn
                class="w-75 ma-3"
                prepend-icon="mdi-magnify"
                color="green-darken-3"
                >Traži</v-btn
              >
              <v-btn
                class="w-75 ma-3"
                prepend-icon="mdi-close-circle-multiple"
                color="red-darken-3"
                >Obriši sve</v-btn
              >
            </div>
          </v-container>
          <!--    DRIVERS    -->

          <v-container
            class="w-100 h-100 d-flex flex-column justify-space-between align-center"
            v-if="admin.adminView == 'Drivers'"
          >
            <div class="pa-6 w-100 h-100 d-flex flex-column">
              <div class="ma-4">
                <v-text-field
                  prepend-icon="mdi-email"
                  v-model="admin.usrEmail"
                  label="Email vozača"
                  clearable
                  type="email"
                ></v-text-field>
              </div>
            </div>
            <div
              class="w-100 h-25 d-flex flex-column align-center justify-space-evenly"
            >
              <v-btn
                class="w-75"
                prepend-icon="mdi-magnify"
                color="green-darken-3"
                >Traži</v-btn
              >
              <v-btn
                class="w-75"
                prepend-icon="mdi-close-circle-multiple"
                color="red-darken-3"
                >Obriši sve</v-btn
              >
            </div>
          </v-container>
        </v-container>
      </v-main>
    </v-layout>
  </v-card>
</template>
