# API DNS Fields Documentation

> Version: 1.0.0
> Last Updated: 2025-09-29

## Overview

This document describes the DNS-related fields available in API responses for name suggestions and domain availability checking functionality in the Project Namer application.

## DNS Status Fields

The DNS domain filtering system adds several fields to API responses to provide real-time domain availability information.

### NameSuggestion DNS Fields

When retrieving name suggestions through the API, the following DNS fields are included:

#### Field Definitions

| Field | Type | Description | Example Values |
|-------|------|-------------|----------------|
| `dns_checked` | `boolean` | Whether DNS lookup has been performed for this name | `true`, `false` |
| `dns_has_records` | `boolean\|null` | Whether DNS records exist for the domain (null if not checked) | `true`, `false`, `null` |
| `dns_checked_at` | `timestamp\|null` | When the DNS check was last performed | `"2025-09-29T10:30:00Z"`, `null` |

#### DNS Status Interpretation

Based on the field combination, you can determine the domain availability status:

| dns_checked | dns_has_records | Status | Meaning |
|-------------|-----------------|---------|---------|
| `false` | `null` | **Pending** | DNS check not yet performed |
| `true` | `false` | **Available** | No DNS records found - domain likely available |
| `true` | `true` | **Taken** | DNS records exist - domain is in use |
| `true` | `null` | **Error** | DNS check failed or timed out |

## API Endpoints with DNS Data

### GET /api/ai/generation/{sessionId}

Returns generation session results including name suggestions with DNS status.

#### Response Format

```json
{
  "session_id": "gen_abc123def456",
  "status": "completed",
  "progress_percentage": 100,
  "current_step": "DNS checking complete",
  "results": {
    "names": [
      {
        "id": 123,
        "name": "TechFlow Solutions",
        "domains": {
          "com": "techflowsolutions.com",
          "io": "techflowsolutions.io",
          "co": "techflowsolutions.co"
        },
        "dns_checked": true,
        "dns_has_records": false,
        "dns_checked_at": "2025-09-29T10:30:15Z",
        "ai_model_used": "gpt-4",
        "ai_generation_mode": "creative",
        "generation_metadata": {
          "confidence_score": 0.92,
          "industry_relevance": "high"
        }
      },
      {
        "id": 124,
        "name": "DataSync Pro",
        "domains": {
          "com": "datasyncpro.com",
          "io": "datasyncpro.io",
          "co": "datasyncpro.co"
        },
        "dns_checked": true,
        "dns_has_records": true,
        "dns_checked_at": "2025-09-29T10:30:22Z",
        "ai_model_used": "gpt-4",
        "ai_generation_mode": "creative",
        "generation_metadata": {
          "confidence_score": 0.89,
          "industry_relevance": "high"
        }
      }
    ]
  },
  "error_message": null
}
```

### Frontend Display Logic

Based on DNS fields, the frontend should display appropriate status indicators:

#### Status Badge Examples

```javascript
function getDnsStatusBadge(suggestion) {
  if (!suggestion.dns_checked) {
    return {
      status: 'checking',
      color: 'yellow',
      text: 'Checking...',
      icon: 'loading'
    };
  }

  if (suggestion.dns_has_records === null) {
    return {
      status: 'error',
      color: 'red',
      text: 'Check Failed',
      icon: 'warning'
    };
  }

  if (suggestion.dns_has_records) {
    return {
      status: 'taken',
      color: 'red',
      text: 'Domain Taken',
      icon: 'unavailable'
    };
  }

  return {
    status: 'available',
    color: 'green',
    text: 'Available',
    icon: 'checkmark'
  };
}
```

## DNS Checking Workflow

### Automatic DNS Checking

When names are generated through the AI generation API:

1. **Initial Response**: Names are returned with `dns_checked: false`
2. **Background Processing**: DNS checks are queued for each suggested domain
3. **Progressive Updates**: DNS fields are updated as checks complete
4. **Frontend Polling**: Client polls the generation endpoint to get updated DNS status

### Example Workflow Timeline

```
T+0s:   Name generated, dns_checked: false
T+2s:   DNS job queued for techflowsolutions.com
T+5s:   DNS lookup complete, dns_checked: true, dns_has_records: false
T+6s:   Frontend polls API and displays "Available" status
```

## Error Handling

### DNS Service Degradation

When DNS service is degraded or unavailable:

```json
{
  "dns_checked": false,
  "dns_has_records": null,
  "dns_checked_at": null,
  "dns_service_status": "degraded",
  "dns_fallback_mode": "optimistic"
}
```

### DNS Timeout Scenarios

When DNS lookups timeout:

```json
{
  "dns_checked": true,
  "dns_has_records": null,
  "dns_checked_at": "2025-09-29T10:30:00Z",
  "dns_error": "timeout"
}
```

## Rate Limiting

DNS checking is subject to rate limiting to prevent abuse:

- **Queue-based processing**: DNS checks are processed via background jobs
- **Automatic throttling**: System automatically adjusts check frequency based on load
- **Circuit breaker**: Protects against DNS service overload

