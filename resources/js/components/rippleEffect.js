/**
 * Ripple Effect Component
 *
 * Adds Material Design-style ripple effect to buttons and interactive elements
 */

export default () => ({
    init() {
        // Add ripple effect to primary action buttons
        this.attachRippleToButtons();
    },

    attachRippleToButtons() {
        // Select all primary buttons and add ripple container
        const buttons = document.querySelectorAll(
            'button[class*="primary"], .btn-primary, [data-ripple="true"]'
        );

        buttons.forEach((button) => {
            // Skip if already has ripple container
            if (button.classList.contains('ripple-container')) {
                return;
            }

            // Add ripple container class
            button.classList.add('ripple-container');

            // Add click event listener
            button.addEventListener('click', (e) => this.createRipple(e, button));
        });
    },

    createRipple(event, button) {
        // Check for reduced motion preference
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        // Get button dimensions and position
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        // Create ripple element
        const ripple = document.createElement('span');
        ripple.classList.add('ripple');
        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;

        // Add ripple to button
        button.appendChild(ripple);

        // Remove ripple after animation completes
        setTimeout(() => {
            ripple.remove();
        }, 600);
    },

    // Re-attach ripples to dynamically added buttons
    refresh() {
        this.attachRippleToButtons();
    },
});
