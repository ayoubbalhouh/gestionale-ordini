<script setup>
import { STATI } from '../format';
defineProps({ stato: String });
</script>

<template>
  <span class="badge" :class="'s-' + stato">{{ STATI[stato]?.label || stato }}</span>
</template>
