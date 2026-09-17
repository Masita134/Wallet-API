# Wallet API

Proyecto de una API REST para una billetera virtual desarrollada con Laravel y PHP.

## Requisitos

Para ejecutar el proyecto se necesita:

- PHP
- Composer
- Laravel
- Git

## Instalación

Clonar el repositorio y entrar a la carpeta del proyecto.

Instalar las dependencias:

```bash

composer install 
```

Copiar el archivo .env.example y crear el .env:

```bash
cp .env.example .env
```
Generar la clave de la aplicación:

```bash
php artisan key:generate
```
La configuración de la base de datos se encuentra en el archivo .env

Migraciones

Para crear las tablas de la base de datos ejecutar:
```bash
php artisan migrate
```
Iniciar el proyecto

Para iniciar el servidor local:
```bash
php artisan serve
```
Luego se puede acceder desde:

http://localhost:8000 
