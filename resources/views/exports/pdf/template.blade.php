<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exportable->business_name ?? 'Logo Export' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 40px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 14px;
            color: #6b7280;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #d1d5db;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 8px 20px 8px 0;
            font-weight: bold;
            width: 150px;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            padding: 8px 0;
            vertical-align: top;
        }
        .footer {
            position: fixed;
            bottom: 30px;
            left: 40px;
            right: 40px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        @if(($settings['template'] ?? 'default') === 'professional')
        .professional-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            margin: -40px -40px 40px -40px;
            text-align: center;
        }
        .professional-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        @endif
    </style>
</head>
<body>
    @if(($settings['template'] ?? 'default') === 'professional')
        <div class="professional-header">
            <div class="professional-title">{{ $exportable->business_name ?? 'Business Logos' }}</div>
            <div>Professional Logo Export</div>
        </div>
    @else
        <div class="header">
            <div class="title">{{ $exportable->business_name ?? 'Logo Export' }}</div>
            <div class="subtitle">Generated on {{ now()->format('F j, Y \a\t g:i A') }}</div>
        </div>
    @endif

    <div class="section">
        <div class="section-title">Business Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Business Name:</div>
                <div class="info-value">{{ $exportable->business_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Description:</div>
                <div class="info-value">{{ $exportable->business_description ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst($exportable->status ?? 'N/A') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Created:</div>
                <div class="info-value">{{ $exportable->created_at?->format('F j, Y \a\t g:i A') ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    @if($include_domains && isset($exportable->domain_available))
        <div class="section">
            <div class="section-title">Domain Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Domain Available:</div>
                    <div class="info-value">{{ $exportable->domain_available ? 'Yes' : 'No' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Domain Checked:</div>
                    <div class="info-value">{{ $exportable->domain_checked_at?->format('F j, Y \a\t g:i A') ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    @endif

    @if($include_metadata)
        <div class="section">
            <div class="section-title">Export Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Export Type:</div>
                    <div class="info-value">PDF Document</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Exported By:</div>
                    <div class="info-value">{{ $export->user->name ?? 'Unknown' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Export Date:</div>
                    <div class="info-value">{{ now()->format('F j, Y \a\t g:i A') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Template:</div>
                    <div class="info-value">{{ ucfirst(($settings['template'] ?? 'default')) }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="footer">
        @if($include_branding)
            <div>Generated with {{ config('app.name') }} - AI-Powered Logo Generation Platform</div>
        @endif
        <div>This document was automatically generated on {{ now()->format('F j, Y \a\t g:i A') }}</div>
    </div>
</body>
</html>