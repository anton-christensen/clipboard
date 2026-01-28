<script setup lang="ts">
import { ref, watchEffect } from 'vue';

type Theme = 'DARK' | 'LIGHT';
type ThemeSelection = Theme | 'SYSTEM';

const STORAGE_KEY = 'user-selected-theme';
const BUTTONS: { icon: string; value: ThemeSelection }[] = [
  {
    icon: 'desktop_windows',
    value: 'SYSTEM',
  },
  {
    icon: 'light_mode',
    value: 'LIGHT',
  },
  {
    icon: 'dark_mode',
    value: 'DARK',
  },
];

const theme = ref<Theme>(getThemeFromLocalStorage() ?? getThemeFromMediaQuery());
const selectedTheme = ref<ThemeSelection>(getThemeFromLocalStorage() ?? 'SYSTEM');

function getThemeFromLocalStorage(): Theme | undefined {
  const value = localStorage.getItem(STORAGE_KEY);
  if (value === 'DARK' || value === 'LIGHT') {
    return value;
  }
}

function getThemeFromMediaQuery(): Theme {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'DARK' : 'LIGHT';
}

function setTheme(selection: ThemeSelection): void {
  if (selection === 'SYSTEM') {
    localStorage.removeItem(STORAGE_KEY);
    theme.value = getThemeFromMediaQuery();
  } else {
    localStorage.setItem(STORAGE_KEY, selection);
    theme.value = selection;
  }
  selectedTheme.value = selection;
}

watchEffect(() => document.documentElement.classList.toggle('dark', theme.value === 'DARK'));
</script>

<template>
  <div class="absolute right-4 bottom-4 select-none">
    <div
      class="relative flex gap-1 rounded-full bg-black/10 p-0.75 backdrop-blur-sm dark:bg-white/5"
    >
      <div
        class="absolute size-8 rounded-full bg-white ring ring-black/50 transition-transform dark:bg-gray-700 dark:ring-white/50"
        :class="{
          'translate-x-9': selectedTheme === 'LIGHT',
          'translate-x-18': selectedTheme === 'DARK',
        }"
      ></div>
      <div
        v-for="button in BUTTONS"
        :key="button.value"
        class="material-symbols-outlined z-10 size-8 cursor-pointer rounded-full p-1 hover:bg-white/50 dark:text-gray-400 dark:hover:bg-white/10"
        @click="setTheme(button.value)"
      >
        {{ button.icon }}
      </div>
    </div>
  </div>
</template>
