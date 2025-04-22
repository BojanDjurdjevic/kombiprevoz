<script setup>
  import { ref } from 'vue'
  import { useUserStore } from '@/stores/user';
  const user = useUserStore()
</script>

<template>
    <v-container
          v-if="user.user"
          width="10%"
          fluid
    >
          <v-row justify="center">
            <v-menu min-width="15rem" >
              <template v-slot:activator="{ props }">
                <v-btn
                  icon
                  v-bind="props"
                >
                  <v-avatar
                    color="green-darken-3"
                    size="large"
                  >
                    <span class="text-h5">{{ user.user.initials }}</span>
                  </v-avatar>
                </v-btn>
              </template>
              <v-card>
                <v-card-text>
                  <div class="mx-auto text-center">
                    <v-avatar
                      color="green-darken-3"
                    >
                      <span class="text-h5">{{ user.user.initials }}</span>
                    </v-avatar>
                    <h3>{{ user.user.fullName }}</h3>
                    <p class="text-caption mt-1">
                      {{ user.user.email }}
                    </p>
                    <v-divider class="my-3"></v-divider>
                    <v-btn
                      variant="text"
                      rounded
                    >
                      Uredi Profil
                    </v-btn>
                    <v-divider class="my-3"></v-divider>
                    <v-btn
                      variant="text"
                      rounded
                    >
                      Moje vožnje
                    </v-btn>
                    <v-divider class="my-3"></v-divider>
                    <v-btn
                      variant="text"
                      rounded
                    >
                      Izloguj se
                    </v-btn>
                  </div>
                </v-card-text>
              </v-card>
            </v-menu>
          </v-row>
    </v-container>

    <v-container
          v-else
          width="10%"
          fluid
    >
          <v-row justify="center">
            <v-menu min-width="15rem">
              <template v-slot:activator="{ props }">
                <v-btn
                  icon="mdi-dots-vertical"
                  v-bind="props"
                >
                  
                </v-btn>
              </template>
              <v-card>
                <v-card-text>
                  <div class="mx-auto text-center">
                    
                    <v-divider class="my-3"></v-divider>
                    <v-btn
                      variant="text"
                      rounded
                    >
                      Uloguj se
                    </v-btn>
                    <v-divider class="my-3"></v-divider>
                    <v-btn
                      variant="text"
                      rounded
                      :to="{name: 'signin'}"
                    >
                      Registruj se
                    </v-btn>
                    <v-divider class="my-3"></v-divider>
                    
                  </div>
                </v-card-text>
              </v-card>
            </v-menu>
          </v-row>
    </v-container>
</template>