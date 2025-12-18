<script setup>
import { onMounted, ref } from 'vue';
import { useUserStore } from '@/stores/user';
import { useDestStore } from '@/stores/destinations';
import { useField, useForm } from 'vee-validate';
import router from '@/router';
import api from '@/api';
import { useSearchStore } from '@/stores/search';

//import addApi from '../api/address';

const user = useUserStore()
const dest = useDestStore()
const search = useSearchStore()

const newUser = ref({
  users: {
      name: '',
      email: '',
      pass: '',
      city: '',
      address: '',
      phone: '',
      remember: false,
      signin: true
  }
   
})
const remember = ref(false)

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
      pass (value) {
        if (/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}/i.test(value)) return true

        return 'Lozinka mora imati minimum 8 karaktera, 1 malo/veliko slovo i jedan specijalni karakter.'
      },
      country (value) {
        if (value) return true

        return 'Odaberite Državu.'
      },
      city (value) {
        if (value) return true

        return 'Odaberite Vaš grad.'
      },
      address (value) {
        if (value) return true

        return 'Upišite Vašu adresu.'
      },
      checkbox (value) {
        if (value === true) return true

        return 'Mora biti označeno!'
      }, /*
      remember (value) {
        return value
      }, */
    },
  })
  const name = useField('name')
  const phone = useField('phone')
  const email = useField('email')
  const pass = useField('pass')
  const country = useField('country')
  const city = useField('city')
  const address = useField('address')
  const checkbox = useField('checkbox')
  //const remember = useField('remember')

  const items = ref([
    'Item 1',
    'Item 2',
    'Item 3',
    'Item 4',
  ])

  const submit = handleSubmit(values => {
    newUser.value.users = values
    newUser.value.users.remember = remember.value
    newUser.value.users.signin = true
    console.log(newUser.value)
    
    //alert(JSON.stringify(values, null, 2))
    user.actions.handleSignin(newUser.value)
  })

  function makeInitials(str) {
    let first = str[0]

    return first
  }

  const countries = ref([])
  const cities = ref([])

  async function getMyCountry() {
    user.loading = true
    try {
      const msg = await api.getCountries(search.allCount)
      let input = Object.values(msg.data.drzave)
      countries.value = input
      console.log(countries.value)
    } catch(error) {
      console.log(error)
    } finally {
      user.loading = false
    }
  }

  onMounted(() => {
    getMyCountry()
  })

  async function getMyCity(id) {
    console.log('poslat id: ', id)
    user.loading = true
    let dto = {
      country_id: id
    }
    try {
      const msg = await api.getCities(dto)
      //cities.value = Object.values(msg.data.cities)
      cities.value = []
      city.value.value = ''
      msg.data.cities.forEach(item => {
        cities.value.push(item.name)
      });
      //cities.value = msg.data.cities.name
      console.log(cities.value)
    } catch(error) {
      console.log(error)
    } finally {
      user.loading = false
    }
  }

</script>

<template>
  <v-container class="text-center mb-4">
    <h1>Registracija</h1>
    <v-divider />
  </v-container>

  <v-container class="d-flex justify-center">
    <v-card class="pa-6" max-width="900" width="100%">
      <v-form @submit.prevent="submit">

        <v-row dense>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="name.value.value"
              label="Ime"
              :error-messages="name.errorMessage.value"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="email.value.value"
              label="E-mail"
              :error-messages="email.errorMessage.value"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="pass.value.value"
              label="Lozinka"
              :error-messages="pass.errorMessage.value"
              type="password"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="phone.value.value"
              label="Broj telefona"
              :error-messages="phone.errorMessage.value"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-autocomplete
              v-model="country.value.value"
              :items="countries"
              label="Država"
              :error-messages="country.errorMessage.value"
              return-object
              clearable
              @update:model-value="val => getMyCity(val.id)"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-autocomplete
              v-model="city.value.value"
              :items="cities"
              label="Grad"
              :error-messages="city.errorMessage.value"
              clearable
            />
          </v-col>

          <v-col cols="12">
            <v-combobox
              v-model="address.value.value"
              label="Adresa"
              :error-messages="address.errorMessage.value"
              clearable
            />
          </v-col>
        </v-row>

        <v-checkbox
          v-model="checkbox.value.value"
          :error-messages="checkbox.errorMessage.value"
          label="Saglasan sam sa uslovima korišćenja."
        />

        <v-checkbox
          v-model="remember"
          label="Zapamti me"
        />

        <v-divider class="my-4" />

        <v-card-actions class="flex-column flex-sm-row justify-space-between">
          <div>
            <v-btn type="submit" color="indigo-darken-4" class="me-sm-2 mb-2 mb-sm-0" variant="elevated">
              Potvrdi
            </v-btn>
            <v-btn variant="outlined" @click="handleReset">
              Obriši
            </v-btn>
          </div>

          <div class="text-center mt-4 mt-sm-0">
            Već imate nalog?
            <v-btn
              color="indigo-darken-4"
              variant="elevated"
              to="/login"
            >
              Uloguj se
            </v-btn>
          </div>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-container>

  <!--
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
                v-model="pass.value.value"
                :error-messages="pass.errorMessage.value"
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

                <v-autocomplete
                v-model="country.value.value"
                :error-messages="country.errorMessage.value"
                :items="countries"
                item-title="name"
                item-value="id"
                label="Država"
                clearable
                return-object
                @update:model-value="val => getMyCity(val.id)"
                ></v-autocomplete>

                <v-autocomplete
                v-model="city.value.value"
                :error-messages="city.errorMessage.value"
                :items="cities"
                label="Grad"
                clearable
                ></v-autocomplete>

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
                    v-model="remember"
                    label="Zapamti me"
                    type="checkbox"
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
    -->
</template>