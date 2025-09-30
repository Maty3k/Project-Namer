# DNS Filtering User Guide

> Version: 1.0.0
> Last Updated: September 29, 2025

## Overview

The DNS Filtering system in Project Namer automatically checks domain availability for generated business names in real-time, helping you focus on names that actually have available domains. This system works behind the scenes to filter out suggestions with existing DNS records, ensuring you only see viable naming options.

## How DNS Filtering Works

### Automatic Domain Checking

When you generate business names, the system:

1. **Generates AI-powered name suggestions** based on your input
2. **Queues DNS checks** for each suggested domain in the background
3. **Filters results progressively** as DNS information becomes available
4. **Shows only available domains** in your final results

### Progressive Enhancement

The DNS filtering system uses progressive enhancement to provide the best user experience:

- **Immediate Results**: See suggested names instantly while DNS checks run in background
- **Real-time Updates**: Watch as domains are automatically filtered based on availability
- **Loading Indicators**: Visual feedback shows DNS checking progress
- **Smart Caching**: Previously checked domains load instantly on repeat visits

## Understanding Domain Status

### Status Indicators

Each domain suggestion displays one of these status indicators:

- 🟢 **Available**: No DNS records found - domain appears to be available
- 🔴 **Taken**: DNS records exist - domain is likely registered and in use
- 🟡 **Checking**: DNS lookup in progress - status will update shortly
- ⚪ **Unknown**: DNS check failed or timed out - manual verification recommended

### What Each Status Means

#### Available Domains (🟢)
- No DNS records (A, AAAA, CNAME, MX, NS) found
- Domain appears to be unregistered and available for purchase
- **Recommended Action**: Check domain registrar for actual availability and pricing

#### Taken Domains (🔴)
- DNS records found indicating an active website or email service
- Domain is registered and likely in use
- **Recommended Action**: Consider alternative names or variations

#### Checking Status (🟡)
- DNS lookup is currently in progress
- Status will automatically update within a few seconds
- **Recommended Action**: Wait for the check to complete

#### Unknown Status (⚪)
- DNS lookup failed due to network issues or server timeouts
- Could indicate DNS server problems or network connectivity issues
- **Recommended Action**: Try again later or manually check the domain

## Filtered vs. Unfiltered Views

### Default Filtered View

By default, the system shows only domains that appear available:
- Hides domains with DNS records (taken domains)
- Shows available and unknown domains
- Provides the cleanest, most actionable results

### Show All Domains Option

You can toggle to see all generated names regardless of DNS status:
- View complete list of AI-generated suggestions
- See why certain names were filtered out
- Useful for finding alternative variations of filtered names

## DNS Check Coverage

### Checked Record Types

The system checks for these DNS record types:
- **A Records**: IPv4 website addresses
- **AAAA Records**: IPv6 website addresses
- **CNAME Records**: Domain aliases and redirects
- **MX Records**: Email server configurations
- **NS Records**: Name server configurations

### Checked Domain Extensions

The system automatically checks these popular extensions:
- **.com** - Most common commercial domains
- **.io** - Popular for tech startups
- **.co** - Alternative to .com
- **.net** - Network-related domains

## Performance and Reliability

### Background Processing

DNS checks run asynchronously to ensure:
- **Fast Initial Response**: See name suggestions immediately
- **Non-blocking Operation**: UI remains responsive during checks
- **Efficient Resource Usage**: Optimized DNS query patterns

### Intelligent Caching

The system caches DNS results to improve performance:
- **24-hour Cache**: DNS results cached for fast repeat access
- **Smart Invalidation**: Automatic cache updates for changed domains
- **Reduced API Calls**: Minimizes external DNS query load

### Error Handling and Fallbacks

When DNS services experience issues:
- **Graceful Degradation**: System continues working without DNS filtering
- **Automatic Retries**: Failed DNS checks are automatically retried
- **Fallback DNS Servers**: Multiple DNS servers ensure reliability
- **Health Monitoring**: System automatically detects and recovers from DNS issues

## Limitations and Considerations

### DNS vs. Domain Registration

**Important**: DNS filtering checks for DNS records, not domain registration status.

