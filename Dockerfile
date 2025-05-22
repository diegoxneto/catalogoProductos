# Usar una imagen base de PHP oficial
FROM php:8.2-apache

# Establecer el directorio de trabajo dentro del contenedor
WORKDIR /var/www/html

# Copiar todos los archivos de tu proyecto al directorio de trabajo del contenedor
COPY . .
RUN docker-php-ext-install pdo pdo_pgsql # <--- ¡Esta es la línea clave!

# Exponer el puerto 80 que usa Apache por defecto
EXPOSE 80