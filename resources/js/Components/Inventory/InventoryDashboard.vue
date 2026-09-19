<template>
  <div class="space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-teal-600 font-bold">
          <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
          Hospital Central Supply & Materials Management
        </div>
        <h1 class="text-xl font-black text-slate-900 mt-1">Medical & General Inventory</h1>
        <p class="text-xs text-slate-500 mt-0.5">Surgical consumables, PPE, ward linens, and facility supplies (separate from pharmacy drug stock).</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="openNewItemModal"
          class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-teal-500/20 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          + Add Inventory Item
        </button>
      </div>
    </div>

    <!-- Executive KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-bold">Total Catalog Items</div>
        <div class="text-2xl font-black text-slate-900 mt-1">
          {{ summary.total_inventory_items }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1">
          {{ summary.medical_items_count }} medical &bull; {{ summary.non_medical_items_count }} non-medical
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-emerald-600 font-bold">Total Valuation</div>
        <div class="text-2xl font-black text-emerald-600 mt-1">
          ${{ formatMoney(summary.total_valuation) }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1">
          Holding inventory cost
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-amber-600 font-bold">Low Stock Reorders</div>
        <div class="text-2xl font-black text-amber-600 mt-1">
          {{ summary.low_stock_count }}
        </div>
        <div class="text-[11px] text-amber-700/80 mt-1">
          At or below min threshold
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-rose-600 font-bold">Out of Stock</div>
        <div class="text-2xl font-black text-rose-600 mt-1">
          {{ summary.out_of_stock_count }}
        </div>
        <div class="text-[11px] text-rose-700/80 mt-1">
          Immediate PO required
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-mono uppercase tracking-wider text-teal-600 font-bold">Adequate Stock</div>
        <div class="text-2xl font-black text-teal-700 mt-1">
          {{ summary.adequate_stock_count }}
        </div>
        <div class="text-[11px] text-slate-500 mt-1">
          Optimal buffer level
        </div>
      </div>
    </div>

    <!-- Proactive Low-Stock & Reorder Alerts Banner -->
    <div v-if="lowStockAlerts.length > 0" class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-xs">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2 text-amber-800 font-bold text-sm">
          <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          Reorder Alert: {{ lowStockAlerts.length }} items need immediate procurement
        </div>
        <span class="text-xs font-mono text-amber-700 font-semibold">Automatic Replenishment Suggestions</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="alert in lowStockAlerts.slice(0, 6)"
          :key="alert.id"
          class="bg-white p-3.5 rounded-xl border border-amber-200/80 text-xs space-y-1 shadow-2xs"
        >
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-900 truncate pr-2">{{ alert.name }}</span>
            <span
              :class="alert.current_stock === 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800'"
              class="px-2 py-0.5 rounded-full text-[10px] font-mono font-black"
            >
              {{ alert.current_stock }} / {{ alert.min_stock_level }}
            </span>
          </div>
          <div class="text-[11px] text-slate-400 font-mono flex items-center justify-between">
            <span>Code: {{ alert.item_code }}</span>
            <span>Est: ${{ formatMoney(alert.estimated_reorder_cost) }}</span>
          </div>
          <div class="pt-1 flex items-center justify-between text-[11px]">
            <span class="text-slate-600">Reorder Lot: <strong>{{ alert.reorder_quantity }} {{ alert.unit_of_measure }}s</strong></span>
            <span class="text-teal-700 font-semibold">{{ alert.default_vendor?.name || 'General Vendor' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Tabs & Search Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
        <!-- Category Filter -->
        <div class="flex items-center bg-slate-100 p-1 rounded-xl">
          <button
            v-for="cat in ['all', 'medical', 'non_medical']"
            :key="cat"
            @click="categoryFilter = cat; fetchItems()"
            :class="categoryFilter === cat ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
            class="px-3 py-1.5 rounded-lg capitalize transition cursor-pointer"
          >
            {{ cat.replace('_', ' ') }}
          </button>
        </div>

        <!-- Stock Status Filter -->
        <div class="flex items-center bg-slate-100 p-1 rounded-xl">
          <button
            v-for="st in ['all', 'low_stock', 'adequate', 'out_of_stock']"
            :key="st"
            @click="stockStatusFilter = st; fetchItems()"
            :class="stockStatusFilter === st ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
            class="px-3 py-1.5 rounded-lg capitalize transition cursor-pointer"
          >
            {{ st.replace('_', ' ') }}
          </button>
        </div>
      </div>

      <div class="relative w-full sm:w-80">
        <input
          v-model="searchQuery"
          @input="fetchItems"
          type="text"
          placeholder="Search by Code, Item Name, Location..."
          class="w-full text-xs pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500"
        />
        <svg class="w-3.5 h-3.5 absolute left-2.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>

    <!-- Inventory Items Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-xs text-slate-400">
        Loading inventory supplies...
      </div>
      <div v-else-if="items.length === 0" class="p-12 text-center text-xs text-slate-400">
        No inventory items found matching current filters.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-mono uppercase text-[11px] border-b border-slate-200">
            <tr>
              <th class="p-4">Item Code & Name</th>
              <th class="p-4">Category</th>
              <th class="p-4 text-center">Current Stock</th>
              <th class="p-4 text-center">Min Level</th>
              <th class="p-4 text-right">Unit Cost</th>
              <th class="p-4">Storage Location</th>
              <th class="p-4">Supplier</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50/60 transition">
              <td class="p-4">
                <div class="font-bold text-slate-900">{{ item.name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ item.item_code }} &bull; {{ item.unit_of_measure }}</div>
              </td>
              <td class="p-4">
                <span
                  :class="item.category === 'medical' ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                  class="px-2 py-0.5 rounded-md font-mono text-[10px] uppercase font-bold border"
                >
                  {{ item.category.replace('_', ' ') }}
                </span>
                <span v-if="item.sub_category" class="text-[10px] text-slate-400 block font-mono capitalize mt-0.5">
                  {{ item.sub_category.replace('_', ' ') }}
                </span>
              </td>
              <td class="p-4 text-center">
                <div class="font-mono font-black text-sm" :class="{
                  'text-rose-600': item.current_stock === 0,
                  'text-amber-600': item.is_low_stock,
                  'text-slate-800': !item.is_low_stock && item.current_stock > 0
                }">
                  {{ item.current_stock }}
                </div>
                <span
                  :class="{
                    'text-rose-600 bg-rose-50 border-rose-200': item.current_stock === 0,
                    'text-amber-600 bg-amber-50 border-amber-200': item.is_low_stock,
                    'text-emerald-700 bg-emerald-50 border-emerald-200': !item.is_low_stock && item.current_stock > 0
                  }"
                  class="text-[9px] font-mono uppercase font-bold px-1.5 py-0.5 rounded border inline-block mt-0.5"
                >
                  {{ item.stock_status.replace('_', ' ') }}
                </span>
              </td>
              <td class="p-4 text-center font-mono font-semibold text-slate-500">
                {{ item.min_stock_level }}
              </td>
              <td class="p-4 text-right font-mono font-bold text-slate-900">
                ${{ item.unit_cost.toFixed(2) }}
              </td>
              <td class="p-4 text-slate-600">
                {{ item.storage_location || 'Central Depot' }}
              </td>
              <td class="p-4">
                <div class="font-medium text-slate-800">{{ item.default_vendor?.name || 'Standard Supplier' }}</div>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openConsumeModal(item)"
                    title="Log Department Consumption"
                    class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-[11px] font-bold transition cursor-pointer"
                  >
                    Consume
                  </button>
                  <button
                    @click="openAdjustModal(item)"
                    title="Adjust Stock Count"
                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition cursor-pointer"
                  >
                    Adjust
                  </button>
                  <button
                    @click="openReconcileModal(item)"
                    title="Reconcile Audit Trail"
                    class="px-2.5 py-1.5 bg-teal-50 hover:bg-teal-100 text-teal-700 rounded-lg text-[11px] font-bold transition cursor-pointer"
                  >
                    Audit
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Add Inventory Item -->
    <div
      v-if="showNewItemModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Add New Inventory Item</h3>
            <p class="text-xs text-slate-400">Register a medical consumable, linen, or facility asset item.</p>
          </div>
          <button @click="showNewItemModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Item Code *</label>
              <input
                v-model="itemForm.item_code"
                type="text"
                placeholder="e.g. MED-GLV-002"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Category *</label>
              <select
                v-model="itemForm.category"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500 font-semibold"
              >
                <option value="medical">Medical Supplies</option>
                <option value="non_medical">Non-Medical / Facility</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Item Name *</label>
            <input
              v-model="itemForm.name"
              type="text"
              placeholder="e.g. Sterile Latex Gloves Medium"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500 font-bold"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Subcategory</label>
              <input
                v-model="itemForm.sub_category"
                type="text"
                placeholder="e.g. ppe, surgical, linens"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Unit of Measure *</label>
              <input
                v-model="itemForm.unit_of_measure"
                type="text"
                placeholder="e.g. box, piece, pack, bottle"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Initial Stock</label>
              <input
                v-model.number="itemForm.current_stock"
                type="number"
                min="0"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Min Threshold *</label>
              <input
                v-model.number="itemForm.min_stock_level"
                type="number"
                min="1"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Reorder Lot</label>
              <input
                v-model.number="itemForm.reorder_quantity"
                type="number"
                min="1"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500 font-mono"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Unit Cost ($)</label>
              <input
                v-model.number="itemForm.unit_cost"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Storage Location</label>
              <input
                v-model="itemForm.storage_location"
                type="text"
                placeholder="e.g. Warehouse Aisle 3, Rack B"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500"
              />
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showNewItemModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitCreateItem"
            :disabled="isSubmitting || !itemForm.name || !itemForm.item_code"
            class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            Save Item
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Log Department Consumption -->
    <div
      v-if="showConsumeModal && activeItem"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Record Department Consumption</h3>
            <p class="text-xs text-slate-400">{{ activeItem.name }} (Current: {{ activeItem.current_stock }} {{ activeItem.unit_of_measure }}s)</p>
          </div>
          <button @click="showConsumeModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Consumption Quantity ({{ activeItem.unit_of_measure }}s) *</label>
            <input
              v-model.number="consumeForm.quantity"
              type="number"
              min="1"
              :max="activeItem.current_stock"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 font-mono text-sm"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Disbursed To Department *</label>
            <select
              v-model="consumeForm.department"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 font-medium"
            >
              <option value="Emergency Department">Emergency Department</option>
              <option value="Operation Theatre (OT)">Operation Theatre (OT)</option>
              <option value="Intensive Care Unit (ICU)">Intensive Care Unit (ICU)</option>
              <option value="General Inpatient Ward">General Inpatient Ward</option>
              <option value="Diagnostic Laboratory">Diagnostic Laboratory</option>
              <option value="Radiology & Imaging">Radiology & Imaging</option>
              <option value="Hospital Sanitation">Hospital Sanitation</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Requisition Notes</label>
            <textarea
              v-model="consumeForm.notes"
              rows="2"
              placeholder="Clinical reason, procedure case number, or shift requisitioner..."
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showConsumeModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitConsumption"
            :disabled="isSubmitting || consumeForm.quantity <= 0"
            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            Confirm Consumption
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Stock Adjustment -->
    <div
      v-if="showAdjustModal && activeItem"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Manual Stock Count Adjustment</h3>
            <p class="text-xs text-slate-400">{{ activeItem.name }} (Current: {{ activeItem.current_stock }} {{ activeItem.unit_of_measure }}s)</p>
          </div>
          <button @click="showAdjustModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Adjustment Type *</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="adjustForm.type = 'adjustment_addition'"
                :class="adjustForm.type === 'adjustment_addition' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700 font-semibold'"
                class="py-2 rounded-xl transition cursor-pointer text-center text-xs"
              >
                + Stock Addition
              </button>
              <button
                type="button"
                @click="adjustForm.type = 'adjustment_reduction'"
                :class="adjustForm.type === 'adjustment_reduction' ? 'bg-rose-600 text-white font-bold' : 'bg-slate-100 text-slate-700 font-semibold'"
                class="py-2 rounded-xl transition cursor-pointer text-center text-xs"
              >
                &minus; Stock Reduction
              </button>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Quantity (Units) *</label>
            <input
              v-model.number="adjustForm.quantity"
              type="number"
              min="1"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500 font-mono text-sm"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Audit Justification / Reason *</label>
            <textarea
              v-model="adjustForm.notes"
              rows="2"
              placeholder="e.g. Physical inventory count discrepancy, transit damage, expired batch..."
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            @click="showAdjustModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="submitAdjustment"
            :disabled="isSubmitting || adjustForm.quantity <= 0 || !adjustForm.notes"
            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            Apply Adjustment
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Stock Reconciliation Audit -->
    <div
      v-if="showReconcileModal && activeItem"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Stock Count Audit Reconciliation</h3>
            <p class="text-xs text-slate-400">{{ activeItem.name }} ({{ activeItem.item_code }})</p>
          </div>
          <button @click="showReconcileModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="reconciliationData" class="space-y-4 text-xs">
          <div
            :class="reconciliationData.is_reconciled ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'"
            class="p-4 rounded-xl border flex items-center justify-between"
          >
            <div>
              <div class="font-bold text-sm">
                {{ reconciliationData.is_reconciled ? '✓ Stock 100% Reconciled' : '⚠️ Discrepancy Detected' }}
              </div>
              <div class="text-[11px] mt-0.5 opacity-90">
                Audit trail across {{ reconciliationData.total_movement_records }} transactions matches database ledger.
              </div>
            </div>
            <div class="text-right font-mono">
              <div class="text-xs uppercase font-bold text-slate-500">Discrepancy</div>
              <div class="text-lg font-black">{{ reconciliationData.discrepancy }}</div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 font-mono">
            <div class="p-3 bg-slate-50 rounded-xl">
              <span class="text-slate-400 block text-[10px] uppercase">Current Stock Ledger</span>
              <span class="text-base font-black text-slate-900">{{ reconciliationData.current_stock }}</span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
              <span class="text-slate-400 block text-[10px] uppercase">Calculated Stock</span>
              <span class="text-base font-black text-slate-900">{{ reconciliationData.calculated_stock_from_movements }}</span>
            </div>
            <div class="p-3 bg-emerald-50/60 rounded-xl text-emerald-800">
              <span class="block text-[10px] uppercase">Received From POs</span>
              <span class="text-base font-black">+{{ reconciliationData.total_received_from_po }}</span>
            </div>
            <div class="p-3 bg-rose-50/60 rounded-xl text-rose-800">
              <span class="block text-[10px] uppercase">Department Consumption</span>
              <span class="text-base font-black">-{{ reconciliationData.total_consumed_by_departments }}</span>
            </div>
          </div>
        </div>
        <div v-else class="p-8 text-center text-xs text-slate-400">
          Calculating audit balance...
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            @click="showReconcileModal = false"
            class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold cursor-pointer"
          >
            Close Audit
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { showAlert } from '../../Services/modalDialog';

const props = defineProps({
  branchId: {
    type: String,
    default: null,
  },
});

const loading = ref(false);
const isSubmitting = ref(false);
const categoryFilter = ref('all');
const stockStatusFilter = ref('all');
const searchQuery = ref('');

const items = ref([]);
const lowStockAlerts = ref([]);
const summary = ref({
  total_inventory_items: 0,
  out_of_stock_count: 0,
  low_stock_count: 0,
  adequate_stock_count: 0,
  total_valuation: 0,
  medical_items_count: 0,
  non_medical_items_count: 0,
});

const showNewItemModal = ref(false);
const showConsumeModal = ref(false);
const showAdjustModal = ref(false);
const showReconcileModal = ref(false);
const activeItem = ref(null);
const reconciliationData = ref(null);

const itemForm = ref({
  item_code: '',
  name: '',
  category: 'medical',
  sub_category: '',
  unit_of_measure: 'box',
  current_stock: 0,
  min_stock_level: 10,
  reorder_quantity: 50,
  unit_cost: 0,
  storage_location: '',
});

const consumeForm = ref({
  quantity: 1,
  department: 'Emergency Department',
  notes: '',
});

const adjustForm = ref({
  quantity: 1,
  type: 'adjustment_addition',
  notes: '',
});

async function fetchSummary() {
  try {
    const res = await axios.get('/api/v1/inventory/summary', {
      params: { branch_id: props.branchId || undefined }
    });
    if (res.data?.data) {
      summary.value = res.data.data;
    }
  } catch (err) {
    console.error('Failed to fetch summary:', err);
  }
}

async function fetchAlerts() {
  try {
    const res = await axios.get('/api/v1/inventory/alerts/low-stock', {
      params: { branch_id: props.branchId || undefined }
    });
    lowStockAlerts.value = res.data?.data || [];
  } catch (err) {
    console.error('Failed to fetch alerts:', err);
  }
}

async function fetchItems() {
  loading.value = true;
  try {
    const params = {
      branch_id: props.branchId || undefined,
      category: categoryFilter.value !== 'all' ? categoryFilter.value : undefined,
      stock_status: stockStatusFilter.value !== 'all' ? stockStatusFilter.value : undefined,
      search: searchQuery.value || undefined,
    };
    const res = await axios.get('/api/v1/inventory/items', { params });
    items.value = res.data?.data || [];
  } catch (err) {
    console.error('Failed to fetch items:', err);
  } finally {
    loading.value = false;
  }
}

function openNewItemModal() {
  itemForm.value = {
    item_code: '',
    name: '',
    category: 'medical',
    sub_category: '',
    unit_of_measure: 'box',
    current_stock: 0,
    min_stock_level: 10,
    reorder_quantity: 50,
    unit_cost: 0,
    storage_location: '',
  };
  showNewItemModal.value = true;
}

async function submitCreateItem() {
  isSubmitting.value = true;
  try {
    const payload = {
      organization_id: '01a09429-0097-71b5-9f5b-6ff38a7cba8b',
      branch_id: props.branchId || 'b9ff561a-5396-4309-9b08-3e7b358310e9',
      item_code: itemForm.value.item_code,
      name: itemForm.value.name,
      category: itemForm.value.category,
      sub_category: itemForm.value.sub_category || undefined,
      unit_of_measure: itemForm.value.unit_of_measure,
      current_stock: itemForm.value.current_stock || 0,
      min_stock_level: itemForm.value.min_stock_level || 10,
      reorder_quantity: itemForm.value.reorder_quantity || 50,
      unit_cost_cents: Math.round(itemForm.value.unit_cost * 100),
      storage_location: itemForm.value.storage_location || undefined,
    };
    await axios.post('/api/v1/inventory/items', payload);
    showNewItemModal.value = false;
    await Promise.all([fetchItems(), fetchSummary(), fetchAlerts()]);
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to create inventory item.');
  } finally {
    isSubmitting.value = false;
  }
}

function openConsumeModal(item) {
  activeItem.value = item;
  consumeForm.value = {
    quantity: 1,
    department: 'Emergency Department',
    notes: '',
  };
  showConsumeModal.value = true;
}

async function submitConsumption() {
  if (!activeItem.value) return;
  isSubmitting.value = true;
  try {
    await axios.post(`/api/v1/inventory/items/${activeItem.value.id}/consume`, {
      quantity: consumeForm.value.quantity,
      department: consumeForm.value.department,
      notes: consumeForm.value.notes || undefined,
    });
    showConsumeModal.value = false;
    await Promise.all([fetchItems(), fetchSummary(), fetchAlerts()]);
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to log consumption.');
  } finally {
    isSubmitting.value = false;
  }
}

function openAdjustModal(item) {
  activeItem.value = item;
  adjustForm.value = {
    quantity: 1,
    type: 'adjustment_addition',
    notes: '',
  };
  showAdjustModal.value = true;
}

async function submitAdjustment() {
  if (!activeItem.value) return;
  isSubmitting.value = true;
  try {
    await axios.post(`/api/v1/inventory/items/${activeItem.value.id}/adjust`, {
      quantity: adjustForm.value.quantity,
      type: adjustForm.value.type,
      notes: adjustForm.value.notes,
    });
    showAdjustModal.value = false;
    await Promise.all([fetchItems(), fetchSummary(), fetchAlerts()]);
  } catch (err) {
    await showAlert(err.response?.data?.message || 'Failed to adjust stock.');
  } finally {
    isSubmitting.value = false;
  }
}

async function openReconcileModal(item) {
  activeItem.value = item;
  reconciliationData.value = null;
  showReconcileModal.value = true;
  try {
    const res = await axios.get(`/api/v1/inventory/items/${item.id}/reconcile`);
    reconciliationData.value = res.data?.data;
  } catch (err) {
    console.error('Failed to load reconciliation:', err);
  }
}

function formatMoney(amount) {
  const val = Number(amount) || 0;
  return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

onMounted(() => {
  fetchSummary();
  fetchAlerts();
  fetchItems();
});
</script>
