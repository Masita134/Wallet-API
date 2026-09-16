# Wallet API

API REST para una billetera virtual desarrollada con Laravel, PHP y autenticacion JWT.

## Requisitos

- [Laravel Herd](https://herd.laravel.com/) para administrar PHP y Composer.
- PHP 8.3 o superior. Herd utiliza PHP 8.5 en el entorno de desarrollo actual.
- Git.
- Node.js y npm, necesarios para compilar los assets de Vite.

## Instalacion

Clonar el repositorio y entrar en la carpeta del proyecto:

```powershell
git clone <url-del-repositorio>
Set-Location Wallet-API
```

Instalar las dependencias con Herd:

```powershell
herd composer install
```

Crear el archivo de entorno y generar las claves de la aplicacion y de JWT:

```powershell
Copy-Item .env.example .env
herd php artisan key:generate
herd php artisan jwt:secret
```

El proyecto usa SQLite por defecto. El archivo `database/database.sqlite` se incluye en el proyecto; si no existe, crearlo antes de migrar:

```powershell
New-Item database/database.sqlite -ItemType File -Force
herd php artisan migrate
```

Para instalar y compilar los assets frontend:

```powershell
npm install
npm run build
```

## Ejecutar la aplicacion

Iniciar el servidor local:

```powershell
herd php artisan serve
```

La API queda disponible en `http://127.0.0.1:8000`.

## Pruebas

Ejecutar toda la suite:

```powershell
herd php artisan test
```

Ejecutar sólo las pruebas del perfil:

```powershell
herd php artisan test --filter=ProfileTest
```

## API

Las rutas usan el prefijo `/api/v1`. Las rutas protegidas requieren un token JWT en el header:

```text
Authorization: Bearer <token>
```

| Metodo | Endpoint | Requiere token | Descripcion |
| --- | --- | --- | --- |
| POST | `/api/v1/auth/register` | No | Registra un usuario y crea su cuenta. |
| POST | `/api/v1/auth/login` | No | Valida las credenciales y devuelve un JWT. |
| GET | `/api/v1/auth/me` | Si | Devuelve el usuario autenticado y su cuenta. |
| POST | `/api/v1/auth/logout` | Si | Invalida la sesion JWT actual. |
| GET | `/api/v1/profile` | Si | Devuelve `id`, `name` y `email` del usuario autenticado. |

Ejemplo de login:

```powershell
$login = Invoke-RestMethod `
	-Method Post `
	-Uri http://127.0.0.1:8000/api/v1/auth/login `
	-ContentType 'application/json' `
	-Body '{"email":"usuario@example.com","password":"password123"}'

$login.access_token
```

Ejemplo de consulta del perfil propio:

```powershell
Invoke-RestMethod `
	-Method Get `
	-Uri http://127.0.0.1:8000/api/v1/profile `
	-Headers @{ Authorization = "Bearer $($login.access_token)" }
```

Las rutas protegidas responden con JSON y estado `401` cuando falta el token o es invalido. El cliente no envia `user_id`: el usuario se obtiene exclusivamente del JWT.

## Configuracion relevante

- `DB_CONNECTION=sqlite`: base de datos local por defecto.
- `JWT_SECRET`: secreto usado para firmar los tokens; se genera con `herd php artisan jwt:secret` y no debe versionarse.
- `JWT_TTL`: duracion del token en minutos. El valor predeterminado es `60`.
