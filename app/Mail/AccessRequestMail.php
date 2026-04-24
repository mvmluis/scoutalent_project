<?php

namespace App\Mail;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AccessRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public AccessRequest $req;

    public function __construct(AccessRequest $req)
    {
        $this->req = $req;
    }

    public function build()
    {
        $approveUrl = URL::temporarySignedRoute(
            'access.request.approve',
            now()->addDays(7),
            ['id' => $this->req->id]
        );

        $rejectUrl = URL::temporarySignedRoute(
            'access.request.reject',
            now()->addDays(7),
            ['id' => $this->req->id]
        );

        return $this->subject('Novo pedido de acesso — Scoutalent')
            ->view('access_request')
            ->with([
                'req' => $this->req,
                'approveUrl' => $approveUrl,
                'rejectUrl'  => $rejectUrl,
            ]);
    }
}
