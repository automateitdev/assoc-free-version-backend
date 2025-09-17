<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceAgrementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $partner_info;
    public $module_list;
    public $data;
    /**
     * Create a new message instance.
     */
    public function __construct($partner_info, $module_list, $data)
    {
        $this->partner_info = $partner_info;
        $this->module_list = $module_list;
        $this->data = $data;
    }

    public function build()
    {
        $subject = "Service Agrement Of Academy"." Date:-".date('d-m-y');
        return $this->view('mail.subscription_form.service-agriment')
        ->with(
            [
                'data'=>$this->data,
                'partner_info'=>$this->partner_info,
                'module_list'=>$this->module_list
            ]
            )
        ->from('service@automateIt.com')
        ->subject($subject);

    }

    // /**
    //  * Get the message envelope.
    //  */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Service Agrement Mail',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    // /**
    //  * Get the attachments for the message.
    //  *
    //  * @return array<int, \Illuminate\Mail\Mailables\Attachment>
    //  */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
