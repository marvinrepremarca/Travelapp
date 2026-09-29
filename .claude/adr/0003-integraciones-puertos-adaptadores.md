# 0003. Integraciones con proveedores mediante puertos y adaptadores

- Estado: Aceptado
- Fecha: 2026-09-29

## Contexto
El sistema debe conectarse con muchos proveedores heterogéneos (GDS/NDC, bancos de camas, rent-a-car, actividades, seguros, pagos, facturación electrónica), con APIs REST/SOAP, versiones cambiantes, disponibilidad variable y semánticas distintas. El primer proveedor es Amadeus (vuelos, hoteles, autos, traslados, tours y actividades); después se sumarán otros según el negocio.

## Opciones consideradas
1. **Llamar a los SDK/APIs directamente desde las Actions** — rápido al inicio / acopla el dominio a cada proveedor.
2. **Puertos por tipo de producto + adaptador por proveedor (Anti-Corruption Layer)** — dominio estable, proveedores intercambiables y probables con fixtures / más código inicial.

## Decisión
Opción 2. Los puertos viven en `Integrations/Contracts`, los DTOs de dominio en `Integrations/Data` y los adaptadores en `Integrations/Adapters/<Proveedor>`. Un `ProviderRegistry` resuelve el adaptador habilitado por tipo de producto (y, si hace falta, por sucursal o canal). Reglas de resiliencia (timeouts, idempotencia, circuit breaker, registro redactado) obligatorias.

## Consecuencias
- Agregar un proveedor no toca el dominio.
- Los puertos deben diseñarse con capacidades opcionales para no forzar el mínimo común denominador.
- Se necesita mantener fixtures anonimizadas actualizadas por versión de API.
