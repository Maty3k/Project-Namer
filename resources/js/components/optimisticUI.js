/**
 * Optimistic UI Manager
 *
 * Handles optimistic updates for UI interactions with automatic rollback on failure
 */

export default () => ({
    // Track pending operations for rollback
    pendingOperations: new Map(),

    init() {
        // Listen for Livewire request failures
        window.addEventListener('livewire:request-failed', (event) => {
            this.handleRequestFailed(event.detail);
        });
    },

    /**
     * Optimistically hide a name suggestion
     */
    hidesuggestion(suggestionId, originalState = {}) {
        const operationId = `hide-${suggestionId}-${Date.now()}`;

        // Store original state for potential rollback
        this.pendingOperations.set(operationId, {
            type: 'hide',
            suggestionId,
            originalState: { ...originalState, is_hidden: false },
            element: document.querySelector(`[data-suggestion-id="${suggestionId}"]`),
        });

        // Optimistically update UI
        const element = document.querySelector(`[data-suggestion-id="${suggestionId}"]`);
        if (element) {
            element.style.opacity = '0.5';
            element.style.transition = 'opacity 0.3s ease-out';

            setTimeout(() => {
                element.style.display = 'none';
            }, 300);
        }

        // Return operation ID for tracking
        return operationId;
    },

    /**
     * Optimistically show a name suggestion
     */
    showSuggestion(suggestionId, originalState = {}) {
        const operationId = `show-${suggestionId}-${Date.now()}`;

        // Store original state for potential rollback
        this.pendingOperations.set(operationId, {
            type: 'show',
            suggestionId,
            originalState: { ...originalState, is_hidden: true },
            element: document.querySelector(`[data-suggestion-id="${suggestionId}"]`),
        });

        // Optimistically update UI
        const element = document.querySelector(`[data-suggestion-id="${suggestionId}"]`);
        if (element) {
            element.style.display = '';
            element.style.opacity = '0';

            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transition = 'opacity 0.3s ease-in';
            }, 10);
        }

        return operationId;
    },

    /**
     * Optimistically toggle star/favorite status
     */
    toggleStar(itemId, itemType, currentlyStarred) {
        const operationId = `star-${itemType}-${itemId}-${Date.now()}`;

        // Store original state
        this.pendingOperations.set(operationId, {
            type: 'star',
            itemId,
            itemType,
            originalState: { is_starred: currentlyStarred },
            element: document.querySelector(`[data-${itemType}-id="${itemId}"] [data-star-icon]`),
        });

        // Optimistically update star icon
        const starIcon = document.querySelector(`[data-${itemType}-id="${itemId}"] [data-star-icon]`);
        if (starIcon) {
            if (currentlyStarred) {
                // Remove star
                starIcon.classList.remove('text-yellow-400', 'fill-current');
                starIcon.classList.add('text-gray-400');
            } else {
                // Add star
                starIcon.classList.remove('text-gray-400');
                starIcon.classList.add('text-yellow-400', 'fill-current');
            }
        }

        return operationId;
    },

    /**
     * Optimistically delete an item with undo capability
     */
    deleteWithUndo(itemId, itemType, undoCallback) {
        const operationId = `delete-${itemType}-${itemId}-${Date.now()}`;

        const element = document.querySelector(`[data-${itemType}-id="${itemId}"]`);

        // Store original state including element reference
        this.pendingOperations.set(operationId, {
            type: 'delete',
            itemId,
            itemType,
            element,
            undoCallback,
            deleted: false,
        });

        // Optimistically remove from UI with animation
        if (element) {
            element.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
            element.style.opacity = '0';
            element.style.transform = 'translateX(-20px)';

            setTimeout(() => {
                element.style.display = 'none';
            }, 300);
        }

        // Show undo toast
        this.showUndoToast(itemType, operationId);

        return operationId;
    },

    /**
     * Undo a delete operation
     */
    undoDelete(operationId) {
        const operation = this.pendingOperations.get(operationId);

        if (!operation || operation.type !== 'delete') {
            return;
        }

        // Restore element
        const { element, undoCallback } = operation;
        if (element) {
            element.style.display = '';
            element.style.opacity = '0';
            element.style.transform = 'translateX(-20px)';

            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateX(0)';
            }, 10);
        }

        // Call undo callback if provided
        if (undoCallback && typeof undoCallback === 'function') {
            undoCallback();
        }

        // Remove from pending operations
        this.pendingOperations.delete(operationId);

        // Hide undo toast
        this.hideUndoToast(operationId);
    },

    /**
     * Confirm successful operation completion
     */
    confirmSuccess(operationId) {
        // Remove from pending operations on success
        this.pendingOperations.delete(operationId);
    },

    /**
     * Handle request failure and rollback
     */
    handleRequestFailed(detail) {
        const { operationId } = detail || {};

        if (operationId && this.pendingOperations.has(operationId)) {
            this.rollback(operationId, detail.error || 'Operation failed');
        }
    },

    /**
     * Rollback an optimistic update
     */
    rollback(operationId, errorMessage = 'Operation failed') {
        const operation = this.pendingOperations.get(operationId);

        if (!operation) {
            return;
        }

        const { type, element, originalState } = operation;

        // Restore original UI state based on operation type
        switch (type) {
            case 'hide':
                if (element) {
                    element.style.display = '';
                    element.style.opacity = '1';
                }
                break;

            case 'show':
                if (element) {
                    element.style.display = 'none';
                    element.style.opacity = '0';
                }
                break;

            case 'star':
                if (element && originalState) {
                    if (originalState.is_starred) {
                        element.classList.add('text-yellow-400', 'fill-current');
                        element.classList.remove('text-gray-400');
                    } else {
                        element.classList.remove('text-yellow-400', 'fill-current');
                        element.classList.add('text-gray-400');
                    }
                }
                break;

            case 'delete':
                if (element) {
                    element.style.display = '';
                    element.style.opacity = '1';
                    element.style.transform = 'translateX(0)';
                }
                break;
        }

        // Show error toast
        this.showErrorToast(errorMessage);

        // Clean up
        this.pendingOperations.delete(operationId);
    },

    /**
     * Show undo toast notification
     */
    showUndoToast(itemType, operationId) {
        window.dispatchEvent(new CustomEvent('show-undo-toast', {
            detail: {
                message: `${itemType} deleted`,
                operationId,
                duration: 5000,
            },
        }));
    },

    /**
     * Hide undo toast notification
     */
    hideUndoToast(operationId) {
        window.dispatchEvent(new CustomEvent('hide-undo-toast', {
            detail: { operationId },
        }));
    },

    /**
     * Show error toast notification
     */
    showErrorToast(message) {
        // Dispatch to existing toast notification system
        if (window.Livewire) {
            window.Livewire.dispatch('show-toast', {
                message: message || 'Something went wrong. Please try again.',
                type: 'error',
            });
        } else {
            // Fallback to custom event
            window.dispatchEvent(new CustomEvent('show-error-toast', {
                detail: { message },
            }));
        }
    },

    /**
     * Clean up old completed operations (called periodically)
     */
    cleanup() {
        const now = Date.now();
        const maxAge = 30000; // 30 seconds

        for (const [operationId, operation] of this.pendingOperations.entries()) {
            const operationAge = now - parseInt(operationId.split('-').pop());
            if (operationAge > maxAge) {
                this.pendingOperations.delete(operationId);
            }
        }
    },
});
