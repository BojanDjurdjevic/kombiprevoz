<script setup>
    import api from '@/api';
import { useUserStore } from '@/stores/user';
    import { ref } from 'vue';

    const user = useUserStore()
    const request = ref({
        users: {
            email: null
        },
        resetPass: null
    })
    function reqSubmit() {
        
        if(request.value.users.email) {
            request.value.users.email = request.value.users.email.trim()
            request.value.resetPass = true
            user.actions.requestPassReset(request.value)
        } else {
            user.errorMsg = "Neispravno unet Email! Molimo Vas da ispravno unesete email sa kojim je povezan Vaš nalog."
            setTimeout(() => {
                user.errorMsg = null
                return
            }, 6000)
        }
    }
    function clsData() {
        request.value.users = {
            email: null
        }
    }
</script>

<template>
    <v-container class="text-center">
        <h1>Unesite Vaš email, povezan sa vašim nalogom</h1>
        <v-divider></v-divider>
    </v-container>
    <v-container class="d-flex flex-column justify-center align-center">
        <v-card class="w-75 h-100 pa-12">
            <v-form @submit.prevent="reqSubmit"
                class="d-flex flex-column align-center"
            >
                <v-text-field
                    v-model="request.users.email"
                    class="w-75"
                    prepend-icon="mdi-email"
                    type="email"
                    hint="Unesite email"
                    :rules="[user.rules.required, user.rules.email]"
                    label="Email"
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