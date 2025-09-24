<script setup>
    import { ref } from 'vue'
    import { useUserStore } from '@/stores/user';
    const user = useUserStore()

    const adminView = ref('bookings')

</script>

<template>
    <v-card class="h-100">
      <v-layout class="h-100">
        <v-navigation-drawer expand-on-hover permanent rail >
          <v-list>
            <v-list-item
              
              :subtitle="user.user.email"
              :title="user.user.name"
              
            >
                <template v-slot:prepend>
                    <v-avatar color="green-darken-3" size="38">
                        <span class="text-white font-weight-bold">
                        {{ user.user.initials }}
                        </span>
                    </v-avatar>
                </template>
            </v-list-item>
          </v-list>

          <v-divider></v-divider>

          <v-list density="compact" nav>
            <v-list-item
              prepend-icon="mdi-book-open-variant"
              title="Rezervacije"
              value="Rezervacije"
              @click="adminView = 'bookings'"
            ></v-list-item>
            <v-list-item
              prepend-icon="mdi-account-multiple"
              title="Korisnici"
              value="Korisnici"
              @click="adminView = 'users'"
            ></v-list-item>
            <v-list-item
              prepend-icon="mdi-car"
              title="Vožnje"
              value="Vožnje"
              @click="adminView = 'tours'"
            ></v-list-item>
            <v-list-item
              prepend-icon="mdi-star"
              title="Vozači"
              value="Vozači"
              @click="adminView = 'drivers'"
            ></v-list-item>
          </v-list>
        </v-navigation-drawer>

        <v-main class="d-flex "
            
        >
        
        <v-container class="h-100 w-75 pa-3 d-flex flex-column justify-space-between align-center">
            <div class="w-100 text-center">
                <h1 class="mt-3"> {{ adminView }} </h1>
                <v-divider class="w-100"></v-divider>
            </div>
            
        </v-container>
        <v-divider vertical></v-divider>
        <v-container class="w-25 pa-3 d-flex flex-column justify-space-between align-center">
            <div class="w-100 text-center">
                <h1 class="mt-3"> Filters </h1>
                <v-divider class="w-100"></v-divider>
            </div>
        </v-container>
        
        </v-main>

      </v-layout>
    </v-card>
</template>