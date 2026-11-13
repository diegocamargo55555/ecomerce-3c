<script setup>
defineProps({ name: String, size: { type: [String, Number], default: 20 } });
const paths = {
  // logo: omega invertido, desenho próprio (letra grega), não o emblema oficial
  omega: "M3 4h6v3a7.5 7.5 0 1 0 6 0V4h6",
  bag: "M6 7h12l1 14H5L6 7Z M9 7V5a3 3 0 0 1 6 0v2",
  grid: "M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z",
  box: "m12 3 9 5-9 5-9-5 9-5Z M3 8v9l9 5 9-5V8 M12 13v9 M7.5 5.5l9 5",
  tag: "M20 13 11 22 2 13V3h10l8 8a1.5 1.5 0 0 1 0 2Z M7 7h.01",
  orders: "M6 3h12v19l-3-2-3 2-3-2-3 2V3Z M9 8h6 M9 12h6 M9 16h3",
  cart: "M2 3h3l3 12h11l3-9H6 M9 20h.01 M18 20h.01",
  users:
    "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M16 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z",
  pin: "M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z M15 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z",
  wallet:
    "M20 7V4H5a3 3 0 0 0 0 6h16v11H5a3 3 0 0 1-3-3V7 M21 13h-5v4h5 M17 15h.01",
  search: "M21 21l-4.4-4.4 M19 10.5a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z",
  plus: "M12 5v14 M5 12h14",
  arrow: "M7 17 17 7 M7 7h10v10",
  right: "m9 5 7 7-7 7",
  left: "m15 5-7 7 7 7",
  close: "m6 6 12 12 M6 18 18 6",
  edit: "m16 3 5 5-12 12-6 1 1-6L16 3Z M13 6l5 5",
  trash: "M3 6h18 M9 6V3h6v3 M5 6l1 15h12l1-15 M10 10v7 M14 10v7",
  refresh:
    "M20 7v5h-5 M4 17v-5h5 M6 6a8 8 0 0 1 13 2l1 4 M4 12l1 4a8 8 0 0 0 13 2",
  logout: "M9 3H3v18h6 M10 12h12 M17 7l5 5-5 5",
  menu: "M4 6h16 M4 12h16 M4 18h16",
  check: "m5 12 4 4L19 6",
  alert: "M12 9v4 M12 17h.01 M10.3 3 1 19h22L13.7 3a2 2 0 0 0-3.4 0Z",
  sort: "M8 3v18 m-4-4 4 4 4-4 M16 21V3 m-4 4 4-4 4 4",
  eye: "M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z",
  clock: "M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z M12 6v6l4 2",
};
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.65"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path :d="paths[name] || paths.box" />
  </svg>
</template>
