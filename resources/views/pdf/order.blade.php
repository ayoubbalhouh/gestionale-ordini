@php($eur = fn ($c) => number_format($c / 100, 2, ',', '.').' €')
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>{{ $order->numero }}</title>
<style>
  * { font-family: DejaVu Sans, sans-serif; }
  body { font-size: 11px; color: #1e1b4b; margin: 0; }
  .head { width: 100%; border-bottom: 3px solid #4f46e5; padding-bottom: 14px; margin-bottom: 20px; }
  .brand { font-size: 20px; font-weight: bold; color: #4f46e5; }
  .muted { color: #6b7280; }
  h1 { font-size: 22px; margin: 0; }
  table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
  table.items th { text-align: left; font-size: 9px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #d1d5db; padding: 6px 4px; }
  table.items td { padding: 8px 4px; border-bottom: 1px solid #eef0f4; }
  .num { text-align: right; }
  .totals { width: 260px; margin-left: auto; margin-top: 16px; }
  .totals td { padding: 4px; }
  .grand td { font-size: 14px; font-weight: bold; border-top: 2px solid #1e1b4b; padding-top: 8px; }
  .box { background: #f5f5ff; padding: 12px; border-radius: 6px; }
  .stato { display: inline-block; padding: 3px 8px; border-radius: 10px; background: #e0e7ff; color: #3730a3; font-size: 10px; text-transform: uppercase; }
</style>
</head>
<body>
  <table class="head">
    <tr>
      <td>
        <div class="brand">Ordinato</div>
        <div class="muted">Distribuzione Veneta S.r.l. · Via dell'Industria 5, 35129 Padova · P.IVA 06789012348</div>
      </td>
      <td class="num">
        <h1>Ordine {{ $order->numero }}</h1>
        <div class="muted">Data {{ $order->created_at->format('d/m/Y') }}</div>
        <span class="stato">{{ $order->stato->label() }}</span>
      </td>
    </tr>
  </table>

  <table style="width:100%">
    <tr>
      <td style="width:55%" class="box">
        <div class="muted">Cliente</div>
        <strong>{{ $order->customer->ragione_sociale }}</strong><br>
        @if ($order->customer->indirizzo) {{ $order->customer->indirizzo }}<br> @endif
        @if ($order->customer->citta) {{ $order->customer->citta }}<br> @endif
        @if ($order->customer->piva) P.IVA {{ $order->customer->piva }} @endif
      </td>
      <td style="width:45%; padding-left:20px; vertical-align:top">
        @if ($order->data_consegna)<div class="muted">Consegna prevista</div><strong>{{ $order->data_consegna->format('d/m/Y') }}</strong>@endif
      </td>
    </tr>
  </table>

  <table class="items">
    <thead><tr><th>Prodotto</th><th class="num">Q.tà</th><th class="num">Prezzo</th><th class="num">IVA</th><th class="num">Importo</th></tr></thead>
    <tbody>
      @foreach ($order->items as $item)
        <tr>
          <td>{{ $item->descrizione }}</td>
          <td class="num">{{ $item->quantita }}</td>
          <td class="num">{{ $eur($item->prezzo_cents) }}</td>
          <td class="num">{{ $item->iva }}%</td>
          <td class="num">{{ $eur($item->quantita * $item->prezzo_cents) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr><td class="muted">Imponibile</td><td class="num">{{ $eur($order->imponibile_cents) }}</td></tr>
    <tr><td class="muted">IVA</td><td class="num">{{ $eur($order->iva_cents) }}</td></tr>
    <tr class="grand"><td>Totale</td><td class="num">{{ $eur($order->totale_cents) }}</td></tr>
  </table>

  @if ($order->note)
    <p class="box" style="margin-top:24px"><span class="muted">Note:</span> {{ $order->note }}</p>
  @endif
</body>
</html>
