<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    public function test_contact_page_is_publicly_accessible(): void
    {
        $this
            ->get('/contact')
            ->assertOk();
    }

    public function test_contact_form_sends_message_to_bizzsoft_support(): void
    {
        Mail::fake();

        $this
            ->from('/contact')
            ->post('/contact', [
                'name' => 'Test Customer',
                'email' => 'customer@example.com',
                'subject' => 'Payment assistance',
                'message' => 'I need help reviewing my pending payment.',
            ])
            ->assertRedirect('/contact')
            ->assertSessionHasNoErrors();

        Mail::assertSent(
            ContactMessageMail::class,
            function (ContactMessageMail $mail) {
                $envelope = $mail->envelope();

                return $mail->hasTo('support@bizzsoft.dev')
                    && $mail->customerName === 'Test Customer'
                    && $mail->customerEmail === 'customer@example.com'
                    && $mail->contactSubject === 'Payment assistance'
                    && $mail->contactMessage ===
                        'I need help reviewing my pending payment.'
                    && count($envelope->replyTo) === 1
                    && $envelope->replyTo[0]->address ===
                        'customer@example.com'
                    && $envelope->replyTo[0]->name ===
                        'Test Customer';
            }
        );
    }

    public function test_invalid_contact_form_does_not_send_email(): void
    {
        Mail::fake();

        $this
            ->from('/contact')
            ->post('/contact', [
                'name' => '',
                'email' => 'not-an-email',
                'subject' => '',
                'message' => 'short',
            ])
            ->assertRedirect('/contact')
            ->assertSessionHasErrors([
                'name',
                'email',
                'subject',
                'message',
            ]);

        Mail::assertNothingSent();
    }

    public function test_contact_form_is_rate_limited_after_three_submissions_per_minute(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Rate Limit Customer',
            'email' => 'rate-limit@example.com',
            'subject' => 'Support request',
            'message' => 'This is a valid support request for rate limit testing.',
        ];

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this
                ->post('/contact', $payload)
                ->assertRedirect();
        }

        $this
            ->post('/contact', $payload)
            ->assertStatus(429);

        Mail::assertSent(ContactMessageMail::class, 3);
    }
}