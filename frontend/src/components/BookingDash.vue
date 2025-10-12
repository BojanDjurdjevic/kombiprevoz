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
    <v-card class="w-100">
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
                Pretraga
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
