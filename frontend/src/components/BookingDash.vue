<script setup>
import { useAdminStore } from "@/stores/admin";
import { ref, computed } from "vue";

const admin = useAdminStore();
/*
const bookings24 = computed(() => admin.in24 || []);
const bookings48 = computed(() => admin.in48 || []);
*/
const bookings24 = computed(() => Object.values(admin.in24?.orders || {}));
const bookings48 = computed(() => Object.values(admin.in48?.orders || {}));

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
  }));
});

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
  }));
});
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
                        :items="admin.in24.drivers"
                        :item-title="admin.in24.drivers.name"
                        :item-value="admin.in24.drivers.id"
                        label="Vozač"
                        dense
                        hide-details
                        style="max-width: 150px"
                        return-object
                      />
                      </div>
                    </template>
                    <template v-slot:item.assign="{ item }">
                      <v-btn
                        color="green-darken-4"
                        size="small"
                        icon="mdi-clipboard-check"
                        @click="admin.actions.assignDriver(item.tour_id)"
                      >
                        
                      </v-btn>
                    </template>
                    <template v-slot:item.details="{ item }">
                      <v-btn
                        color="indigo-darken-4"
                        size="small"
                        icon="mdi-location-enter"
                        @click="admin.actions.openTour(item)"
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
                        :items="admin.drivers_48"
                        item-title="name"
                        item-value="id"
                        label="Vozač"
                        dense
                        hide-details
                        style="max-width: 150px"
                        return-object
                      />
                      </div>
                    </template>
                    <template v-slot:item.assign="{ item }">
                      <v-btn
                        color="green-darken-4"
                        size="small"
                        icon="mdi-clipboard-check"
                        @click="admin.actions.assignDriver(item.tour_id)"
                      >
                        
                      </v-btn>
                    </template>
                    <template v-slot:item.details="{ item }">
                      <v-btn
                        color="indigo-darken-4"
                        size="small"
                        icon="mdi-location-enter"
                        @click="admin.actions.openTour(item)"
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