- **Available DNS ≠ Available Domain**: A domain without DNS records might still be registered
- **Taken DNS = Taken Domain**: Domains with DNS records are definitely registered and in use
- **Always Verify**: Check with domain registrars for definitive availability and pricing

### Timing Considerations

- **DNS Propagation**: Recent domain changes may take time to reflect in DNS
- **Cache Delays**: Some DNS changes may not appear immediately due to caching
- **Regional Differences**: DNS results may vary slightly by geographic location

### Coverage Limitations

- **Extension Coverage**: Only checks popular extensions (.com, .io, .co, .net)
- **Record Types**: Focuses on common DNS record types
- **Regional TLDs**: Country-specific domains (.uk, .de, etc.) not included by default

## Troubleshooting

### DNS Check Not Working

If DNS status remains in "Checking" state:

1. **Check Internet Connection**: Ensure stable network connectivity
2. **Wait and Refresh**: DNS checks may take up to 30 seconds
3. **Try Again Later**: DNS servers may be temporarily unavailable
4. **Contact Support**: Persistent issues may indicate system problems

### Unexpected Results

If available domains show as taken when manually checked:

1. **Verify Extension**: Ensure you're checking the same domain extension
2. **Check Timing**: DNS changes may not have propagated yet
3. **Manual Verification**: Always confirm availability with domain registrars
4. **Report Issues**: Help improve the system by reporting discrepancies

### Performance Issues

If DNS filtering seems slow:

1. **Clear Browser Cache**: Old cached data may cause display issues
2. **Check Network Speed**: Slow connections affect DNS lookup performance
3. **Try Different Time**: DNS services may be busy during peak hours
4. **Report Persistent Issues**: Ongoing slowness may indicate system optimization needs

## Privacy and Security

### Data Handling

The DNS filtering system:
- **No Personal Data**: Only checks domain names, no personal information stored
- **Temporary Logging**: DNS queries logged temporarily for debugging and optimization
- **No Data Sharing**: DNS check data is not shared with third parties
- **Secure Connections**: All DNS queries use secure, encrypted connections

### Security Measures

- **Input Validation**: All domain names are validated before DNS queries
- **Rate Limiting**: Prevents abuse and ensures fair resource usage
- **Error Handling**: Malicious or malformed domains are safely handled
- **No Injection Risks**: System is protected against DNS injection attacks

## Getting the Most from DNS Filtering

### Best Practices

1. **Use Filtered View**: Start with filtered results to focus on viable options
2. **Check Multiple Extensions**: Consider .com, .io, and .co variations
3. **Verify Before Purchase**: Always confirm availability with registrars
4. **Consider Alternatives**: Use filtered names as inspiration for variations
5. **Act Quickly**: Available domains may be registered by others at any time

### Advanced Tips

- **Refresh Results**: Generate new suggestions if too many are filtered out
- **Try Longer Names**: Shorter domains are more likely to be taken
- **Consider Hyphens**: Hyphenated versions may be available when base names aren't
- **Think Global**: Check country-specific extensions for international markets
- **Monitor Favorites**: Keep track of preferred available domains for quick action

## Support and Feedback

### Getting Help

- **Documentation**: Check this guide and other documentation in `/docs`
- **System Status**: Monitor DNS system health via status indicators
- **Error Messages**: Pay attention to specific error messages for guidance
- **Community**: Share experiences with other users for tips and tricks

### Providing Feedback

Help improve the DNS filtering system:
- **Report Bugs**: Document specific issues with domain names and expected vs. actual results
- **Suggest Features**: Share ideas for improving DNS filtering functionality
- **Performance Reports**: Report slow DNS checks or timeout issues
- **Accuracy Feedback**: Let us know about DNS filtering accuracy issues

## Technical Details

For developers and advanced users interested in technical implementation:

- **DNS Resolution**: Uses authoritative DNS servers for accurate results
- **Caching Strategy**: Implements intelligent TTL-based caching
- **Queue Processing**: Background job processing for scalable DNS checks
- **Circuit Breaker**: Automatic failover when DNS services are unavailable
- **Health Monitoring**: Real-time monitoring of DNS service performance
- **Recovery Procedures**: Automated recovery from DNS service failures

Refer to the Technical Documentation for detailed implementation information.