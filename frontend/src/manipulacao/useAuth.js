import { reactive, ref, watch } from "vue";
import { login } from "../requisicoes/auth/login.js";
import { register } from "../requisicoes/auth/register.js";
import { resourceData } from "../requisicoes/http.js";

export function useAuth(emit) {
  const mode = ref("login");
  const busy = ref(false);
  const showPassword = ref(false);
  const error = ref("");
  const errors = ref({});
  const form = reactive({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
  });
  watch(mode, () => {
    error.value = "";
    errors.value = {};
    form.password = "";
    form.password_confirmation = "";
  });

  async function submit() {
    busy.value = true;
    error.value = "";
    errors.value = {};
    try {
      const payload =
        mode.value === "login"
          ? { email: form.email.trim(), password: form.password }
          : { ...form, name: form.name.trim(), email: form.email.trim() };
      const response = await (mode.value === "login" ? login(payload) : register(payload));
      const user = resourceData(response);
      form.password = "";
      form.password_confirmation = "";
      emit("authenticated", user, response.cart_warnings || []);
    } catch (failure) {
      error.value = failure.message;
      errors.value = failure.errors || {};
    } finally {
      busy.value = false;
    }
  }

  return {
    mode,
    busy,
    showPassword,
    error,
    errors,
    form,
    submit,
  };
}
