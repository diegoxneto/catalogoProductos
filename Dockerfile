# Usar la imagen oficial de MySQL como base
#FROM mysql:8.0

# Establecer variables de entorno para la configuración del contenedor
# Se pueden personalizar los valores según tus necesidades
#ENV MYSQL_ROOT_PASSWORD=root_password
#ENV MYSQL_DATABASE=mi_base_de_datos
#ENV MYSQL_USER=usuario
#ENV MYSQL_PASSWORD=usuario_password

# Exponer el puerto por defecto de MySQL
#EXPOSE 3306

# Usar una imagen base de PHP oficial
FROM php:8.2-apache

# Establecer el directorio de trabajo dentro del contenedor
WORKDIR /var/www/html

# Copiar todos los archivos de tu proyecto al directorio de trabajo del contenedor
COPY . .

# Configurar Apache para usar index.php por defecto (ya viene en la imagen apache)
# Por defecto, la imagen php:apache ya configura Apache para servir desde /var/www/html
# y ya incluye el módulo PHP.

# Exponer el puerto 80 que usa Apache por defecto
EXPOSE 80

# Comando de inicio del servidor web Apache (ya viene por defecto en la imagen php:apache)
# CMD ["apache2-foreground"]
# No necesitas un comando CMD explícito si la imagen ya lo tiene.