<?php

namespace App\Mail;

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnrollmentOnboardingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EnrollmentApplicant $applicant,
        public Student $student,
        public string $tempPassword,
        public ?string $msError = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@amis.edu.ph', 'AMIS Enrollment'),
            subject: '🎉 Welcome to AMIS! Official Student Microsoft 365 Account Credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enrollment-onboarding',
            with: [
                'studentName' => $this->studentName(),
                'genderWord' => $this->genderWord(),
                'pronoun' => $this->genderWord() === 'son' ? 'him' : 'her',
            ],
        );
    }

    private function studentName(): string
    {
        return trim($this->applicant->first_name.' '.$this->applicant->last_name);
    }

    private function genderWord(): string
    {
        return strtolower((string) ($this->applicant->gender ?? 'male')) === 'female' ? 'daughter' : 'son';
    }
}
