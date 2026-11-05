<script setup>
import { computed, useId } from "vue";
defineOptions({ inheritAttrs: false });
const props = defineProps({
  modelValue: [String, Number],
  label: String,
  error: [String, Array],
  id: String,
});
defineEmits(["update:modelValue"]);
const generatedId = useId();
const fieldId = computed(() => props.id || generatedId);
const message = computed(() =>
  Array.isArray(props.error) ? props.error[0] : props.error,
);
</script>
<template>
  <div class="field" :class="$attrs.class">
    <label :for="fieldId">{{ label }}</label>
    <input
      :id="fieldId"
      :value="modelValue"
      v-bind="{ ...$attrs, class: undefined }"
      :aria-invalid="message ? true : undefined"
      :aria-describedby="message ? `${fieldId}-error` : undefined"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <small v-if="message" :id="`${fieldId}-error`" class="field-error">{{
      message
    }}</small>
  </div>
</template>
