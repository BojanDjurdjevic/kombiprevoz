<script setup>
const props = defineProps({
  modelValue: Object
})

const emit = defineEmits(['update:modelValue', 'submit'])
</script>

<template>
  <v-container class="d-flex justify-center">
    <v-card class="w-100 w-md-75 pa-6">
      <v-card-title class="text-center">
        <h2>Promena lozinke</h2>
      </v-card-title>

      <v-form @submit.prevent="emit('submit')">
        <v-text-field
          v-model="modelValue.users.pass"
          label="Trenutna lozinka"
          type="password"
        />

        <v-text-field
          v-model="modelValue.new_pass.password"
          label="Nova lozinka"
          type="password"
        />

        <v-text-field
          v-model="modelValue.new_pass.confirmation_pass"
          label="Potvrda lozinke"
          type="password"
        />

        <v-btn
          type="submit"
          block
          color="indigo-darken-4"
        >
          Potvrdi
        </v-btn>
      </v-form>
    </v-card>
  </v-container>
</template>
