<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvelle Facture {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: #0f172a; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 600; letter-spacing: -0.5px; }
        .content { padding: 24px; }
        .invoice-badge { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 9999px; font-weight: 600; font-size: 13px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 14px; }
        th { text-align: left; padding: 10px; background: #f1f5f9; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        .text-right { text-align: right; }
        .totals { margin-top: 16px; margin-left: auto; width: 60%; font-size: 14px; }
        .totals td { padding: 6px 10px; }
        .total-row { font-weight: bold; font-size: 16px; color: #0f172a; border-top: 2px solid #e2e8f0; }
        .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px; text-align: center; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>DUGHU DEALTOO SAS</h1>
            <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.8;">Plateforme de Facturation & Abonnements B2B</p>
        </div>
        <div class="content">
            <span class="invoice-badge">Facture Émise</span>
            <h2 style="margin: 0 0 8px; font-size: 18px;">Facture n° {{ $invoice->invoice_number }}</h2>
            <p style="margin: 0 0 16px; color: #64748b; font-size: 14px;">
                Destinataire : <strong>{{ $invoice->organization->name }}</strong><br>
                Date d'émission : {{ $invoice->issue_date?->format('d/m/Y') }}<br>
                Date d'échéance : <strong>{{ $invoice->due_date?->format('d/m/Y') }}</strong>
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Qté</th>
                        <th class="text-right">P.U (XOF)</th>
                        <th class="text-right">Total (XOF)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                            <td class="text-right">{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($item->total, 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <td>Sous-total HT :</td>
                    <td class="text-right">{{ number_format($invoice->subtotal, 0, ',', ' ') }} XOF</td>
                </tr>
                <tr>
                    <td>TVA (18%) :</td>
                    <td class="text-right">{{ number_format($invoice->tax_amount, 0, ',', ' ') }} XOF</td>
                </tr>
                <tr class="total-row">
                    <td>Total TTC :</td>
                    <td class="text-right">{{ number_format($invoice->total, 0, ',', ' ') }} XOF</td>
                </tr>
            </table>

            @if($invoice->notes)
                <div style="margin-top: 20px; padding: 12px; background: #f8fafc; border-left: 3px solid #cbd5e1; font-size: 13px; color: #475569;">
                    <strong>Notes :</strong> {{ $invoice->notes }}
                </div>
            @endif
        </div>
        <div class="footer">
            Cet email est généré automatiquement par la plateforme DUGHU DEALTOO SAS.<br>
            Merci de procéder au règlement avant la date d'échéance indiquée.
        </div>
    </div>
</body>
</html>
