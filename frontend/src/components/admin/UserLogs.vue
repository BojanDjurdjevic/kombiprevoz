<script setup>
import { useAdminStore } from '@/stores/admin';
import { useUserStore } from '@/stores/user';


const user = useUserStore();
const admin = useAdminStore();

</script>

<template>
  <v-card elevation="3">
    <!-- HEADER -->
    <v-card-title class="d-flex align-center justify-space-between">
        <div>
            <v-icon class="mr-2" color="primary">mdi-history</v-icon>
            <span class="text-h6">Istorija promena</span>
        </div>
        <v-btn
            icon="mdi-close"
            color="indigo-darken-3"
            @click="user.profileHistory = false"
        ></v-btn>
    </v-card-title>

    <v-divider />

    <v-card-text>
      <!-- OBICAN KORISNIK -->
      <div
        v-if="user.user.status !== 'Superadmin' && user.user.status !== 'Admin'"
      >
        <v-alert
          v-if="!user.logs || user.logs.length === 0"
          type="info"
          variant="tonal"
          border="start"
          class="mb-4"
        >
          Nema zabeleženih izmena profila.
        </v-alert>

        <v-list v-else density="compact">
          <v-list-item
            v-for="(log, i) in user.logs"
            :key="i"
          >
            <template #prepend>
              <v-icon color="grey-darken-1">mdi-pencil</v-icon>
            </template>

            <v-list-item-title>
              Izmenjeno polje:
              <strong>{{ log.field_changed }}</strong>
            </v-list-item-title>

            <v-list-item-subtitle>
              {{ new Date(log.created_at).toLocaleString('sr-RS') }}
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </div>

      <!-- ADMIN -->
      <div v-else>
        <v-table density="compact" hover>
          <thead>
            <tr>
              <th>Datum</th>
              <th>Ko je menjao</th>
              <th>Akcija</th>
              <th>Polje</th>
              <th>Stara vrednost</th>
              <th>Nova vrednost</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="log in admin.userLogs"
              :key="log.id"
            >
              <td>
                {{ new Date(log.created_at).toLocaleString('sr-RS') }}
              </td>
              <td>{{ log.changed_by_name }}</td>
              <td>
                <v-chip
                  size="small"
                  color="primary"
                  variant="tonal"
                >
                  {{ log.action }}
                </v-chip>
              </td>
              <td>{{ log.field_changed }}</td>
              <td class="text-grey">
                {{ log.old_value || '—' }}
              </td>
              <td>
                {{ log.new_value || '—' }}
              </td>
              <td class="text-caption">
                {{ log.ip_address }}
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>
    </v-card-text>
  </v-card>
</template>
