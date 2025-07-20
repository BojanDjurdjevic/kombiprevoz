<script setup>
import { useUserStore } from '@/stores/user';
import { ref } from 'vue';

const user = useUserStore()

const profile = ref({
    user: true,
    users: {
        name: user.user.name,
        email: user.user.email,
        city: user.user.city,
        address: user.user.address,
        phone: user.user.phone
    },
    updateProfile: true
})

const newPass = ref({
    updatePass: true,
    user: true,
    users: {
        id: user.user.id,
        pass: ''
    },
    new_pass: {
        password: '', 
        confirmation_pass: ''
    }
})

</script>

<template>
    <v-container>
        <h1 class="text-center">Moj Profil</h1>
        <hr>
    </v-container>
    <v-container class="d-flex justify-center">
        <v-card class="w-75 h-75">
            <v-card-title primary-title>
                <h2 class="text-center">Lični podaci</h2>
            </v-card-title>
            <v-card-text class="pa-9">
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Ime</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.name }}</p>
                </div>
                <hr>
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Email</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.email }}</p>
                </div>
                <hr>
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Grad</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.city }}</p>
                </div>
                <hr>
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Adresa</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.address }}</p>
                </div>
                <hr>
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Telefon</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.phone }}</p>
                </div>
            </v-card-text>
            <v-card-actions class="d-flex justify-center">
                <v-btn 
                    color="indigo-darken-4"
                    variant="elevated"
                    mb-5
                    height="3rem"
                >
                <v-btn 
                    icon="mdi-pencil-circle"
                    variant="plain"
                ></v-btn>
                Uredi podatke</v-btn>
            </v-card-actions>
        </v-card>
    </v-container>
    <v-spacer></v-spacer>
    <v-container class="d-flex justify-center w-100">
        <v-card class="w-75 h-75 pa-12">
            <v-card-title primary-title>
                <h2 class="text-center">Promena Lozinke</h2>
            </v-card-title>
            <v-spacer></v-spacer>
            <v-card-text>
                <div class="w-100 d-flex justify-center">
                    <v-text-field
                        v-model="newPass.users.pass"
                        class="w-75 text-center"
                        prepend-icon="mdi-key"
                        label="Trenutna Lozinka"
                        hint="Unesite trenutnu lozinku"
                        :rules="[user.rules.required, user.rules.password]"
                        type="password"
                        clearable
                    ></v-text-field>
                    <v-text-field
                        v-model="newPass.new_pass.password"
                        class="w-75"
                        prepend-icon="mdi-key"
                        label="Nova Lozinka"
                        hint="Unesite novu lozinku"
                        :rules="[user.rules.required, user.rules.password]"
                        type="password"
                        clearable
                    ></v-text-field>
                    <v-text-field
                        v-model="newPass.new_pass.confirmation_pass"
                        class="w-75"
                        prepend-icon="mdi-key"
                        label="Potvrda Nove Lozinka"
                        hint="Unesite potvrdu lozinke"
                        :rules="[user.rules.required, user.rules.password]"
                        type="password"
                        clearable
                    ></v-text-field>
                </div>
            </v-card-text>
            <v-card-actions class="d-flex justify-center">
                <v-btn 
                    color="indigo-darken-4"
                    variant="elevated"
                    mb-1
                    height="3rem"
                    min-width="12rem"
                    prepend-icon="mdi-pencil-circle"
                >
                
                Potvrdi</v-btn>
            </v-card-actions>
        </v-card>
    </v-container>
</template>