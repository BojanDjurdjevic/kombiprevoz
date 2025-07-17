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

const rules = {
    required: (value) => !!value || "Obavezno polje.",
    counter: (value) => value.length <= 21 || "Maksimum 21 karakter",
    email: (value) => {
        const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        
        return pattern.test(value) || 'Neadekvatan e-mail.'
    },
    password: (value) => {
        const pattern = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}$/

        return pattern.test(value) || 'Neadekvatna Lozinka. '
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
                    v-model="logUser.users.email"
                    class="w-75"
                    prepend-icon="mdi-email"
                    type="email"
                    hint="Unesite email"
                    :rules="[rules.required, rules.email]"
                    label="Email"
                    clearable
                ></v-text-field>
                <v-text-field
                    v-model="logUser.users.pass"
                    class="w-75"
                    prepend-icon="mdi-key"
                    label="Lozinka"
                    hint="Unesite lozinku"
                    :rules="[rules.required, rules.password]"
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
    </v-container>

</template>