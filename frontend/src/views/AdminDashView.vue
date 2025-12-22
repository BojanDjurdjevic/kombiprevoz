<script setup>

import { useUserStore } from '@/stores/user';
import AdminNav from '@/components/AdminNav.vue';
    
    const user = useUserStore()


import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminContent from '@/components/admin/AdminContent.vue'

</script>

<template>
    <!--
      <AdminNav />  
    -->
    <AdminLayout>
        <AdminContent />
    </AdminLayout>
</template>
