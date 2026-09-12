<template>
  <div class="space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-blue-600 font-bold">
          <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
          Hospital Cashier & Front Desk
        </div>
        <h1 class="text-xl font-black text-slate-900 mt-1">Patient Billing Counter</h1>
        <p class="text-xs text-slate-500 mt-0.5">Itemized invoicing, multi-mode payment collection, receipts, and discounts.</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="openNewInvoiceModal"
          class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-blue-500/20 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          + Generate New Invoice
        </button>
      </div>
    </div>

    <!-- Filter Tabs & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-semibold">
        <button
          v-for="st in ['all', 'unpaid', 'partially_paid', 'paid']"
          :key="st"
          @click="statusFilter = st; fetchInvoices()"
          :class="statusFilter === st ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
          class="px-3.5 py-1.5 rounded-lg capitalize transition cursor-pointer"
        >
          {{ st.replace('_', ' ') }}
        </button>
      </div>

      <div class="relative w-full sm:w-80">
        <input
          v-model="searchQuery"
          @input="fetchInvoices"
          type="text"
          placeholder="Search by Invoice #, MRN, or Name..."
          class="w-full text-xs pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <svg class="w-3.5 h-3.5 absolute left-2.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>

    <!-- Invoices List Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-xs text-slate-400">
        Loading invoices...
      </div>
      <div v-else-if="invoices.length === 0" class="p-12 text-center text-xs text-slate-400">
        No invoices found matching current filter.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Invoice #</th>
              <th class="p-4">Patient</th>
              <th class="p-4">Billing Type</th>
              <th class="p-4 text-right">Total</th>
              <th class="p-4 text-right">Paid</th>
              <th class="p-4 text-right">Balance Due</th>
              <th class="p-4 text-center">Status</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-50/60 transition">
              <td class="p-4 font-mono font-bold text-slate-900">
                <a @click.prevent="openReceiptModal(inv)" href="#" class="text-blue-600 hover:underline">
                  {{ inv.invoice_number }}
                </a>
              </td>
              <td class="p-4">
                <div class="font-bold text-slate-900">{{ inv.patient?.full_name || (inv.patient?.first_name + ' ' + inv.patient?.last_name) }}</div>
                <div class="text-[10px] text-slate-400 font-mono">MRN: {{ inv.patient?.mrn }}</div>
              </td>
              <td class="p-4">
                <span class="capitalize font-medium text-slate-800">{{ inv.department }}</span>
                <span class="text-[10px] text-slate-400 uppercase font-mono block">{{ inv.billing_type }}</span>
              </td>
              <td class="p-4 text-right font-mono font-bold text-slate-900">${{ inv.total.toFixed(2) }}</td>
              <td class="p-4 text-right font-mono text-emerald-700 font-medium">${{ inv.paid.toFixed(2) }}</td>
              <td class="p-4 text-right font-mono font-bold">
                <span :class="inv.balance_cents > 0 ? 'text-rose-600' : 'text-slate-400'">
                  ${{ inv.balance.toFixed(2) }}
                </span>
              </td>
              <td class="p-4 text-center">
                <span
                  :class="inv.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : (inv.status === 'partially_paid' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800')"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono"
                >
                  {{ inv.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    v-if="inv.balance_cents > 0"
                    @click="openPaymentModal(inv)"
                    class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition shadow-xs"
                  >
                    Collect Pay
                  </button>
                  <button
                    @click="openReceiptModal(inv)"
                    class="px-2 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 text-[11px] font-medium transition"
                    title="View & Print Statement Receipt"
                  >
                    Receipt
                  </button>
                  <button
                    v-if="inv.balance_cents > 0"
                    @click="openDiscountModal(inv)"
                    class="px-2 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 text-[11px] font-medium transition"
                    title="Request discount"
                  >
                    Discount
                  </button>
                  <button
                    v-if="inv.paid_cents > 0"
                    @click="openRefundModal(inv)"
                    class="px-2 py-1 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 text-[11px] font-medium transition"
                    title="Request refund"
                  >
                    Refund
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL 1: GENERATE INVOICE -->
    <div v-if="isInvoiceModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-8">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900">Generate Itemized Bill / Invoice</h3>
            <p class="text-xs text-slate-500">Add medical consultations, procedures, investigations, or drugs.</p>
          </div>
          <button @click="isInvoiceModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitInvoice" class="p-6 space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Patient</label>
              <select
                v-model="invoiceForm.patient_id"
                required
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500"
              >
                <option value="" disabled>Select patient</option>
                <option v-for="p in patientsList" :key="p.id" :value="p.id">
                  {{ p.first_name }} {{ p.last_name }} (MRN: {{ p.mrn }})
                </option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Billing Classification</label>
              <select
                v-model="invoiceForm.billing_type"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 capitalize"
              >
                <option value="opd">Outpatient (OPD)</option>
                <option value="ipd">Inpatient (IPD)</option>
                <option value="pharmacy">Pharmacy</option>
                <option value="diagnostic">Diagnostics (Lab/Imaging)</option>
                <option value="emergency">Emergency</option>
              </select>
            </div>
          </div>

          <!-- Line Items Table -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="font-bold text-slate-700">Line Items & Services</label>
              <button
                type="button"
                @click="addInvoiceItemRow"
                class="text-xs text-blue-600 font-bold hover:text-blue-800 cursor-pointer"
              >
                + Add Item
              </button>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
              <div
                v-for="(row, idx) in invoiceForm.items"
                :key="idx"
                class="p-3 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-12 gap-2 items-center text-xs"
              >
                <div class="col-span-5">
                  <input
                    v-model="row.description"
                    type="text"
                    required
                    placeholder="Description (e.g. Consult, CBC)"
                    class="w-full p-2 bg-white border border-slate-200 rounded-lg text-xs"
                  />
                </div>
                <div class="col-span-2">
                  <input
                    v-model.number="row.quantity"
                    type="number"
                    min="1"
                    required
                    placeholder="Qty"
                    class="w-full p-2 bg-white border border-slate-200 rounded-lg font-mono text-center text-xs"
                  />
                </div>
                <div class="col-span-2">
                  <input
                    v-model.number="row.unit_price"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    placeholder="Price ($)"
                    class="w-full p-2 bg-white border border-slate-200 rounded-lg font-mono text-right text-xs"
                  />
                </div>
                <div class="col-span-2 text-right font-mono font-bold text-slate-900">
                  ${{ ((row.quantity || 1) * (row.unit_price || 0)).toFixed(2) }}
                </div>
                <div class="col-span-1 text-center">
                  <button
                    v-if="invoiceForm.items.length > 1"
                    type="button"
                    @click="invoiceForm.items.splice(idx, 1)"
                    class="text-rose-500 hover:text-rose-700 font-bold text-sm"
                  >
                    &times;
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Calculation Banner -->
          <div class="p-3.5 rounded-xl bg-slate-100 flex items-center justify-between text-xs font-mono">
            <span class="text-slate-600 font-bold">Estimated Total:</span>
            <strong class="text-base text-slate-900">${{ computedInvoiceTotal.toFixed(2) }}</strong>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="isInvoiceModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingInvoice"
              class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-md shadow-blue-500/20"
            >
              {{ submittingInvoice ? 'Generating...' : 'Create Invoice' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: COLLECT PAYMENT -->
    <div v-if="isPaymentModalOpen && activeInvoice" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900">Collect Payment</h3>
            <p class="text-xs text-slate-500">Invoice #{{ activeInvoice.invoice_number }}</p>
          </div>
          <button @click="isPaymentModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitPayment" class="p-6 space-y-4 text-xs">
          <!-- Balance Summary Banner -->
          <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
            <div>
              <span class="text-[10px] uppercase font-mono text-slate-400 block font-bold">Total Invoiced</span>
              <span class="font-mono text-slate-800 font-bold">${{ activeInvoice.total.toFixed(2) }}</span>
            </div>
            <div class="text-right">
              <span class="text-[10px] uppercase font-mono text-rose-500 block font-bold">Outstanding Balance</span>
              <strong class="font-mono text-rose-600 text-base font-black">${{ activeInvoice.balance.toFixed(2) }}</strong>
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="font-semibold text-slate-700">Payment Amount ($ USD)</label>
              <button
                type="button"
                @click="paymentForm.amount = activeInvoice.balance"
                class="text-[10px] text-blue-600 font-bold hover:underline"
              >
                Pay Full Balance (${{ activeInvoice.balance.toFixed(2) }})
              </button>
            </div>
            <input
              v-model.number="paymentForm.amount"
              type="number"
              step="0.01"
              min="0.01"
              :max="activeInvoice.balance"
              required
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 font-mono text-base font-bold"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Payment Mode</label>
            <select
              v-model="paymentForm.payment_mode"
              required
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 font-medium capitalize"
            >
              <option value="cash">Cash</option>
              <option value="card">Credit / Debit Card</option>
              <option value="mobile_money">Mobile Money (M-Pesa / MTN)</option>
              <option value="insurance">Insurance Direct Settlement</option>
              <option value="bank_transfer">Bank Wire / Transfer</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Transaction Reference (Optional)</label>
            <input
              v-model="paymentForm.transaction_reference"
              type="text"
              placeholder="e.g. POS Auth Code, MM Trans ID"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 font-mono"
            />
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="isPaymentModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingPayment"
              class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-md shadow-emerald-500/20"
            >
              {{ submittingPayment ? 'Processing...' : 'Record Payment' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 3: REQUEST DISCOUNT -->
    <div v-if="isDiscountModalOpen && activeInvoice" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900">Request Bill Discount</h3>
            <p class="text-xs text-slate-500">Invoice #{{ activeInvoice.invoice_number }}</p>
          </div>
          <button @click="isDiscountModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitDiscount" class="p-6 space-y-4 text-xs">
          <!-- Configurable Threshold Notice -->
          <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-[11px] flex items-start gap-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div>
              <strong>Second Approval Policy:</strong> Discounts exceeding $50.00 require approval from the Finance Director before applying to invoice balance.
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Discount Type</label>
              <select
                v-model="discountForm.discount_type"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"
              >
                <option value="fixed">Fixed Dollar ($)</option>
                <option value="percentage">Percentage (%)</option>
              </select>
            </div>

            <div v-if="discountForm.discount_type === 'fixed'">
              <label class="block font-semibold text-slate-700 mb-1">Discount Amount ($)</label>
              <input
                v-model.number="discountForm.amount"
                type="number"
                step="0.01"
                min="0.01"
                :max="activeInvoice.subtotal"
                required
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono"
              />
            </div>
            <div v-else>
              <label class="block font-semibold text-slate-700 mb-1">Percentage (%)</label>
              <input
                v-model.number="discountForm.percentage"
                type="number"
                step="0.5"
                min="1"
                max="100"
                required
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Clinical / Financial Justification</label>
            <textarea
              v-model="discountForm.reason"
              rows="2"
              required
              placeholder="e.g. Courtesy staff discount, financial hardship waiver..."
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"
            ></textarea>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="isDiscountModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-md shadow-blue-500/20"
            >
              Submit Discount
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 4: REQUEST REFUND -->
    <div v-if="isRefundModalOpen && activeInvoice" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900">Request Payment Refund</h3>
            <p class="text-xs text-slate-500">Invoice #{{ activeInvoice.invoice_number }} (Paid: ${{ activeInvoice.paid.toFixed(2) }})</p>
          </div>
          <button @click="isRefundModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitRefund" class="p-6 space-y-4 text-xs">
          <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-[11px] flex items-start gap-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div>
              <strong>Second Approval Policy:</strong> Refunds exceeding $50.00 require supervisor approval before payout.
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Refund Amount ($ USD)</label>
            <input
              v-model.number="refundForm.amount"
              type="number"
              step="0.01"
              min="0.01"
              :max="activeInvoice.paid"
              required
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Disbursement Mode</label>
            <select
              v-model="refundForm.refund_mode"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl capitalize"
            >
              <option value="cash">Cash Return</option>
              <option value="card">Card Reversal</option>
              <option value="mobile_money">Mobile Money Reversal</option>
              <option value="credit_note">Patient Credit Note</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Reason for Refund</label>
            <textarea
              v-model="refundForm.reason"
              rows="2"
              required
              placeholder="e.g. Cancelled laboratory order, duplicate payment..."
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl"
            ></textarea>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="isRefundModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold transition shadow-md shadow-rose-500/20"
            >
              Submit Refund
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 5: RECEIPT STATEMENT VIEWER -->
    <InvoiceReceiptView
      :is-open="isReceiptModalOpen"
      :invoice="receiptInvoice"
      @close="isReceiptModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import InvoiceReceiptView from './InvoiceReceiptView.vue';

const props = defineProps({
  branchId: {
    type: String,
    required: true,
  },
});

const invoices = ref([]);
const loading = ref(false);
const statusFilter = ref('all');
const searchQuery = ref('');

const patientsList = ref([]);

// Modals
const isInvoiceModalOpen = ref(false);
const submittingInvoice = ref(false);
const invoiceForm = ref({
  patient_id: '',
  billing_type: 'opd',
  items: [
    { description: 'General Medical Consultation', quantity: 1, unit_price: 35.00 },
  ],
});

const isPaymentModalOpen = ref(false);
const submittingPayment = ref(false);
const activeInvoice = ref(null);
const paymentForm = ref({
  amount: 0,
  payment_mode: 'cash',
  transaction_reference: '',
});

const isDiscountModalOpen = ref(false);
const discountForm = ref({
  discount_type: 'fixed',
  amount: 10,
  percentage: 10,
  reason: '',
});

const isRefundModalOpen = ref(false);
const refundForm = ref({
  amount: 10,
  refund_mode: 'cash',
  reason: '',
});

const isReceiptModalOpen = ref(false);
const receiptInvoice = ref(null);

const computedInvoiceTotal = computed(() => {
  return invoiceForm.value.items.reduce((sum, item) => {
    return sum + ((item.quantity || 1) * (item.unit_price || 0));
  }, 0);
});

onMounted(() => {
  fetchInvoices();
  fetchPatients();
});

async function fetchInvoices() {
  loading.value = true;
  try {
    let url = `/api/v1/billing/invoices?`;
    if (statusFilter.value !== 'all') {
      url += `status=${statusFilter.value}&`;
    }
    if (searchQuery.value.trim()) {
      url += `search=${encodeURIComponent(searchQuery.value.trim())}&`;
    }

    const res = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    const json = await res.json();
    if (json.success) {
      invoices.value = json.data;
    }
  } catch (e) {
    console.error('Invoices fetch error:', e);
  } finally {
    loading.value = false;
  }
}

async function fetchPatients() {
  try {
    const res = await fetch('/api/v1/patient/patients?per_page=50', {
      headers: {
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
    });
    const json = await res.json();
    if (json.success) {
      patientsList.value = json.data;
    }
  } catch (e) {
    console.error('Patients fetch error:', e);
  }
}

function openNewInvoiceModal() {
  invoiceForm.value = {
    patient_id: patientsList.value[0]?.id || '',
    billing_type: 'opd',
    items: [
      { description: 'General Medical Consultation', quantity: 1, unit_price: 35.00 },
    ],
  };
  isInvoiceModalOpen.value = true;
}

function addInvoiceItemRow() {
  invoiceForm.value.items.push({
    description: '',
    quantity: 1,
    unit_price: 25.00,
  });
}

async function submitInvoice() {
  submittingInvoice.value = true;
  try {
    const payloadItems = invoiceForm.value.items.map(item => ({
      item_type: 'procedure',
      description: item.description,
      quantity: item.quantity,
      unit_price_cents: Math.round(item.unit_price * 100),
      discount_cents: 0,
    }));

    const res = await fetch('/api/v1/billing/invoices', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        patient_id: invoiceForm.value.patient_id,
        billing_type: invoiceForm.value.billing_type,
        department: 'general',
        created_by: '00000000-0000-0000-0000-000000000000',
        items: payloadItems,
      }),
    });

    const json = await res.json();
    if (json.success) {
      isInvoiceModalOpen.value = false;
      await fetchInvoices();
    } else {
      alert(json.message || 'Failed to create invoice.');
    }
  } catch (e) {
    alert('Error creating invoice.');
  } finally {
    submittingInvoice.value = false;
  }
}

function openPaymentModal(inv) {
  activeInvoice.value = inv;
  paymentForm.value = {
    amount: inv.balance,
    payment_mode: 'cash',
    transaction_reference: '',
  };
  isPaymentModalOpen.value = true;
}

async function submitPayment() {
  if (!activeInvoice.value) return;

  submittingPayment.value = true;
  try {
    const res = await fetch('/api/v1/billing/payments', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        invoice_id: activeInvoice.value.id,
        amount_cents: Math.round(paymentForm.value.amount * 100),
        payment_mode: paymentForm.value.payment_mode,
        transaction_reference: paymentForm.value.transaction_reference,
        cashier_id: '00000000-0000-0000-0000-000000000000',
      }),
    });

    const json = await res.json();
    if (json.success) {
      alert(`Payment of $${paymentForm.value.amount.toFixed(2)} recorded under Receipt #${json.data.receipt_number}`);
      isPaymentModalOpen.value = false;
      await fetchInvoices();
    } else {
      alert(json.message || 'Payment recording failed.');
    }
  } catch (e) {
    alert('Error processing payment.');
  } finally {
    submittingPayment.value = false;
  }
}

function openReceiptModal(inv) {
  receiptInvoice.value = inv;
  isReceiptModalOpen.value = true;
}

function openDiscountModal(inv) {
  activeInvoice.value = inv;
  discountForm.value = {
    discount_type: 'fixed',
    amount: Math.min(20, inv.balance),
    percentage: 10,
    reason: '',
  };
  isDiscountModalOpen.value = true;
}

async function submitDiscount() {
  if (!activeInvoice.value) return;

  try {
    const res = await fetch('/api/v1/billing/discounts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        invoice_id: activeInvoice.value.id,
        discount_type: discountForm.value.discount_type,
        amount_cents: Math.round(discountForm.value.amount * 100),
        percentage: discountForm.value.percentage,
        reason: discountForm.value.reason,
        requested_by: '00000000-0000-0000-0000-000000000000',
      }),
    });

    const json = await res.json();
    alert(json.message || 'Discount processed.');
    isDiscountModalOpen.value = false;
    await fetchInvoices();
  } catch (e) {
    alert('Failed to request discount.');
  }
}

function openRefundModal(inv) {
  activeInvoice.value = inv;
  refundForm.value = {
    amount: Math.min(25, inv.paid),
    refund_mode: 'cash',
    reason: '',
  };
  isRefundModalOpen.value = true;
}

async function submitRefund() {
  if (!activeInvoice.value) return;

  try {
    const res = await fetch('/api/v1/billing/refunds', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Branch-ID': props.branchId,
      },
      body: JSON.stringify({
        invoice_id: activeInvoice.value.id,
        amount_cents: Math.round(refundForm.value.amount * 100),
        refund_mode: refundForm.value.refund_mode,
        reason: refundForm.value.reason,
        requested_by: '00000000-0000-0000-0000-000000000000',
      }),
    });

    const json = await res.json();
    alert(json.message || 'Refund submitted.');
    isRefundModalOpen.value = false;
    await fetchInvoices();
  } catch (e) {
    alert('Failed to process refund.');
  }
}
</script>
