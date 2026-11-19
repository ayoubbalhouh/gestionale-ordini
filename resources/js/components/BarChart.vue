<script setup>
import { computed } from 'vue';
import { euro } from '../format';

const props = defineProps({ data: Array });
const max = computed(() => Math.max(1, ...props.data.map((d) => d.imponibile_cents)));
</script>

<template>
  <div class="chart" role="img" :aria-label="'Fatturato: ' + data.map((d) => d.mese + ' ' + euro(d.imponibile_cents)).join(', ')">
    <div v-for="(d, i) in data" :key="i" class="chart-col" :class="{ last: i === data.length - 1 }">
      <span class="chart-val">{{ euro(d.imponibile_cents).replace(',00', '') }}</span>
      <div class="chart-bar" :style="{ height: (d.imponibile_cents / max) * 100 + '%' }"></div>
      <span class="chart-label">{{ d.mese }}</span>
    </div>
  </div>
</template>
