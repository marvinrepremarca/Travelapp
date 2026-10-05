# ADR-0005: Documentos PDF con spatie/laravel-pdf y el motor DomPDF

- **Estado:** Aceptado (2026-10-02)
- **Contexto:** La Fase 2.5 necesita cotizaciones, vouchers e itinerarios en PDF con la marca de la agencia. El stack fijaba `spatie/laravel-pdf`, que en su versión 2 admite varios motores: Browsershot (Node + Chromium), Chrome PHP (Chromium), DomPDF (PHP puro), Gotenberg y WeasyPrint (servicios externos).
- **Decisión:** `spatie/laravel-pdf` ^2 con el motor **DomPDF** (`dompdf/dompdf` ^3), aprobado por el usuario. Recursos remotos deshabilitados (anti-SSRF): el logo se incrusta como data URI. Módulo `Documents` con el contrato `DocumentRenderer` y el diseño base `documents::layout`; cada módulo (Quotes, Bookings) aporta su plantilla y autoriza su descarga.
- **Consecuencias:**
  - Funciona en XAMPP y en cualquier hosting sin binarios adicionales.
  - DomPDF no soporta Tailwind, flex/grid, variables CSS ni `oklch()`: los PDF usan `resources/css/pdf.css` con los tokens convertidos a hexadecimal (excepción documentada en `rules/frontend.md`).
  - Si en el futuro se necesitan diseños más ricos, se cambia el motor en `config/laravel-pdf.php` (`LARAVEL_PDF_DRIVER`) sin tocar los módulos.
  - La generación es síncrona (documentos de pocas páginas); los envíos masivos irán en cola.
