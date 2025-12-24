<script setup>

import minibusImg from '@/assets/img/minibus.jpg';

  /**
   
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
      h-85
      d-flex flex-column flex-md-row justify-space-around align-center"
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
        class="d-flex flex-column justify-space-around align-end"
      >
        <v-card height="18rem" width="18rem" elevation="9" to="gradovi" 
            class="rounded-xl ma-3"
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
        <v-card height="18rem" width="18rem" elevation="9" to="destinacije" 
            class="rounded-xl ma-3"
        >
            <v-img
                class="align-end text-white"
                height="100%"
                        
                cover
                src="https://picsum.photos/id/551/3500/1650"
            >
                <template #sources>
                    <source srcset="https://picsum.photos/id/551/3500/1650">
                </template>
                <v-card-title class="text-center">Destinacije</v-card-title>
            </v-img> 
        </v-card>
      </v-container>
    </v-container>
  </v-container>

</template>

   */

</script>

<template>
  <v-container fluid class="pa-0 position-relative">

    <!-- HERO IMAGE -->
    <v-img
      src="https://picsum.photos/id/314/3500/1650"
      height="100vh"
      cover
      alt="Kombi prevoz putnika iz Srbije ka Evropi"
    />

    <!-- OVERLAY -->
    <div class="hero-overlay"></div>

    <!-- HERO CONTENT -->
    <v-container
      class="hero-content text-white d-flex flex-column flex-md-row align-center justify-space-between"
    >
      <!-- LEFT TEXT -->
      <div class="hero-text">
        <h1>Kombi transfer putnika iz Srbije ka Evropi</h1>
        <h2 class="subtitle">
          Siguran, udoban i pouzdan prevoz sa vaše adrese do željene destinacije.
        </h2>
        <br>
        <ul class="hero-benefits">
          <li>✔ Prevoz od vrata do vrata</li>
          <li>✔ Iskusni vozači</li>
          <li>✔ Moderna i udobna vozila</li>
        </ul>
        <div>
        <v-btn
          color="pink-darken-3"
          size="large"
          class="mt-6"
          to="/destinacije"
        >
          Pogledaj destinacije
        </v-btn>
        </div>
        <div class="mt-6">
        <v-btn variant="outlined" color="green-lighten-3" class="ml-4" to="/kontakt">
          Zatraži ponudu
        </v-btn>
        </div>
      </div>

      <!-- RIGHT CARDS -->
      <div class="hero-cards d-flex flex-column">
        <v-card
          class="rounded-xl mb-4"
          elevation="8"
          to="/gradovi"
        >
          <v-img
            :src="minibusImg"
            height="220"
            cover
            alt="Naša moderna kombi vozila za međunarodni prevoz"
          >
            
            <v-card-title class="text-white text-center">
              Naša vozila
            </v-card-title>
          </v-img>
        </v-card>

        <v-card
          class="rounded-xl"
          elevation="8"
          to="/destinacije"
        >
          <v-img
            src="https://picsum.photos/id/551/3500/1650"
            height="220"
            cover
            alt="Destinacije kombi transfera širom Evrope"
          >
            <v-card-title class="text-white text-center">
              Destinacije
            </v-card-title>
          </v-img>
        </v-card>
      </div>
    </v-container>

    <section class="seo-section">
      <h2>Kombi prevoz putnika iz Srbije</h2>

      <p>
        Nudimo profesionalni kombi prevoz putnika iz Srbije ka
        Nemačkoj, Austriji, Švajcarskoj, Italiji i drugim zemljama Evrope.
        Prevoz se vrši modernim i klimatizovanim vozilima, direktno sa vaše adrese.
      </p>
    </section>
  </v-container>
</template>

<style scoped>
.v-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.v-card:hover {
  transform: translateY(-4px);
  cursor: pointer;
}
.hero-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.45);
}

.hero-content {
  position: absolute;
  inset: 0;
  padding: 2rem;
}

.hero-text {
  max-width: 520px;
}

.subtitle {
  font-size: 1.2rem;
  line-height: 1.6;
}

@media (max-width: 960px) {
  .hero-content {
    justify-content: center;
    text-align: center;
  }

  .hero-cards {
    margin-top: 2rem;
  }
}
</style>



