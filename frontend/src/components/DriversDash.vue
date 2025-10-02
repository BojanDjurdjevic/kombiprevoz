<script setup>
import { useAdminStore } from "@/stores/admin";
import { ref } from "vue";

const admin = useAdminStore();
</script>

<template>
  <div
    class="mt-6 pa-3 w-100 d-flex flex-column align-center"
    v-if="admin.adminView == 'Drivers'"
  >
    Drivers
  </div>
</template>
