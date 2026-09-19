<template>
  <teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="state.isOpen"
        class="fixed inset-0 z-[999999] overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="handleBackdropClick"
        @keydown.esc="handleCancel"
        tabindex="-1"
      >
        <transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-2"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-2"
        >
          <div
            v-if="state.isOpen"
            class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 overflow-hidden transform transition-all"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="'hms-modal-title'"
          >
            <!-- Header Icon & Close -->
            <div class="flex items-start justify-between">
              <div
                class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-inner"
                :class="iconContainerClass"
              >
                <!-- Danger / Error Icon -->
                <svg
                  v-if="state.status === 'danger' || state.status === 'error'"
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                  />
                </svg>

                <!-- Warning Icon -->
                <svg
                  v-else-if="state.status === 'warning'"
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>

                <!-- Success Icon -->
                <svg
                  v-else-if="state.status === 'success'"
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>

                <!-- Prompt Icon -->
                <svg
                  v-else-if="state.type === 'prompt'"
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                  />
                </svg>

                <!-- Default Info / Confirm Icon -->
                <svg
                  v-else
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>

              <!-- Close X Button -->
              <button
                type="button"
                @click="handleCancel"
                class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition cursor-pointer"
                title="Dismiss"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Title & Message -->
            <div class="mt-4">
              <h3 id="hms-modal-title" class="text-base font-bold text-slate-900">
                {{ state.title }}
              </h3>
              <div class="text-xs text-slate-600 mt-2 leading-relaxed whitespace-pre-line break-words">
                {{ state.message }}
              </div>
            </div>

            <!-- Prompt Input Field -->
            <div v-if="state.type === 'prompt'" class="mt-4">
              <input
                ref="promptInputRef"
                v-model="state.inputValue"
                type="text"
                :placeholder="state.inputPlaceholder || 'Enter value...'"
                class="w-full text-xs rounded-xl border border-slate-300 py-2.5 px-3.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-slate-50 focus:bg-white transition"
                @keydown.enter.prevent="handleConfirm"
              />
            </div>

            <!-- Actions -->
            <div class="mt-6 flex items-center justify-end gap-2.5">
              <!-- Cancel Button for Confirm / Prompt -->
              <button
                v-if="state.type === 'confirm' || state.type === 'prompt'"
                type="button"
                @click="handleCancel"
                class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 transition cursor-pointer"
              >
                {{ state.cancelText || 'Cancel' }}
              </button>

              <!-- Confirm / Acknowledge Button -->
              <button
                ref="confirmBtnRef"
                type="button"
                @click="handleConfirm"
                :class="confirmButtonClass"
                class="px-5 py-2 text-xs font-bold rounded-xl text-white shadow-sm transition cursor-pointer"
              >
                {{ state.confirmText || (state.type === 'confirm' ? 'Confirm' : (state.type === 'prompt' ? 'Submit' : 'OK')) }}
              </button>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed, ref, watch, nextTick } from 'vue';
import { dialogState, resolveDialog } from '../../Services/modalDialog';

const state = dialogState;
const confirmBtnRef = ref(null);
const promptInputRef = ref(null);

watch(
  () => state.isOpen,
  async (isOpen) => {
    if (isOpen) {
      await nextTick();
      if (state.type === 'prompt' && promptInputRef.value) {
        promptInputRef.value.focus();
        promptInputRef.value.select?.();
      } else if (confirmBtnRef.value) {
        confirmBtnRef.value.focus();
      }
    }
  }
);

const iconContainerClass = computed(() => {
  if (state.status === 'danger' || state.status === 'error') {
    return 'bg-rose-50 text-rose-600 border border-rose-200';
  }
  if (state.status === 'warning') {
    return 'bg-amber-50 text-amber-600 border border-amber-200';
  }
  if (state.status === 'success') {
    return 'bg-emerald-50 text-emerald-600 border border-emerald-200';
  }
  if (state.type === 'prompt') {
    return 'bg-indigo-50 text-indigo-600 border border-indigo-200';
  }
  return 'bg-blue-50 text-blue-600 border border-blue-200';
});

const confirmButtonClass = computed(() => {
  if (state.status === 'danger') {
    return 'bg-rose-600 hover:bg-rose-700 active:bg-rose-800 shadow-rose-600/20';
  }
  if (state.status === 'warning') {
    return 'bg-amber-600 hover:bg-amber-700 active:bg-amber-800 shadow-amber-600/20';
  }
  if (state.status === 'success') {
    return 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-emerald-600/20';
  }
  if (state.type === 'prompt') {
    return 'bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 shadow-indigo-600/20';
  }
  return 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-blue-600/20';
});

function handleConfirm() {
  if (state.type === 'prompt') {
    resolveDialog(state.inputValue);
  } else {
    resolveDialog(true);
  }
}

function handleCancel() {
  if (state.type === 'prompt') {
    resolveDialog(null);
  } else {
    resolveDialog(false);
  }
}

function handleBackdropClick() {
  // Clicking outside on alert dismisses; on confirm/prompt it cancels
  handleCancel();
}
</script>
