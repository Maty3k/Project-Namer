// Suppress non-critical ResizeObserver warnings in development
// These warnings occur during CSS animations and layout changes
const originalConsoleError = console.error;
console.error = function(...args) {
    // Suppress ResizeObserver loop completed warnings
    if (args[0] && args[0].toString().includes('ResizeObserver loop completed')) {
        return;
    }
    originalConsoleError.apply(console, args);
};