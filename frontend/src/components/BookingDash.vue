<script setup>
import { useAdminStore } from "@/stores/admin";
import { ref, computed, watch } from "vue";

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
                          <v-card class="rounded-2xl shadow-md hover:shadow-lg transition-all">
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

                      <!-- Main Content -->
                      <v-card-text class="pa-4">
                        <p><strong>Ruta:</strong> {{ admin.selected?.from_city }} → {{ admin.selected?.to_city }}</p>
                        <p><strong>Datum:</strong> {{ admin.formatDate(selected?.date) }}</p>
                        <p><strong>Putnici:</strong> {{ admin.selected?.places }}</p>
                        <p><strong>Cena:</strong> {{ admin.selected?.price }} €</p>
                        <p><strong>Korisnik:</strong> {{ admin.selected?.user }}</p>
                        <p><strong>Email:</strong> {{ admin.selected?.email }}</p>
                      </v-card-text>

                      <!-- Btn -->
                      <v-card-actions>
                        <v-btn block color="success" @click="admin.manageDialog = false">
                          Zatvori
                        </v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-dialog>
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
