# Sistema de Diseño Prestamos

La interfaz del sistema prioriza la estética "Prestamos" moderna. Este documento describe la alineación con los requerimientos originales:

## Paleta de Colores y Theming
- Funciona 100% sobre TailwindCSS.
- Backgrounds sólidos oscuros por defecto (`bg-slate-900`) simulando un tema Dark Mode de alto contraste.
- Detalles en verdes y azules eléctricos (`#00FF87` y `text-blue-500`) para generar confianza tecnológica y mostrar estados de éxito/finanzas.

## Glassmorphism
- Empleado en tarjetas principales (Dashboard) a través de los helpers CSS de la clase `.glass-card`.
- Utiliza la funcionalidad de `backdrop-filter: blur(16px)` para lograr un efecto traslúcido y opacidad parcial `rgba()`.
- Contribuye a un diseño jerárquico no-invasivo donde los gráficos y datos monetarios destacan sobre el fondo.

## Micro-interacciones
- Renderizadas vía Alpine.js y CSS nativo.
- Efecto de "lift" (elevación y sombra profunda) al hacer `:hover` sobre las tarjetas del dashboard.
- Animación de Keyframes `@keyframes pulse-neon` aplicada a estados de préstamo "Activos", brindando sensación de conectividad y "sistema en vivo" a los usuarios sin impactar la CPU del cliente.

## Gráficos Dinámicos
- Integración vía CDN de ApexCharts, una biblioteca robusta y visualmente atractiva adaptada a nuestro perfil `dark-mode` y colores "neon".
