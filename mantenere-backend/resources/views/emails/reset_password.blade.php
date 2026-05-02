<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Restablecer Contraseña - Mantenere</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2 style="color: #0284c7; text-align: center;">Mantenere Administrador</h2>
        <p>Hola,</p>
        <p>Recibes este correo porque se solicitó un restablecimiento de contraseña para tu cuenta.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetLink }}" style="background-color: #f59e0b; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                Restablecer Contraseña
            </a>
        </div>

        <p>Este enlace de recuperación de contraseña expirará en 60 minutos.</p>
        <p>Si no solicitaste un restablecimiento de contraseña, no es necesario realizar ninguna acción y tu contraseña actual seguirá siendo segura.</p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="font-size: 12px; color: #888;">Si tienes problemas haciendo clic en el botón, copia y pega la siguiente URL en tu navegador web:</p>
        <p style="font-size: 12px; color: #888; word-break: break-all;">{{ $resetLink }}</p>
    </div>
</body>
</html>
