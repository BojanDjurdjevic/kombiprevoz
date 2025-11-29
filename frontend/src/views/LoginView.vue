<script setup>
import { ref } from 'vue';
import { useUserStore } from '@/stores/user';

const user = useUserStore()

const logUser = ref({
    users: {
        email: '',
        pass: '',
        remember: false,
        login: true 
    }
})

function logSubmit() {
    if(logUser.value.users.email && logUser.value.users.pass) {
        logUser.value.users.email = logUser.value.users.email.trim()
        logUser.value.users.pass = logUser.value.users.pass.trim()
        user.actions.handleLogin(logUser.value)
    } else {
        return
    }
}

function clsData() {
    logUser.value.users = {
        email: null,
        pass: null,
        remember: false,
        login: true
    }
}

</script>
<template>
    <v-container class="text-center">
        <h1>Uloguj se</h1>
        <v-divider></v-divider>
    </v-container>
    <v-container class="d-flex flex-column justify-center align-center">
        <v-card class="w-75 h-100 pa-12">
            <v-form @submit.prevent="logSubmit"
                class="d-flex flex-column align-center"
            >
                <v-text-field
                    v-model="logUser.users.email"
                    class="w-75"
                    prepend-icon="mdi-email"
                    type="email"
                    hint="Unesite email"
                    :rules="[user.rules.required, user.rules.email]"
                    label="Email"
                    clearable
                ></v-text-field>
                <v-text-field
                    v-model="logUser.users.pass"
                    class="w-75"
                    prepend-icon="mdi-key"
                    label="Lozinka"
                    hint="Unesite lozinku"
                    :rules="[user.rules.required]"
                    type="password"
                    clearable
                ></v-text-field>
                <v-checkbox
                    v-model="logUser.users.remember"
                    label="Zapamti me"
                />
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
        <v-card class="w-75 h-20 pa-12 ma-3">
            <div class="d-flex justify-space-between align-center">
                <p>Nemate nalog: <v-btn
                    variant="elevated"
                    color="indigo-darken-4"
                    class="ma-6" 
                    to="/registracija"   
                >Registruj se</v-btn> </p>
                <p>Zaboraili ste lozinku? <v-btn
                    variant="elevated"
                    color="indigo-darken-4"
                    class="ma-6"    
                    to="/request-password-reset"
                >Reset Lozinke</v-btn> </p>
            </div>
        </v-card>
    </v-container>

</template>