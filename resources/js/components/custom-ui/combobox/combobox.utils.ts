export type ComboboxOption = Record<string, unknown>;

export function getOptionValue(option: ComboboxOption, valueKey: string): unknown {
  return option[valueKey];
}

export function getOptionLabel(option: ComboboxOption, labelKey: string): string {
  const label = option[labelKey];

  return label == null ? '' : String(label);
}

export function isSameOption(
  first: ComboboxOption,
  second: ComboboxOption,
  valueKey: string,
): boolean {
  return Object.is(getOptionValue(first, valueKey), getOptionValue(second, valueKey));
}

export function mergeOptions(optionGroups: ComboboxOption[][], valueKey: string): ComboboxOption[] {
  const merged: ComboboxOption[] = [];

  for (const options of optionGroups) {
    for (const option of options) {
      const index = merged.findIndex((current) => isSameOption(current, option, valueKey));

      if (index === -1) {
        merged.push(option);
      } else {
        merged[index] = option;
      }
    }
  }

  return merged;
}

export function defaultFilterOption(
  option: ComboboxOption,
  search: string,
  labelKey: string,
): boolean {
  return getOptionLabel(option, labelKey).toLocaleLowerCase().includes(search.toLocaleLowerCase());
}
