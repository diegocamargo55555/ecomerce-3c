<script setup>
import Icon from "./Icon.vue";
import { useModal } from "../manipulacao/useModal.js";

defineProps({ title: String, busy: Boolean, small: Boolean });
const emit = defineEmits(["close"]);
const {
  dialog,
} = useModal(emit);
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
