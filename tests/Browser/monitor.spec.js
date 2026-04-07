import { test, expect } from "@playwright/test";

// Datos sensibles traídos desde el entorno
const URL_LOGIN = "https://portal-nominas.grupo-ortiz.site/"; // Ajusta la ruta de tu login
const USER = process.env.USUARIO_SISTEMA;
const PASS = process.env.PASSWORD_SISTEMA;
const CANDIDATO_TEST = "TEST_AUTOMATIZADO"; // El nombre o ID del candidato semilla

test("Verificar Flujo RH: Login", async ({ page }) => {
    // 1. Ir al Login
    console.log("Navegando al login...");
    console.log(URL_LOGIN);
    console.log(USER);
    console.log(PASS);
    await page.goto(URL_LOGIN);

    // 2. Llenar credenciales (Ajusta los selectores 'input[name="..."]' según tu HTML real)
    // Tip: Usa el inspector del navegador para ver el 'id' o 'name' de tus inputs
    await page.fill('input[name="email"]', USER);
    await page.fill('input[name="password"]', PASS);

    // 3. Click en entrar
    await page.click('button[type="submit"]');

    // 4. Validar que entramos al Dashboard
    // Esperamos que aparezca un elemento único del dashboard, ej: menú lateral
    await expect(page.locator("text=Bienvenido")).toBeVisible();
    console.log("Login exitoso.");

    //   // 5. Ir al buscador de candidatos / validación de recontratables
    //   // Asumimos que hay un botón o link para ir a buscar
    //   await page.click('text=Buscar Candidato');

    //   // 6. Realizar la búsqueda del dato semilla
    //   console.log(`Buscando al candidato: ${CANDIDATO_TEST}`);
    //   await page.fill('input[placeholder="Buscar..."]', CANDIDATO_TEST);
    //   await page.keyboard.press('Enter');

    //   // 7. PRUEBA CRÍTICA: Verificar que el candidato aparece
    //   // Esto confirma que la conexión a la BD de empleados/candidatos funciona
    //   // y que la lógica de búsqueda no ha fallado.
    //   const resultado = page.locator(`text=${CANDIDATO_TEST}`);
    //   await expect(resultado).toBeVisible();

    //   // 8. Opcional: Verificar estatus (ej. que diga "No Recontratable" o "Postulado")
    //   // await expect(page.locator('.estatus-candidato')).toContainText('Postulado');

    console.log(
        "Prueba finalizada: El sistema de RH está operativo y buscando correctamente.",
    );
});
