# Libros Pobre Mundo

## Búsqueda de libros por ISBN en el admin

En `admin/upload_books_table.php`, ingresá un ISBN de 10 o 13 dígitos y pulsá **Completar por ISBN**. OpenAI busca fichas de librerías, editoriales y distribuidores de la edición exacta. El servidor lee los datos estructurados de esas fichas para completar título, autor, editorial, páginas y portada; usa los datos de la búsqueda para los campos que falten. La sinopsis se extrae literalmente de una ficha que muestra ese ISBN, conservando sus párrafos. Si no se puede extraer, se puede cargar una descripción extensa generada por IA y el admin la señala para revisión. Si tampoco hay datos suficientes para generarla, la descripción queda vacía. Revisá los enlaces y los campos de la fila. Precio y stock se ingresan manualmente antes de **Subir libros**.

La búsqueda usa el modelo `gpt-6-luna` con la herramienta `web_search`; cada consulta genera cargos por búsqueda y por tokens. El endpoint exige sesión de admin y token CSRF.

1. Configurá `$OPENAI_API_KEY` directamente en `api/_shared/config.php`. El valor de ese archivo tiene prioridad sobre cualquier variable de entorno `OPENAI_API_KEY` del servidor.
2. Subí `admin/upload_books_table.php`, `api/admin/isbn-fuentes.php` y `api/_shared/openai-isbn.php` al servidor; mantené allí tu `config.php` privado. Probá con un ISBN conocido desde la tabla del admin. Actualizar solo `config.php` no instala las correcciones de la búsqueda.
3. Si PHP muestra un error de certificado HTTPS, actualizá el archivo de CA que usa cURL en el servidor. En Windows se usa el almacén de certificados del sistema cuando está disponible.

El archivo `api/_shared/config.example.php` muestra la configuración sin secretos. Un ISBN sin fuentes citadas o sin coincidencia exacta queda para carga manual; la API no publica el libro automáticamente.

Prueba local de validación y extracción: `php tests/isbn-validation.php`.
