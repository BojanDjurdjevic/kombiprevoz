<script setup>
import { useUserStore } from '@/stores/user';

    
    const user = useUserStore()

</script>

<template>
    <v-card class="h-100">
      <v-layout class="h-100">
        <v-navigation-drawer expand-on-hover permanent rail >
          <v-list>
            <v-list-item
              :prepend-avatar="user.user.initials"
              :subtitle="user.user.email"
              :title="user.user.name"
            ></v-list-item>
          </v-list>

          <v-divider></v-divider>

          <v-list density="compact" nav>
            <v-list-item
              prepend-icon="mdi-book-open-variant"
              title="Rezervacije"
              value="Rezervacije"
            ></v-list-item>
            <v-list-item
              prepend-icon="mdi-account-multiple"
              title="Korisnici"
              value="Korisnici"
            ></v-list-item>
            <v-list-item
              prepend-icon="mdi-car"
              title="Vožnje"
              value="Vožnje"
            ></v-list-item>
            <v-list-item
              prepend-icon="mdi-star"
              title="Vozači"
              value="Vozači"
            ></v-list-item>
          </v-list>
        </v-navigation-drawer>

        <v-main style="height: 250px"></v-main>
      </v-layout>
    </v-card>
</template>
