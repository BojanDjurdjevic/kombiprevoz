<script setup>
    import router from '@/router';
    import { useUserStore } from '@/stores/user';
    //import { ref } from 'vue';

    const user = useUserStore()

    /*
    function handleReset() {
        user.profile.users.name = user.user.name
        user.profile.users.email = user.user.email
        user.profile.users.address = user.user.address
        user.profile.users.phone = user.user.phone
        user.profile.users.city = user.user.city
    }
    function handleClear() {
        user.profile.users.name = '',
        user.profile.users.email = '',
        user.profile.users.address = '',
        user.profile.users.phone = '',
        user.profile.users.city = ''
    }
    function submit() {
        if(user.profile.users.name && user.profile.users.email && user.profile.users.address && user.profile.users.phone && user.profile.users.city) {
            user.profile.users.name = user.profile.users.name.trim()
            user.profile.users.email = user.profile.users.email.trim()
            user.profile.users.address = user.profile.users.address.trim()
            user.profile.users.phone = user.profile.users.phone.trim() 
            user.profile.users.id = user.user.id */
            /*
            console.dir(typeof(user.profile.users.city))
            if(String(user.profile.users.city )) {
                user.profile.users.city = user.profile.user.city.trim()
            } */ /*
            if(user.actions.checkSession()) {
                user.actions.profileUpdate(user.profile)
            } else {
                router.push('/login')
            }
            user.profileDialog = false
        } else {
            return
        }
    } */

   function handleReset() {
        Object.assign(user.profile.users, {
            name: user.user.name,
            email: user.user.email,
            address: user.user.address,
            phone: user.user.phone,
            city: user.user.city
        })
    }

        function handleClear() {
        Object.assign(user.profile.users, {
            name: '',
            email: '',
            address: '',
            phone: '',
            city: ''
        })
    }

        function submit() {
        const u = user.profile.users

        if (!u.name || !u.email || !u.address || !u.phone || !u.city) return

        Object.keys(u).forEach(k => {
            if (typeof u[k] === 'string') u[k] = u[k].trim()
        })

        u.id = user.user.id

        if (user.actions.checkSession()) {
            user.actions.profileUpdate(user.profile)
            user.profileDialog = false
        } else {
            router.push('/login')
        }
    }
</script>
<template>

  <v-card class="pa-6" max-width="800"> <!-- ⬅ CHANGED -->
    
    <!-- HEADER -->
    <v-card-title class="d-flex justify-space-between align-center">
      <h2>Izmeni podatke</h2>

      <v-btn
        icon="mdi-close"
        variant="text"
        color="error"
        @click="user.profileDialog = false"
      />
    </v-card-title>

    <v-divider class="mb-4" />

    <!-- FORM -->
    <v-form @submit.prevent="submit">
      <v-row dense> <!-- ⬅ CHANGED -->

        <!-- Ime -->
        <v-col cols="12" md="6">
          <v-text-field
            v-model="user.profile.users.name"
            label="Ime"
            prepend-icon="mdi-account"
            :rules="[user.rules.required]"
            clearable
          />
        </v-col>

        <!-- Email -->
        <v-col cols="12" md="6">
          <v-text-field
            v-model="user.profile.users.email"
            label="Email"
            prepend-icon="mdi-email"
            :rules="[user.rules.required, user.rules.email]"
            clearable
          />
        </v-col>

        <!-- Telefon -->
        <v-col cols="12" md="6">
          <v-text-field
            v-model="user.profile.users.phone"
            label="Telefon"
            prepend-icon="mdi-phone"
            :rules="[user.rules.required]"
            clearable
          />
        </v-col>

        <!-- Grad -->
        <v-col cols="12" md="6">
          <v-text-field
            v-model="user.profile.users.city"
            label="Grad"
            prepend-icon="mdi-city"
            :rules="[user.rules.required]"
            clearable
          />
        </v-col>

        <!-- Adresa -->
        <v-col cols="12">
          <v-text-field
            v-model="user.profile.users.address"
            label="Adresa"
            prepend-icon="mdi-map-marker"
            :rules="[user.rules.required]"
            clearable
          />
        </v-col>
      </v-row>

      <!-- ACTIONS -->
      <v-divider class="my-4" />

      <v-card-actions class="flex-column flex-sm-row justify-space-between"> <!-- ⬅ CHANGED -->

        <v-btn
          type="submit"
          color="success"
          prepend-icon="mdi-check-circle"
        >
          Potvrdi
        </v-btn>

        <v-btn
          color="indigo-darken-4"
          variant="outlined"
          @click="handleReset"
        >
          Vrati na staro
        </v-btn>

        <v-btn
          color="error"
          variant="outlined"
          @click="handleClear"
        >
          Obriši sve
        </v-btn>

      </v-card-actions>
    </v-form>
  </v-card>

    <!--
    <v-sheet color="indigo-darken-4" height="80%">
    <v-container class="text-center" width="80%">
        <h1>Izmeni podatke</h1>
        <v-divider></v-divider>
    </v-container>
    <v-container class="d-flex justify-center align-center" height="80%">
        <v-card class="pa-6 d-flex flex-column justify-center align-center h-100 w-75"
        >
            <v-card-title primary-title>
                <div>
                    <v-btn @click="user.profileDialog = false"
                        variant="elevated"
                        color="error"
                        prepend-icon="mdi-close-circle"
                    >
                    Zatvori
                    </v-btn>
                </div>
            </v-card-title>
            <v-form @submit.prevent="submit"
                class="w-75 h-75"
                elevation-9
            >
                <v-text-field
                v-model="user.profile.users.name"
                prepend-icon="mdi-text-account"
                type="name"
                hint="Unesite Ime"
                :rules="[user.rules.required]"
                label="Ime"
                clearable
                ></v-text-field>

                <v-text-field
                v-model="user.profile.users.email"
                prepend-icon="mdi-email"
                hint="Unesite email"
                type="email"
                label="Email"
                :rules="[user.rules.required, user.rules.email]"
                clearable
                ></v-text-field>

                <v-text-field
                v-model="user.profile.users.phone"
                :rules="[user.rules.required]"
                prepend-icon="mdi-phone-in-talk"
                label="Broj telefona"
                clearable
                ></v-text-field>

                <v-text-field
                v-model="user.profile.users.city"
                :rules="[user.rules.required]"
                prepend-icon="mdi-home-city"
                label="Grad"
                clearable
                ></v-text-field>

                <v-text-field
                v-model="user.profile.users.address"
                :rules="[user.rules.required]"
                prepend-icon="mdi-map-marker"
                label="Adresa"
                clearable
                
                ></v-text-field>

                <v-card-actions class="w-100 ">
                    <div class="w-100 pa-3 d-flex justify-space-between">
                        <v-btn
                        class="me-4"
                        type="submit"
                        color="success"
                        variant="elevated"
                        prepend-icon="mdi-checkbox-marked-circle"
                        >
                        Potvrdi
                        </v-btn>

                        <v-btn @click="handleReset"
                            variant="elevated"
                            color="indigo-darken-4"
                        >
                        Vrati na staro
                        </v-btn>
                        <v-btn @click="handleClear"
                            variant="elevated"
                            color="error"
                        >
                        Obriši sve 
                        </v-btn>
                    </div>
                    
                </v-card-actions>
                  
            </v-form>
            
        </v-card>
    </v-container>
    <v-divider></v-divider>
    </v-sheet>
    -->
</template>