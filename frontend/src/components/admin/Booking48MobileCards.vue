<script setup>
    import { ref, computed, watch } from 'vue'
    import { useAdminStore } from '@/stores/admin';


    const admin = useAdminStore()

    const bookings48 = computed(() => Object.values(admin.in48?.orders || {}));
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
        seats: tour.seats,
        total_places: tour.rides.reduce((sum, r) => sum + r.places, 0),
        rides: tour.rides,
        drivers: tour.drivers,
        selectedDriver: null,
        }))
    }
    }, { immediate: true })
</script>

<template>
    <v-container fluid class="pa-0">
    <v-card
      v-for="item in table_48"
      :key="item.tour_id"
      class="mb-3"
      variant="outlined"
    >
      <v-card-title class="d-flex justify-space-between">
        <div>
          <strong>{{ item.from_city }}</strong>
          →
          <strong>{{ item.to_city }}</strong>
        </div>

        <v-chip
          size="small"
          :color="item.total_places >= item.seats ? 'red' : 'green'"
        >
          {{ item.total_places }}/{{ item.seats }}
        </v-chip>
      </v-card-title>

      <v-card-subtitle>
        {{ item.date }}
      </v-card-subtitle>

      <v-divider />

      <v-card-actions class="justify-space-evenly">
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
        </div>
        <v-btn
          size="small"
          variant="tonal"
          @click="console.log('in24 Objekat: ', item)"
        >
          Detalji
        </v-btn>
      </v-card-actions>
    </v-card>
    <v-card v-if="table_48.length == 0">
        <p><b>Nema rezervacija za traženi datum.</b></p>
    </v-card>
  </v-container>
</template>