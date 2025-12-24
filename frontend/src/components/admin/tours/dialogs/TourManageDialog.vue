<script setup>
import { useAdminStore } from '@/stores/admin';
import TourDetailsCard from '../parts/TourDetailsCard.vue';
import TourStatusActions from '../parts/TourStatusActions.vue';
import TourEditForm from '../parts/TourEditForm.vue';
import { useDisplay } from 'vuetify'
import TourEditAddForm from '../parts/TourEditAddForm.vue';

const { smAndDown } = useDisplay()

const admin = useAdminStore()
</script>

<template>
    <v-dialog v-model="admin.manageTourDialog" max-width="1100">
        <v-card>

            <v-card-title class="flex justify-between items-center">
            Upravljanje rutom
            <v-btn icon="mdi-close" @click="admin.manageTourDialog = false" />
            </v-card-title>

            <v-divider />

            <v-card-text>

            <!-- DESKTOP -->
            <v-row v-if="!smAndDown" dense>
                <v-col cols="5">
                    <TourDetailsCard />
                </v-col>

                <v-col cols="7">
                    <TourEditAddForm mode="edit"  />
                </v-col>
            </v-row>

            <!-- MOBILE -->
            <div v-else class="space-y-4">

                <!-- DETAILS -->
                <v-card class="rounded-xl">
                    <v-chip
                        v-if="smAndDown"
                        color="primary"
                        size="small"
                        class="mb-2"
                        >
                        Detalji rute
                    </v-chip>

                    <TourDetailsCard />
                </v-card>

                <!-- EDIT -->
                <v-card class="rounded-xl">
                    <v-chip
                        v-if="smAndDown"
                        color="primary"
                        size="small"
                        class="mb-2"
                        >
                        Izmena rute
                    </v-chip>
                    <tour-edit-add-form mode="edit" />
                </v-card>

            </div>

            </v-card-text>

            <v-divider />

            <v-card-actions class="w-100 d-flex justify-space-around">
                <TourStatusActions />
            </v-card-actions>

        </v-card>
    </v-dialog>
</template>