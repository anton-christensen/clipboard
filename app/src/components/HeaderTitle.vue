<script setup lang="ts">
import { onMounted } from 'vue';

const adjectives = [
  'binary',
  'cloud',
  'cyber',
  'cyberspace',
  'digital',
  'e-',
  'fully automated gay space',
  'fully connected',
  'futuristic',
  'matrix',
  'net',
  'online',
  'silicon',
  'techno',
  'virtual',
  'web',
  'wired',
  'world wide',
  'pretty fun',
];

const randomAdjective = adjectives[Math.floor(Math.random() * adjectives.length)];
document.title = `The ${randomAdjective} Clipboard`;
</script>

<template>
  <div class="flex justify-center">
    <h1
      class="font-heading group text-center text-4xl text-sky-600 select-none lg:text-6xl xl:text-8xl"
    >
      The
      <span
        class="animate-spin-slow relative inline-block [animation-play-state:paused] group-hover:[animation-play-state:running] before:absolute before:-inset-1 before:block before:-skew-y-3 before:bg-pink-500"
      >
        <span class="relative text-amber-300">{{ randomAdjective }}</span>
      </span>
      Clipboard
    </h1>
  </div>
</template>
