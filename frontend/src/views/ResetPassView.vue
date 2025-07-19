<script setup>
    import router from '@/router';
    import { useUserStore } from '@/stores/user';
    import { ref } from 'vue';

    const url = window.location.href

    console.log(url);

    const arr = url.split("=")
    const token = arr[1]
    console.log(token)

    const user = useUserStore()

    const data = ref({
        users: {
            email: '',  
        },
        token: token,
        new_pass: {
            password: '',
            confirmation_pass: ''
        }
    })

    function proceed() {
        if(data.value.token) {
        data.value.users.email = data.value.users.email.trim()
        data.value.new_pass.password = data.value.new_pass.password.trim()
        data.value.new_pass.confirmation_pass = data.value.new_pass.confirmation_pass.trim()
        user.actions.sendToken(data.value)
        console.log(data.value)
        } else {
            user.errorMsg = "Neispravan token! Molimo Vas da proverite da li ste pravilno uneli Vaš email i da ponovo zatražite link."
            setTimeout(() => {
                user.errorMsg = null
            }, 6000)
            router.push('/request-password-reset')
        }
    }

    
    
</script>

<template>
    <v-container>
        <h2 class="text-center">Promena lozinke</h2>
    </v-container>

    <v-container>
        <v-form @submit.prevent="proceed">
            <v-text-field
                label="Email"
                v-model="data.users.email"
            ></v-text-field>
            <v-text-field
                label="Lozinka"
                v-model="data.new_pass.password"
            ></v-text-field>
            <v-text-field
                label="Potvrda lozinke"
                v-model="data.new_pass.confirmation_pass"
            ></v-text-field>
            <v-btn
                color="indigo-darken-4"
                variant="elevated"
                type="submit"
            >
                Pošalji
            </v-btn>
        </v-form>
        
    </v-container>

</template>