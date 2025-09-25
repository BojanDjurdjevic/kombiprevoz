<script setup>
    import { ref } from 'vue'
    import { VDateInput } from 'vuetify/labs/VDateInput'
    import { VNumberInput } from 'vuetify/labs/VNumberInput'
    import { useUserStore } from '@/stores/user';
import { useAdminStore } from '@/stores/admin';
    const user = useUserStore()
    const admin = useAdminStore()


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
              @click="admin.adminView = 'Bookings'"
            ></v-list-item>
            <v-list-item
              prepend-icon="mdi-account-multiple"
              title="Korisnici"
              value="Korisnici"
              @click="admin.adminView = 'Users'"
            ></v-list-item>
            <v-list-item
              prepend-icon="mdi-car"
              title="Vožnje"
              value="Vožnje"
              @click="admin.adminView = 'Tours'"
            ></v-list-item>
            <v-list-item
              prepend-icon="mdi-star"
              title="Vozači"
              value="Vozači"
              @click="admin.adminView = 'Drivers'"
            ></v-list-item>
          </v-list>
        </v-navigation-drawer>

        <v-main class="d-flex "
            
        >
        
        <v-container class="h-100 w-75 pa-3 d-flex flex-column justify-space-between align-center">
            <div class="w-100 text-center">
                <h1 class="mt-3"> {{ admin.adminView }} </h1>
                <v-divider class="w-100"></v-divider>
            </div>
            
        </v-container>
        <v-divider vertical></v-divider>
        <v-container class="w-25 pa-3 d-flex flex-column justify-space-between align-center">
            <div class="w-100 text-center">
                <h1 class="mt-3"> Filters </h1>
                <v-divider class="w-100"></v-divider>
            </div>
            <div class="w-100 d-flex flex-column align-center">
              <v-date-input

              >

              </v-date-input>
            </div>
        </v-container>
        
        </v-main>

      </v-layout>
    </v-card>
</template>