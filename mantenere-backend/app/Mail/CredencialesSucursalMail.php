<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
class CredencialesSucursalMail extends Mailable
{
    use Queueable, SerializesModels;
    public $encargado;
    public $password;
    public $negocioNombre;
    public function __construct(User $encargado, $password, $negocioNombre)
    {
        $this->encargado = $encargado;
        $this->password = $password;
        $this->negocioNombre = $negocioNombre;
    }
    public function build()
    {
        return $this->subject('Tus credenciales de acceso - Sucursal: ' . $this->negocioNombre)
                    ->view('emails.credenciales_sucursal');
    }
}
