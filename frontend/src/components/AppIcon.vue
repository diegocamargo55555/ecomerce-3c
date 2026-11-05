<script setup>
defineProps({
  name: { type: String, default: "bag" },
  size: { type: [Number, String], default: 20 },
});
const paths = {
  bag: ["M6 7h12l2 14H4L6 7Z", "M9 8V6a3 3 0 0 1 6 0v2"],
  search: ["m21 21-4.5-4.5", "M19 10.5a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z"],
  cart: ["M2 3h3l3 12h11l3-9H6", "M10 20h.01M18 20h.01"],
  user: [
    "M20 21v-2a6 6 0 0 0-6-6h-4a6 6 0 0 0-6 6v2",
    "M16 5a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z",
  ],
  arrow: ["M4 12h16m-6-6 6 6-6 6"],
  back: ["M20 12H4m6-6-6 6 6 6"],
  check: ["m5 12 4 4L19 6"],
  close: ["m6 6 12 12M6 18 18 6"],
  plus: ["M12 5v14M5 12h14"],
  minus: ["M5 12h14"],
  truck: [
    "M1 4h13v13H1z",
    "M14 9h5l4 5v3h-9",
    "M7 19a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm14 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z",
  ],
  grid: ["M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"],
  box: [
    "m12 2 10 5-10 5L2 7l10-5Zm10 5v10l-10 5-10-5V7",
    "M12 12v10M7 4.5l10 5",
  ],
  tag: ["M20 12 12 20a2 2 0 0 1-3 0L2 13V2h11l7 7a2 2 0 0 1 0 3Z", "M7 7h.01"],
  edit: ["m16 3 5 5-12 12-6 1 1-6L16 3Zm-3 3 5 5"],
  trash: ["M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"],
  chevron: ["m9 5 7 7-7 7"],
  heart: [
    "M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z",
  ],
  laptop: ["M4 3h16v13H4zM2 20h20l-2-4H4l-2 4Z"],
  phone: ["M7 2h10v20H7zM11 18h2"],
  shirt: ["m8 3-6 4 3 5 3-2v11h8V10l3 2 3-5-6-4a4 4 0 0 1-8 0Z"],
  headphones: ["M3 14v-3a9 9 0 0 1 18 0v3M3 12h4v9H3zM17 12h4v9h-4z"],
  keyboard: [
    "M2 6h20v13H2zM5 10h.01M9 10h.01M13 10h.01M17 10h.01M5 13h.01M9 13h.01M13 13h.01M17 13h.01M7 16h10",
  ],
};
</script>
<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path
      v-for="(path, index) in paths[name] || paths.bag"
      :key="index"
      :d="path"
    />
  </svg>
</template>
