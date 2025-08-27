# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-sidebar/spec.md

> Created: 2025-08-27
> Version: 1.0.0

## Test Coverage

### Unit Tests

**NamingSession Model**
- Test UUID generation on creation
- Test relationship with User model
- Test relationship with SessionResults
- Test scope queries (starred, recent, search)
- Test auto-title generation from business description
- Test JSON serialization of session data

**SessionResult Model**
- Test relationship with NamingSession
- Test JSON column casting
- Test data integrity constraints
- Test cascade deletion

**SessionService**
- Test session creation with default values
- Test session data persistence
- Test session loading and state restoration
- Test session duplication logic
- Test search functionality with various queries
- Test session cleanup for inactive users

### Integration Tests

**SessionSidebar Livewire Component**
- Test component mounting with user sessions
- Test new session creation flow
- Test session loading and state restoration
- Test session deletion with confirmation
- Test session renaming inline
- Test star/unstar functionality
- Test search with debouncing
- Test pagination and infinite scroll
- Test focus mode toggle and persistence

**Dashboard Component Integration**
- Test session initialization on mount
- Test auto-save functionality
- Test session switching without data loss
- Test session state restoration
- Test handling of orphaned sessions
- Test concurrent session updates

### Feature Tests

**End-to-End Session Management**
```php
test('user can create and manage multiple naming sessions', function () {
    $user = User::factory()->create();
    
    // Create first session
    livewire(Dashboard::class)
        ->actingAs($user)
        ->call('createNewSession')
        ->set('businessDescription', 'First project idea')
        ->call('generateNames')
        ->assertSet('sessionId', fn($id) => !is_null($id));
    
    // Create second session
    livewire(Dashboard::class)
        ->actingAs($user)
        ->call('createNewSession')
        ->set('businessDescription', 'Second project idea')
        ->call('generateNames');
    
    // Verify both sessions exist
    livewire(SessionSidebar::class)
        ->actingAs($user)
        ->assertSee('First project idea')
        ->assertSee('Second project idea')
        ->assertCount('sessions', 2);
});
```

**Focus Mode Testing**
```php
test('focus mode hides sidebar and expands workspace', function () {
    $user = User::factory()->create();
    
    livewire(SessionSidebar::class)
        ->actingAs($user)
        ->assertSet('focusMode', false)
        ->call('toggleFocusMode')
        ->assertSet('focusMode', true)
        ->assertDispatched('focus-mode-toggled', true);
    
    // Verify persistence
    livewire(SessionSidebar::class)
        ->actingAs($user)
        ->assertSet('focusMode', true);
});
```

**Search Functionality**
```php
test('users can search through session history', function () {
    $user = User::factory()->create();
    
    // Create sessions with different content
    NamingSession::factory()->count(5)->create([
        'user_id' => $user->id,
        'business_description' => 'coffee shop'
    ]);
    
    NamingSession::factory()->count(3)->create([
        'user_id' => $user->id,
        'business_description' => 'tech startup'
    ]);
    
    livewire(SessionSidebar::class)
        ->actingAs($user)
        ->set('searchQuery', 'coffee')
        ->call('searchSessions')
        ->assertCount('sessions', 5);
});
```

### Mocking Requirements

**External Services**
- None required for core functionality

**Time-based Testing**
- Mock Carbon for testing session grouping by date
- Mock session timeout and auto-save intervals

### Browser Testing (Dusk - Optional)

```php
test('complete session workflow with keyboard shortcuts', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAs(User::factory()->create())
            ->visit('/dashboard')
            ->press('New Session')
            ->type('businessDescription', 'Test project')
            ->press('Generate Names')
            ->keys('', ['{cmd}', '/']) // Toggle focus mode
            ->assertMissing('.sidebar')
            ->keys('', ['{cmd}', '/']) // Toggle back
            ->assertVisible('.sidebar');
    });
});
```

### Performance Tests

**Load Testing**
- Test sidebar performance with 1000+ sessions
- Test search performance with large datasets
- Test virtual scrolling implementation
- Test session switching speed

**Memory Tests**
- Test memory usage with multiple sessions loaded
- Test cleanup of old session data
- Test prevention of memory leaks in Livewire components

### Accessibility Tests

**WCAG 2.1 AA Compliance**
- Test keyboard navigation through entire interface
- Test screen reader announcements for state changes
- Test focus management when toggling modes
- Test color contrast in both light and dark modes
- Test touch target sizes on mobile

### Cross-Browser Testing

**Desktop Browsers**
- Chrome/Edge (Chromium)
- Firefox
- Safari

**Mobile Browsers**
- iOS Safari
- Chrome Mobile
- Samsung Internet

### Security Tests

**Data Isolation**
- Test users cannot access other users' sessions
- Test session IDs cannot be guessed or enumerated
- Test XSS prevention in session titles and content
- Test SQL injection prevention in search queries