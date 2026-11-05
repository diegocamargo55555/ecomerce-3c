<script setup>
import { date, money, statuses } from "@/utils/format";
import AppIcon from "./AppIcon.vue";
defineProps({ order: Object, admin: Boolean, busy: Boolean });
defineEmits(["status"]);
</script>
<template>
  <article class="order-card">
    <div class="order-top">
      <div>
        <span class="eyebrow">{{ date(order.created_at) }}</span>
        <h2>Pedido #{{ order.id }}</h2>
        <p v-if="admin" class="muted">
          {{ order.customer?.name }} · {{ order.customer?.email }}
        </p>
      </div>
      <div class="order-meta">
        <span class="badge" :class="`badge-${order.status}`">{{
          statuses[order.status]
        }}</span
        ><strong>{{ money(order.total) }}</strong>
      </div>
    </div>
    <details class="order-details">
      <summary>Ver detalhes <AppIcon name="chevron" :size="16" /></summary>
      <div class="order-body">
        <div class="order-line" v-for="item in order.items" :key="item.id">
          <span
            >{{ item.quantity }} × {{ item.product_name
            }}<small>{{ money(item.unit_price) }} por unidade</small></span
          ><strong>{{ money(item.subtotal) }}</strong>
        </div>
        <div v-if="order.address" class="address-preview">
          <span class="eyebrow">Endereço de entrega</span>
          <p>
            <strong>{{ order.address.recipient_name }}</strong
            ><br />{{ order.address.street }}, {{ order.address.number
            }}<template v-if="order.address.complement">
              · {{ order.address.complement }}</template
            ><br />{{ order.address.neighborhood }} ·
            {{ order.address.city }}/{{ order.address.state }}<br />CEP
            {{ order.address.zip_code }}
          </p>
        </div>
        <div class="summary-row">
          <span>Frete</span
          ><span class="text-green">{{
            Number(order.shipping_cost) ? money(order.shipping_cost) : "Grátis"
          }}</span>
        </div>
      </div>
    </details>
    <div v-if="admin && order.status === 'pendente'" class="order-actions">
      <button
        class="btn btn-danger-outline"
        :disabled="busy"
        @click="$emit('status', 'cancelado')"
      >
        Cancelar pedido</button
      ><button
        class="btn btn-primary"
        :disabled="busy"
        @click="$emit('status', 'pago')"
      >
        <AppIcon name="check" :size="17" /> Marcar como pago
      </button>
    </div>
  </article>
</template>
