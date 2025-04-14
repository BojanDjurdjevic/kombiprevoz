<script setup>

</script>

<template>
  <v-container
    class="fill-height fill-width pa-0 overflow-hidden position-relative"
    fluid
  >
    <v-img
      src="https://picsum.photos/id/314/3500/1650"
      height="100%"
      cover
    ></v-img>

    <v-container fluid 
      class="fill-height fill-width bg-black opacity-30
      position-absolute top-9 left-0
    "></v-container>
    <v-container fluid
      class="text-white
      position-absolute top-9 left-0 
      h-75
      d-flex justify-space-around align-center flex-wrap"
    >
      <v-container>
        <h1>Kombi Prevoz Putnika</h1>
        <br />
        <h4>SIGURNO I UDOBNO</h4>
        <br />
        <h2>Najbolji kombi prevoz do</h2>
        <br />
        <h2 color="pink-darken-4">Inostranstva</h2>
        <br />
        <h2>sa vaše adrese</h2>
      </v-container>
      <v-container
        class="d-flex justify-end"
      >
        <v-card height="18rem" width="18rem" elevation="9" to="gradovi" 
            class="rounded-xl"
        >
            <v-img
                class="align-end text-white"
                height="100%"
                        
                cover
                src="../assets/img/minibus.jpg"
            >
                <template #sources>
                    <source srcset="../assets/img/minibus.jpg">
                </template>
                <v-card-title class="text-center">Naša vozila</v-card-title>
            </v-img> 
        </v-card>
      </v-container>
    </v-container>
  </v-container>

</template>
