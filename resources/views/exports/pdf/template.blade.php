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
            color: rgb(51 51 51);
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgb(229 231 235);
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            color: rgb(31 41 55);
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 14px;
            color: rgb(107 114 128);
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: rgb(55 65 81);
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid rgb(209 213 219);
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
            color: rgb(107 114 128);
            text-align: center;
            border-top: 1px solid rgb(229 231 235);
            padding-top: 10px;
        }
        @if(($settings['template'] ?? 'default') === 'professional')
        .professional-header {
            background: linear-gradient(135deg, rgb(102 126 234) 0%, rgb(118 75 162) 100%);
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

    @if(isset($exportable->generatedLogos) && $exportable->generatedLogos->isNotEmpty())
        <div class="section">
            <div class="section-title">Generated Logos</div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px;">
                @foreach($exportable->generatedLogos as $logo)
                    <div style="text-align: center; padding: 15px; border: 1px solid rgb(209 213 219); border-radius: 8px;">
                        @if($logo->image_path && \Storage::exists($logo->image_path))
                            <img src="{{ \Storage::path($logo->image_path) }}"
                                 alt="Logo {{ $loop->iteration }}"
                                 style="max-width: 100%; height: auto; max-height: 200px; margin-bottom: 10px; border-radius: 4px;">
                        @endif
                        <div style="font-size: 12px; color: rgb(107 114 128); margin-top: 8px;">
                            <div><strong>Style:</strong> {{ ucfirst($logo->style ?? 'N/A') }}</div>
                            @if($logo->color_scheme)
                                <div><strong>Colors:</strong> {{ ucfirst(str_replace('_', ' ', $logo->color_scheme)) }}</div>
                            @endif
                            <div><strong>Created:</strong> {{ $logo->created_at?->format('M j, Y') ?? 'N/A' }}</div>
                        </div>
                    </div>
                @endforeach
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