## Caching

DNS results are cached to improve performance:

- **Cache TTL**: 24 hours for positive results, 1 hour for negative results
- **Cache keys**: Based on domain name and TLD
- **Cache warming**: Popular domains are pre-cached

## Health Monitoring

The DNS system includes comprehensive health monitoring:

### Health Check Endpoint

```bash
GET /health/dns
```

#### Response Format

```json
{
  "status": "healthy",
  "checks": {
    "dns_connectivity": "pass",
    "dns_response_time": "pass",
    "dns_error_rate": "pass",
    "dns_cache_hit_rate": "pass",
    "dns_queue_depth": "pass"
  },
  "metrics": {
    "average_response_time_ms": 150,
    "error_rate_percentage": 2.1,
    "cache_hit_rate_percentage": 87.3,
    "queue_depth": 12
  },
  "last_checked": "2025-09-29T10:30:00Z"
}
```

## Configuration

### Environment Variables

DNS behavior can be configured via environment variables:

```env
# DNS Service Configuration
DNS_ENABLED=true
DNS_TIMEOUT=2
DNS_SERVERS=1.1.1.1,8.8.8.8

# DNS Cache Configuration
DNS_CACHE_ENABLED=true
DNS_CACHE_TTL=86400

# DNS Fallback Configuration
DNS_FALLBACK_ENABLED=true
DNS_FALLBACK_TIMEOUT_PRIMARY=2
DNS_FALLBACK_TIMEOUT_FALLBACK=5

# DNS Health Monitoring
DNS_HEALTH_CHECK_ENABLED=true
DNS_ALERTS_ENABLED=true

# DNS Performance Settings
DNS_CONCURRENT_LOOKUPS=10
DNS_BATCH_SIZE=25
DNS_MEMORY_LIMIT=256M
```

## Integration Examples

### JavaScript Frontend Integration

```javascript
// Fetch generation results with DNS status
async function fetchGenerationResults(sessionId) {
  const response = await fetch(`/api/ai/generation/${sessionId}`);
  const data = await response.json();

  if (data.results && data.results.names) {
    data.results.names.forEach(name => {
      updateDnsStatus(name.id, {
        checked: name.dns_checked,
        hasRecords: name.dns_has_records,
        checkedAt: name.dns_checked_at
      });
    });
  }

  return data;
}

// Poll for DNS updates
function startDnsStatusPolling(sessionId) {
  const interval = setInterval(async () => {
    const results = await fetchGenerationResults(sessionId);

    // Stop polling when all DNS checks are complete
    const allChecked = results.results?.names?.every(name => name.dns_checked) ?? false;
    if (allChecked) {
      clearInterval(interval);
    }
  }, 3000); // Poll every 3 seconds
}
```

### PHP Backend Integration

```php
// Check DNS status for name suggestions
$suggestions = NameSuggestion::where('project_id', $projectId)
    ->select(['id', 'name', 'dns_checked', 'dns_has_records', 'dns_checked_at'])
    ->get();

foreach ($suggestions as $suggestion) {
    $dnsStatus = [
        'status' => $this->determineDnsStatus($suggestion),
        'checked' => $suggestion->dns_checked,
        'has_records' => $suggestion->dns_has_records,
        'checked_at' => $suggestion->dns_checked_at,
    ];

    // Add to API response
    $suggestion->setAttribute('dns_status', $dnsStatus);
}
```

## Best Practices

### For Frontend Developers

1. **Progressive Enhancement**: Show names immediately, update DNS status as available
2. **Loading States**: Provide clear feedback during DNS checking
3. **Error Handling**: Gracefully handle DNS check failures
4. **Polling Strategy**: Use reasonable intervals (3-5 seconds) to avoid excessive API calls
5. **Caching**: Cache DNS results on the frontend to reduce API calls

### For Backend Developers

1. **Queue Management**: Use background jobs for DNS checking
2. **Error Recovery**: Implement retry logic for failed DNS lookups
3. **Rate Limiting**: Respect DNS provider limits
4. **Monitoring**: Track DNS performance and error rates
5. **Graceful Degradation**: Continue functioning when DNS service is unavailable

## Troubleshooting

### Common Issues

#### DNS Checks Never Complete
- **Cause**: Queue workers not running
- **Solution**: Check `php artisan queue:work` processes

#### High DNS Error Rates
- **Cause**: Network connectivity or DNS server issues
- **Solution**: Check DNS server configuration and connectivity

#### Slow DNS Performance
- **Cause**: DNS server response times or high concurrent load
- **Solution**: Optimize DNS servers, increase cache TTL, reduce concurrent lookups

### Debug Commands

```bash
# Check DNS system health
php artisan dns:health-check

# Test DNS connectivity
php artisan dns:test:connectivity

# Check DNS job queue
php artisan queue:monitor dns

# View DNS metrics
php artisan dns:metrics:report --period=1h
```

For additional troubleshooting steps, see the [DNS Troubleshooting Guide](dns-troubleshooting-guide.md).

---

*This API documentation is part of the DNS domain filtering system documentation suite.*