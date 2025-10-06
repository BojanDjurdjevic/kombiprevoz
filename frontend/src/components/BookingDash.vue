<script setup>
import { useAdminStore } from "@/stores/admin";
import { ref, computed } from "vue";

const admin = useAdminStore();

const bookings24 = computed(() => admin.in24 || []);
const bookings48 = computed(() => admin.in48 || []);

const table_24 = computed(() => Object.values(admin.in24?.orders || {}) )
const table_48 = computed(() => Object.values(admin.in48?.orders || {}) )

/*
[
  { 
    from_city: bookings24.value.orders.from_city,
    to_city: bookings24.value.orders.to_city,
    pickuptime: bookings24.value.orders.pickuptime,
    rides: bookings24.value.orders.rides.length, 
  }
]*/
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
                  ></v-data-table>
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
                  ></v-data-table>
                </v-card>
              </div>
            </v-card-text>
          </v-card>
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card>
  </div>
</template>
