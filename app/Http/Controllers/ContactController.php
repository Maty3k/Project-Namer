<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Send email to darius@artisan.build
        Mail::to('darius@artisan.build')->send(
            new ContactFormMail(
                name: $validated['name'],
                email: $validated['email'],
                subject: $validated['subject'],
                message: $validated['message'],
            )
        );

        return redirect()
            ->route('contact')
            ->with('success', 'Thank you for your message! We\'ll get back to you as soon as possible.');
    }
}
