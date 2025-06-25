<script setup>
    const url = window.location.href

    console.log(url);

    const arr = url.split("=")
    const token = arr[1]
    console.log(token)
    
</script>

<template>
    <v-container>
        <h2 class="text-center">Promena lozinke</h2>
    </v-container>

    <v-container>
        <v-form>
            <v-text-field
                label="Email"
            ></v-text-field>
            <v-text-field
                label="Lozinka"
            ></v-text-field>
            <v-text-field
                label="Potvrda lozinke"
            ></v-text-field>
        </v-form>
        <v-btn>
            Pošalji
        </v-btn>
    </v-container>

</template>