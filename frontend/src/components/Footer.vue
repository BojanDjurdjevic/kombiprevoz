<template>
  <v-footer color="indigo-darken-4" class="text-white pa-6">
    <v-container>
      <v-row>

        <!-- BRAND -->
        <v-col cols="12" md="4">
          <h3>KombiTransfer</h3>
          <p class="text-body-2 mt-2">
            Pouzdan kombi prevoz putnika iz Srbije ka destinacijama širom Evrope.
          </p>
        </v-col>

        <!-- LINKS -->
        <v-col cols="6" md="4">
          <h4>Brzi linkovi</h4>
          <ul class="footer-links">
            <li><RouterLink to="/">Početna</RouterLink></li>
            <li><RouterLink to="/destinacije">Destinacije</RouterLink></li>
            <li><RouterLink to="/rezervacije">Moje rezervacije</RouterLink></li>
            <li><RouterLink to="/kontakt">Kontakt</RouterLink></li>
          </ul>
        </v-col>

        <!-- CONTACT -->
        <v-col cols="6" md="4">
          <h4>Kontakt</h4>
          <p class="text-body-2">📞 +381 XX XXX XXX</p>
          <p class="text-body-2">✉️ info@kombitransfer.com</p>
        </v-col>

      </v-row>

      <v-divider class="my-4"></v-divider>

      <div class="text-center text-caption">
        © {{ new Date().getFullYear() }} KombiTransfer. Sva prava zadržana.
        
      </div>
      <br>
      <div class="text-center text-caption">
        <u>*** Napomena: Ovaj sajt je izrađen isključivo u svrhu učenja programiranja! Svaka podudarnost je slučajna.</u>
      </div>
    </v-container>
  </v-footer>
</template>

<style scoped>
.footer-links {
  list-style: none;
  padding: 0;
}

.footer-links li {
  margin-bottom: 6px;
}

.footer-links a {
  color: white;
  text-decoration: none;
}

.footer-links a:hover {
  text-decoration: underline;
}
</style>
