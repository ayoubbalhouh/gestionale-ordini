<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';

defineProps({ title: String, wide: Boolean });
const emit = defineEmits(['close']);
const box = ref(null);

const onKey = (e) => { if (e.key === 'Escape') emit('close'); };
onMounted(() => {
    document.addEventListener('keydown', onKey);
    box.value?.querySelector('input, select, textarea')?.focus();
});
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('close')">
    <div ref="box" class="modal" :class="{ wide }" role="dialog" aria-modal="true" :aria-label="title">
      <header class="modal-head"><h2>{{ title }}</h2><button class="icon-btn" aria-label="Chiudi" @click="emit('close')">×</button></header>
      <slot />
    </div>
  </div>
</template>
