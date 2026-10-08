# Changelog — Crm

## Fase 1.5

### Agregado
- **Clientes** (persona o empresa) visibles por alcance y reasignables por directores y gerente. Documento único por tipo: se guarda cifrado y se busca por huella HMAC (`TRAVEL_PII_HASH_KEY`). Aviso de posible duplicado por correo o teléfono sin revelar al dueño.
- **Autorización Ley 1581:** obligatoria para registrar un cliente (canal, fecha, versión de la política); la de ofertas es opcional. Cada cambio es un registro nuevo e inmutable.
- **Datos sensibles** (documento, fecha de nacimiento, pasaporte) enmascarados; mostrarlos exige el permiso `crm.sensitive_data.view` y un motivo, y queda en la auditoría.
- **Pasajeros** del cliente con fecha de nacimiento y pasaporte cifrados, nombre en formato aerolínea, tipo de pasajero por edad a la fecha del servicio y alerta de vigencia mínima del pasaporte (6 meses ⚙).
- **Embudo de leads:** Nuevo → Contactado → Cotizado → Ganado / Perdido; perder exige motivo; ganar exige un cliente y emite `LeadWon`; el primer contacto registrado pasa el lead a "Contactado"; seguimiento de interacciones.

## Embudo tipo kanban

### Cambiado
- El **embudo de ventas** es un tablero kanban: las etapas (Nuevo, Contactado, Cotizado) van lado a lado, con los leads apilados en tarjetas (contacto, destino, fecha de viaje, canal) y desplazamiento horizontal en pantallas pequeñas, "Mostrando N de M" y **Ver más** por etapa para listas largas.

## 2026-10-22
- Clientes y viajeros pasan al módulo del núcleo `Customers` (ADR-0007). Crm queda como capacidad Comercial (prospectos).
