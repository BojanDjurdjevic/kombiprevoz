<script setup>
import { useUserStore } from '@/stores/user';
import AdminNav from '@/components/AdminNav.vue';
    
    const user = useUserStore()

</script>

<template>
    <AdminNav /> 
</template>
