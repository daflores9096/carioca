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

## Notas

- `vendor/` y `node_modules/` viven en volúmenes Docker (mejor rendimiento en Windows).
- El dump inicial está en `docker/db/init/01-carioca.sql`.
- Variable de CORS: `CORS_ORIGIN=http://localhost:48080` (ya configurada en Compose).
