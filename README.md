# Wallet API

API REST para una billetera virtual desarrollada con **Laravel**, **PHP** y autenticación **JWT**.

El proyecto fue desarrollado como trabajo grupal para practicar el diseño y desarrollo de una API REST, autenticación, manejo de cuentas y movimientos, validaciones, persistencia con base de datos y testing automatizado.

---

## Autores

- **Fernandez Gamalerio, Anahi**
- **Gordillo Panighini, José Luís**
- **Holstein, Maximo**
- **Martinez Godoy, Agustina**

---

## Tecnologías

- **PHP** 8.3 o superior
- **Laravel** 13
- **Laravel Herd** para administrar PHP y Composer en el entorno local
- **SQLite** como base de datos local
- **JWT** para autenticación
- **Composer** para dependencias PHP
- **Node.js / npm** para los assets del proyecto
- **PHPUnit** para testing
- **Git / GitHub** para control de versiones

---

## Requisitos

Antes de comenzar, tener instalado:

- PHP 8.3 o superior
- Composer
- Laravel Herd (recomendado para este proyecto)
- Git
- Node.js y npm

### Importante sobre PHP

En el entorno de desarrollo de este proyecto se utiliza **Laravel Herd** porque permite trabajar con la versión de PHP requerida por Laravel y las dependencias del proyecto.

En Windows, si también tenés XAMPP instalado, `php` puede apuntar a otra versión de PHP. Por eso, para los comandos de Artisan y Composer de este proyecto se recomienda utilizar:

```powershell
herd php artisan ...
herd composer ...
```

---

## Instalación

### 1. Clonar el repositorio

```powershell
git clone https://github.com/Masita134/Wallet-API.git
Set-Location Wallet-API
```

### 2. Instalar las dependencias

```powershell
herd composer install
```

### 3. Crear el archivo `.env`

```powershell
Copy-Item .env.example .env
```

### 4. Generar la clave de Laravel

```powershell
herd php artisan key:generate
```

### 5. Generar el secreto de JWT

```powershell
herd php artisan jwt:secret
```

Esto genera `JWT_SECRET` en el archivo `.env`.

> **Importante:** `.env` contiene información sensible y no debe subirse al repositorio.

### 6. Configurar SQLite

El proyecto utiliza SQLite para la base de datos local.

Si el archivo todavía no existe:

```powershell
New-Item database/database.sqlite -ItemType File -Force
```

Luego ejecutar las migraciones:

```powershell
herd php artisan migrate
```

### 7. Instalar y compilar los assets

```powershell
npm install
npm run build
```

---

## Ejecutar la aplicación

Para iniciar el servidor local:

```powershell
herd php artisan serve
```

La API estará disponible en:

```text
http://127.0.0.1:8000
```

---

## ¿De qué se trata la API?

Wallet API representa una billetera virtual en la que cada usuario puede tener una cuenta asociada.

La aplicación contempla:

- Registro de usuarios.
- Inicio y cierre de sesión mediante JWT.
- Consulta del usuario autenticado.
- Consulta del perfil propio.
- Consulta de la cuenta propia.
- Gestión de CBUs guardados.
- Depósitos.
- Consulta de movimientos.
- Aislamiento de los datos de cada usuario.
- Validación de datos de entrada.
- Respuestas JSON con códigos HTTP.
- Tests automatizados.

El acceso a los recursos privados se realiza mediante el token JWT generado durante el login.

---

## Autenticación

Las rutas privadas requieren un token JWT enviado mediante el header:

```text
Authorization: Bearer <token>
```

### Ejemplo de login

En PowerShell:

```powershell
$login = Invoke-RestMethod `
    -Method Post `
    -Uri http://127.0.0.1:8000/api/v1/auth/login `
    -ContentType 'application/json' `
    -Body '{"email":"usuario@example.com","password":"password123"}'

$login.access_token
```

El valor de `access_token` debe utilizarse para acceder a las rutas protegidas.

