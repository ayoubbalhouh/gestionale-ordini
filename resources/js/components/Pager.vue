<script setup>
defineProps({ meta: Object });
const emit = defineEmits(['go']);
</script>

<template>
  <div v-if="meta && meta.last_page > 1" class="pager">
    <span class="muted">{{ meta.from }}–{{ meta.to }} di {{ meta.total }}</span>
    <div>
      <button class="btn btn-ghost btn-sm" :disabled="meta.current_page === 1" @click="emit('go', meta.current_page - 1)">← Prec.</button>
      <button class="btn btn-ghost btn-sm" :disabled="meta.current_page === meta.last_page" @click="emit('go', meta.current_page + 1)">Succ. →</button>
    </div>
  </div>
</template>
