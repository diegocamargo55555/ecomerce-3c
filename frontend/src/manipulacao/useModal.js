import { onMounted, onBeforeUnmount, ref } from "vue";

export function useModal(emit) {
  const dialog = ref(null);
  const previousFocus = document.activeElement;
  onMounted(() => dialog.value.showModal());
  onBeforeUnmount(() => {
    dialog.value?.close();
    previousFocus?.focus();
  });

  return {
    dialog,
  };
}
