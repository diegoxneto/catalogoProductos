FROM php:8.2-apache

# Establecer el directorio de trabajo dentro del contenedor
WORKDIR /var/www/html

# Copiar todos los archivos de tu proyecto al directorio de trabajo del contenedor
COPY . .

# Instalar dependencias del sistema operativo para PostgreSQL
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    rm -rf /var/lib/apt/lists/*

# Instalar las extensiones de PDO para PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# Exponer el puerto 80 que usa Apache por defecto
EXPOSE 80