### Ejemplo de consulta del perfil

```powershell
Invoke-RestMethod `
    -Method Get `
    -Uri http://127.0.0.1:8000/api/v1/profile `
    -Headers @{ Authorization = "Bearer $($login.access_token)" }
```

Las rutas protegidas responden con JSON y estado `401 Unauthorized` cuando falta el token o el token no es válido.

---

## Endpoints

Todas las rutas de la API utilizan el prefijo:

```text
/api/v1
```

### Autenticación

| Método | Endpoint | Token | Descripción |
| --- | --- | --- | --- |
| POST | `/api/v1/auth/register` | No | Registra un usuario y crea su cuenta. |
| POST | `/api/v1/auth/login` | No | Valida las credenciales y devuelve un JWT. |
| GET | `/api/v1/auth/me` | Sí | Devuelve información del usuario autenticado y su cuenta. |
| POST | `/api/v1/auth/logout` | Sí | Invalida la sesión JWT actual. |

### Usuario y cuenta

| Método | Endpoint | Token | Descripción |
| --- | --- | --- | --- |
| GET | `/api/v1/profile` | Sí | Devuelve `id`, `name` y `email` del usuario autenticado. |
| GET | `/api/v1/account` | Sí | Devuelve la cuenta asociada al usuario autenticado. |

### CBUs guardados

| Método | Endpoint | Token | Descripción |
| --- | --- | --- | --- |
| POST | `/api/v1/cbu/{cbu}/users/{idUser}` | Sí | Guarda un CBU asociado a un usuario. |
| GET | `/api/v1/cbu` | Sí | Consulta los CBUs guardados. |
| DELETE | `/api/v1/cbu/{cbu}/users/{idUser}` | Sí | Elimina un CBU guardado. |

### Depósitos

| Método | Endpoint | Token | Descripción |
| --- | --- | --- | --- |
| POST | `/api/v1/deposits` | Sí | Registra un depósito en la cuenta del usuario autenticado. |

El depósito recibe un monto positivo y modifica únicamente la cuenta asociada al usuario autenticado.

### Movimientos

| Método | Endpoint | Token | Descripción |
| --- | --- | --- | --- |
| GET | `/api/v1/movements` | Sí | Consulta los movimientos de la cuenta del usuario autenticado. |

La consulta de movimientos contempla:

- Paginación.
- `15` elementos por página por defecto.
- Hasta `100` elementos por página.
- Orden ascendente o descendente por fecha.
- Orden descendente por defecto.
- CBU de contraparte cuando corresponde.
- Exclusión de operaciones rechazadas.
- Aislamiento de los movimientos pertenecientes a otros usuarios.

Ejemplos de parámetros:

```text
GET /api/v1/movements
GET /api/v1/movements?page=2
GET /api/v1/movements?per_page=25
GET /api/v1/movements?order=asc
GET /api/v1/movements?page=2&per_page=25&order=asc
```

---

## Movimientos

Los movimientos se encuentran asociados a una cuenta.

Actualmente se contemplan los siguientes tipos:

```text
deposit
transfer_out
transfer_in
```

### CBU de contraparte

El campo `counterparty_cbu` representa el CBU de la otra cuenta involucrada en una operación.

| Tipo de movimiento | `counterparty_cbu` |
| --- | --- |
| `deposit` | `null` |
| `transfer_out` | CBU de la cuenta receptora |
| `transfer_in` | CBU de la cuenta emisora |

La cuenta propia se identifica mediante la cuenta asociada al usuario autenticado, por lo que el cliente no debe enviar un `account_id` para consultar los movimientos propios.

---

## Seguridad y aislamiento de datos

Las operaciones privadas se resuelven utilizando el usuario autenticado a partir del JWT.

Esto permite evitar que un cliente pueda consultar o modificar información de otra cuenta enviando identificadores ajenos.

En particular:

- No se confía en un `user_id` enviado por el cliente para determinar el usuario autenticado.
- No se utiliza un `account_id` enviado por el cliente para seleccionar la cuenta en operaciones protegidas.
- Las contraseñas no se devuelven en las respuestas.
- El secreto `JWT_SECRET` se mantiene en `.env`.
- Las rutas privadas requieren autenticación JWT.

---

## Base de datos

La aplicación utiliza SQLite durante el desarrollo.

Las principales entidades son:

```text
User
 └── Account
      └── Movement
