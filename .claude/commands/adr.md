---
description: Crea un Architecture Decision Record en .claude/adr con el siguiente número disponible.
argument-hint: <título de la decisión>
---

Crea el ADR: **$ARGUMENTS**

1. Busca el último número en `.claude/adr/` y usa el siguiente (`NNNN-titulo-en-kebab-case.md`).
2. Si no tienes el contexto de la decisión, pregunta: problema, opciones consideradas y decisión tomada.
3. Usa la plantilla:

```markdown
# NNNN. <Título>

- Estado: Propuesto | Aceptado | Reemplazado por NNNN
- Fecha: AAAA-MM-DD

## Contexto
<problema y fuerzas en juego>

## Opciones consideradas
1. <opción> — pros / contras

## Decisión
<qué se decide>

## Consecuencias
<positivas, negativas, qué hay que vigilar>
```

4. Agrega la entrada a la tabla de `.claude/adr/README.md`.
