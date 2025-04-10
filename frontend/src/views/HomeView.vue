<script setup>

</script>

<template>
  <v-container
    class="fill-height fill-width pa-0 overflow-hidden position-relative"
    fluid
  >
    <v-img
      src="https://picsum.photos/3500/1650?random"
      height="100%"
      cover
    ></v-img>

    <v-container fluid 
      class="fill-height fill-width bg-black opacity-50
      position-absolute top-9 left-0
    "></v-container>
    <v-container fluid
      class="fill-height fill-width text-white
      position-absolute top-9 left-0 d-flex justify-center"
    >
      <h1>Pozdrav</h1>
    </v-container>
  </v-container>

</template>
