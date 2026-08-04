import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import BadgeList from './BadgeList.vue';

describe('BadgeList', () => {
  it('renders list of items with labelMap mapping', () => {
    const wrapper = mount(BadgeList, {
      props: {
        items: ['user-management', 'role-management'],
        labelMap: {
          'user-management': 'Manajemen Pengguna',
          'role-management': 'Manajemen Group',
        },
      },
    });

    expect(wrapper.text()).toContain('Manajemen Pengguna');
    expect(wrapper.text()).toContain('Manajemen Group');
  });

  it('highlights matching badge and prioritizes it to the front when search is provided', () => {
    const wrapper = mount(BadgeList, {
      props: {
        items: ['user-management', 'role-management'],
        labelMap: {
          'user-management': 'Manajemen Pengguna',
          'role-management': 'Manajemen Group',
        },
        search: 'Group',
      },
    });

    const badges = wrapper.findAll('.inline-flex');
    expect(badges[0].text()).toContain('Manajemen Group');
    expect(badges[0].classes()).toContain('bg-primary');
  });

  it('renders remaining count badge when items length exceeds max', () => {
    const items = Array.from({ length: 15 }, (_, i) => `item-${i}`);
    const wrapper = mount(BadgeList, {
      props: {
        items,
        max: 10,
      },
    });

    expect(wrapper.text()).toContain('+5..');
  });

  it('renders empty fallback text when items is empty', () => {
    const wrapper = mount(BadgeList, {
      props: {
        items: [],
        emptyText: 'Tidak ada fitur',
      },
    });

    expect(wrapper.text()).toBe('Tidak ada fitur');
  });

  it('renders expired badge with muted dashed styling and clock icon', () => {
    const wrapper = mount(BadgeList, {
      props: {
        items: [
          { name: 'Admin OPD', expired: false },
          { name: 'Admin Wilayah (Expired)', expired: true, title: 'Kedaluwarsa pada 31 Jul 2026' },
        ],
      },
    });

    expect(wrapper.text()).toContain('Admin OPD');
    expect(wrapper.text()).toContain('Admin Wilayah (Expired)');
    const expiredBadge = wrapper.findAll('.inline-flex')[1];
    expect(expiredBadge.classes()).toContain('border-dashed');
    expect(expiredBadge.classes()).toContain('opacity-60');
    expect(expiredBadge.attributes('title')).toBe('Kedaluwarsa pada 31 Jul 2026');
  });
});
