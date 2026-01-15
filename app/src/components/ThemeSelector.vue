<script setup lang="ts">
import { ref, watchEffect } from 'vue';

type Theme = 'DARK' | 'LIGHT';
const STORAGE_KEY = 'user-selected-theme';

const selectedTheme = ref(getThemeFromLocalStorage() ?? getThemeFromMediaQuery());

function getThemeFromLocalStorage(): Theme | undefined {
  const value = localStorage.getItem(STORAGE_KEY);
  if (value === 'DARK' || value === 'LIGHT') {
    return value;
  }
}

function getThemeFromMediaQuery(): Theme {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'DARK' : 'LIGHT';
}

function setTheme(theme: Theme | 'SYSTEM'): void {
  if (theme === 'SYSTEM') {
    localStorage.removeItem(STORAGE_KEY);
    selectedTheme.value = getThemeFromMediaQuery();
  } else {
    localStorage.setItem(STORAGE_KEY, theme);
    selectedTheme.value = theme;
  }
}

watchEffect(() =>
  document.documentElement.classList.toggle('dark', selectedTheme.value === 'DARK'),
);
</script>

<template>
  Current theme: {{ selectedTheme }}
  <button @click="setTheme('LIGHT')">Light</button>
  <button @click="setTheme('DARK')">Dark</button>
  <button @click="setTheme('SYSTEM')">System</button>
</template>
