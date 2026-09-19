import { reactive } from 'vue';

export const dialogState = reactive({
  isOpen: false,
  type: 'alert', // 'alert' | 'confirm' | 'prompt'
  status: 'info', // 'info' | 'success' | 'warning' | 'error' | 'danger'
  title: 'Notice',
  message: '',
  confirmText: 'OK',
  cancelText: 'Cancel',
  inputValue: '',
  inputPlaceholder: '',
});

let currentResolver = null;

function inferStatusAndTitle(message, type, explicitStatus, explicitTitle) {
  const lower = String(message || '').toLowerCase();

  let status = explicitStatus;
  if (!status) {
    if (lower.includes('delete') || lower.includes('remove') || lower.includes('deactivate') || lower.includes('reject') || lower.includes('release bed') || lower.includes('cancel shift')) {
      status = 'danger';
    } else if (lower.includes('failed') || lower.includes('error') || lower.includes('unauthorized') || lower.includes('invalid') || lower.includes('critical')) {
      status = 'error';
    } else if (lower.includes('success') || lower.includes('created') || lower.includes('saved') || lower.includes('passed') || lower.includes('verified') || lower.includes('recorded') || lower.includes('broadcasted')) {
      status = 'success';
    } else if (lower.includes('warning') || lower.includes('caution') || lower.includes('seal') || lower.includes('confirm') || lower.includes('are you sure')) {
      status = 'warning';
    } else {
      status = type === 'confirm' ? 'warning' : 'info';
    }
  }

  let title = explicitTitle;
  if (!title) {
    if (type === 'confirm') {
      title = status === 'danger' ? 'Confirm Action' : 'Please Confirm';
    } else if (type === 'prompt') {
      title = 'Input Required';
    } else {
      if (status === 'success') title = 'Success';
      else if (status === 'error' || status === 'danger') title = 'Attention Required';
      else if (status === 'warning') title = 'Warning';
      else title = 'Notice';
    }
  }

  return { status, title };
}

export function showAlert(message, options = {}) {
  return new Promise((resolve) => {
    currentResolver = resolve;

    const { status, title } = inferStatusAndTitle(
      message,
      'alert',
      options.status || options.type,
      options.title
    );

    dialogState.type = 'alert';
    dialogState.status = status;
    dialogState.title = title;
    dialogState.message = String(message ?? '');
    dialogState.confirmText = options.confirmText || 'OK';
    dialogState.cancelText = '';
    dialogState.inputValue = '';
    dialogState.isOpen = true;
  });
}

export function showConfirm(message, options = {}) {
  return new Promise((resolve) => {
    currentResolver = resolve;

    const isDestructive = options.isDestructive || options.destructive;
    const { status, title } = inferStatusAndTitle(
      message,
      'confirm',
      isDestructive ? 'danger' : (options.status || options.type),
      options.title
    );

    dialogState.type = 'confirm';
    dialogState.status = status;
    dialogState.title = title;
    dialogState.message = String(message ?? '');
    dialogState.confirmText = options.confirmText || (status === 'danger' ? 'Confirm' : 'Continue');
    dialogState.cancelText = options.cancelText || 'Cancel';
    dialogState.inputValue = '';
    dialogState.isOpen = true;
  });
}

export function showPrompt(message, defaultValue = '', options = {}) {
  return new Promise((resolve) => {
    currentResolver = resolve;

    const { status, title } = inferStatusAndTitle(
      message,
      'prompt',
      options.status || options.type,
      options.title
    );

    dialogState.type = 'prompt';
    dialogState.status = status;
    dialogState.title = title;
    dialogState.message = String(message ?? '');
    dialogState.confirmText = options.confirmText || 'Submit';
    dialogState.cancelText = options.cancelText || 'Cancel';
    dialogState.inputValue = defaultValue || '';
    dialogState.inputPlaceholder = options.placeholder || 'Enter value...';
    dialogState.isOpen = true;
  });
}

export function resolveDialog(result) {
  dialogState.isOpen = false;
  if (currentResolver) {
    const fn = currentResolver;
    currentResolver = null;
    fn(result);
  }
}

// Override standard browser alert as fallback
if (typeof window !== 'undefined') {
  window.alert = (msg) => {
    showAlert(msg);
  };
}

export default {
  alert: showAlert,
  confirm: showConfirm,
  prompt: showPrompt,
  showAlert,
  showConfirm,
  showPrompt,
  dialogState,
  resolveDialog,
};
