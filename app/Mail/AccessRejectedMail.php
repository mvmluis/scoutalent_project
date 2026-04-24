<?php

namespace App\Mail;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccessRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public AccessRequest $req;

    public function __construct(AccessRequest $req)
    {
        $this->req = $req;
    }

    public function build()
    {
        return $this->subject('Pedido de acesso recusado — Scoutalent')
            ->view('emails.access_rejected')
            ->with(['req' => $this->req]);
    }
}
