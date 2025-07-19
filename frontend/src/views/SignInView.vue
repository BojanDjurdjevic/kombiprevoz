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
    initials: '',
    name: '',
    email: '',
    password: '',
    town: '',
    address: '',
    phone: ''
})

const { handleSubmit, handleReset } = useForm({
    validationSchema: {
      name (value) {
        if (value?.length >= 2) return true

        return 'Name needs to be at least 2 characters.'
      },
      phone (value) {
        if (/^[0-9-]{7,}$/.test(value)) return true

        return 'Phone number needs to be at least 7 digits.'
      },
      email (value) {
        if (/^[a-z.-]+@[a-z.-]+\.[a-z]+$/i.test(value)) return true

        return 'Must be a valid e-mail.'
      },
      password (value) {
        if (/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}/i.test(value)) return true

        return 'Must be a valid password.'
      },
      city (value) {
        if (value) return true

        return 'Select an item.'
      },
      address (value) {
        if (value) return true

        return 'Select an item.'
      },
      checkbox (value) {
        if (value === '1') return true

        return 'Must be checked.'
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
  

  const items = ref([
    'Item 1',
    'Item 2',
    'Item 3',
    'Item 4',
  ])

  const submit = handleSubmit(values => {
    alert(JSON.stringify(values, null, 2))

    let init = makeInitials(values.name)

    user.user = {
        initials: init,
        fullName: values.name,
        email: values.email,
        password: values.password,
        town: values.city,
        address: values.address,
        phone: values.phone
    }

    router.push({
      path: '/'
    })
  })

  function makeInitials(str) {
    let first = str[0]

    return first
  }

  async function handleAddress(query) {
    console.log(query)
    try {
      let response = await addApi.getAddress(query)
      console.log(response)
    } catch (error) {
      console.log(error)
    }
  }

</script>

<template>
    <v-container class="text-center">
        <h1>Registracija</h1>
        <v-divider></v-divider>
    </v-container>
    <v-container class="d-flex justify-center align-center" height="95%">
        <v-card class="pa-6 d-flex justify-center align-center h-100 w-75"
        >
            <v-form @submit.prevent="submit"
                class="w-75 h-75"
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

                <v-select
                v-model="city.value.value"
                :error-messages="city.errorMessage.value"
                :items="dest.cities.Srbija"
                label="Grad"
                clearable
                ></v-select>

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
                label="Option"
                type="checkbox"
                value="1"
                ></v-checkbox>
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