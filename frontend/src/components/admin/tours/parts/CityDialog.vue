<script setup>
import { useAdminStore } from '@/stores/admin'

const admin = useAdminStore()
</script>

<template>
  <v-dialog
    v-model="admin.cityDialog"
    fullscreen
    transition="dialog-bottom-transition"
    persistent
  >
    <v-card class="d-flex flex-column">

      <!-- HEADER -->
      <v-toolbar color="indigo-darken-4">
        <v-btn icon @click="admin.closeCityDialog">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <v-toolbar-title>
          Grad: {{ admin.myCity?.name }}
        </v-toolbar-title>
      </v-toolbar>

      <!-- CONTENT -->
      <v-card-text class="flex-grow-1">
        <v-container fluid>

          <!-- TITLE -->
          <v-row justify="center">
            <v-col cols="12" class="text-center">
              <h2>{{ admin.myCity?.name }}</h2>
            </v-col>
          </v-row>

          <!-- ACTIVE IMAGES -->
          <v-row v-if="admin.myCityPics.length > 0" class="mt-4">
            <v-col cols="12">
              <h3 class="text-center mb-4">Aktivne slike</h3>

              <v-item-group
                v-model="admin.selectedPictures"
                multiple
                class="d-flex flex-wrap justify-center"
              >
                <v-item
                  v-for="photo in admin.myCityPics"
                  :key="photo.photo_id"
                  :value="photo.photo_id"
                >
                  <template #default="{ isSelected, toggle }">
                    <div
                      class="ma-2 position-relative"
                      style="cursor:pointer"
                      @click="toggle"
                    >
                      <v-img
                        :src="photo.file_path"
                        width="120"
                        height="120"
                        cover
                        class="rounded-lg elevation-3"
                      />

                      <div
                        v-if="isSelected"
                        class="position-absolute d-flex align-center justify-center"
                        style="
                          inset: 0;
                          background: rgba(0,0,0,0.4);
                          border-radius: 12px;
                        "
                      >
                        <v-icon size="36" color="white">
                          mdi-check-circle
                        </v-icon>
                      </div>
                    </div>
                  </template>
                </v-item>
              </v-item-group>

              <v-row justify="center" class="mt-4">
                <v-col cols="12" sm="6">
                  <v-btn
                    block
                    color="red-darken-4"
                    :disabled="admin.selectedPictures.length === 0"
                    @click="admin.actions.deleteSelected"
                  >
                    Obriši selektovane ({{ admin.selectedPictures.length }})
                  </v-btn>
                </v-col>
              </v-row>
            </v-col>
          </v-row>

          <!-- NO ACTIVE -->
          <v-row v-else class="mt-6">
            <v-col cols="12" class="text-center">
              <p>Trenutno nema aktivnih slika za ovaj grad</p>
            </v-col>
          </v-row>

          <!-- DELETED IMAGES -->
          <v-row v-if="admin.cityDeletedPics.length > 0" class="mt-10">
            <v-col cols="12">
              <h3 class="text-center mb-4">Neaktivne slike</h3>

              <v-item-group
                v-model="admin.unSelectedPictures"
                multiple
                class="d-flex flex-wrap justify-center"
              >
                <v-item
                  v-for="photo in admin.cityDeletedPics"
                  :key="photo.photo_id"
                  :value="photo.photo_id"
                >
                  <template #default="{ isSelected, toggle }">
                    <div
                      class="ma-2 position-relative"
                      style="cursor:pointer"
                      @click="toggle"
                    >
                      <v-img
                        :src="photo.file_path"
                        width="120"
                        height="120"
                        cover
                        class="rounded-lg elevation-2"
                      />

                      <div
                        v-if="isSelected"
                        class="position-absolute d-flex align-center justify-center"
                        style="
                          inset: 0;
                          background: rgba(0,0,0,0.4);
                          border-radius: 12px;
                        "
                      >
                        <v-icon size="36" color="white">
                          mdi-check-circle
                        </v-icon>
                      </div>
                    </div>
                  </template>
                </v-item>
              </v-item-group>

              <v-row justify="center" class="mt-4">
                <v-col cols="12" sm="6">
                  <v-btn
                    block
                    color="red-darken-4"
                    :disabled="admin.unSelectedPictures.length === 0"
                    @click="admin.actions.restoreSelected"
                  >
                    Aktiviraj selektovane ({{ admin.unSelectedPictures.length }})
                  </v-btn>
                </v-col>
              </v-row>
            </v-col>
          </v-row>

          <!-- UPLOAD -->
          <v-row justify="center" class="mt-12">
            <v-col cols="12" sm="8" md="6" class="text-center">
              <v-file-input
                v-model="admin.cityPics"
                label="Dodaj nove slike"
                multiple
                chips
                clearable
                @update:model-value="admin.selectCityPics"
                @click:clear="admin.clearCityPics"
              />

              <div
                v-if="admin.cityPreview"
                class="d-flex flex-wrap justify-center mt-4"
                :key="admin.cityPreviewKey"
              >
                <img
                  v-for="(p, index) in admin.cityPreview"
                  :key="index"
                  :src="p"
                  style="
                    max-height: 5rem;
                    max-width: 5rem;
                    border-radius: 50%;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                    margin: 4px;
                  "
                />
              </div>

              <v-btn
                block
                color="green-darken-3"
                class="mt-4"
                :disabled="!admin.cityPics"
                @click="admin.actions.addCityPics"
              >
                Dodaj slike
              </v-btn>
            </v-col>
          </v-row>

        </v-container>
      </v-card-text>

      <!-- FOOTER -->
      <v-card-actions>
        <v-btn block color="success" @click="admin.closeCityDialog">
          Zatvori
        </v-btn>
      </v-card-actions>

    </v-card>
  </v-dialog>
</template>
