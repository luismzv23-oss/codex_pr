# Modelos de Amortización

El sistema está configurado a nivel de arquitectura para soportar tres métodos matemáticos para amortización, calculados mediante la librería `BCMath` en el servicio `AmortizationService`.

## 1. Sistema Francés (Cuota Fija)
- **Concepto:** El prestatario siempre abona la misma cantidad en cada cuota.
- **Dinámica:** La proporción de intereses en la cuota disminuye con el tiempo, mientras que la proporción del principal aumenta.
- **Fórmula Base:** `c = V * (i(1+i)^n) / ((1+i)^n - 1)`

## 2. Sistema Alemán (Principal Fijo)
- **Concepto:** El prestatario amortiza el principal en partes iguales en cada cuota.
- **Dinámica:** Los intereses se calculan sobre el saldo remanente. Como el saldo decrece, la cuota total decrece a lo largo del tiempo.
- **Fórmula Amortización:** `A = V / n`
- **Fórmula Interés:** `I = Saldo Pendiente * i`

## 3. Sistema Americano (Bullet)
- **Concepto:** El prestatario solo paga intereses durante toda la vida del préstamo. El importe total del principal (capital) se paga en la última cuota.
- **Dinámica:** La cuota siempre es constante. La última cuota, sin embargo, es un pago globo (pago bullet) que liquida el capital completo más el último mes de interés.

## Implementación Técnica
El proceso de amortización se activa al ejecutar `$workflowService->disburse()`. Se delegan los parámetros de término, monto y tasa al `AmortizationService`, el cual genera un Array de objetos que el `Model` insertará posteriormente en la tabla `installments`.
