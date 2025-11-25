<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * Show the contact form.
     */
    public function index(): View
    {
        return view('contact');
    }

    /**
     * Handle the contact form submission.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $mail = new ContactFormMail(
                name: $validated['name'],
                email: $validated['email'],
                contactSubject: $validated['subject'],
                contactMessage: $validated['message'],
            );

            // Queue email for background processing
            $recipientEmail = config('mail.contact_recipient', '03matei@gmail.com');
            Mail::to($recipientEmail)->queue($mail);

            Log::info('Contact form email queued', [
                'from' => $validated['email'],
                'subject' => $validated['subject'],
            ]);

            return redirect()
                ->route('contact')
                ->with('success', 'Thank you for your message! We\'ll get back to you as soon as possible.');
        } catch (\Exception $e) {
            Log::error('Failed to queue contact form email', [
                'error' => $e->getMessage(),
                'from' => $validated['email'],
                'subject' => $validated['subject'],
            ]);

            return redirect()
                ->route('contact')
                ->with('error', 'Sorry, there was an issue sending your message. Please try again later.');
        }
    }
}
