<script setup>
import { onMounted, onBeforeUnmount, ref } from "vue";
import Icon from "./Icon.vue";
defineProps({ title: String, busy: Boolean, small: Boolean });
const emit = defineEmits(["close"]);
const dialog = ref(null);
const previousFocus = document.activeElement;
onMounted(() => dialog.value.showModal());
onBeforeUnmount(() => {
  dialog.value?.close();
  previousFocus?.focus();
});
</script>

<template>
  <dialog
    ref="dialog"
    :class="['modal', { 'modal-small': small }]"
    aria-labelledby="modal-title"
    @cancel.prevent="!busy && emit('close')"
    @click="$event.target === dialog && !busy && emit('close')"
  >
    <div class="modal-heading">
      <h2 id="modal-title">{{ title }}</h2>
      <button
        class="icon-button"
        aria-label="Fechar janela"
        :disabled="busy"
        @click="emit('close')"
      >
        <Icon name="close" />
      </button>
    </div>
    <slot />
  </dialog>
</template>
