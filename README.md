# Wallet API

API REST robusta para una billetera virtual, desarrollada con **Laravel**, **PHP** y autenticación **JWT**.

El proyecto abarca el diseño y desarrollo completo de un sistema financiero escalable que incluye: autenticación, manejo de cuentas, transferencias seguras, agenda de contactos, simulación de plazos fijos, panel de administración y testing automatizado.

Todo el entorno de producción se encuentra desplegado en **AlwaysData**.

`https://afgamalerio.alwaysdata.net/front/
https://afgamalerio.alwaysdata.net/`

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
- **MySQL / SQLite** como motores de base de datos
- **JWT** (`php-open-source-saver/jwt-auth`) para autenticación
- **Swagger / OpenAPI** para documentación de endpoints
- **Composer** para dependencias
- **Node.js / npm** para los assets del frontend base
- **PHPUnit** para testing automatizado
- **Git / GitHub** para control de versiones y trabajo colaborativo
- **AlwaysData** para el despliegue en producción

---

## Requisitos

Antes de comenzar, es necesario contar con:

- PHP 8.3 o superior
- Composer
- Laravel Herd (recomendado para entorno local)
- Git
- Node.js y npm

> **Importante sobre PHP:** En entornos Windows con XAMPP, el comando `php` puede apuntar a versiones antiguas. Se recomienda usar Laravel Herd y anteponer `herd` a los comandos (`herd php artisan ...`).

---

## Instalación Local

### 1. Clonar el repositorio

```powershell
git clone [https://github.com/Masita134/Wallet-API.git](https://github.com/Masita134/Wallet-API.git)
cd Wallet-API
```

### 2. Instalar las dependencias

```powershell
herd composer install
```

### 3. Configurar variables de entorno

```powershell
cp .env.example .env
```

### 4. Generar claves de seguridad

```powershell
herd php artisan key:generate
herd php artisan jwt:secret
```
> **Nota:** `.env` contiene información sensible (como el `JWT_SECRET`) y nunca debe subirse al repositorio.

### 5. Configurar la Base de Datos y ejecutar Migraciones

Si utilizás SQLite localmente, creá el archivo primero:

```powershell
touch database/database.sqlite
```

Luego, ejecutá las migraciones y poblá la base con datos de prueba (seeders):

```powershell
herd php artisan migrate --seed
```

### 6. Instalar y compilar los assets del Frontend

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

* **API Base URL:** `http://127.0.0.1:8000/api/v1`
* **Documentación Swagger:** `http://127.0.0.1:8000/api/documentation`

---

## ¿De qué se trata la API?

Wallet API representa el motor backend de una billetera virtual. Sus características principales son:

- **Autenticación:** Registro y login seguro mediante JWT.
- **Gestión de Cuentas:** Cada usuario posee una cuenta única en ARS o USD con validación de saldo.
- **Operaciones Core:** Depósitos y transferencias a terceros.
- **Agenda:** Guardado y gestión de CBUs frecuentes.
- **Simulador Financiero:** Cálculo de rendimientos para plazos fijos.
- **Historial:** Registro paginado de movimientos (ingresos y egresos).
- **Panel Administrativo:** CRUD completo de usuarios, cuentas y transacciones protegido por roles.
- **Seguridad Transaccional:** Uso de `DB::transaction()` para garantizar la integridad del dinero.

---

## Autenticación y Credenciales de Prueba

Todas las rutas protegidas requieren un token JWT enviado en los headers:
`Authorization: Bearer <token>`

Al ejecutar el seeder (`php artisan migrate:fresh --seed`), se generan usuarios para facilitar las pruebas:

| Usuario | Email | Contraseña | Rol |
| :--- | :--- | :--- | :--- |
| Test User | `test@example.com` | `password` | user |
| Second User | `second@example.com` | `password` | user |
| Admin User | `admin@example.com` | `password` | admin |

---

## Endpoints Principales

*Todos los endpoints utilizan el prefijo `/api/v1`*

