<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-8">
      <!-- Toolbar (Screen only) -->
      <div class="p-4 bg-slate-100 border-b border-slate-200 flex items-center justify-between print:hidden">
        <div class="flex items-center gap-2">
          <span class="font-mono text-xs font-bold text-slate-700">Official Receipt Statement</span>
          <span
            :class="invoice?.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : (invoice?.status === 'partially_paid' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800')"
            class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono"
          >
            {{ invoice?.status }}
          </span>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="printReceipt"
            class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Receipt / Invoice
          </button>
          <button
            @click="$emit('close')"
            class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>

      <!-- Printable Receipt Document -->
      <div id="printable-receipt-content" class="p-8 space-y-6 text-slate-800">
        <!-- Hospital Branding Header -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-6">
          <div>
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-base">
                +
              </div>
              <h1 class="text-xl font-black text-slate-900 tracking-tight">Metro General Hospital</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">100 Medical Center Parkway, Metropolis &bull; Tel: +1 (555) 019-2830</p>
            <p class="text-[11px] text-slate-400 font-mono">Tax ID: TAX-MID-5544 &bull; billing@metrohospital.org</p>
          </div>

          <div class="text-right">
            <div class="text-xs font-mono uppercase tracking-wider text-slate-400 font-bold">Tax Invoice / Receipt</div>
            <div class="text-lg font-black font-mono text-slate-900 mt-0.5">{{ invoice?.invoice_number }}</div>
            <div class="text-xs text-slate-500 font-mono mt-1">Date: {{ formatDate(invoice?.created_at) }}</div>
          </div>
        </div>

        <!-- Patient & Billing Metadata -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs">
          <div>
            <span class="text-[10px] font-mono uppercase text-slate-400 block font-bold">Patient Name</span>
            <strong class="text-slate-900 font-semibold">{{ invoice?.patient?.full_name || (invoice?.patient?.first_name + ' ' + invoice?.patient?.last_name) }}</strong>
          </div>
          <div>
            <span class="text-[10px] font-mono uppercase text-slate-400 block font-bold">Patient MRN</span>
            <span class="font-mono text-slate-700 font-bold">{{ invoice?.patient?.mrn }}</span>
          </div>
          <div>
            <span class="text-[10px] font-mono uppercase text-slate-400 block font-bold">Department</span>
            <span class="capitalize text-slate-700 font-medium">{{ invoice?.department }} ({{ invoice?.billing_type?.toUpperCase() }})</span>
          </div>
          <div>
            <span class="text-[10px] font-mono uppercase text-slate-400 block font-bold">Attending Doctor</span>
            <span class="text-slate-700 font-medium">{{ invoice?.doctor?.name || 'General Clinic' }}</span>
          </div>
        </div>

        <!-- Itemized Line Items -->
        <div>
          <h2 class="text-xs font-mono uppercase font-bold text-slate-400 tracking-wider mb-2">Itemized Medical Charges</h2>
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-100 text-slate-600 font-mono uppercase text-[10px] border-y border-slate-200">
              <tr>
                <th class="py-2.5 px-3">Description</th>
                <th class="py-2.5 px-3 text-center">Type</th>
                <th class="py-2.5 px-3 text-center">Qty</th>
                <th class="py-2.5 px-3 text-right">Unit Price</th>
                <th class="py-2.5 px-3 text-right">Discount</th>
                <th class="py-2.5 px-3 text-right">Amount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="item in invoice?.items" :key="item.id">
                <td class="py-2.5 px-3">
                  <div class="font-semibold text-slate-900">{{ item.description }}</div>
                  <div v-if="item.item_code" class="text-[10px] font-mono text-slate-400">{{ item.item_code }}</div>
                </td>
                <td class="py-2.5 px-3 text-center capitalize text-slate-500 text-[11px]">{{ item.item_type }}</td>
                <td class="py-2.5 px-3 text-center font-mono font-medium">{{ item.quantity }}</td>
                <td class="py-2.5 px-3 text-right font-mono">${{ item.unit_price.toFixed(2) }}</td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-400">
                  {{ item.discount_cents > 0 ? '-$' + item.discount.toFixed(2) : '$0.00' }}
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">${{ item.total.toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Financial Summary Totals (Exact Reconciliation) -->
        <div class="flex justify-end pt-2">
          <div class="w-72 space-y-2 text-xs border-t border-slate-200 pt-3">
            <div class="flex justify-between text-slate-600">
              <span>Subtotal:</span>
              <span class="font-mono font-medium">${{ (invoice?.subtotal || 0).toFixed(2) }}</span>
            </div>

            <div v-if="invoice?.discount_cents > 0" class="flex justify-between text-emerald-700">
              <span>Discounts Applied:</span>
              <span class="font-mono font-bold">-${{ (invoice?.discount || 0).toFixed(2) }}</span>
            </div>

            <div v-if="invoice?.tax_cents > 0" class="flex justify-between text-slate-600">
              <span>Sales Tax:</span>
              <span class="font-mono font-medium">${{ (invoice?.tax || 0).toFixed(2) }}</span>
            </div>

            <div class="flex justify-between text-sm font-black text-slate-900 border-t border-slate-300 pt-2">
              <span>Invoice Total:</span>
              <span class="font-mono">${{ (invoice?.total || 0).toFixed(2) }}</span>
            </div>

            <div class="flex justify-between text-emerald-700 font-bold border-t border-slate-100 pt-1">
              <span>Total Paid:</span>
              <span class="font-mono">${{ (invoice?.paid || 0).toFixed(2) }}</span>
            </div>

            <div class="flex justify-between font-black text-sm p-2 rounded-lg bg-slate-100 border border-slate-200">
              <span :class="invoice?.balance_cents > 0 ? 'text-rose-700' : 'text-slate-800'">Outstanding Balance:</span>
              <span class="font-mono" :class="invoice?.balance_cents > 0 ? 'text-rose-700' : 'text-slate-800'">
                ${{ (invoice?.balance || 0).toFixed(2) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Payment History Receipts -->
        <div v-if="invoice?.payments?.length > 0" class="border-t border-slate-200 pt-4">
          <h3 class="text-xs font-mono uppercase font-bold text-slate-400 tracking-wider mb-2">Recorded Payment Receipts</h3>
          <div class="space-y-1.5">
            <div
              v-for="p in invoice.payments"
              :key="p.id"
              class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-mono"
            >
              <div>
                <span class="font-bold text-slate-900">{{ p.receipt_number }}</span>
                <span class="text-slate-400 mx-2">&bull;</span>
                <span class="capitalize text-slate-600">{{ p.payment_mode.replace('_', ' ') }}</span>
                <span v-if="p.transaction_reference" class="text-slate-400 text-[10px] ml-2">({{ p.transaction_reference }})</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="text-slate-400 text-[10px]">{{ formatDate(p.received_at) }}</span>
                <strong class="text-emerald-700">${{ p.amount.toFixed(2) }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- Signature & Footer -->
        <div class="border-t border-slate-200 pt-6 flex items-end justify-between text-[10px] text-slate-400">
          <div>
            <p>Thank you for choosing Metro General Hospital.</p>
            <p>For billing inquiries, please present this receipt to the Patient Accounts Desk.</p>
          </div>
          <div class="text-center">
            <div class="w-40 border-b border-slate-300 pb-8 mb-1"></div>
            <span>Authorized Cashier Signature</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  invoice: {
    type: Object,
    default: null,
  },
});

defineEmits(['close']);

function printReceipt() {
  window.print();
}

function formatDate(isoStr) {
  if (!isoStr) return '--';
  return new Date(isoStr).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
