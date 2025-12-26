<script setup>
import { useAdminStore } from '@/stores/admin'
import { useSearchStore } from '@/stores/search'
import { computed } from 'vue'
import { VNumberInput } from 'vuetify/labs/VNumberInput'

const props = defineProps({
  mode: {
    type: String,
    default: 'edit' // 'edit' ili 'add'
  }
})

const admin = useAdminStore()
const search = useSearchStore()

const isAddMode = computed(() => props.mode === 'add')

const isDisabled = computed(() => {
  if (isAddMode.value) {
    return !admin.countryFrom || !admin.cityFrom ||
           !admin.countryTo || !admin.cityTo ||
           !admin.changeDeps || !admin.changeTime ||
           !admin.changeDuration || !admin.changeTourSeats ||
           !admin.changePrice
  } else {
    return !admin.changeDeps || !admin.changeTime ||
           !admin.changeDuration || !admin.changeTourSeats ||
           !admin.changePrice
  }
})

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
  <div class="w-100 pa-6 d-flex flex-column justify-space-around">
    
    <!-- ADD MODE FIELDS: Country / City -->
    <div v-if="isAddMode">
      <v-select
        v-model="admin.countryFrom"
        :items="admin.dbCountries"
        label="Država polaska"
        item-title="name"
        item-value="id"
        return-object
        clearable
        v-on:update:model-value="val => search.allCities(val, true)"
      />

      <v-select
        v-model="admin.cityFrom"
        :rules="[search.rules.required]"
        :items="search.availableCities"
        label="Grad polaska"
        item-title="name"
        item-value="id"
        return-object
        clearable
      />

      <v-select
        v-model="admin.countryTo"
        :items="admin.dbCountries"
        label="Država dolaska"
        item-title="name"
        item-value="id"
        return-object
        clearable
        v-on:update:model-value="val => search.allCities(val, false)"
      />

      <v-select
        v-model="admin.cityTo"
        :rules="[search.rules.required]"
        :items="search.availableCitiesTo"
        label="Grad dolaska"
        item-title="name"
        item-value="id"
        return-object
        clearable
      />
    </div>

    <!-- DAYS + TIME -->
    <div class="mt-6">
      <v-select
        v-model="admin.changeDeps"
        class="w-100 mt-5"
        prepend-icon="mdi-calendar-month-outline"
        clearable
        chips
        multiple
        label="Izaberi dane polaska"
        :items="tourDays"
        item-title="day"
        item-value="id"
        return-object
        @click:clear="admin.changeDeps = null"
      />

      <v-text-field
        v-model="admin.changeTime"
        class="w-100 mt-5"
        prepend-icon="mdi-clock-time-three-outline"
        label="Vreme polaska"
        placeholder="hh:mm:ss"
        clearable
        hint="Format: 08:30:00"
        persistent-hint
        :rules="[admin.validateTime]"
      />
    </div>

    <!-- NUMBERS -->
    <div class="mt-6">
      <div class="w-100 d-flex flex-column align-center">
        <h5>Trajanje (sati)</h5>
        <v-number-input
          v-model="admin.changeDuration"
          class="w-75 mt-1"
          control-variant="split"
          :min="1"
          :max="33"
        />
      </div>

      <div class="w-100 d-flex flex-column align-center mt-4">
        <h5>Maksimum putnika</h5>
        <v-number-input
          v-model="admin.changeTourSeats"
          class="w-75 mt-1"
          control-variant="split"
          :min="1"
          :max="8"
        />
      </div>

      <div class="w-100 d-flex flex-column align-center mt-4">
        <h5>Cena (€)</h5>
        <v-number-input
          v-model="admin.changePrice"
          class="w-75 mt-1"
          control-variant="split"
          :min="30"
        />
      </div>
    </div>

    <!-- ACTIONS -->
    <div class="w-100 d-flex justify-space-around mt-6">
      <v-btn
        color="green-darken-4"
        variant="elevated"
        :disabled="isDisabled"
        @click="isAddMode ? admin.actions.addTour() : admin.actions.updateTour()"
      >
        Potvrdi
      </v-btn>

      <v-btn
        color="red-darken-3"
        @click="isAddMode ? admin.actions.clearNewTour() : admin.actions.clearTourEdit()"
      >
        Poništi
      </v-btn>
    </div>
  </div>
</template>
