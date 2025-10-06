/**
 * Lazy Load Observer Component
 *
 * Uses IntersectionObserver to detect when elements enter the viewport
 * and triggers loading of deferred content
 */

export default () => ({
    observer: null,

    init() {
        // Create IntersectionObserver instance
        this.observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        this.loadElement(entry.target);
                        this.observer.unobserve(entry.target);
                    }
                });
            },
            {
                rootMargin: '50px', // Start loading 50px before element enters viewport
                threshold: 0.01,
            }
        );

        // Observe all elements with data-lazy attribute
        this.observeLazyElements();
    },

    observeLazyElements() {
        const lazyElements = this.$el.querySelectorAll('[data-lazy]');
        lazyElements.forEach((el) => {
            this.observer.observe(el);
        });
    },

    loadElement(element) {
        // Check if element has a Livewire component to load
        if (element.hasAttribute('data-lazy-component')) {
            const componentId = element.getAttribute('data-lazy-component');
            // Trigger Livewire component loading
            if (window.Livewire) {
                const component = window.Livewire.find(componentId);
                if (component && typeof component.loadData === 'function') {
                    component.call('loadData');
                }
            }
        }

        // Handle lazy images
        if (element.tagName === 'IMG' && element.hasAttribute('data-lazy-src')) {
            const src = element.getAttribute('data-lazy-src');
            element.src = src;
            element.removeAttribute('data-lazy-src');
        }

        // Dispatch custom event for other integrations
        element.dispatchEvent(new CustomEvent('lazy-loaded', {
            bubbles: true,
            detail: { element }
        }));

        element.removeAttribute('data-lazy');
    },

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
});
