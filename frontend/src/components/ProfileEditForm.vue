<script setup>
    import router from '@/router';
import { useUserStore } from '@/stores/user';
    //import { ref } from 'vue';

    const user = useUserStore()

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
            user.profile.users.id = user.user.id
            /*
            console.dir(typeof(user.profile.users.city))
            if(String(user.profile.users.city )) {
                user.profile.users.city = user.profile.user.city.trim()
            } */
            if(user.actions.checkSession()) {
                user.actions.profileUpdate(user.profile)
            } else {
                router.push('/login')
            }
            user.profileDialog = false
        } else {
            return
        }
    }
</script>
<template>
    <v-sheet color="indigo-darken-4" height="80%">
    <v-container class="text-center" width="80%">
        <h1>Izmeni podatke</h1>
        <v-divider></v-divider>
    </v-container>
    <v-container class="d-flex justify-center align-center" height="80%">
        <v-card class="pa-6 d-flex justify-center align-center h-100 w-75"
        >
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

                <v-card-actions class="w-100">
                    <div class="w-100 pa-3 d-flex justify-space-between">
                        <v-btn
                        class="me-4"
                        type="submit"
                        color="success"
                        variant="elevated"
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
</template>