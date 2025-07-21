<script setup>
import { ref } from 'vue';
import { useUserStore } from '@/stores/user';
import { useDestStore } from '@/stores/destinations';
import { useField, useForm } from 'vee-validate';
import router from '@/router';
//import addApi from '../api/address';

const user = useUserStore()
const dest = useDestStore()

const newUser = ref({
  users: {
      name: '',
      email: '',
      password: '',
      city: '',
      address: '',
      phone: '',
      remember: false,
      signin: true
  }
   
})

const { handleSubmit, handleReset } = useForm({
    validationSchema: {
      name (value) {
        if (value?.length >= 2) return true

        return 'Ime mora imati najmanje 3 slova.'
      },
      phone (value) {
        if (/^[0-9-]{7,}$/.test(value)) return true

        return 'Broj telefona mora imati najmanje 7 cifara.'
      },
      email (value) {
        if (/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/i.test(value)) return true

        return 'Molimo Vas unesite validan e-mail.'
      },
      password (value) {
        if (/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}/i.test(value)) return true

        return 'Lozinka mora imati minimum 8 karaktera, 1 malo/veliko slovo i jedan specijalni karakter.'
      },
      city (value) {
        if (value) return true

        return 'Upišite Vaš grad.'
      },
      address (value) {
        if (value) return true

        return 'Upišite Vašu adresu.'
      },
      checkbox (value) {
        if (value === true) return true

        return 'Mora biti označeno!'
      },
      checkbox2 (value) {
        return true
      },
    },
  })
  const name = useField('name')
  const phone = useField('phone')
  const email = useField('email')
  const password = useField('password')
  const city = useField('city')
  const address = useField('address')
  const checkbox = useField('checkbox')
  const checkbox2 = useField('checkbox2')

  const items = ref([
    'Item 1',
    'Item 2',
    'Item 3',
    'Item 4',
  ])

  const submit = handleSubmit(values => {

    alert(JSON.stringify(values, null, 2))
  })

  function makeInitials(str) {
    let first = str[0]

    return first
  }

  

</script>

<template>
    <v-container class="text-center">
        <h1>Registracija</h1>
        <v-divider></v-divider>
    </v-container>
    <v-container class="d-flex justify-center align-center" height="95%">
        <v-card class="pa-3 d-flex justify-center align-center h-100 w-75"
        >
            <v-form @submit.prevent="submit"
                class="w-75"
            >
                <v-text-field
                v-model="name.value.value"
                :counter="10"
                :error-messages="name.errorMessage.value"
                label="Ime"
                clearable
                ></v-text-field>

                <v-text-field
                v-model="email.value.value"
                :error-messages="email.errorMessage.value"
                label="E-mail"
                clearable
                ></v-text-field>

                <v-text-field
                v-model="password.value.value"
                :error-messages="password.errorMessage.value"
                label="Lozinka"
                clearable
                ></v-text-field>

                <v-text-field
                v-model="phone.value.value"
                :counter="7"
                :error-messages="phone.errorMessage.value"
                label="Broj telefona"
                clearable
                ></v-text-field>

                <v-combobox
                v-model="city.value.value"
                :error-messages="city.errorMessage.value"
                :items="dest.cities.Srbija"
                label="Grad"
                clearable
                ></v-combobox>

                <v-combobox
                v-model="address.value.value"
                :error-messages="address.errorMessage.value"
                :items="items"
                label="Adresa"
                clearable
                
                ></v-combobox>

                <v-checkbox
                v-model="checkbox.value.value"
                :error-messages="checkbox.errorMessage.value"
                label="Saglasan sam sa uslovima korišćenja sajta KombiPrevoz."
                type="checkbox"
                ></v-checkbox>
                <v-checkbox
                    v-model="newUser.users.remember"
                    label="Zapamti me"
                />
                <div class="d-flex justify-space-between align-center">
                  <div>
                    <v-btn
                    class="me-4"
                    type="submit"
                    color="indigo-darken-4"
                    >
                    Potvrdi
                    </v-btn>

                    <v-btn @click="handleReset">
                    Obriši
                    </v-btn>
                  </div>
                  <div>
                    <span class="ma-6">Već imate nalog? <v-btn
                        variant="elevated"
                        color="indigo-darken-4"
                        class="ma-6" 
                        to="/login"   
                      >Uloguj se</v-btn> 
                    </span>
                  </div>
                </div>
            </v-form>
        </v-card>
    </v-container>
</template>