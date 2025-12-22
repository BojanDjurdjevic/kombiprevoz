<script setup>
import { ref } from "vue";
import { useAdminStore } from "@/stores/admin";
import { useForm, useField } from "vee-validate";

const admin = useAdminStore();

const { handleSubmit, handleReset } = useForm({
  validationSchema: {
    name: value => value?.length >= 2 || 'Ime mora imati najmanje 2 slova.',
    phone: value => /^[0-9-]{7,}$/.test(value) || 'Broj telefona mora imati najmanje 7 cifara.',
    email: value => /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/i.test(value) || 'Molimo Vas unesite validan e-mail.',
    country: value => !!value || 'Odaberite državu.',
    city: value => !!value || 'Odaberite grad.',
    address: value => !!value || 'Upišite adresu.',
    status: value => !!value || 'Izaberite status korisnika.',
  },
});

const name = useField('name');
const phone = useField('phone');
const email = useField('email');
const country = useField('country');
const city = useField('city');
const address = useField('address');
const status = useField('status');

const newUser = ref({ users: { byAdmin: true } });

const submit = handleSubmit(values => {
  newUser.value.users = values;
  newUser.value.users.byAdmin = true;
  admin.actions.createUser(newUser.value);
});
</script>

<template>
  <v-container class="d-flex justify-center align-center">
    <v-card class="pa-3 w-full md:w-3/4">
      <v-form @submit.prevent="submit">
        <v-text-field v-model="name.value.value" :error-messages="name.errorMessage.value" label="Ime" clearable></v-text-field>
        <v-text-field v-model="email.value.value" :error-messages="email.errorMessage.value" label="Email" clearable></v-text-field>
        <v-text-field v-model="phone.value.value" :error-messages="phone.errorMessage.value" label="Telefon" clearable></v-text-field>
        <v-autocomplete v-model="country.value.value" :items="admin.dbCountries" item-title="name" item-value="id" label="Država" :error-messages="country.errorMessage.value" clearable @update:model-value="admin.userCityOptions(country.value.value)"/>
        <v-autocomplete v-model="city.value.value" :items="admin.userOptions" label="Grad" :error-messages="city.errorMessage.value" clearable :disabled="!admin.userOptions"/>
        <v-combobox v-model="address.value.value" label="Adresa" :error-messages="address.errorMessage.value" clearable/>
        <v-autocomplete v-model="status.value.value" :items="['User','Driver','Admin']" label="Status" :error-messages="status.errorMessage.value" clearable/>

        <div class="mt-4 flex justify-between">
          <v-btn type="submit" color="indigo-darken-4">Potvrdi</v-btn>
          <v-btn type="button" @click="handleReset">Obriši</v-btn>
        </div>
      </v-form>
    </v-card>
  </v-container>
</template>
