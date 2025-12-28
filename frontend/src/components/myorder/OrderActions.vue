<script setup>
import EditAddressDialog from "../myorderdialogs/EditAddressDialog.vue";
import EditPlacesDialog from "../myorderdialogs/EditPlacesDialog.vue";
import RescheduleDialog from "../myorderdialogs/RescheduleDialog.vue";
import DeleteDialog from "../myorderdialogs/DeleteDialog.vue";
import OrderHistoryDialog from "../myorderdialogs/OrderHistoryDialog.vue";
import { useUserStore } from "@/stores/user";

defineProps({
  order: Object
})

const user = useUserStore()
</script>

<template>
  <v-card-actions class="d-flex flex-column ga-2">

    <EditAddressDialog :order="order" />
    <EditPlacesDialog :order="order" />
    <RescheduleDialog :order="order" /> <!--
    <DeleteDialog :orderId="!user.user.is_demo ? order.id : order.tour_id" />-->
    <DeleteDialog :order="order" />
    <OrderHistoryDialog v-if="!user.user.is_demo" :order="order" />

  </v-card-actions>
</template>
