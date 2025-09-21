<script setup>
import { useMyOrdersStore } from '@/stores/myorders';
import { useSearchStore } from '@/stores/search';
import { useUserStore } from '@/stores/user';
import { VNumberInput } from 'vuetify/labs/VNumberInput';
import { VDateInput } from 'vuetify/labs/VDateInput';
const orders = useMyOrdersStore()
const user = useUserStore()
const search = useSearchStore()

</script>

<template>
    <v-container>
        <div class="d-flex justify-space-between ma-2 pa-3">
            <div class="d-flex">
                <v-btn
                    icon="mdi-keyboard-backspace"
                    to="/rezervacije"
                ></v-btn>
                <h1 class="ml-9">Rezervacija broj: {{ orders.oneOrder.code }}</h1>
            </div>
            <h1> Ukupna Cena: {{ orders.oneOrder.total }} </h1>
        </div>
        <v-divider></v-divider>
    </v-container>

    <v-container class="d-flex flex-column justify-space-evenly align-center" v-for="order in orders.oneOrder.items" >
        
        <v-card class="w-75 ma-3 pa-12 rounded-lg d-flex flex-column justify-space-around " height="660" elevation="3" 
            v-if="order.deleted == 0 || order.deleted == 1"
        >
            <v-card-title class="text-center font-weight-bold"> {{ order.from }} - {{ order.to }}</v-card-title>
            <v-divider></v-divider>
            <v-card-title class="d-flex justify-space-between"><p>Adresa Polaska:</p><p> {{ order.pickup }}</p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Adresa Dolaska:</p><p> {{ order.dropoff }}</p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Broj Sedišta:</p><p> {{ order.places }} </p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Datum Vožnje:</p><p> {{ order.date }} </p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Vreme Polaska:</p><p> {{ order.time }}</p> </v-card-title>
            <v-card-title class="d-flex justify-space-between"><p>Cena:</p><p> {{ order.price }}</p> </v-card-title>
            <v-divider></v-divider>
            <v-card-actions class="d-flex justify-center" v-if="order.deleted == 0">
                <v-dialog
                    v-model="orders.addressDialog"
                    max-width="75%"
                    transition="dialog-transition"
                >
                    <template v-slot:activator="{props: activatorProps}">
                        <v-btn
                            variant="elevated"
                            color="success"
                            width="20%"
                            prepend-icon="mdi-pencil-circle"
                            v-bind="activatorProps"
                            @click="orders.populatePickup(order)"
                        >
                            Adrese
                        </v-btn>
                    </template>
                    <v-card>
                        <v-form @submit.prevent="orders.actions.addUpdate(orders.pickup, order)">
                            <v-card-title primary-title>
                                Izmena adrese 
                            </v-card-title>
                            <v-card-text>
                                <v-text-field
                                    name="Adresa polaska"
                                    label="Adresa polaska"
                                    clearable
                                    v-model="orders.pickup.add_from"
                                    :rules="[user.rules.required, user.rules.validStr]"
                                ></v-text-field>
                                <v-text-field
                                    name="Adresa dolaska"
                                    label="Adresa dolaska"
                                    clearable
                                    v-model="orders.pickup.add_to"
                                    :rules="[user.rules.required, user.rules.validStr]"
                                ></v-text-field>
                            </v-card-text>
                            <v-card-actions>
                                <v-btn color="success"
                                    type="submit"
                                >Potvrdi</v-btn>
                                <v-btn color="error"
                                    @click="orders.clearPickup"
                                >Obriši</v-btn>
                                <v-btn color="error"
                                    @click="orders.addressDialog = false"
                                >Zatvori</v-btn>
                            </v-card-actions>
                        </v-form>
                    </v-card>
                </v-dialog>
                
                <v-dialog
                    v-model="orders.plsDialog"
                    max-width="75%"
                    transition="dialog-transition"
                >
                    <template v-slot:activator="{props: activatorPropsS}">
                        <v-btn
                            variant="elevated"
                            color="indigo-darken-4"
                            width="20%"
                            prepend-icon="mdi-pencil-circle"
                            v-bind="activatorPropsS"
                            @click="orders.places(order)"
                        >
                            Broj mesta
                        </v-btn>
                    </template>
                    <v-card>
                        <v-form>
                            <v-card-title primary-title>
                                Izaberi broj mesta
                            </v-card-title>
                            <v-card-text>
                                <v-number-input
                                    control-variant="split"
                                    :max="7"
                                    :min="1"
                                    v-model="orders.seatsUp.seats"
                                    v-on:update:model-value="orders.calculateNewPrice"
                                ></v-number-input>
                                <div>
                                    <p>Trenutna cena: {{ orders.currentPrice }}</p>
                                    <p v-if="orders.newPrice">Nova cena: {{ orders.newPrice }} </p>
                                </div>
                            </v-card-text>
                            <v-card-actions>
                                <v-dialog
                                    v-model="orders.plsConfDialog"
                                    
                                    max-width="75%"
                                    transition="dialog-transition"
                                >
                                    <template v-slot:activator="{props: activatorPropsC}">
                                        <v-btn
                                            color="success"
                                            v-bind="activatorPropsC"
                                        >
                                            Potvrdi
                                        </v-btn>
                                    </template>
                                    <v-card>
                                        <v-card-title primary-title>
                                            Da li ste sigurni da želite da promenite broj mesta?
                                        </v-card-title>
                                        <v-card-text>
                                            <p>Novi broj mesta: {{ orders.seatsUp.seats }} </p>
                                            <p>Nova cena: {{ orders.newPrice }} </p>
                                        </v-card-text>
                                        <v-card-actions>
                                            <v-btn color="success"
                                                type="submit"
                                                @click="orders.actions.changePlaces"
                                            >Prihvati</v-btn>
                                            <v-btn color="error"
                                                @click="orders.clsSeats"
                                            >Odustani</v-btn>
                                        </v-card-actions>
                                    </v-card>
                                </v-dialog>
                                
                                <v-btn color="error"
                                    @click="orders.clsSeats"
                                >Zatvori</v-btn>
                            </v-card-actions>
                        </v-form>
                    </v-card>
                </v-dialog>

                <!--  Start reschedule  -->

                <v-dialog
                    v-model="orders.dateDialog"
                    max-width="75%"
                    transition="dialog-transition"
                >
                    <template v-slot:activator="{props: activatorProps}">
                        <v-btn
                            variant="elevated"
                            color="indigo-darken-4"
                            width="20%"
                            prepend-icon="mdi-pencil-circle"
                            v-bind="activatorProps"
                            @click="orders.prepareDates(order.from, order.to, order.id)"
                        >
                            Datum
                        </v-btn>
                    </template>
                    <v-card>
                        <v-form>
                            <v-card-title primary-title>
                                Izaberi novi datum
                            </v-card-title>
                            <v-card-text>
                                <v-date-input  
                                    :rules="[search.rules.required]"
                                    @update:model-value="orders.onRequestDate"
                                    label="Novi Datum Polaska" 
                                    :allowed-dates="search.isDateAllowed"
                                >
                                <template #day="{ date }">
                                    <div
                                        :class="[
                                        'v-btn',
                                        'v-size-default',
                                        {
                                            'bg-red-darken-2 text-white pointer-events-none' : search.allowedDays.fullyBooked.includes(date),
                                            'opacity-50 pointer-events-none': !search.isDateAllowed(date)
                                        }
                                        ]"
                                    >
                                        {{ new Date(date).getDate() }}
                                    </div>
                                </template>
                                </v-date-input>
                                <v-date-input  
                                    :rules="[search.rules.required]"
                                    @update:model-value="orders.onRequestDateIn"
                                    label="Novi Datum Povratka" 
                                    :allowed-dates="search.isDateInAllowed"
                                >
                                <template #day="{ date }">
                                    <div
                                        :class="[
                                        'v-btn',
                                        'v-size-default',
                                        {
                                            'bg-red-darken-2 text-white pointer-events-none' : search.allowedDaysIn.fullyBooked.includes(date),
                                            'opacity-50 pointer-events-none': !search.isDateInAllowed(date)
                                        }
                                        ]"
                                    >
                                        {{ new Date(date).getDate() }}
                                    </div>
                                </template>
                                </v-date-input>
                            </v-card-text>
                            <v-card-actions>
                                <v-dialog
                                    v-model="orders.dateConfDialog"
                                    
                                    max-width="75%"
                                    transition="dialog-transition"
                                >
                                    <template v-slot:activator="{props: activatorPropsC}">
                                        <v-btn
                                            color="success"
                                            v-bind="activatorPropsC"
                                            
                                            @click="orders.checkDates"
                                        >
                                            Potvrdi
                                        </v-btn>
                                    </template>
                                    <v-card>
                                        <v-card-title primary-title class="text-center">
                                            <p style="color: #D50000;">Da li ste sigurni da želite da promenite datum?</p>
                                        </v-card-title>
                                        <v-card-text class="w-75 d-flex justify-space-evenly">
                                            <div class="pa-3">
                                                <p class="align-self-center text-center" >Polazak</p>
                                                <p class="ma-3" >Novi datum: <span style="color: #00C853;">{{ orders.requestDateView }}</span> </p>
                                                <p class="ma-3" >Trenutni datum: <span style="color: #D81B60;">{{ orders.currentDate }} </span></p>
                                            </div>
                                            <div class="pa-3">
                                                <p class="justify-self-center text-center" >Povratak</p>
                                                <p class="ma-3" >Novi datum: <span style="color: #00C853;">{{ orders.requestDateInView }}</span> </p>
                                                <p class="ma-3" >Trenutni datum: <span style="color: #EC407A;">{{ orders.currentDateIn }}</span> </p>
                                            </div>
                                        </v-card-text>
                                        <v-card-actions>
                                            <v-btn color="success"
                                                type="submit"
                                                @click="orders.actions.reschedule"
                                            >Prihvati</v-btn>
                                            <v-btn color="error"
                                                @click="orders.clsReschedule"
                                            >Odustani</v-btn>
                                        </v-card-actions>
                                    </v-card>
                                </v-dialog>
                                
                                <v-btn color="error"
                                    @click="orders.clsReschedule"
                                >Zatvori</v-btn>
                            </v-card-actions>
                        </v-form>
                    </v-card>
                </v-dialog>

                <!--  End reschedule  -->

                <v-dialog
                    v-model="orders.delDialog"
                    max-width="75%"
                    transition="dialog-transition"
                >
                    <template v-slot:activator="{props: activatorPropsD}">
                        <v-btn
                            variant="elevated"
                            color="red-darken-4"
                            width="20%"
                            v-bind="activatorPropsD"
                            @click="orders.deleteRequest(order.id)"
                        >
                            Obriši
                        </v-btn>
                    </template>
                    <v-card>
                        <v-card-title primary-title>
                            <p style="color: #D50000;">Da li ste sigurni da želite da obrišete ovu vožnju?</p>
                        </v-card-title>
                        <v-card-actions>
                            <v-btn color="success"
                                @click="orders.actions.cancel"
                            >Potvrdi</v-btn>
                            <v-btn color="error"
                                @click="orders.deleteDeny"
                            >Odustani</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </v-card-actions>
            <v-card-actions v-if="order.deleted == 1"
                class="d-flex justify-center align-center"
            >
                <v-btn 
                    variant="elevated"
                    color="red-darken-4"
                    width="30%"
                >Obrisano</v-btn>
            </v-card-actions>
        </v-card>    
        
    </v-container>
</template>