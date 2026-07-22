<script setup lang="ts">
import type { ComboboxInputEmits, ComboboxInputProps } from 'reka-ui';

import type { HTMLAttributes } from 'vue';
import { SearchIcon } from '@lucide/vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxInput, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';
import { InputGroup, InputGroupAddon } from '@/components/ui/input-group';

defineOptions({
  inheritAttrs: false,
});

const props = defineProps<
  ComboboxInputProps & {
    class?: HTMLAttributes['class'];
    inputClass?: HTMLAttributes['class'];
  }
>();

const emits = defineEmits<ComboboxInputEmits>();

const delegatedProps = reactiveOmit(props, 'class', 'inputClass');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <InputGroup
    :class="cn('mx-3 mt-2 mb-1 h-8 w-auto border-input/30 bg-input/30 shadow-none', props.class)"
  >
    <InputGroupAddon>
      <SearchIcon class="size-4 shrink-0 opacity-50" />
    </InputGroupAddon>
    <ComboboxInput
      data-slot="combobox-input"
      :class="
        cn(
          'flex-1 outline-hidden disabled:cursor-not-allowed disabled:opacity-50',
          props.inputClass,
        )
      "
      v-bind="{ ...$attrs, ...forwarded }"
    />
  </InputGroup>
</template>
