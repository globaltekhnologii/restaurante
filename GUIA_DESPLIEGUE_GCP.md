# Guía de Despliegue en Google Cloud Platform (GCP)

Esta guía detalla los pasos para subir tu aplicación "Restaurante" a Google App Engine y configurar la base de datos en la nube.

---

## 1. Requisitos Previos

1.  tener una cuenta de **Google Cloud Platform** activa.
2.  Tener instalado **Google Cloud SDK** (comando `gcloud`) en tu computadora.
    *   Si no lo tienes, descárgalo e instálalo desde: [https://cloud.google.com/sdk/docs/install](https://cloud.google.com/sdk/docs/install)

---

## 2. Preparar Base de Datos en la Nube (Cloud SQL)

Tu aplicación local usa XAMPP (MySQL), pero en la nube usaremos **Cloud SQL per MySQL**.

### Paso 2.1: Crear Instancia SQL
1.  Entra a la consola de GCP: [https://console.cloud.google.com/sql](https://console.cloud.google.com/sql)
2.  Haz clic en **"Crear instancia"** -> Selecciona **MySQL**.
3.  Configura:
    *   **ID de la instancia**: ej. `restaurante-db`
    *   **Contraseña de root**: ¡EEscribe una segura y guárdala! (Elcabezon11?)
    *   **Versión**: MySQL 8.0.
    *   **Región**: Elige `us-central1` (o la más cercana a ti).
    *   **Edición**: "Enterprise" o "Sandbox" (para pruebas es más barato).
4.  Espera a que termine de crearse (toma unos minutos).

### Paso 2.2: Crear la Base de Datos
1.  En la página de tu instancia, ve a la pestaña **"Bases de datos"** (menú izquierdo).
2.  Haz clic en **"Crear base de datos"**.
3.  Nombre: `menu_restaurante` (Debe coincidir con la de tu código).
4.  Juego de caracteres: `utf8mb4`.

### Paso 2.3: Importar tus Datos (Backup Local)
1.  En tu PC, genera un archivo `.sql` de tu base de datos actual. (Ya tenemos `admin_respaldos.php`, pero lo más fiable es usar `mysqldump` manual o phpMyAdmin).
    *   Ve a `http://localhost/phpmyadmin`
    *   Selecciona `menu_restaurante` -> Exportar -> Formato SQL -> "Continuar".
    *   Guarda el archivo como `backup_nube.sql`.
2.  En la consola de GCP (Cloud SQL), ve a **"Importar"**.
3.  Te pedirá subir el archivo a un "Bucket" de Cloud Storage. Sigue los pasos para subir tu `backup_nube.sql` y selecciónalo.
4.  En "Base de datos de destino", selecciona `menu_restaurante`.
5.  Haz clic en **"Importar"**.

---

## 3. Configurar la Aplicación

### Paso 3.1: Obtener el "Nombre de conexión"
1.  En la página "Descripción general" de tu instancia SQL, busca el cuadro "Conectar a esta instancia".
2.  Copia el **"Nombre de conexión de la instancia"**.
    *   Se ve así: `proyecto:region:instancia` (ej: `micros-saas:us-central1:restaurante-db`).

### Paso 3.2: Editar `app.yaml`
1.  Abre el archivo `app.yaml` en tu carpeta del proyecto.
2.  Reemplaza los valores con tus datos reales:

```yaml
env_variables:
  CLOUDSQL_CONNECTION_NAME: "PEGAR_AQUI_TU_NOMBRE_DE_CONEXION"
  DB_USER: "root"
  DB_PASSWORD: "TU_CONTRASEÑA_DE_CLOUD_SQL"
  DB_NAME: "menu_restaurante"
```

---

## 4. Desplegar (Subir a Internet)

1.  Abre una terminal (`cmd` o `PowerShell`) en la carpeta de tu proyecto (`C:\xampp\htdocs\Restaurante`).
2.  Inicia sesión en Google:
    ```bash
    gcloud auth login
    ```
3.  Selecciona tu proyecto:
    ```bash
    gcloud config set project ID_DE_TU_PROYECTO
    ```
4.  Despliega la app:
    ```bash
    gcloud app deploy
    ```
5.  Confirma con `Y`.

Al finalizar, te dará una URL (ej: `https://tu-proyecto.uc.r.appspot.com`). ¡Esa es tu página web en vivo! 🚀

---

## Troubleshooting (Solución de Problemas)

*   **Error de conexión a BD**: Verifica que el `CLOUDSQL_CONNECTION_NAME` en `app.yaml` sea exacto y que la contraseña sea correcta.
*   **Error "No database selected"** o **"Unknown database"**:
    *   *Causa*: La base de datos no existe en la nube o el archivo SQL no sabe cuál usar.
    *   *Solución*: Abre tu archivo `.sql` y agrega esto al **puro principio**:
        ```sql
        CREATE DATABASE IF NOT EXISTS menu_restaurante;
        USE menu_restaurante;
        ```
    *   Guarda, vuelve a subir a Cloud Storage e intenta importar de nuevo.
*   **Error "BLOB, TEXT column can't have a default value"**:
    *   *Causa*: MySQL en la nube es más estricto. Las columnas de texto largo no pueden tener valor por defecto.
    *   *Solución*: Busca en tu archivo `.sql` la columna `horario_atencion` (o la que falle).
    *   Elimina la parte que dice `DEFAULT '...'`.
    *   Ejemplo: cambia `horario_atencion TEXT DEFAULT 'algo'`  a  `horario_atencion TEXT`.
*   **Permisos de API**: Si es la primera vez, tal vez debas habilitar la "Cloud SQL Admin API" en tu proyecto de Google.
*   **Imágenes no cargan**: Las imágenes subidas localmente (`img/platos`) NO se sincronizan automáticamente si las sube el usuario *después* del despliegue. App Engine es "read-only" para el sistema de archivos local.
    *   *Solución a futuro*: Configurar un Bucket de Storage para guardar imágenes. Por ahora, asegúrate de subir todas las imágenes en el despliegue inicial.

---
**¿Dudas?** Revisa la configuración en `config.php`.