```

Un usuario posee una cuenta y una cuenta puede tener múltiples movimientos.

También existe la gestión de cuentas/CBUs guardados para facilitar futuras operaciones.

---

## Estructura principal del proyecto

```text
Wallet-API/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   └── Models/
├── database/
│   ├── factories/
│   ├── migrations/
│   ├── seeders/
│   └── database.sqlite
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── composer.json
├── package.json
└── README.md
```

---

## Testing

El proyecto utiliza PHPUnit a través de Laravel.

### Ejecutar todos los tests

```powershell
herd php artisan test
```

### Ejecutar un archivo específico

Por ejemplo:

```powershell
herd php artisan test --filter=ProfileTest
```

```powershell
herd php artisan test --filter=DepositTest
```

```powershell
herd php artisan test --filter=MovementTest
```

### Base de datos de testing

Los tests utilizan `RefreshDatabase` para trabajar con una base de datos limpia y aislada durante las pruebas.

Esto permite verificar cambios en usuarios, cuentas y movimientos sin dejar datos de pruebas persistentes.

---

## Comandos útiles

### Ver las rutas disponibles

```powershell
herd php artisan route:list
```

### Limpiar la configuración

```powershell
herd php artisan config:clear
```

### Recrear completamente la base de datos

> Usar este comando únicamente en desarrollo/testing, ya que elimina las tablas existentes.

```powershell
herd php artisan migrate:fresh
```

### Recrear la base de datos y ejecutar seeders

```powershell
herd php artisan migrate:fresh --seed
```

---

## Variables de entorno

Las variables principales utilizadas por la aplicación incluyen:

```env
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=sqlite

JWT_SECRET=
JWT_TTL=60
```

El valor de `JWT_SECRET` debe generarse mediante:

```powershell
herd php artisan jwt:secret
```

No compartir ni versionar el archivo `.env`.

---

## Flujo de trabajo con Git

El proyecto se desarrolla utilizando ramas por funcionalidad.

Ejemplo:

```text
dev
 ├── feature/WAL-004-login-jwt
 ├── wal-005
 ├── wal-007
 └── wal-009
```

La idea es que cada integrante trabaje sobre una funcionalidad concreta y luego integre los cambios mediante un Pull Request hacia `dev`.

### Antes de comenzar una tarea

Actualizar la rama de desarrollo:

```powershell
git checkout dev
git pull origin dev
```

Crear la rama correspondiente:

```powershell
git checkout -b wal-009
```

### Revisar cambios antes del commit

```powershell
git status
git diff
git diff --check
```

### Crear un commit

```powershell
git add .
git commit -m "feat: implementar consulta de movimientos"
```

### Subir la rama

```powershell
git push -u origin wal-009
```

Luego se crea un Pull Request:

```text
wal-009 → dev
```

---

## Objetivo del proyecto

El objetivo de Wallet API es construir una API REST funcional para una billetera virtual aplicando buenas prácticas de desarrollo backend:

- Separación de responsabilidades.
- Validación de entradas.
- Autenticación y autorización.
- Persistencia de datos.
- Relaciones entre modelos.
- Respuestas HTTP y JSON consistentes.
- Protección de información privada.
- Testing automatizado.
- Trabajo colaborativo con Git y GitHub.

El proyecto se desarrolla de forma incremental a partir de historias de usuario, manteniendo cada funcionalidad aislada y testeada antes de integrarla a la rama `dev`.