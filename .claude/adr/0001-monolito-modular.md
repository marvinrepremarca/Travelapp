# 0001. Monolito modular en Laravel 12

- Estado: Aceptado
- Fecha: 2026-09-29

## Contexto
El sistema cubre muchos subdominios (CRM, reservas, pagos, finanzas, operación, integraciones) con flujos transaccionales que los cruzan (reservar → cobrar → facturar → liquidar). El equipo inicial es pequeño y el entorno de desarrollo es XAMPP en Windows.

## Opciones consideradas
1. **Microservicios** — escalado independiente / alta complejidad operativa, consistencia distribuida desde el día uno.
2. **Monolito tradicional por capas** — simple / tiende al acoplamiento con el crecimiento.
3. **Monolito modular** — un despliegue, transacciones locales, límites explícitos verificados por tests / requiere disciplina.

## Decisión
Monolito modular en `app/Modules/*` con API pública por módulo (Contracts, Data, Enums, Events, Models de lectura), verificada por arch tests. `Reports` puede leer cualquier tabla y nunca escribe.

## Consecuencias
- Despliegue y depuración simples; consistencia transaccional donde importa.
- `Search` e `Integrations` pueden extraerse como servicios si la carga lo exige, gracias a sus puertos.
- Toda excepción a los límites requiere un ADR nuevo.
