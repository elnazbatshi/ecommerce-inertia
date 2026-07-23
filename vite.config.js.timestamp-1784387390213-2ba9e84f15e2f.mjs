// vite.config.js
import { defineConfig } from "file:///D:/newProject/shop-inertia/node_modules/vite/dist/node/index.js";
import laravel from "file:///D:/newProject/shop-inertia/node_modules/laravel-vite-plugin/dist/index.js";
import tailwindcss from "file:///D:/newProject/shop-inertia/node_modules/@tailwindcss/vite/dist/index.mjs";
import vue from "file:///D:/newProject/shop-inertia/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import Components from "file:///D:/newProject/shop-inertia/node_modules/unplugin-vue-components/dist/vite.js";
import { PrimeVueResolver } from "file:///D:/newProject/shop-inertia/node_modules/@primevue/auto-import-resolver/index.mjs";
import { fileURLToPath, URL } from "node:url";
var __vite_injected_original_import_meta_url = "file:///D:/newProject/shop-inertia/vite.config.js";
var optimizeDeps = [
  "@inertiajs/vue3",
  "@primeuix/themes",
  "@primeuix/themes/aura",
  "@primevue/core/api",
  "axios",
  "jalaali-js",
  "laravel-vite-plugin/inertia-helpers",
  "primevue/accordion",
  "primevue/accordioncontent",
  "primevue/accordionheader",
  "primevue/accordionpanel",
  "primevue/autocomplete",
  "primevue/breadcrumb",
  "primevue/button",
  "primevue/chart",
  "primevue/checkbox",
  "primevue/column",
  "primevue/config",
  "primevue/confirmdialog",
  "primevue/confirmationservice",
  "primevue/datatable",
  "primevue/dialog",
  "primevue/dropdown",
  "primevue/editor",
  "primevue/fileupload",
  "primevue/iconfield",
  "primevue/image",
  "primevue/inputicon",
  "primevue/inputmask",
  "primevue/inputnumber",
  "primevue/inputtext",
  "primevue/menu",
  "primevue/message",
  "primevue/multiselect",
  "primevue/paginator",
  "primevue/password",
  "primevue/popover",
  "primevue/progressspinner",
  "primevue/select",
  "primevue/tag",
  "primevue/textarea",
  "primevue/toast",
  "primevue/toastservice",
  "primevue/treetable",
  "primevue/toggleswitch",
  "primevue/useconfirm",
  "primevue/usetoast",
  "vue",
  "vuedraggable"
];
var vite_config_default = defineConfig({
  plugins: [
    laravel({
      input: ["resources/css/app.css", "resources/js/app.js"],
      refresh: [
        "resources/views/**/*.blade.php",
        "routes/**/*.php"
      ]
    }),
    vue(),
    tailwindcss(),
    Components({
      resolvers: [...PrimeVueResolver()]
    })
  ],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./resources/js", __vite_injected_original_import_meta_url))
    }
  },
  server: {
    host: "127.0.0.1",
    port: 5173,
    strictPort: true,
    origin: "http://127.0.0.1:5173",
    cors: true,
    hmr: {
      host: "127.0.0.1"
    },
    watch: {
      ignored: [
        "**/storage/**",
        "**/vendor/**",
        "**/public/build/**"
      ]
    }
  },
  optimizeDeps: {
    include: optimizeDeps
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJEOlxcXFxuZXdQcm9qZWN0XFxcXHNob3AtaW5lcnRpYVwiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9maWxlbmFtZSA9IFwiRDpcXFxcbmV3UHJvamVjdFxcXFxzaG9wLWluZXJ0aWFcXFxcdml0ZS5jb25maWcuanNcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfaW1wb3J0X21ldGFfdXJsID0gXCJmaWxlOi8vL0Q6L25ld1Byb2plY3Qvc2hvcC1pbmVydGlhL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCB0YWlsd2luZGNzcyBmcm9tICdAdGFpbHdpbmRjc3Mvdml0ZSc7XG5pbXBvcnQgdnVlIGZyb20gJ0B2aXRlanMvcGx1Z2luLXZ1ZSc7XG5pbXBvcnQgQ29tcG9uZW50cyBmcm9tICd1bnBsdWdpbi12dWUtY29tcG9uZW50cy92aXRlJztcbmltcG9ydCB7IFByaW1lVnVlUmVzb2x2ZXIgfSBmcm9tICdAcHJpbWV2dWUvYXV0by1pbXBvcnQtcmVzb2x2ZXInO1xuaW1wb3J0IHsgZmlsZVVSTFRvUGF0aCwgVVJMIH0gZnJvbSAnbm9kZTp1cmwnO1xuXG5jb25zdCBvcHRpbWl6ZURlcHMgPSBbXG4gICAgJ0BpbmVydGlhanMvdnVlMycsXG4gICAgJ0BwcmltZXVpeC90aGVtZXMnLFxuICAgICdAcHJpbWV1aXgvdGhlbWVzL2F1cmEnLFxuICAgICdAcHJpbWV2dWUvY29yZS9hcGknLFxuICAgICdheGlvcycsXG4gICAgJ2phbGFhbGktanMnLFxuICAgICdsYXJhdmVsLXZpdGUtcGx1Z2luL2luZXJ0aWEtaGVscGVycycsXG4gICAgJ3ByaW1ldnVlL2FjY29yZGlvbicsXG4gICAgJ3ByaW1ldnVlL2FjY29yZGlvbmNvbnRlbnQnLFxuICAgICdwcmltZXZ1ZS9hY2NvcmRpb25oZWFkZXInLFxuICAgICdwcmltZXZ1ZS9hY2NvcmRpb25wYW5lbCcsXG4gICAgJ3ByaW1ldnVlL2F1dG9jb21wbGV0ZScsXG4gICAgJ3ByaW1ldnVlL2JyZWFkY3J1bWInLFxuICAgICdwcmltZXZ1ZS9idXR0b24nLFxuICAgICdwcmltZXZ1ZS9jaGFydCcsXG4gICAgJ3ByaW1ldnVlL2NoZWNrYm94JyxcbiAgICAncHJpbWV2dWUvY29sdW1uJyxcbiAgICAncHJpbWV2dWUvY29uZmlnJyxcbiAgICAncHJpbWV2dWUvY29uZmlybWRpYWxvZycsXG4gICAgJ3ByaW1ldnVlL2NvbmZpcm1hdGlvbnNlcnZpY2UnLFxuICAgICdwcmltZXZ1ZS9kYXRhdGFibGUnLFxuICAgICdwcmltZXZ1ZS9kaWFsb2cnLFxuICAgICdwcmltZXZ1ZS9kcm9wZG93bicsXG4gICAgJ3ByaW1ldnVlL2VkaXRvcicsXG4gICAgJ3ByaW1ldnVlL2ZpbGV1cGxvYWQnLFxuICAgICdwcmltZXZ1ZS9pY29uZmllbGQnLFxuICAgICdwcmltZXZ1ZS9pbWFnZScsXG4gICAgJ3ByaW1ldnVlL2lucHV0aWNvbicsXG4gICAgJ3ByaW1ldnVlL2lucHV0bWFzaycsXG4gICAgJ3ByaW1ldnVlL2lucHV0bnVtYmVyJyxcbiAgICAncHJpbWV2dWUvaW5wdXR0ZXh0JyxcbiAgICAncHJpbWV2dWUvbWVudScsXG4gICAgJ3ByaW1ldnVlL21lc3NhZ2UnLFxuICAgICdwcmltZXZ1ZS9tdWx0aXNlbGVjdCcsXG4gICAgJ3ByaW1ldnVlL3BhZ2luYXRvcicsXG4gICAgJ3ByaW1ldnVlL3Bhc3N3b3JkJyxcbiAgICAncHJpbWV2dWUvcG9wb3ZlcicsXG4gICAgJ3ByaW1ldnVlL3Byb2dyZXNzc3Bpbm5lcicsXG4gICAgJ3ByaW1ldnVlL3NlbGVjdCcsXG4gICAgJ3ByaW1ldnVlL3RhZycsXG4gICAgJ3ByaW1ldnVlL3RleHRhcmVhJyxcbiAgICAncHJpbWV2dWUvdG9hc3QnLFxuICAgICdwcmltZXZ1ZS90b2FzdHNlcnZpY2UnLFxuICAgICdwcmltZXZ1ZS90cmVldGFibGUnLFxuICAgICdwcmltZXZ1ZS90b2dnbGVzd2l0Y2gnLFxuICAgICdwcmltZXZ1ZS91c2Vjb25maXJtJyxcbiAgICAncHJpbWV2dWUvdXNldG9hc3QnLFxuICAgICd2dWUnLFxuICAgICd2dWVkcmFnZ2FibGUnLFxuXTtcblxuZXhwb3J0IGRlZmF1bHQgZGVmaW5lQ29uZmlnKHtcbiAgICBwbHVnaW5zOiBbXG4gICAgICAgIGxhcmF2ZWwoe1xuICAgICAgICAgICAgaW5wdXQ6IFsncmVzb3VyY2VzL2Nzcy9hcHAuY3NzJywgJ3Jlc291cmNlcy9qcy9hcHAuanMnXSxcbiAgICAgICAgICAgIHJlZnJlc2g6IFtcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL3ZpZXdzLyoqLyouYmxhZGUucGhwJyxcbiAgICAgICAgICAgICAgICAncm91dGVzLyoqLyoucGhwJyxcbiAgICAgICAgICAgIF0sXG4gICAgICAgIH0pLFxuICAgICAgICB2dWUoKSxcbiAgICAgICAgdGFpbHdpbmRjc3MoKSxcbiAgICAgICAgQ29tcG9uZW50cyh7XG4gICAgICAgICAgICByZXNvbHZlcnM6IFsuLi5QcmltZVZ1ZVJlc29sdmVyKCldLFxuICAgICAgICB9KSxcbiAgICBdLFxuICAgIHJlc29sdmU6IHtcbiAgICAgICAgYWxpYXM6IHtcbiAgICAgICAgICAgICdAJzogZmlsZVVSTFRvUGF0aChuZXcgVVJMKCcuL3Jlc291cmNlcy9qcycsIGltcG9ydC5tZXRhLnVybCkpLFxuICAgICAgICB9LFxuICAgIH0sXG4gICAgc2VydmVyOiB7XG4gICAgICAgIGhvc3Q6ICcxMjcuMC4wLjEnLFxuICAgICAgICBwb3J0OiA1MTczLFxuICAgICAgICBzdHJpY3RQb3J0OiB0cnVlLFxuICAgICAgICBvcmlnaW46ICdodHRwOi8vMTI3LjAuMC4xOjUxNzMnLFxuICAgICAgICBjb3JzOiB0cnVlLFxuICAgICAgICBobXI6IHtcbiAgICAgICAgICAgIGhvc3Q6ICcxMjcuMC4wLjEnLFxuICAgICAgICB9LFxuICAgICAgICB3YXRjaDoge1xuICAgICAgICAgICAgaWdub3JlZDogW1xuICAgICAgICAgICAgICAgICcqKi9zdG9yYWdlLyoqJyxcbiAgICAgICAgICAgICAgICAnKiovdmVuZG9yLyoqJyxcbiAgICAgICAgICAgICAgICAnKiovcHVibGljL2J1aWxkLyoqJyxcbiAgICAgICAgICAgIF0sXG4gICAgICAgIH0sXG4gICAgfSxcbiAgICBvcHRpbWl6ZURlcHM6IHtcbiAgICAgICAgaW5jbHVkZTogb3B0aW1pemVEZXBzLFxuICAgIH0sXG59KTtcbiJdLAogICJtYXBwaW5ncyI6ICI7QUFBc1EsU0FBUyxvQkFBb0I7QUFDblMsT0FBTyxhQUFhO0FBQ3BCLE9BQU8saUJBQWlCO0FBQ3hCLE9BQU8sU0FBUztBQUNoQixPQUFPLGdCQUFnQjtBQUN2QixTQUFTLHdCQUF3QjtBQUNqQyxTQUFTLGVBQWUsV0FBVztBQU44SCxJQUFNLDJDQUEyQztBQVFsTixJQUFNLGVBQWU7QUFBQSxFQUNqQjtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUNKO0FBRUEsSUFBTyxzQkFBUSxhQUFhO0FBQUEsRUFDeEIsU0FBUztBQUFBLElBQ0wsUUFBUTtBQUFBLE1BQ0osT0FBTyxDQUFDLHlCQUF5QixxQkFBcUI7QUFBQSxNQUN0RCxTQUFTO0FBQUEsUUFDTDtBQUFBLFFBQ0E7QUFBQSxNQUNKO0FBQUEsSUFDSixDQUFDO0FBQUEsSUFDRCxJQUFJO0FBQUEsSUFDSixZQUFZO0FBQUEsSUFDWixXQUFXO0FBQUEsTUFDUCxXQUFXLENBQUMsR0FBRyxpQkFBaUIsQ0FBQztBQUFBLElBQ3JDLENBQUM7QUFBQSxFQUNMO0FBQUEsRUFDQSxTQUFTO0FBQUEsSUFDTCxPQUFPO0FBQUEsTUFDSCxLQUFLLGNBQWMsSUFBSSxJQUFJLGtCQUFrQix3Q0FBZSxDQUFDO0FBQUEsSUFDakU7QUFBQSxFQUNKO0FBQUEsRUFDQSxRQUFRO0FBQUEsSUFDSixNQUFNO0FBQUEsSUFDTixNQUFNO0FBQUEsSUFDTixZQUFZO0FBQUEsSUFDWixRQUFRO0FBQUEsSUFDUixNQUFNO0FBQUEsSUFDTixLQUFLO0FBQUEsTUFDRCxNQUFNO0FBQUEsSUFDVjtBQUFBLElBQ0EsT0FBTztBQUFBLE1BQ0gsU0FBUztBQUFBLFFBQ0w7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLE1BQ0o7QUFBQSxJQUNKO0FBQUEsRUFDSjtBQUFBLEVBQ0EsY0FBYztBQUFBLElBQ1YsU0FBUztBQUFBLEVBQ2I7QUFDSixDQUFDOyIsCiAgIm5hbWVzIjogW10KfQo=
