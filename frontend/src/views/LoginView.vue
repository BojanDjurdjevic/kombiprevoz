<script setup>
import { ref } from 'vue';
import { useUserStore } from '@/stores/user';

const logUser = ref({
    email: '',
    password: ''
})

function logSubmit() {
    alert('Email: ' + logUser.value.email + "\n" + 'Pass: ' + logUser.value.password)
}

function clsData() {
    logUser.value = {
        email: null,
        password: null
    }
}

</script>
<template>
    <v-container class="text-center">
        <h1>Uloguj se</h1>
        <v-divider></v-divider>
    </v-container>
    <v-container class="d-flex justify-center align-center">
        <v-card class="w-75 h-100 pa-12">
            <v-form @submit.prevent="logSubmit"
                class="d-flex flex-column align-center"
            >
                <v-text-field
                    v-model="logUser.email"
                    class="w-75"
                    prepend-icon="mdi-email"
                    type="email"
                    hint="Unesite email"
                    label="Email"
                    clearable
                ></v-text-field>
                <v-text-field
                    v-model="logUser.password"
                    class="w-75"
                    prepend-icon="mdi-key"
                    label="Lozinka"
                    hint="Unesite lozinku"
                    type="password"
                    clearable
                ></v-text-field>
                <div>
                    <v-btn
                        variant="elevated"
                        color="indigo-darken-4"
                        class="ma-6"
                        type="submit"
                    >Potvrdi</v-btn>
                    <v-btn
                        variant="elevated"
                        @click="clsData"
                    >Obriši</v-btn>
                </div>
                
            </v-form>
        </v-card>
    </v-container>

</template>