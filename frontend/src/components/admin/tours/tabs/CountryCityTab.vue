<script setup>
import { useAdminStore } from '@/stores/admin';
import CountryCard from '../parts/CountryCard.vue';
import CityCard from '../parts/CityCard.vue';
import CountryDialog from '../parts/CountryDialog.vue';
import CityDialog from '../parts/CityDialog.vue';

    const admin = useAdminStore()
</script>

<template>
  <v-container  class="w-100">
    <v-row dense>
      <!-- Izbor države -->
      <v-col cols="12" md="6" class="mb-4">
        <v-autocomplete
          v-model="admin.myCountry"
          class="w-100"
          label="Izaberi postojeću državu"
          clearable
          :items="admin.dbCountries"
          item-title="name"
          item-value="id"
          return-object
          @update:model-value="admin.actions.searchByCountry"
        ></v-autocomplete>

        <CountryCard v-if="admin.myCountry" :country="admin.myCountry" @edit="admin.countryDialog = true"/>
      </v-col>

      <!-- Gradovi -->
      <v-col cols="12" md="6">
        <div v-if="admin.citiesByCountry && admin.myCountry">
          <h3 class="text-center mb-4">Gradovi države: {{ admin.myCountry?.name }}</h3>

          <v-row dense>
            <v-col
              v-for="c in admin.citiesByCountry.cities"
              :key="c"
              cols="12"
              sm="6"
            >
              <CityCard :city="c" @edit="admin.openCityDialog(c)"/>
            </v-col>
          </v-row>
        </div>
      </v-col>
    </v-row>

    <!-- Dialogi -->
    <CountryDialog v-model:visible="admin.countryDialog" :country="admin.myCountry"/>
    <CityDialog v-model:visible="admin.cityDialog" :city="admin.myCity"/>
  </v-container>
</template>
