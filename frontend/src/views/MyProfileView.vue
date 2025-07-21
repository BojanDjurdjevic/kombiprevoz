<script setup>
import router from '@/router';
import ProfileEditForm from '../components/ProfileEditForm.vue';
import { useUserStore } from '@/stores/user';
import { ref } from 'vue';

const user = useUserStore()

const newPass = ref({
    updatePass: true,
    users: {
        id: user.user.id,
        pass: ''
    },
    new_pass: {
        password: '', 
        confirmation_pass: ''
    }
})

function fill() {
    user.profile.users.name = user.user.name,
    user.profile.users.email = user.user.email,
    user.profile.users.address = user.user.address,
    user.profile.users.phone = user.user.phone
    user.profile.users.city = user.user.city
}

function submitPass() {
    if(newPass.value.users.pass && newPass.value.new_pass.password && newPass.value.new_pass.confirmation_pass) {
        if(user.actions.checkSession()) {
            user.actions.requestPassReset(newPass.value)
            newPass.value.users.pass = ''
            newPass.value.new_pass.password = ''
            newPass.value.new_pass.confirmation_pass = ''
        }
    } else {
        user.errorMsg = "Molimo vas da pravilno popunite podatke!"
        return setTimeout(() => {
            user.errorMsg = false
        }, 2000)
    }
}

</script>

<template>
    <v-container>
        <h1 class="text-center">Moj Profil</h1>
        <v-divider></v-divider>
    </v-container>
    <v-container class="d-flex justify-center">
        <v-card class="w-75 h-75" z-index="0">
            <v-card-title primary-title>
                <h2 class="text-center">Lični podaci</h2>
            </v-card-title>
            <v-card-text class="pa-9">
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Ime</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.name }}</p>
                </div>
                <v-divider></v-divider>
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Email</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.email }}</p>
                </div>
                <v-divider></v-divider>
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Grad</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.city }}</p>
                </div>
                <v-divider></v-divider>
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Adresa</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.address }}</p>
                </div>
                <v-divider></v-divider>
                <div class="ma-6 d-flex justify-space-between align-center">
                    <p class="text-center">Telefon</p>
                    <p class="text-center font-weight-bold font-italic">{{ user.user.phone }}</p>
                </div>
            </v-card-text>
            <v-card-actions class="d-flex justify-center">
                <v-dialog
                    v-model="user.profileDialog"
                    max-width="75%"
                    transition="dialog-transition"
                >
                    <template v-slot:activator="{ props: activatorProps }" >
                        <v-btn 
                            color="indigo-darken-4"
                            variant="elevated"
                            mb-5
                            height="3rem"
                            prepend-icon="mdi-pencil-circle"
                            v-bind="activatorProps"
                            @click="fill"
                        >
                        Uredi podatke</v-btn>
                    </template>
                    <ProfileEditForm />
                </v-dialog>
            </v-card-actions>
        </v-card>
    </v-container>
    <v-spacer></v-spacer>
    <v-container class="d-flex justify-center w-100">
        <v-card class="w-75 h-75 pa-12">
            <v-card-title primary-title>
                <h2 class="text-center">Promena Lozinke</h2>
                <v-divider></v-divider>
            </v-card-title>
            <v-spacer></v-spacer>
            <v-form @submit.prevent="submitPass">
            <v-card-text>
                
                <div class="w-100 d-flex flex-column align-center">
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
                    prepend-icon="mdi-checkbox-marked-circle"
                    type="submit"
                >
                
                Potvrdi</v-btn>
            </v-card-actions>
            </v-form>
        </v-card>
    </v-container>
</template>