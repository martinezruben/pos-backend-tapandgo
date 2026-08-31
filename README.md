# Tap&Go POS — Backend

Backend del sistema POS **Tap&Go** para la app Android. Construido con Laravel + MySQL, desplegado en Docker.

## Características

- **API para dispositivos Android** (Sanctum): registro por pairing token, login por licencia, y sincronización offline-first (`/api/sync/push` y `/api/sync/pull` con catálogo, usuarios, familias, subfamilias, productos e imágenes optimizadas).
- **Panel de administración** (guard `admin`): CRUD configurable por pantallas (`config/admin_screens.php`), matriz RBAC, licencias, transacciones con export Excel, import/export de productos, tokens de emparejamiento y logs de API.
- **Facturación Dominicana**: secuencias NCF con alerta de rango bajo; plan de e-invoicing DGII en `docs/implementacion-dgii.md`.

## Documentación

- Guía de instalación Docker: `documentation/docker/INSTALL_GUIDE.md`
- Referencia de la API: colecciones Postman en `postman/`
- Despliegue: `./deploy.sh` (ver `./deploy.sh help`)

## Tests

```bash
php artisan test
```
