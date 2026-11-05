<script setup>
import AppIcon from "@/components/AppIcon.vue";
</script>
<template>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <span class="eyebrow">Administração</span>
      <h2>Painel da loja</h2>
      <nav aria-label="Administração">
        <RouterLink to="/admin/produtos"
          ><AppIcon name="box" /> Produtos</RouterLink
        ><RouterLink to="/admin/categorias"
          ><AppIcon name="tag" /> Categorias</RouterLink
        ><RouterLink to="/admin/pedidos"
          ><AppIcon name="bag" /> Pedidos</RouterLink
        >
      </nav>
      <RouterLink class="admin-back" to="/"
        ><AppIcon name="back" :size="17" /> Voltar à loja</RouterLink
      >
    </aside>
    <div class="admin-content"><RouterView /></div>
  </div>
</template>
