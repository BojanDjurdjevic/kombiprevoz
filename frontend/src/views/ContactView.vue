<script setup>
import { ref } from 'vue';
import CustomerChatWidget from '@/components/chat/CustomerChatWidget.vue';

const chat = ref(false)

const msgs = ref([])

const type = ref()

function sendMsg() {
    const msg = {
        author: 'Ja',
        text: type.value
    }
    msgs.value.push(msg)
    
    type.value = null
}

</script>

<template>
    <v-container>
        <h1 class="text-center ma-3">
            Kontakt – Kombi prevoz putnika iz Srbije ka Evropi
        </h1>
        <p class="text-center text-body-2 mt-4 mb-6">
            Kontaktirajte nas za pouzdan i udoban kombi prevoz putnika iz Srbije
            ka gradovima širom Evrope. Dostupni smo telefonom i emailom.
        </p>
        <v-divider></v-divider>
    </v-container>
    <v-container
        class="d-flex flex-column align-center"
    >
        <v-hover>
            <template v-slot:default="{isHovering, props}">
                <v-card
                    v-bind="props"
                    :color="isHovering ? 'primary' : 'teal-darken-3' "
                    :text-green="isHovering ? 'true' : 'false'"
                    class="w-100 w-md-50 pa-6 ma-9 rounded-xl 
                    d-flex align-center justify-center"
                    min-height="6rem"
                >
                    <v-icon icon="mdi-phone" class="mr-3"></v-icon>
                    <v-card-title>
                        <a href="tel:+38162XXXXXX" class="text-white text-decoration-none">
                            +381 62 XXX XXX
                        </a>
                    </v-card-title>
                </v-card>
            </template>
        </v-hover>
        <v-hover>
            <template v-slot:default="{isHovering, props}">
                <v-card
                    v-bind="props"
                    :color="isHovering ? 'primary' : 'teal-darken-3'"
                    class="w-100 w-md-50 pa-6 ma-9 rounded-xl 
                    d-flex align-center justify-center"
                    min-height="6rem"
                >
                    <v-icon icon="mdi-email" class="mr-3"></v-icon>
                    <v-card-title>
                        <a href="mailto:info@kombitransfer.com" class="text-white text-decoration-none">
                            info@kombitransfer.com
                        </a>
                    </v-card-title>

                </v-card>
            </template>
        </v-hover>
        <!--
        <v-dialog
            v-model="chat"
            location="top center"
            transition="fade"
            height="80vh"
            class="w-100 w-md-25"
        >
            <template v-slot:activator="{ props: activatorProps }">
                <v-btn
                    class="ma-12 bg-teal-darken-3 rounded-circle
                    position-fixed bottom-0 right-0"
                    icon="mdi-forum"
                    width="4.2rem" height="4.2rem" elevation="6"
                    v-bind="activatorProps"
                    v-if="!chat"
                >
                
                </v-btn>
                <v-btn
                    class="ma-12 bg-teal-darken-3 rounded-circle
                    position-fixed bottom-0 right-0"
                    icon="mdi-close"
                    width="4rem" height="4rem" elevation="6"
                    v-bind="activatorProps"
                    v-if="chat"
                ></v-btn>
            </template>

            <v-card class="rounded-xl">
                <v-toolbar>
                    <v-btn
                        icon="mdi-close"
                        @click="chat = false"
                    ></v-btn>
                    <v-toolbar-title>Chat</v-toolbar-title>
                </v-toolbar>
                <v-main height="75%"
                    class="overflow-hidden 
                    d-flex flex-column align-center"    
                >
                    <v-card
                        v-for="m in msgs"
                        class="w-75 ma-6 rounded-lg bg-light-blue-accent-4"
                        elevation="3"
                    >
                        <v-card-title class="text-light-green-accent-4"> {{ m.author }} </v-card-title>
                        <v-card-text> {{ m.text }} </v-card-text>
                    </v-card>
                </v-main>
                <v-text-field
                    v-model="type"
                    @keydown.enter="sendMsg"
                ></v-text-field>
            </v-card>
        </v-dialog>
        -->

        <CustomerChatWidget />
    </v-container>
</template>