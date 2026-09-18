# Genesis Profesional — Sistema de Gestión de Pasantías y Prácticas

Aplicación web integral para la administración, seguimiento y evaluación de pasantías profesionales. Diseñada para centralizar la comunicación y los flujos de trabajo entre estudiantes/pasantes, supervisores y vicedecanato/administración, incorporando generación de CVs con exportación PDF, control de informes de avance, gestión de vacantes y asistencia inteligente con IA.

---

## Stack Tecnológico

- **Frontend:** Vue 3, Vite, Tailwind CSS v4, Axios, jsPDF, vuedraggable.
- **Backend:** PHP 8.2+, Laravel 12, Laravel DomPDF, Smalot PDFParser.
- **Base de Datos:** Postgresql .

---

## Estrategia de Ramas

```text
main
└── alpha
    ├── feature/auth-roles
    ├── feature/pasante-cv-builder
    ├── feature/informes-evaluacion
    ├── feature/vacantes-postulaciones
    ├── feature/ai-assistant
    └── feature/chat-realtime
├── release/v1.0.0
└── hotfix/fix-token-auth
```

### Descripción de cada rama

| Rama        | Propósito                                                  | Sale de   | Merge a              |
| :---------- | :--------------------------------------------------------- | :-------- | :------------------- |
| `main`      | Código en producción. Estable y nunca se toca directamente | —         | —                    |
| `alpha`     | Integración del equipo. Siempre funcional y actualizada    | `main`    | `main` vía `release` |
| `feature/*` | Una funcionalidad o módulo nuevo por rama                  | `alpha`   | `alpha`              |
| `fix/*`     | Corrección de bugs encontrados en `alpha`                  | `alpha`   | `alpha`              |
| `hotfix/*`  | Corrección crítica de urgencia en producción               | `main`    | `main` + `alpha`     |

### Reglas de ramas

- ✅ Toda rama nueva parte desde `alpha` actualizado
- ✅ Los nombres de rama son descriptivos y en minúsculas (`feature/nombre-descriptivo`)
- ✅ Una rama = una sola funcionalidad o corrección
- ✅ Toda rama entra por Pull Request, nunca por push directo
- ✅ Mínimo 1 aprobación (Code Review) antes de mergear
- ✅ La rama se elimina después de haberse integrado el merge
- ❌ Nunca hacer push directo a `main` o `alpha`
- ❌ Nunca hacer force push (`git push --force`) en ramas compartidas
- ❌ No dejar ramas abiertas más de 1 semana sin mergear

---

## 📝 Convenciones de Commits

### Formato

```text
tipo(scope): descripción corta en minúsculas
```

### Tipos de commit

| Tipo       | Cuándo usarlo                                                          |
| :--------- | :--------------------------------------------------------------------- |
| `feat`     | Nueva funcionalidad para el sistema                                    |
| `fix`      | Corrección de un error o bug                                           |
| `style`    | Cambios de formato, UI o CSS que no alteran la lógica                  |
| `refactor` | Reorganización o mejora interna del código sin cambiar comportamiento  |
| `docs`     | Cambios exclusivos en documentación o `README.md`                      |
| `chore`    | Tareas de configuración, dependencias, paquetes o variables de entorno |
| `db`       | Migraciones de base de datos, factories o seeders                      |
| `test`     | Adición o corrección de pruebas unitarias o de integración             |
| `perf`     | Optimizaciones de rendimiento de código o consultas                    |

### Ejemplos

```bash
# Backend (Laravel & API)
feat(api): agregar endpoint POST para registro y validación de vacantes
feat(auth): implementar login con tokens y control de roles (pasante, supervisor, vicedecano)
db(pasante): crear migración para tabla informes y estados de evaluación
fix(auth): corregir expiración de código de recuperación de contraseña
feat(ai): integrar asistente IA para revisión y retroalimentación de CVs
chore(deps): actualizar dompdf y configurar generación de reportes

# Frontend (Vue 3 & Vite)
feat(cv): diseñar wizard por pasos para elaboración y descarga de curriculum vitae
feat(chat): integrar cliente socket.io para mensajería en tiempo real
style(ui): ajustar contraste y modo responsivo del panel de control con Tailwind CSS v4
fix(pdf): solucionar salto de página al exportar informes en formato PDF
feat(routing): proteger rutas de navegación según permisos de usuario

# QA & Pruebas
docs(test-cases): redactar casos de prueba para flujo de postulación a vacantes
docs(bug-report): registrar reporte de error 500 al adjuntar documentos pesados
docs(sign-off): validar y certificar módulo feature/pasante-cv-builder para merge
```

---

## 🔗 Trazabilidad: Requerimiento → Código

```text
Historia de Usuario (GitHub Issue)
        ↓
Tarea Técnica (checklist en el Issue)
        ↓
Rama feature/nombre-descriptivo
        ↓
Commits con referencia al Issue (#número)
        ↓
Pull Request → alpha
        ↓
Code Review + QA Approval
        ↓
Merge y cierre automático del Issue
```

### Cómo referenciar Issues en commits

```bash
# Referencia simple (vincula el commit al historial del Issue)
feat(cv): agregar vista previa dinámica del curriculum vitae #14

# Cierra el Issue automáticamente al mergear el Pull Request
feat(informes): habilitar formulario de entrega de informe mensual closes #22

# Múltiples issues resueltos en el mismo commit
feat(auth): completar flujo de verificación por código y registro closes #8, closes #9
```
