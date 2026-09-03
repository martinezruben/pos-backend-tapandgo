# AGENTS.md — Tap&Go POS Backend

Backend Laravel (PHP 8.3, Laravel 13) para la app POS Android "Tap&Go" + panel de administración Blade/Alpine/Tailwind. Marca del producto: **Tap&Go** (no confundir con menciones técnicas al framework Laravel, que son válidas).

## Comandos

```bash
php artisan test                          # suite completa
php artisan test tests/Feature/XxxTest.php  # test enfocado
vendor/bin/pint <archivos>                # formatter/lint (obligatorio antes de commit)
npm run build                             # assets Vite (obligatorio si se toca resources/js o CSS)
php artisan pos:generate-thumbnails       # backfill miniaturas WebP de imágenes existentes
./deploy.sh                               # despliegue Docker (build/up/logs)
```

## Estructura clave

- `app/Http/Controllers/Api/` — API para la app Android: `AuthController` (registro por pairing token + licencia), `SyncController` (push/pull offline-first), `ReportController`.
- `app/Http/Controllers/Admin/` — panel admin. El CRUD es **config-driven**: `ScreenCrudController` renderiza pantallas definidas en `config/admin_screens.php`. Para agregar una pantalla/reporte nuevo: entrada en `config/admin_screens.php` + grupo en `config/admin_nav_groups.php`; reportes usan `'readonly' => true`.
- `app/Services/ImageThumbnailService.php` — miniaturas WebP (máx 400px) por convención de ruta `{carpeta}/thumbs/{nombre}.webp`; `syncUrl()` resuelve la URL para el POS (externas pasan intactas).
- `app/Support/` — motor de grids (`AdminGridQuery`, `AdminGridCell`), RBAC (`AdminRbac`), pairing tokens.
- `config/` — `admin_screens.php` (pantallas/columnas/filtros/`field_order`/`visible_limit`), `admin_nav_groups.php`, `admin_rbac.php`, `sync_catalog.php`.
- `postman/` — mejor referencia de la API (colecciones con todos los contratos).

## Reglas de arquitectura

- Todos los modelos usan **UUID** como PK (trait `HasUuidPrimaryKey`); nunca asumir `id` autoincremental.
- RBAC: permisos `{recurso}.view/.edit/.delete` donde recurso = clave de pantalla con `-`→`_`. Pantallas readonly solo exponen `.view`. super-admin pasa por `Gate::before` (AppServiceProvider), no necesita permisos.
- API usa Sanctum con modelo `Device` + middleware `device.operational` (valida licencia). El panel usa guard `admin` sobre `admin_users`.
- Payloads del API en **camelCase**; timestamps en UTC ISO `Y-m-d\TH:i:sZ`. La clave del código de barras de producto es **`codebar`** (mapea de `products.barcode`; null si vacío).
- Los filtros de grid se definen en config (`apply.type`: `column`, `whereHas`, `date_from`, `date_to`); el orden de columnas del grid usa `grid.field_order`, independiente del orden de `fields` (form).

## Gotchas

- **Tests corren en sqlite en memoria** (`phpunit.xml`): las migraciones deben ser compatibles o hacer no-op para sqlite (ver ejemplo en `2026_08_23_000001_add_inactive_status_to_licenses_table.php`, que salta el `MODIFY COLUMN` de MySQL).
- 3 tests de `FamilyImageUploadTest` fallan siempre: no usan `RefreshDatabase` y requieren una BD real con `DemoPosSeeder`. No intentar "arreglarlos" sin saber esto.
- `intervention/image` v4: API es `decodePath()` + `save($path, quality:)` (no `read()`/`toWebp()`). Al guardar en ruta nueva del disco, crear el directorio antes (`Storage::disk('public')->makeDirectory()`).
- Las imágenes nuevas subidas desde el admin generan miniatura automáticamente; para imágenes previas existe `pos:generate-thumbnails`.
- Después de cambiar `config/` en producción: `php artisan config:clear` (o rebuild del contenedor).
- `deploy.sh` y Docker esperan que `npm run build` se haya corrido; GD está instalado en el Dockerfile (procesamiento de imágenes OK).

## Docs del proyecto

- `docs/implementacion-dgii.md` — plan de facturación electrónica DGII (NCF ya implementado en `app/Services/NcfService.php`).
- `documentation/docker/INSTALL_GUIDE.md` — instalación Docker desde cero.
