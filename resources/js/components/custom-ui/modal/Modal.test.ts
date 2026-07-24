import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';
import Modal from './Modal.vue';

describe('Modal Component', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('renders modal when open prop is true', async () => {
    const wrapper = mount(Modal, {
      props: {
        open: true,
        title: 'Modal Test Title',
        description: 'Modal Test Description',
      },
      slots: {
        default: '<div class="test-body">Body Content Test</div>',
      },
      attachTo: document.body,
    });

    await nextTick();

    expect(document.body.textContent).toContain('Modal Test Title');
    expect(document.body.textContent).toContain('Modal Test Description');
    expect(document.body.textContent).toContain('Body Content Test');

    wrapper.unmount();
  });

  it('emits confirm event when confirm button is clicked', async () => {
    const wrapper = mount(Modal, {
      props: {
        open: true,
        confirmText: 'Lanjutkan',
      },
      attachTo: document.body,
    });

    await nextTick();

    const buttons = document.body.querySelectorAll('button');
    const confirmButton = Array.from(buttons).find((b) => b.textContent?.trim() === 'Lanjutkan');

    expect(confirmButton).toBeDefined();

    confirmButton?.click();
    await nextTick();

    expect(wrapper.emitted('confirm')).toBeTruthy();
    expect(wrapper.emitted('confirm')?.length).toBe(1);

    wrapper.unmount();
  });

  it('emits cancel and update:open false when cancel button is clicked', async () => {
    const wrapper = mount(Modal, {
      props: {
        open: true,
        cancelText: 'Batal Test',
      },
      attachTo: document.body,
    });

    await nextTick();

    const buttons = document.body.querySelectorAll('button');
    const cancelButton = Array.from(buttons).find((b) => b.textContent?.trim() === 'Batal Test');

    expect(cancelButton).toBeDefined();

    cancelButton?.click();
    await nextTick();

    expect(wrapper.emitted('cancel')).toBeTruthy();
    expect(wrapper.emitted('update:open')).toEqual([[false]]);

    wrapper.unmount();
  });

  it('wraps content in form element when asForm is true', async () => {
    const wrapper = mount(Modal, {
      props: {
        open: true,
        asForm: true,
      },
      attachTo: document.body,
    });

    await nextTick();

    const formElement = document.body.querySelector('form');
    expect(formElement).not.toBeNull();

    wrapper.unmount();
  });

  it('disables buttons and shows spinner when loading is true', async () => {
    const wrapper = mount(Modal, {
      props: {
        open: true,
        loading: true,
        confirmText: 'Submit Data',
      },
      attachTo: document.body,
    });

    await nextTick();

    const buttons = document.body.querySelectorAll('button');
    buttons.forEach((btn) => {
      expect((btn as HTMLButtonElement).disabled).toBe(true);
    });

    // Check if spinner SVG is present
    const spinner = document.body.querySelector('svg.animate-spin');
    expect(spinner).not.toBeNull();

    wrapper.unmount();
  });

  it('hides footer when hideFooter is true', async () => {
    const wrapper = mount(Modal, {
      props: {
        open: true,
        hideFooter: true,
        confirmText: 'Submit Button',
      },
      attachTo: document.body,
    });

    await nextTick();

    expect(document.body.textContent).not.toContain('Submit Button');

    wrapper.unmount();
  });
});
