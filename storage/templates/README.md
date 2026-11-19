# Plantillas PDF

## Carta Natal (Canva)

Archivo activo:

```text
storage/templates/reporte-cn.pdf
```

Estructura esperada del export **REPORTE CN .pdf**:
1. Portada (datos del consultante se estampan encima, zona libre)
2. Intro Canva (queda en la plantilla; el PDF generado usa 1 → gráfico → interpretación)
3–4. Marcos en blanco para el gráfico SVG y las posiciones planetarias

Orden del PDF generado: **Datos → Gráfico de carta → Interpretación**.

Si re-exportás desde Canva, reemplazá este archivo y, si hace falta, retocá
coordenadas en `config/natal_report.php` (mm desde la esquina superior izquierda).
