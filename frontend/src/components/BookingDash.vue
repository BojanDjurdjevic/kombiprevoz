<script setup>
import { useAdminStore } from "@/stores/admin";
import { ref, computed, watch } from "vue";
import { VDateInput } from "vuetify/labs/VDateInput";
import { VNumberInput } from "vuetify/labs/VNumberInput";

const admin = useAdminStore();
/*
const bookings24 = computed(() => admin.in24 || []);
const bookings48 = computed(() => admin.in48 || []);
*/
const bookings24 = computed(() => Object.values(admin.in24?.orders || {}));
const bookings48 = computed(() => Object.values(admin.in48?.orders || {}));
/*
const table_24 = computed(() => {
  return Object.values(bookings24.value).map((tour) => ({
    tour_id: tour.tour_id,
    from_city: tour.from_city,
    to_city: tour.to_city,
    pickuptime: tour.pickuptime,
    duration: tour.duration,
    rides_count: tour.rides.length,
    date: tour.date,
    total_places: tour.rides.reduce((sum, r) => sum + r.places, 0),
    rides: tour.rides,
    drivers: tour.drivers,
    selectedDriver: null,
  }));
}); */
const table_24 = ref([])

watch(() => bookings24.value, (val) => {
  if(val) {
    table_24.value = Object.values(val).map(tour => ({
      tour_id: tour.tour_id,
      from_city: tour.from_city,
      to_city: tour.to_city,
      pickuptime: tour.pickuptime,
      duration: tour.duration,
      rides_count: tour.rides.length,
      date: tour.date,
      total_places: tour.rides.reduce((sum, r) => sum + r.places, 0),
      rides: tour.rides,
      drivers: tour.drivers,
      selectedDriver: null,
    }))
  }
}, { immediate: true })
/*
const table_48 = computed(() => {
  return Object.values(bookings48.value).map((tour) => ({
    tour_id: tour.tour_id,
    from_city: tour.from_city,
    to_city: tour.to_city,
    pickuptime: tour.pickuptime,
    duration: tour.duration,
    rides_count: tour.rides.length,
    date: tour.date,
    total_places: tour.rides.reduce((sum, r) => sum + r.places, 0),
    rides: tour.rides,
    drivers: tour.drivers, // ovo je dodato
    selectedDriver: null,
  }));
});
*/

const table_48 = ref([])
watch(() => bookings48.value, (val) => {
  if(val) {
    table_48.value = Object.values(val).map(tour => ({
      tour_id: tour.tour_id,
      from_city: tour.from_city,
      to_city: tour.to_city,
      pickuptime: tour.pickuptime,
      duration: tour.duration,
      rides_count: tour.rides.length,
      date: tour.date,
      total_places: tour.rides.reduce((sum, r) => sum + r.places, 0),
      rides: tour.rides,
      drivers: tour.drivers,
      selectedDriver: null,
    }))
  }
}, { immediate: true })
</script>

