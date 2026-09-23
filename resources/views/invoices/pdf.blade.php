@use('Brick\Math\BigDecimal')
@use('Brick\Money\Money')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Invoice</title>
    <style>

        html, body, div, span, applet, object, iframe,
        h1, h2, h3, h4, h5, h6, p, blockquote, pre,
        a, abbr, acronym, address, big, cite, code,
        del, dfn, em, img, ins, kbd, q, s, samp,
        small, strike, strong, sub, sup, tt, var,
        b, u, i, center,
        dl, dt, dd, ol, ul, li,
        fieldset, form, label, legend,
        table, caption, tbody, tfoot, thead, tr, th, td,
        article, aside, canvas, details, embed,
        figure, figcaption, footer, header, hgroup,
        menu, nav, output, ruby, section, summary,
        time, mark, audio, video {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            vertical-align: baseline;
            box-sizing: border-box;
        }

        body {
            line-height: 1;
        }

        ol, ul {
            list-style: none;
        }

        @font-face {
            font-family: 'Outfit';
            src: url('outfit.ttf');
        }

        body {
            font-family: 'Outfit', 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #18181b;
            font-size: 12px;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
            text-align: left;
            width: 100%;
        }

        .table-wrapper {
            border: 1px solid #d4d4d8;
            border-radius: 8px;
            overflow: hidden;
            width: calc(100% - 2px);
            margin-top: 24px;
        }

        table thead {
            background-color: #fafafa;
            border-bottom: 1px #d4d4d8 solid;
        }

        table th {
            font-weight: 500;
            padding: 8px 12px;
            color: #18181b;
        }

        table td {
            font-weight: 400;
            color: #3f3f46;
            padding: 8px 12px;
        }

        table tbody tr {
            border-bottom: 1px #e4e4e7 solid;
        }

        table tbody tr:last-of-type {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .header {
            display: flex;
            justify-content: space-between;
        }

        .muted {
            color: #71717a;
        }

        .totals {
            width: 260px;
            margin-left: auto;
            margin-top: 16px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }

        .totals-row.total {
            border-top: 1px #d4d4d8 solid;
            margin-top: 4px;
            padding-top: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .section {
            margin-top: 24px;
        }
    </style>
</head>
<body>
<div class="header">
    <div>
        <p style="font-size: 24px; font-weight: 600;">Invoice {{ $invoice->reference }}</p>
        <p class="muted" style="margin-top: 4px;">Date: {{ $localization->formatDate($invoice->date) }}</p>
        @if($invoice->due_at)
            <p class="muted">Due: {{ $localization->formatDate($invoice->due_at) }}</p>
        @endif
    </div>
    <div style="text-align: right;">
        <p style="font-weight: 600;">{{ $invoice->seller_name }}</p>
        @if($invoice->seller_address_line_1)<p class="muted">{{ $invoice->seller_address_line_1 }}</p>@endif
        @if($invoice->seller_address_line_2)<p class="muted">{{ $invoice->seller_address_line_2 }}</p>@endif
        @if($invoice->seller_address_city || $invoice->seller_address_post_code)
            <p class="muted">{{ trim(($invoice->seller_address_post_code ?? '').' '.($invoice->seller_address_city ?? '')) }}</p>
        @endif
        @if($invoice->seller_address_country)<p class="muted">{{ $invoice->seller_address_country }}</p>@endif
        @if($invoice->seller_vatin)<p class="muted">VAT: {{ $invoice->seller_vatin }}</p>@endif
        @if($invoice->seller_email)<p class="muted">{{ $invoice->seller_email }}</p>@endif
    </div>
</div>

<div class="section">
    <p style="font-weight: 600;">Bill to</p>
    <p>{{ $invoice->recipient->name }}</p>
    @if($invoice->recipient->address_line_1)<p class="muted">{{ $invoice->recipient->address_line_1 }}</p>@endif
    @if($invoice->recipient->address_city || $invoice->recipient->address_post_code)
        <p class="muted">{{ trim(($invoice->recipient->address_post_code ?? '').' '.($invoice->recipient->address_city ?? '')) }}</p>
    @endif
    @if($invoice->recipient->address_country)<p class="muted">{{ $invoice->recipient->address_country }}</p>@endif
    @if($invoice->recipient->vatin)<p class="muted">VAT: {{ $invoice->recipient->vatin }}</p>@endif
</div>

<div class="table-wrapper">
    <table>
        <thead>
        <tr>
            <th>Description</th>
            <th class="text-right">Quantity</th>
            <th class="text-right">Unit price</th>
            <th class="text-right">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->entries as $entry)
            <tr>
                <td>
                    {{ $entry->name }}
                    @if($entry->description)
                        <br><span class="muted">{{ $entry->description }}</span>
                    @endif
                </td>
                <td class="text-right">{{ $localization->formatNumberWithoutTrailingZeros((float) $entry->quantity) }}</td>
                <td class="text-right">{{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($entry->unit_price, 2)->__toString(), $invoice->currency)) }}</td>
                <td class="text-right">{{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($entry->line_total, 2)->__toString(), $invoice->currency)) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="totals">
    <div class="totals-row">
        <span class="muted">Subtotal</span>
        <span>{{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($totals['subtotal'], 2)->__toString(), $invoice->currency)) }}</span>
    </div>
    @if($totals['discount_total'] > 0)
        <div class="totals-row">
            <span class="muted">Discount</span>
            <span>-{{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($totals['discount_total'], 2)->__toString(), $invoice->currency)) }}</span>
        </div>
    @endif
    @if($invoice->tax_rate)
        <div class="totals-row">
            <span class="muted">Tax ({{ $localization->formatNumberWithoutTrailingZeros(BigDecimal::of($invoice->tax_rate)->dividedBy(100, 2)) }}%)</span>
            <span>{{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($totals['tax_total'], 2)->__toString(), $invoice->currency)) }}</span>
        </div>
    @endif
    <div class="totals-row total">
        <span>Total</span>
        <span>{{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($totals['total'], 2)->__toString(), $invoice->currency)) }}</span>
    </div>
</div>

@if($invoice->payment_terms || $invoice->payment_iban)
    <div class="section">
        <p style="font-weight: 600;">Payment</p>
        @if($invoice->payment_terms)<p class="muted">{{ $invoice->payment_terms }}</p>@endif
        @if($invoice->payment_iban)<p class="muted">IBAN: {{ $invoice->payment_iban }}</p>@endif
    </div>
@endif

@if($invoice->notes)
    <div class="section">
        <p style="font-weight: 600;">Notes</p>
        <p class="muted">{{ $invoice->notes }}</p>
    </div>
@endif

@if($invoice->footer)
    <div class="section">
        <p class="muted">{{ $invoice->footer }}</p>
    </div>
@endif

</body>
</html>
