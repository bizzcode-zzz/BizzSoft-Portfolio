<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        Mail::to('support@bizzsoft.dev')->send(
            new ContactMessageMail(
                customerName: $validated['name'],
                customerEmail: $validated['email'],
                contactSubject: $validated['subject'],
                contactMessage: $validated['message'],
            )
        );

        return back()->with(
            'success',
            'Your message has been sent. BizzSoft support will get back to you as soon as possible.'
        );
    }
}