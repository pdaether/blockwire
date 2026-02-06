import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    lib: {
      entry: './resources/js/editor.js',
      formats: ['iife'],
      name: 'BlockWire',
      fileName: () => 'editor.js',
      cssFileName: 'editor',
    },
    outDir: 'public',
    emptyOutDir: false,
    minify: 'esbuild',
    sourcemap: false,
    cssMinify: true,
  },
});
