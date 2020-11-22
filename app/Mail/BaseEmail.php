<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BaseEmail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $content, $response_address_id;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($content, $reply_to, $response_id, $subject)
    {
        $this->content = $content;
        $this->reply_to = $reply_to;
        $this->response_id = $response_id;
        $this->subject_header = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->view('baseEmail');
        $this->subject($this->subject_header);
        $this->withSwiftMessage(function ($message) {
            $message ->getHeaders()
                ->addTextHeader('In-Reply-To', $this->response_id);
            $message ->getHeaders()
                ->addTextHeader('References', $this->response_id);
            $message
                ->setSender(env('MAIL_FROM_ADDRESS'));
            $message
                ->setReplyTo($this->reply_to);
        });   
        
    
    }
}
