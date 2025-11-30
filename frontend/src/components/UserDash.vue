<script setup>
import { useAdminStore } from "@/stores/admin";
import { useField, useForm } from 'vee-validate';
import { ref } from "vue";

const admin = useAdminStore();

const newUser = ref({
  users: {
      name: '',
      email: '',
      city: '',
      address: '',
      phone: '',
      status: '',
      byAdmin: true
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
      }, /*
      pass (value) {
        if (/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,20}/i.test(value)) return true

        return 'Lozinka mora imati minimum 8 karaktera, 1 malo/veliko slovo i jedan specijalni karakter.'
      }, */
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
      status (value) {
        if (value) return true

        return 'Izaberite status korisnika!.'
      },
    },
  })
  const name = useField('name')
  const phone = useField('phone')
  const email = useField('email')
  //const pass = useField('pass')
  const country = useField('country')
  const city = useField('city')
  const address = useField('address')
  const status = useField('status')

  const submit = handleSubmit(values => {
    newUser.value.users = values
    newUser.value.users.byAdmin = true

    admin.actions.createUser(newUser.value)
  })
</script>

<template>
  <div
    class="mt-6 pa-3 w-100 d-flex flex-column align-center"
    v-if="admin.adminView == 'Users'"
  >
    <v-card class="w-100">
      <v-toolbar>
        <template v-slot>
          <v-tabs v-model="admin.tab_users" align-tabs="title">
            <v-tab
              v-for="item in admin.items_users"
              :key="item"
              :text="item"
              :value="item"
            ></v-tab>
          </v-tabs>
        </template>
      </v-toolbar>

      <v-tabs-window v-model="admin.tab_users">
        <v-tabs-window-item v-for="item in admin.items_users" :key="item" :value="item">
          <v-card flat>
            <v-card-text>
              <div v-if="item == 'Pretraga'">
                <v-card v-if="admin.userByAdmin"
                  class="w-100 h-75 mt-3"
                >
                  <v-card-title class="text-center pa-3">Korisnik: {{ admin.userByAdmin?.name }} </v-card-title>
                  <v-card-subtitle class="text-center pa-3">Status korisnika: {{ admin.userByAdmin?.status }} </v-card-subtitle>
                  <v-card-text class="h-75">
                    <p class="ma-3 pa-1">Grad: {{ admin.userByAdmin?.city }} </p>
                    <p class="ma-3 pa-1">Adresa: {{ admin.userByAdmin?.address }} </p>
                    <p class="ma-3 pa-1">Email: {{ admin.userByAdmin?.email }}</p>
                    <p class="ma-3 pa-1">Telefon: {{ admin.userByAdmin?.phone }}</p>
                  </v-card-text>
                  <v-card-actions 
                    class="w-100 d-flex justify-space-between"
                  >
                    <v-btn
                      color="indigo-darken-4"
                      variant="elevated"
                    >
                      Uredi
                    </v-btn>
                    <v-btn
                      color="red-darken-4"
                      variant="elevated"
                    >
                      Pošalji email
                    </v-btn>
                  </v-card-actions>
                </v-card> 
              </div>
              <div v-if="item == 'Kreiraj novog korisnika'">
                <v-container class="d-flex justify-center align-center" height="95%">
                  <v-card class="pa-3 d-flex justify-center align-center h-100 w-75"
                  >
                      <v-form @submit.prevent="submit"
                          class="w-75"
                      >
                          <v-text-field
                          v-model="name.value.value"
                          :counter="24"
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
                          v-model="phone.value.value"
                          :counter="7"
                          :error-messages="phone.errorMessage.value"
                          label="Broj telefona"
                          clearable
                          ></v-text-field>

                          <v-autocomplete
                          v-model="country.value.value"
                          :error-messages="country.errorMessage.value"
                          :items="admin.dbCountries"
                          item-title="name"
                          item-value="id"
                          label="Država"
                          clearable
                          return-object
                          @update:model-value="admin.userCityOptions(country.value.value)"
                          ></v-autocomplete>

                          <v-autocomplete
                          v-model="city.value.value"
                          :error-messages="city.errorMessage.value"
                          :items="admin.userOptions"
                          label="Grad"
                          :disabled="!admin.userOptions"
                          clearable
                          ></v-autocomplete>

                          <v-combobox
                          v-model="address.value.value"
                          :error-messages="address.errorMessage.value"
                          :items="items"
                          label="Adresa"
                          clearable
                          
                          ></v-combobox>
                          <v-autocomplete
                            v-model="status.value.value"
                            :error-messages="address.errorMessage.value"
                            :items="['User', 'Driver', 'Admin']"
                            label="Status"
                            clearable
                          >

                          </v-autocomplete>
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
                          </div>
                      </v-form>
                  </v-card>
              </v-container>
              </div>
            </v-card-text>
          </v-card>
        </v-tabs-window-item>
      </v-tabs-window>
    </v-card>
  </div>
</template>
