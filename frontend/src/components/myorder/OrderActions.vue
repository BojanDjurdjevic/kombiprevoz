<script setup>
import EditAddressDialog from "../myorderdialogs/EditAddressDialog.vue";
import EditPlacesDialog from "../myorderdialogs/EditPlacesDialog.vue";
import RescheduleDialog from "../myorderdialogs/RescheduleDialog.vue";
import DeleteDialog from "../myorderdialogs/DeleteDialog.vue";

defineProps({
  order: Object
})
</script>

<template>
  <v-card-actions class="d-flex flex-column ga-2">

    <EditAddressDialog :order="order" />
    <EditPlacesDialog :order="order" />
    <RescheduleDialog :order="order" />
    <DeleteDialog :orderId="order.id" />

  </v-card-actions>
</template>
