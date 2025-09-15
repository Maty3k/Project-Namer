/**
 * Performance Monitoring and Benchmarking Utility
 * Task 8.1: Set up performance monitoring and benchmarking
 */

class PerformanceMonitor {
    constructor() {
        this.metrics = {};
        this.observers = {};
        this.measurements = [];
        this.isMonitoring = false;
        
        // Initialize performance monitoring
        this.init();
    }

    init() {
        if (typeof window !== 'undefined' && 'performance' in window) {
            this.setupCoreWebVitals();
            this.setupNavigationTiming();
            this.setupResourceTiming();
            this.setupAnimationFrameMonitoring();
            this.setupMemoryMonitoring();
        }
    }

    /**
     * Core Web Vitals Monitoring (LCP, FID, CLS)
     */
    setupCoreWebVitals() {
        // Largest Contentful Paint (LCP)
        if ('PerformanceObserver' in window) {
            try {
                const lcpObserver = new PerformanceObserver((entryList) => {
                    const entries = entryList.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    this.metrics.lcp = {
                        value: lastEntry.startTime,
                        rating: this.getLCPRating(lastEntry.startTime),
                        timestamp: Date.now()
                    };
                    this.logMetric('LCP', lastEntry.startTime);
                });
                lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
                this.observers.lcp = lcpObserver;
            } catch (e) {
                console.warn('LCP monitoring not supported:', e);
            }

            // First Input Delay (FID)
            try {
                const fidObserver = new PerformanceObserver((entryList) => {
                    for (const entry of entryList.getEntries()) {
                        this.metrics.fid = {
                            value: entry.processingStart - entry.startTime,
                            rating: this.getFIDRating(entry.processingStart - entry.startTime),
                            timestamp: Date.now()
                        };
                        this.logMetric('FID', entry.processingStart - entry.startTime);
                    }
                });
                fidObserver.observe({ entryTypes: ['first-input'] });
                this.observers.fid = fidObserver;
            } catch (e) {
                console.warn('FID monitoring not supported:', e);
            }

            // Cumulative Layout Shift (CLS)
            try {
                let clsValue = 0;
                const clsObserver = new PerformanceObserver((entryList) => {
                    for (const entry of entryList.getEntries()) {
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                        }
                    }
                    this.metrics.cls = {
                        value: clsValue,
                        rating: this.getCLSRating(clsValue),
                        timestamp: Date.now()
                    };
                });
                clsObserver.observe({ entryTypes: ['layout-shift'] });
                this.observers.cls = clsObserver;
            } catch (e) {
                console.warn('CLS monitoring not supported:', e);
            }
        }
    }

    /**
     * Navigation Timing for loading performance
     */
    setupNavigationTiming() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const navigation = performance.getEntriesByType('navigation')[0];
                if (navigation) {
                    this.metrics.navigation = {
                        domContentLoaded: navigation.domContentLoadedEventEnd - navigation.navigationStart,
                        loadComplete: navigation.loadEventEnd - navigation.navigationStart,
                        firstByte: navigation.responseStart - navigation.requestStart,
                        domInteractive: navigation.domInteractive - navigation.navigationStart,
                        timestamp: Date.now()
                    };
                    
                    this.logMetric('DOM Content Loaded', this.metrics.navigation.domContentLoaded);
                    this.logMetric('Load Complete', this.metrics.navigation.loadComplete);
                    this.logMetric('Time to First Byte', this.metrics.navigation.firstByte);
                }
            }, 0);
        });
    }

    /**
     * Resource Timing for asset loading
     */
    setupResourceTiming() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const resources = performance.getEntriesByType('resource');
                const slowResources = resources.filter(resource => resource.duration > 1000);
                
                this.metrics.resources = {
                    total: resources.length,
                    slowResources: slowResources.length,
                    averageLoadTime: resources.reduce((sum, r) => sum + r.duration, 0) / resources.length,
                    timestamp: Date.now()
                };
                
                if (slowResources.length > 0) {
                    console.warn(`Found ${slowResources.length} slow loading resources:`, slowResources);
                }
            }, 1000);
        });
    }

    /**
     * Animation Frame Rate Monitoring
     */
    setupAnimationFrameMonitoring() {
        let frameCount = 0;
        let startTime = performance.now();
        let lastFrameTime = startTime;
        const frameTimes = [];
        
        const measureFrameRate = (currentTime) => {
            if (!this.isMonitoring) return;
            
            frameCount++;
            const deltaTime = currentTime - lastFrameTime;
            frameTimes.push(deltaTime);
            
            // Keep only last 60 frames for average calculation
            if (frameTimes.length > 60) {
                frameTimes.shift();
            }
            
            lastFrameTime = currentTime;
            
            // Calculate FPS every second
            if (currentTime - startTime >= 1000) {
                const fps = Math.round(frameCount * 1000 / (currentTime - startTime));
                const averageFrameTime = frameTimes.reduce((a, b) => a + b, 0) / frameTimes.length;
                
                this.metrics.animation = {
                    fps: fps,
                    averageFrameTime: Math.round(averageFrameTime * 100) / 100,
                    droppedFrames: frameTimes.filter(time => time > 16.67).length,
                    timestamp: Date.now()
                };
                
                frameCount = 0;
                startTime = currentTime;
            }
            
            requestAnimationFrame(measureFrameRate);
        };
        
        this.startAnimationMonitoring = () => {
            this.isMonitoring = true;
            requestAnimationFrame(measureFrameRate);
        };
        
        this.stopAnimationMonitoring = () => {
            this.isMonitoring = false;
        };
    }

    /**
     * Memory Usage Monitoring
     */
    setupMemoryMonitoring() {
        if ('memory' in performance) {
            setInterval(() => {
                this.metrics.memory = {
                    usedJSHeapSize: performance.memory.usedJSHeapSize / 1048576, // MB
                    totalJSHeapSize: performance.memory.totalJSHeapSize / 1048576, // MB
                    jsHeapSizeLimit: performance.memory.jsHeapSizeLimit / 1048576, // MB
                    timestamp: Date.now()
                };
            }, 5000); // Check every 5 seconds
        }
    }

    /**
     * Benchmark specific operations
     */
    async benchmarkOperation(name, operation, iterations = 1) {
        const results = [];
        
        for (let i = 0; i < iterations; i++) {
            const startTime = performance.now();
            await operation();
            const endTime = performance.now();
            results.push(endTime - startTime);
        }
        
        const averageTime = results.reduce((sum, time) => sum + time, 0) / results.length;
        const benchmark = {
            name,
            averageTime: Math.round(averageTime * 100) / 100,
            minTime: Math.min(...results),
            maxTime: Math.max(...results),
            iterations,
            timestamp: Date.now()
        };
        
        this.measurements.push(benchmark);
        this.logMetric(`Benchmark: ${name}`, `${averageTime.toFixed(2)}ms avg`);
        
        return benchmark;
    }

    /**
     * Test loading performance of specific components
     */
    async testComponentLoadTime(componentSelector, timeout = 5000) {
        return new Promise((resolve, reject) => {
            const startTime = performance.now();
            const timeoutId = setTimeout(() => {
                reject(new Error(`Component ${componentSelector} failed to load within ${timeout}ms`));
            }, timeout);
            
            const observer = new MutationObserver((mutations) => {
                const element = document.querySelector(componentSelector);
                if (element) {
                    const endTime = performance.now();
                    const loadTime = endTime - startTime;
                    clearTimeout(timeoutId);
                    observer.disconnect();
                    
                    this.logMetric(`Component Load: ${componentSelector}`, `${loadTime.toFixed(2)}ms`);
                    resolve({ componentSelector, loadTime });
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }

    /**
     * Rating helpers for Core Web Vitals
     */
    getLCPRating(value) {
        if (value <= 2500) return 'good';
        if (value <= 4000) return 'needs-improvement';
        return 'poor';
    }

    getFIDRating(value) {
        if (value <= 100) return 'good';
        if (value <= 300) return 'needs-improvement';
        return 'poor';
    }

    getCLSRating(value) {
        if (value <= 0.1) return 'good';
        if (value <= 0.25) return 'needs-improvement';
        return 'poor';
    }

    /**
     * Generate performance report
     */
    generateReport() {
        const report = {
            timestamp: new Date().toISOString(),
            metrics: this.metrics,
            measurements: this.measurements,
            summary: {
                coreWebVitals: {
                    lcp: this.metrics.lcp,
                    fid: this.metrics.fid,
                    cls: this.metrics.cls
                },
                loading: {
                    navigation: this.metrics.navigation,
                    resources: this.metrics.resources
                },
                runtime: {
                    animation: this.metrics.animation,
                    memory: this.metrics.memory
                }
            }
        };
        
        console.group('📊 Performance Report');
        console.table(report.summary.coreWebVitals);
        if (report.summary.loading.navigation) {
            console.table(report.summary.loading.navigation);
        }
        if (report.summary.runtime.animation) {
            console.table(report.summary.runtime.animation);
        }
        if (report.summary.runtime.memory) {
            console.table(report.summary.runtime.memory);
        }
        console.groupEnd();
        
        return report;
    }

    /**
     * Log performance metrics
     */
    logMetric(name, value) {
        const color = this.getMetricColor(name, value);
        console.log(`%c🔥 ${name}: ${value}`, `color: ${color}; font-weight: bold;`);
    }

    getMetricColor(name, value) {
        if (name.includes('LCP') && typeof value === 'number') {
            return value <= 2500 ? '#10B981' : value <= 4000 ? '#F59E0B' : '#EF4444';
        }
        if (name.includes('FID') && typeof value === 'number') {
            return value <= 100 ? '#10B981' : value <= 300 ? '#F59E0B' : '#EF4444';
        }
        if (name.includes('CLS') && typeof value === 'number') {
            return value <= 0.1 ? '#10B981' : value <= 0.25 ? '#F59E0B' : '#EF4444';
        }
        return '#6366F1';
    }

    /**
     * Public API
     */
    start() {
        this.startAnimationMonitoring();
        console.log('🚀 Performance monitoring started');
    }

    stop() {
        this.stopAnimationMonitoring();
        Object.values(this.observers).forEach(observer => {
            if (observer && typeof observer.disconnect === 'function') {
                observer.disconnect();
            }
        });
        console.log('⏹️ Performance monitoring stopped');
    }

    getMetrics() {
        return this.metrics;
    }

    getMeasurements() {
        return this.measurements;
    }
}

// Global performance monitor instance
if (typeof window !== 'undefined') {
    window.PerformanceMonitor = PerformanceMonitor;
    window.perfMonitor = new PerformanceMonitor();
    
    // Auto-start monitoring in development
    if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
        window.perfMonitor.start();
        
        // Generate report after page load
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.perfMonitor.generateReport();
            }, 3000);
        });
    }
}

export default PerformanceMonitor;