# Plantillas PDF

## Carta Natal (Canva)

Archivo activo:

```text
storage/templates/reporte-cn.pdf
```

Estructura esperada del export **REPORTE CN .pdf**:
1. Portada Canva (intacta, sin overlays)
2. Intro “Tu Carta Natal” (intacta)
3–4. Marcos en blanco para datos / gráfico / interpretación

Orden del PDF generado:
1–2 Canva · **3 Datos** · **4 Gráfico** · **5+ Interpretación**

Si re-exportás desde Canva, reemplazá este archivo y, si hace falta, retocá
coordenadas en `config/natal_report.php` (mm desde la esquina superior izquierda).
