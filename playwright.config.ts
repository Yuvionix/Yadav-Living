import { defineConfig } from '@playwright/test';
export default defineConfig({testDir:'tests/browser',use:{baseURL:'http://localhost:8889',headless:true},workers:2,reporter:'list'});
