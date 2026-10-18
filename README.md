# Libros Pobre Mundo

## Búsqueda de libros por ISBN en el admin

En `admin/upload_books_table.php`, ingresá un ISBN de 10 o 13 dígitos y pulsá **Completar por ISBN**. OpenAI busca la edición en la web y completa título, autor, editorial, páginas y portada cuando encuentra datos respaldados por fuentes. Para la descripción, el servidor abre una ficha citada que muestra ese ISBN y extrae directamente su sinopsis completa, conservando los párrafos; no usa texto redactado o resumido por la IA. Si la ficha no permite comprobar la sinopsis original, el campo queda vacío y el admin muestra un aviso para completarlo manualmente. Revisá los enlaces de las fuentes y los campos de la fila. Precio y stock se ingresan manualmente antes de **Subir libros**.

La búsqueda usa el modelo `gpt-6-luna` con la herramienta `web_search`; cada consulta genera cargos por búsqueda y por tokens. El endpoint exige sesión de admin y token CSRF.

1. Configurá `$OPENAI_API_KEY` directamente en `api/_shared/config.php`. El valor de ese archivo tiene prioridad sobre cualquier variable de entorno `OPENAI_API_KEY` del servidor.
2. Subí el `config.php` actualizado al servidor y probá con un ISBN conocido desde la tabla del admin.
3. Si PHP muestra un error de certificado HTTPS, actualizá el archivo de CA que usa cURL en el servidor. En Windows se usa el almacén de certificados del sistema cuando está disponible.

El archivo `api/_shared/config.example.php` muestra la configuración sin secretos. Un ISBN sin fuentes citadas o sin coincidencia exacta queda para carga manual; la API no publica el libro automáticamente.

Prueba local de validación y extracción: `php tests/isbn-validation.php`.
