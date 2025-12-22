<script setup>
import { useAdminStore } from '@/stores/admin'
import { useDisplay } from 'vuetify'
import { VDateInput } from 'vuetify/labs/VDateInput'
import { VNumberInput } from 'vuetify/labs/VNumberInput'

const admin = useAdminStore()
const { smAndDown } = useDisplay()
</script>

<template>
  <v-container fluid class="pa-0">

    <v-row dense>
      <!-- EDIT FIELDS -->
      <v-col cols="12" md="8">
        <v-card variant="outlined">
          <v-card-title>Izmena rezervacije</v-card-title>

          <v-card-text>
            <v-row dense>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="admin.changeFromAddress"
                  prepend-icon="mdi-map-marker"
                  label="Nova adresa polaska"
                  clearable
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="admin.changeToAddress"
                  prepend-icon="mdi-map-marker"
                  label="Nova adresa dolaska"
                  clearable
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-date-input
                  v-model="admin.changeDate"
                  label="Novi datum"
                  clearable
                  :allowed-dates="admin.isDateAllowed"
                >
                  <template #day="{ date }">
                    <div
                      :class="[
                        'v-btn',
                        {
                          'red-darken-2 pointer-events-none':
                            admin.allowedDays.fullyBooked.includes(date),
                          'opacity-50 pointer-events-none':
                            !admin.isDateAllowed(date)
                        }
                      ]"
                    >
                      {{ new Date(date).getDate() }}
                    </div>
                  </template>
                </v-date-input>
              </v-col>

              <v-col cols="12" md="6">
                <v-number-input
                  v-model="admin.changeSeats"
                  prepend-icon="mdi-numeric"
                  label="Broj mesta"
                  min="1"
                  max="7"
                  clearable
                />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- ACTIONS -->
      <v-col cols="12" md="4">
        <v-card variant="outlined" class="h-100 d-flex flex-column justify-space-between">

          <v-card-title>Akcije</v-card-title>

          <v-card-text class="d-flex flex-column gap-3">
            <v-btn
              block
              color="green-darken-4"
              @click="admin.actions.manageBookingItems"
            >
              Sačuvaj izmene
            </v-btn>

            <v-btn
              block
              color="red-darken-3"
              variant="outlined"
              @click="admin.actions.clearManageItems"
            >
              Poništi izmene
            </v-btn>
          </v-card-text>

          <v-divider />

          <!-- EXTRA ACTIONS -->
          <v-card-text class="d-flex justify-space-around">
            <v-btn
              icon="mdi-eye"
              color="indigo-darken-3"
              :href="'http://localhost:8080/' + admin.selected?.voucher"
              target="_blank"
            />

            <v-btn
              icon="mdi-email-arrow-right"
              color="green-darken-3"
              @click="admin.actions.resendVoucher"
            />

            <v-btn
              v-if="!admin.selected?.deleted"
              icon="mdi-close-thick"
              color="red-darken-3"
              @click="admin.cancelDialog = true"
            />

            <v-btn
              v-else
              icon="mdi-check-all"
              color="green-darken-3"
              @click="admin.restoreDialog = true"
            />
          </v-card-text>

        </v-card>
      </v-col>
    </v-row>

  </v-container>
</template>
