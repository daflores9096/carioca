# CARIOCA

Sistema de inventario, ventas y compras (API PHP + frontend Vue), listo para levantar en local con Docker.

## Stack

| Capa | Tecnología |
|------|------------|
| Frontend | Vue 3 + Vite + Bootstrap 5 |
| API | PHP 8.3 + Slim 4 |
| Base de datos | MariaDB 10.11 (`carioca`) |
| Entrada única | Nginx gateway en el puerto **48080** |

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/macOS) o Docker Engine + Compose v2 (Linux)
- Puerto libre: **48080**
- ~2 GB libres en disco (imagen + dump inicial)

## Arranque desde cero

```bash
git clone <url-del-repo> carioca
cd carioca
docker compose up -d --build
```

La primera vez:

1. Construye la imagen de la API.
2. Levanta MariaDB e importa `docker/db/init/01-carioca.sql` (puede tardar 1–2 minutos).
3. Instala dependencias PHP (`composer`) y Node (`npm`) dentro de los contenedores.
4. Expone la app en **http://localhost:48080**

Seguí el progreso:

```bash
docker compose ps
docker compose logs -f db
```

Cuando `carioca-app-db` esté `healthy` y el resto en `Up`, abrí:

**http://localhost:48080**

### Usuario inicial (desarrollo)

| Campo | Valor |
|-------|--------|
| Usuario | `admin` |
| Contraseña | `admin123` |
| Rol | Administrador |

Solo para local. No usar en producción.

### Recuperar contraseña

En el login: **¿Olvidaste tu contraseña?** → email del usuario → enlace válido 1 hora.

- **Desarrollo** sin SMTP (`APP_DEBUG=1`): la API responde con `dev_reset_url` para probar el flujo.
- **Producción**: configurá SMTP en `.env.prod` (`SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_ENCRYPTION`, `MAIL_FROM`, `APP_URL`).

## URLs

| Qué | URL |
|-----|-----|
| Aplicación | http://localhost:48080 |
| API health | http://localhost:48080/api/health |

La base de datos **no** se publica en el host (solo red interna Docker). Credenciales internas: `root` / `root`, BD `carioca`.

## Comandos útiles

```bash
# Estado
docker compose ps
docker compose logs -f

# Reiniciar (conserva datos)
docker compose restart

# Parar (conserva BD)
docker compose down

# Reset total (borra volúmenes e importa el dump de nuevo)
docker compose down -v
docker compose up -d --build
```

Si el frontend se ve desactualizado:

```bash
docker exec carioca-app-frontend sh -c "rm -rf /app/node_modules/.vite"
docker compose restart frontend
```

Luego recargá el navegador con **Ctrl+F5**.

## Estructura del repo

```
carioca/
├── app/                 # API Slim (PHP 8.3)
├── frontend/            # SPA Vue 3
├── docker/
│   ├── php83/           # Imagen Apache + PHP
│   ├── db/init/         # Dump y scripts SQL de arranque
│   └── gateway/         # Nginx (puerto 48080)
├── docker-compose.yml
└── README.md
```

## Funcionalidad

- Auth por sesión (cookies); login MD5 legacy → migra a bcrypt
- Artículos, clientes, proveedores
- Ventas y compras (stock / movimientos)
- Inventario físico
- Reportes, usuarios, respaldo SQL
- Impresión de notas de compra/venta

## Producción (NAS / servidor)

El Compose de desarrollo (`docker-compose.yml`) usa Vite en modo `dev`. Para producción usá:

```bash
cp .env.prod.example .env.prod
# Editar .env.prod: CORS_ORIGIN, DB_PASS, etc.

docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build
```

Qué cambia respecto a desarrollo:

| Capa | Desarrollo | Producción |
|------|------------|------------|
| Frontend | Vite HMR | Build estático servido por Nginx |
| API | Bind-mount + debug | Imagen con código + `APP_DEBUG=0` |
| Gateway | Proxy a Vite `:5173` | Proxy a frontend Nginx `:80` |

Si ya tenías la BD levantada con el Compose de desarrollo, el volumen `carioca_app_db_data` se reutiliza. **`DB_PASS` debe coincidir con la clave actual de MariaDB** (si era `root`, dejala o recreá con `docker compose … down -v`).

### HTTPS con Tailscale (igual que book-readings)

`book-readings` suele ocupar el **443** de `https://dflores.….ts.net`. Carioca puede ir en paralelo en otro puerto HTTPS de Tailscale (sin tocar el otro proyecto):

```bash
# Ver qué hay montado hoy
sudo tailscale serve status

# Exponer Carioca (Docker en :48080) con HTTPS de Tailscale en :8443
sudo tailscale serve --bg --https=8443 http://127.0.0.1:48080
```

En `.env.prod`:

```env
CORS_ORIGIN=https://dflores.tail134d69.ts.net:8443
APP_URL=https://dflores.tail134d69.ts.net:8443
```

Luego `docker compose -f docker-compose.prod.yml --env-file .env.prod up -d`.

App: `https://dflores.tail134d69.ts.net:8443`  
(Si usás **Funnel** para acceso fuera de la cola Tailscale, el comando equivalente es `sudo tailscale funnel --bg --https=8443 http://127.0.0.1:48080`.)

Detrás de reverse proxy HTTPS (Synology / dominio propio):

```env
CORS_ORIGIN=https://carioca.tudominio.com
# SESSION_COOKIE_SECURE se activa solo si CORS_ORIGIN es https://
```

Actualizar código en el servidor:

```bash
# subir cambios y reconstruir imágenes
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build
```

## Notas

- `vendor/` y `node_modules/` viven en volúmenes Docker en **desarrollo** (mejor rendimiento en Windows).
- El dump inicial está en `docker/db/init/01-carioca.sql`.
- Variable de CORS: `CORS_ORIGIN=http://localhost:48080` (ya configurada en Compose de desarrollo).