### Autenticación & Perfil
| Método | Endpoint | Privado | Descripción |
| :--- | :--- | :--- | :--- |
| POST | `/auth/register` | No | Registra un usuario y genera su cuenta con saldo 0. |
| POST | `/auth/login` | No | Devuelve el token JWT. |
| GET | `/profile` | Sí | Obtiene datos del usuario logueado. |
| PATCH | `/profile` | Sí | Actualiza los datos del perfil propio. |

### Operaciones Financieras
| Método | Endpoint | Privado | Descripción |
| :--- | :--- | :--- | :--- |
| GET | `/account` | Sí | Obtiene CBU y saldo actual. |
| POST | `/deposits` | Sí | Ingresa dinero a la cuenta propia. |
| POST | `/transfers` | Sí | Envía dinero a otro CBU. Valida saldo y actualiza ambas cuentas. |
| POST | `/investments/fixed-term/simulate` | Sí | Simula los intereses de un plazo fijo a 30 días (TNA 30%). |

### Historial y Agenda
| Método | Endpoint | Privado | Descripción |
| :--- | :--- | :--- | :--- |
| GET | `/movements` | Sí | Lista de movimientos paginada (15 por defecto, ordenable por fecha). |
| GET | `/cbu` | Sí | Lista de CBUs guardados. |
| POST | `/cbu/{cbu}/users/{idUser}` | Sí | Guarda un CBU en la agenda. |

### Administración (Requiere Rol Admin)
* **Usuarios:** `GET /admin/users` (CRUD de usuarios)
* **Cuentas:** `GET /admin/accounts` (CRUD de cuentas)
* **Movimientos:** `GET /admin/movements` (Auditoría de transacciones)

---

## Seguridad e Integridad de Datos

* **Aislamiento:** El backend resuelve la cuenta utilizando el usuario autenticado del token JWT. No se confía en parámetros `user_id` o `account_id` enviados por el cliente.
* **Transacciones BD:** Las transferencias ocurren dentro de un bloque `DB::transaction()`. Si el descuento de saldo funciona pero la acreditación al destino falla, toda la operación se revierte automáticamente.
* **Manejo de Errores:** Todos los errores (401, 403, 404, 422) retornan un formato JSON predecible sin exponer detalles internos o trazas de SQL.

---

## Estructura de Base de Datos

```text
User (Roles: user, admin)
 ├── Account (CBU único, balance, moneda)
 │    └── Movement (Tipo: deposit, transfer_in, transfer_out)
 └── Saved CBU (Agenda de terceros)
```

---

## Testing Automatizado

El proyecto utiliza **PHPUnit**. Los tests emplean el trait `RefreshDatabase` para ejecutar pruebas en un entorno aislado sin afectar los datos reales.

**Ejecutar la suite completa:**
```powershell
herd php artisan test
```

**Ejecutar un test específico (Ej: Transferencias):**
```powershell
herd php artisan test --filter=TransferTest
```

La suite cubre:
* Aislamiento de perfiles (401 / 403).
* Lógica matemática del simulador de plazos fijos.
* Restricciones de transferencias (saldos insuficientes).
* Paginación en historiales.

---

## Flujo de Trabajo con Git

El desarrollo se organiza mediante ramas por funcionalidad (Feature Branch Workflow).

1. La rama principal de desarrollo es `dev`.
2. Para cada ticket (Ej: `WAL-008`), se crea una rama específica:
   ```powershell
   git checkout -b feature/wal-008-transferencias
   ```
3. Al finalizar, se pushean los cambios y se abre un Pull Request hacia `dev`:
   ```powershell
   git push -u origin feature/wal-008-transferencias
   ```
4. Solo se mergea a `main` cuando la versión es estable y lista para producción (AlwaysData).

---

## Comandos Útiles

```powershell
herd php artisan route:list          # Ver todas las rutas
herd php artisan config:clear        # Limpiar caché de configuración
herd php artisan migrate:fresh       # Recrear la base (¡Borra datos!)
herd php artisan migrate:fresh --seed # Recrear base y cargar usuarios de prueba
```