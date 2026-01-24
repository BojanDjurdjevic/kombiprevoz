<script setup>
    const vehicles = [
        { src: '../../public/vans/van1.jpg' },
        { src: '../../public/vans/van2.jpg' },
        { src: '../../public/vans/van3.jpg' },
        { src: '../../public/vans/van4.jpg' },
        { src: '../../public/vans/van5.jpg' },
        { src: '../../public/vans/van6.jpg' },
        { src: '../../public/vans/van7.jpg' }
    ] 

</script>

<template>
    <v-container fluid class="p-6">
      <v-row justify="center" class="mb-6">
        <v-col cols="12" md="10">
            <v-carousel
                show-arrows-on-hover
                height="500"
                hide-delimiter-background
            >
            <v-carousel-item
                v-for="(item, i) in vehicles"
                :key="i"
                :src="item.src"
                eager
                cover
                aspect-ratio="16/9"
                alt="Slika vozila za prevoz putnika"
            />
            </v-carousel>
        </v-col>
        </v-row>  
    </v-container>
</template>