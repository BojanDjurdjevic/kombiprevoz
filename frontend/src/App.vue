<script setup>
import { RouterLink, RouterView } from 'vue-router'
import Header from './components/Header.vue';
</script>

<template>
  <v-app>
    <Header />
    <v-main>
      
      <v-fade-transition mode="out-in">
        <RouterView />
      </v-fade-transition>
      <!--
      <router-view v-slot="{ HomeView, MyBookings, Destinations,
        CitiesView, CityBook, BookNow, SearchResult }">
        <transition >
          <component :is="SearchResult" />
        </transition>
      </router-view>
      -->
    </v-main>
  </v-app>
</template>

<style scoped>

</style>