<template>
  <div
    class="mt-6 pa-3 w-100 d-flex flex-column align-center"
    v-if="admin.adminView == 'Bookings'"
  >
    <v-card class="w-100 h-100">
      <v-toolbar>
        <template v-slot>
          <v-tabs v-model="admin.tab_bookings" align-tabs="title">
            <v-tab
              v-for="item in admin.items_bookings"
              :key="item"
              :text="item"
              :value="item"
              @click="admin.actions.fetchBookings(item)"
            ></v-tab>
          </v-tabs>
        </template>
      </v-toolbar>

      <v-tabs-window v-model="admin.tab_bookings">
        <v-tabs-window-item
          v-for="item in admin.items_bookings"
          :key="item"
          :value="item"
        >
          <v-card flat>
            <v-card-text>
              <div class="w-100" v-if="admin.tab_bookings == 'Pretraga'">
                <v-container v-if="admin.filteredOrders && admin.filteredOrders.has_orders"
                  
                >
                  <v-data-iterator
                    :items="admin.filteredOrders.orders"
                    :items-per-page="5"
                    v-model:page="admin.page"
                  >
                    <template v-slot:default="{ items }">
                      <v-row dense>
                        <v-col
                          v-for="(item, i) in items"
                          :key="item.raw.item_id"
                          cols="12"
                          sm="6"
                          md="4"
                        >
                          <v-card class="rounded-2xl shadow-md hover:shadow-lg transition-all relative">
                            <v-badge
                              v-if="item.raw.deleted == 1"
                              color="red-darken-3"
                              content="OBRISANO"
                              bordered
                              class="absolute top-2 right-2"
                            ></v-badge>
                            <v-card-title class="text-lg font-semibold">
                              {{ item.raw.from_city }} → {{ item.raw.to_city }}
                            </v-card-title>

                            <v-card-subtitle class="text-sm text-gray-600">
                              Datum: {{admin.formatDate(item.raw.date) }}
                            </v-card-subtitle>

                            <v-card-text class="space-y-2">
                              <div><strong>Kod:</strong> {{ item.raw.code }}</div>
                              <div><strong>Putnici:</strong> {{ item.raw.places }}</div>
                              <div><strong>Cena:</strong> {{ item.raw.price }} €</div>
                              <div><strong>Korisnik:</strong> {{ item.raw.user }}</div>
                            </v-card-text>

                            <v-card-actions>
                              <v-btn color="primary" size="small" @click="admin.showDetails(item.raw)">
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
                        :length="pageCount"
                        total-visible="5"
                      ></v-pagination>
                    </template>
                  </v-data-iterator>

                  <!-- DIALOG to show details -->

                  <v-dialog v-model="admin.manageDialog" fullscreen transition="dialog-bottom-transition" persistent>
                    <v-card>
                      <!-- Header -->
                      <v-toolbar color="indigo-darken-4">
                        <v-btn icon @click="admin.manageDialog = false">
                          <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        <v-toolbar-title>Rezervacija #{{ admin.selected?.code }}</v-toolbar-title>
                        <v-spacer></v-spacer>
                      </v-toolbar>

                      <!-- MAIN CONTENT - BOOKING DETAILS -->

                      <!--  Details  -->
                      <v-card-text class="pa-4 ">
                        <h3 class="text-center">Detalji vožnje</h3>
                        <div class="w-100 h-100 d-flex">
                          <div class="w-50 h-100 pa-3 d-flex flex-column justify-space-evenly">
                            <p><strong>Ruta:</strong> {{ admin.selected?.from_city }} → {{ admin.selected?.to_city }}</p>
                            <p><strong>Adresa polaska:</strong> {{ admin.selected?.pickup }}</p>
                            <p><strong>Adresa dolaska:</strong> {{ admin.selected?.dropoff }}</p>
                            <p><strong>Datum:</strong> {{ admin.formatDate(selected?.date) }}</p>
                            <p><strong>Vreme:</strong> {{ admin.selected?.pickuptime }}</p>
                            <p><strong>Broj mesta:</strong> {{ admin.selected?.places }}</p>
                            <p><strong>Cena:</strong> {{ admin.selected?.price }} €</p>
                            <p><strong>Korisnik:</strong> {{ admin.selected?.user }}</p>
                            <p><strong>Email:</strong> {{ admin.selected?.email }}</p>
                          </div>

                          <!--  ACTIONS - booking managing by admin  -->
                          <!--  Update  -->
                          <div class="w-50 h-100 pa-6 mt-3 d-flex flex-column justify-space-around">
                            <div class="h-75 d-flex flex-column justify-space-evenly">
                              <div>
                                <v-text-field
                                  v-model="admin.changeFromAddress"
                                  prepend-icon="mdi-map-marker"
                                  label="Unesi novu adresu polaska"
                                  clearable 
                                  class="w-75"
                                ></v-text-field>
                                <v-text-field
                                  v-model="admin.changeToAddress"
                                  prepend-icon="mdi-map-marker"
                                  label="Unesi novu adresu dolaska"
                                  clearable 
                                  class="w-75"
                                ></v-text-field>
                                <v-date-input
                                  v-model="admin.changeDate"
                                  label="Unesi novi datum"
                                  clearable 
                                  class="w-75"
                                ></v-date-input>
                                <v-number-input
                                  v-model="admin.changeSeats"
                                  prepend-icon="mdi-numeric"
                                  label="Unesi novi broj mesta"
                                  clearable 
                                  class="w-75"
                                  min="1"
                                  max="7"
                                ></v-number-input>
                              </div>
                              <div class="w-75 d-flex justify-space-around">
                                <v-btn 
                                  variant="elevated" 
                                  color="green-darken-4"
                                  @click="admin.actions.manageBookingItems"
                                >Potvrdi</v-btn>
                                <v-btn color="red-darken-3"
                                  @click="admin.actions.clearManageItems"
                                >Poništi</v-btn>
                              </div>
                            <!--  Voucher sending and Cancel  -->
                            </div>
                            <div class="pa-6 h-25 w-75 d-flex justify-space-evenly align-center">
                              <div class="text-center">
                                <h4>Vidi Vaučer</h4>
                                <v-btn icon="mdi-eye"
                                  color="indigo-darken-3"
                                  :href=" 'http://localhost:8080/' + admin.selected?.voucher "
                                  target="_blank"
                                ></v-btn>
                              </div>
                              <div class="text-center">
                                <h4>Pošalji Vaučer</h4>
                                <v-btn icon="mdi-email-arrow-right"
                                  color="green-darken-3"
                                  @click="admin.actions.resendVoucher"
                                ></v-btn>
                              </div>
                              <div class="text-center" v-if="!admin.selected.deleted">
                                <h4>Obriši ovu vožnju</h4>
                                <v-btn 
                                  icon="mdi-close-thick"
                                  color="red-darken-3"
                                  @click="admin.actions.cancelBookingItem"
                                ></v-btn>
                              </div>
                              <div class="text-center" v-if="admin.selected.deleted">
                                <h4>Aktiviraj vožnju</h4>
                                <v-btn 
                                  icon="mdi-check-all"
                                  color="red-darken-3"
                                  @click="admin.actions.cancelBookingItem"
                                ></v-btn>
                              </div>
                            </div>
                          </div>
                        </div>
                      </v-card-text>

                      <!-- Btn -->
                      <v-card-actions>
                        <v-btn block color="success" @click="admin.manageDialog = false">
                          Zatvori
                        </v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-dialog>

                  <!-- CONFIRM CHANGES DIALOG -->

                  <v-dialog v-model="admin.confirmManage" persistent height="20%" width="70%">
                    <v-card >
                      <v-toolbar color="red-darken-3" class="text-center">
                        <v-toolbar-title>Da li ste sigurni da želite da napravite trajne izmene?</v-toolbar-title>
                      </v-toolbar>
                      <v-card-actions class="d-flex justify-center align-center">
                        <v-btn color="success" @click="admin.actions.confimBookingItemsChange">Potvrdi</v-btn>
                        <v-btn color="red-lighten-1" @click="admin.confirmManage = false">odustani</v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-dialog>

                  <!-- CONFIRM CANCEL DIALOG -->

                  <v-dialog v-model="admin.cancelDialog" persistent height="20%" width="70%">
                    <v-card >
                      <v-toolbar color="red-darken-3" class="text-center">
                        <v-toolbar-title>Da li ste sigurni da želite da obrišete ovu vožnju?</v-toolbar-title>
                      </v-toolbar>
                      <v-card-actions class="d-flex justify-center align-center">
                        <v-btn color="success" @click="admin.actions.confirmCancelBookingItem">Potvrdi</v-btn>
                        <v-btn color="red-lighten-1" @click="admin.cancelDialog = false">odustani</v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-dialog>

                </v-container>
                <!-- ----------- IF NO DATA -------------- -->
                <v-container class="w-50 h-100" v-if="admin.filteredOrders && !admin.filteredOrders.has_orders">
                  <p><b>Nema vožnji po zadatim filterima</b></p>  
                </v-container>
              </div>
              <div class="w-100" v-if="admin.tab_bookings == 'U narednih 24h'">
                <v-card title="Rezervacije u narednih 24h" flat>
                  <template v-slot:text>
                    <v-text-field
                      v-model="admin.in24Search"
                      label="Pretraga"
                      prepend-inner-icon="mdi-magnify"
                      variant="outlined"
                      hide-details
                      single-line
                    ></v-text-field>
                  </template>

                  <v-data-table
                    :headers="admin.headers"
                    :items="table_24"
                    :search="admin.in24Search"
                  >
                    <template v-slot:item.actions="{ item }">
                      <div class="d-flex justify-space-between align-center">
                        <v-select
                          v-if="item.drivers && Array.isArray(item.drivers)"
                          v-model="item.selectedDriver"
                          :items="item.drivers"
                          item-title="name"
                          item-value="id"
                          label="Vozač"
                          dense
                          hide-details
                          style="max-width: 150px"
                          clearable
                          return-object
                        />
                        <v-text-field
                          v-if="item.drivers && !Array.isArray(item.drivers)"
                          label="Vozač dodeljen!"
                          disabled
                        />
                        <v-text-field
                          v-if="!item.drivers"
                          label="Nema dostupnih vozača!"
                          disabled
                        />
                      </div>
                    </template>
                    <template v-slot:item.assign="{ item }">
                      <v-btn
                        color="green-darken-4"
                        size="small"
                        icon="mdi-clipboard-check"
                        :disabled="!item.selectedDriver"
                        @click="
                          admin.actions.assignDriver(
                            item.selectedDriver,
                            item.tour_id,
                            item.rides
                          ),
                          item.selectedDriver = null,
                          item.drivers = true
                        "
                      >
                      </v-btn>
                    </template>
                    <template v-slot:item.details="{ item }">
                      <v-btn
                        color="indigo-darken-4"
                        size="small"
                        icon="mdi-location-enter"
                        @click="admin.actions.openTour(item.rides)"
                      >
                      </v-btn>
                    </template>
                  </v-data-table>
                </v-card>
              </div>
              <div class="w-100" v-if="admin.tab_bookings == 'U narednih 48h'">
                <v-card title="Rezervacije u narednih 48h" flat>
                  <template v-slot:text>
                    <v-text-field
                      v-model="admin.in48Search"
                      label="Pretraga"
                      prepend-inner-icon="mdi-magnify"
                      variant="outlined"
                      hide-details
                      single-line
                    ></v-text-field>
                  </template>

                  <v-data-table
                    :headers="admin.headers"
                    :items="table_48"
                    :search="admin.in48Search"
                  >
                    <template v-slot:item.actions="{ item }">
                      <div class="d-flex justify-space-between align-center">
                        <v-select
                          v-if="item.drivers && Array.isArray(item.drivers)"
                          v-model="item.selectedDriver"
                          :items="item.drivers"
                          item-title="name"
                          item-value="id"
                          label="Vozač"
                          dense
                          hide-details
                          style="max-width: 150px"
                          clearable
                          return-object
                        />
                        <v-text-field
                          v-if="item.drivers && !Array.isArray(item.drivers)"
                          label="Vozač dodeljen!"
                          disabled
                        />
                        <v-text-field
                          v-if="!item.drivers"
                          label="Nema dostupnih vozača!"
                          disabled
                        />
                      </div>
                    </template>
                    <template v-slot:item.assign="{ item }">
                      <v-btn
                        color="green-darken-4"
                        size="small"
                        icon="mdi-clipboard-check"
                        :disabled="!item.selectedDriver"
                        @click="
                          admin.actions.assignDriver(
                            item.selectedDriver,
                            item.tour_id,
                            item.rides
                          ),
                          item.selectedDriver = null,
                          item.drivers = true
                        "
                      >
                      </v-btn>
                    </template>
                    <template v-slot:item.details="{ item }">
                      <v-btn
                        color="indigo-darken-4"
                        size="small"
                        icon="mdi-location-enter"
                        @click="admin.actions.openTour(item.rides)"
                      >
                      </v-btn>
                    </template>
                  </v-data-table>
                </v-card>
              </div>
            </v-card-text>
          </v-card>
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card>
  </div>
</template>
