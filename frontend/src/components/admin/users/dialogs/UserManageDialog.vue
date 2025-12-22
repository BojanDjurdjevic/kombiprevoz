<script setup>
import { useAdminStore } from "@/stores/admin";

const admin = useAdminStore();
</script>

<template>
  <v-dialog
    v-model="admin.userEditDialog"
    fullscreen
    transition="dialog-bottom-transition"
    persistent
  >
    <v-card>
      <!-- Header -->
      <v-toolbar color="indigo-darken-4">
        <v-btn icon @click="admin.actions.closeUserEditDialog">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <v-toolbar-title>Korisnik: {{ admin.userByAdmin?.name }}</v-toolbar-title>
        <v-spacer></v-spacer>
      </v-toolbar>

      <!-- Body -->
      <v-card-text class="pa-4">
        <h3 class="text-center">Podaci korisnika</h3>
        <div class="w-100 d-flex flex-column md:flex-row">
          <!-- Left column: details -->
          <div class="w-full md:w-1/2 pa-3">
            <p><strong>Ime:</strong> {{ admin.userByAdmin?.name }}</p>
            <p><strong>Status:</strong> {{ admin.userByAdmin?.status }}</p>
            <p><strong>Grad:</strong> {{ admin.userByAdmin?.city }}</p>
            <p><strong>Adresa:</strong> {{ admin.userByAdmin?.address }}</p>
            <p><strong>Email:</strong> {{ admin.userByAdmin?.email }}</p>
            <p><strong>Telefon:</strong> {{ admin.userByAdmin?.phone }}</p>
          </div>

          <!-- Right column: edit actions -->
          <div class="w-full md:w-1/2 pa-3 flex flex-col justify-evenly">
            <v-text-field
              v-model="admin.editedUser.users.name"
              label="Unesi novo ime"
              clearable
            />
            <v-autocomplete
              v-model="admin.editedUser.users.status"
              label="Izmeni status"
              :items="['Admin','Driver','User']"
              clearable
            />
            <v-text-field
              v-model="admin.editedUser.users.city"
              label="Unesi novi grad"
              clearable
            />
            <v-text-field
              v-model="admin.editedUser.users.address"
              label="Unesi novu adresu"
              clearable
            />
            <v-text-field
              v-model="admin.editedUser.users.email"
              label="Unesi novi email"
              clearable
            />
            <v-text-field
              v-model="admin.editedUser.users.phone"
              label="Unesi novi telefon"
              clearable
            />

            <!-- Buttons -->
            <div class="mt-4 flex justify-around">
              <v-btn color="green-darken-4" @click="admin.actions.confirmEditUser">
                Potvrdi
              </v-btn>
              <v-btn color="red-darken-3" @click="admin.actions.resetUserEdit">
                Poništi
              </v-btn>
            </div>
          </div>
        </div>
      </v-card-text>

      <!-- Footer -->
      <v-card-actions>
        <v-btn block color="success" @click="admin.actions.closeUserEditDialog">
          Zatvori
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
