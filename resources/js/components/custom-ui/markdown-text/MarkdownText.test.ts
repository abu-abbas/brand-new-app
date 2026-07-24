import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MarkdownText from './MarkdownText.vue';

describe('MarkdownText', () => {
  it('merender teks biasa tanpa markdown', () => {
    const wrapper = mount(MarkdownText, {
      props: { content: 'Teks biasa tanpa format' },
    });

    expect(wrapper.text()).toBe('Teks biasa tanpa format');
    expect(wrapper.find('code').exists()).toBe(false);
    expect(wrapper.find('strong').exists()).toBe(false);
    expect(wrapper.find('em').exists()).toBe(false);
  });

  it('merender inline code, bold, italic, dan link menjadi elemen VNode native', () => {
    const wrapper = mount(MarkdownText, {
      props: {
        content:
          'Data *async* melalui `fetcher` dengan **response** dan link [Laravel](https://laravel.com).',
      },
    });

    expect(wrapper.find('em').text()).toBe('async');
    expect(wrapper.find('code').text()).toBe('fetcher');
    expect(wrapper.find('strong').text()).toBe('response');

    const link = wrapper.find('a');
    expect(link.exists()).toBe(true);
    expect(link.text()).toBe('Laravel');
    expect(link.attributes('href')).toBe('https://laravel.com');
    expect(link.attributes('target')).toBe('_blank');
  });
});
