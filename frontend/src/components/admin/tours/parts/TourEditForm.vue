<script setup>
import { useAdminStore } from '@/stores/admin'
import { computed } from 'vue'
import { VNumberInput } from 'vuetify/labs/VNumberInput'

const admin = useAdminStore()
/*
const props = defineProps({
  tourDays: {
    type: Array,
    required: true
  },
  validateTime: {
    type: Function,
    required: true
  },

  deps: Array,
  time: String,
  duration: Number,
  seats: Number,
  price: Number
})

const emit = defineEmits([
  'update:deps',
  'update:time',
  'update:duration',
  'update:seats',
  'update:price',
  'submit',
  'cancel'
])

const depsModel = computed({
  get: () => props.deps,
  set: v => emit('update:deps', v)
})

const timeModel = computed({
  get: () => props.time,
  set: v => emit('update:time', v)
})

const durationModel = computed({
  get: () => props.duration,
  set: v => emit('update:duration', v)
})

const seatsModel = computed({
  get: () => props.seats,
  set: v => emit('update:seats', v)
})

const priceModel = computed({
  get: () => props.price,
  set: v => emit('update:price', v)
})
*/
const isDisabled = computed(() =>
  !admin.changeDeps || !admin.changeTime || !admin.changeDuration || !admin.changeTourSeats || !admin.changePrice
)


</script>

<template>
  <div class="w-100 pa-6 d-flex flex-column justify-space-around">
    <!-- DAYS + TIME -->
    <div>
      <v-select
        v-model="admin.changeDeps"
        class="w-100 mt-5"
        prepend-icon="mdi-calendar-month-outline"
        clearable
        chips
        multiple
        label="Izmeni dane polaska"
        :items="tourDays"
        item-title="day"
        item-value="id"
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
        @click="admin.actions.updateTour"
      >
        Potvrdi
      </v-btn>

      <v-btn
        color="red-darken-3"
        @click="admin.actions.clearTourEdit"
      >
        Poništi
      </v-btn>
    </div>
  </div>
</template>
