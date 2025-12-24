<script setup>
    import { ref, onMounted } from 'vue';
    import { useDestStore } from '@/stores/destinations';
    const dest = useDestStore()

    onMounted(() => {
        let country = JSON.parse(localStorage.getItem('country'))
        dest.country = country.name
        dest.selectedCountryID = country.id
        console.log('CitiesView iz lokala: ', country)
        dest.actions.fetchCities()
    })
</script>
<template>
    <v-container fluid class="py-6">

    <!-- Breadcrumbs -->
    <v-breadcrumbs class="mb-4">
      <v-breadcrumbs-item to="/">Početna</v-breadcrumbs-item>
      <v-breadcrumbs-item to="/destinacije">Destinacije</v-breadcrumbs-item>
      <v-breadcrumbs-item>{{ dest.country }}</v-breadcrumbs-item>
    </v-breadcrumbs>

    <!-- Title -->
    <v-row justify="center" class="mb-6">
      <v-col cols="12" class="text-center">
        <h1>Gradovi u {{ dest.country }}</h1>
        <v-divider></v-divider>
      </v-col>
    </v-row>

    <!-- City Cards -->
    <v-row class="d-flex flex-wrap justify-center">
      <v-col
        v-for="n in dest.cities"
        :key="n.id"
        cols="12"
        sm="6"
        md="4"
        lg="3"
        class="d-flex justify-center mb-6"
        
      >
        <v-card
          class="rounded-xl hover-card"
          elevation="8"
          to="grad"
          style="width: 100%; max-width: 280px; cursor: pointer;"
          @click="dest.takeCity(n)"
        >
          <v-img
            :src="dest.getCityPrimaryImage(n)"
            aspect-ratio="1.2"
            cover
            alt="Kombi prevoz do {{ n.name }}, {{ dest.country }}"
            lazy-src="https://cdn.vuetifyjs.com/images/cards/docks.jpg"
          >
            <v-card-title class="text-center text-white">
              {{ n.name }}
            </v-card-title>
          </v-img>
        </v-card>
      </v-col>
    </v-row>
  </v-container>

    <!--
    <v-container class="text-center">
       <h1>Hi from {{ dest.country }} </h1> 
    </v-container>
    <v-container>
        <v-row class="ma-9 w-100 pa-6">
            <v-col
                v-for="n in dest.cities"
                :key="n"
                cols="12"
                md="6"
                lg="3"
            >
                <v-card height="18rem" width="18rem" elevation="9" to="grad" @click="dest.takeCity(n)" v-if="!n.deleted_city">
                    <v-img
                        class="align-center text-white"
                        height="100%"
                        :src="dest.getCityPrimaryImage(n)"
                        cover
                    >
                        <v-card-title class="text-center"> {{ n.name }} </v-card-title>
                    </v-img> 
                </v-card>
            </v-col>
            
        </v-row>
    </v-container>
    -->
</template>

<style scoped>
.v-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.v-card:hover {
  transform: translateY(-4px);
  cursor: pointer;
}
</style>