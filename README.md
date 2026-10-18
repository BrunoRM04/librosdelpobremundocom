# Libros Pobre Mundo

## Búsqueda de libros por ISBN en el admin

En `admin/upload_books_table.php`, ingresá un ISBN de 10 o 13 dígitos y pulsá **Completar por ISBN**. OpenAI busca la edición en la web y completa título, autor, editorial, páginas, descripción y portada cuando encuentra datos respaldados por fuentes. Revisá los enlaces de las fuentes y los campos de la fila. Precio y stock se ingresan manualmente antes de **Subir libros**.

La búsqueda usa el modelo `gpt-5-nano` con la herramienta `web_search`; cada consulta genera cargos por búsqueda y por tokens. La clave se usa solo en el servidor y el endpoint exige sesión de admin y token CSRF.

1. Revocá la clave compartida en el chat y generá otra en el proyecto de OpenAI.
2. Configurá `OPENAI_API_KEY` como variable de entorno del servidor. Como alternativa, asignala a `$OPENAI_API_KEY` en `api/_shared/config.php`, que debe permanecer fuera de Git.
3. Reiniciá el servidor PHP si cambiaste su entorno y probá con un ISBN conocido desde la tabla del admin.

El archivo `api/_shared/config.example.php` muestra la configuración sin secretos. Un ISBN sin fuentes citadas o sin coincidencia exacta queda para carga manual; la API no publica el libro automáticamente.

Prueba local de validación y extracción: `php tests/openai-isbn.php`.
