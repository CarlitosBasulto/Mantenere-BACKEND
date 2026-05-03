<!DOCTYPE html>
<html>
<head>
    <title>Credenciales de Acceso</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2 style="color: #0284c7;">Bienvenido a Mantenere</h2>
        <p>Hola <strong>{{ $encargado->name }}</strong>,</p>
        <p>Se te ha asignado el acceso como encargado de la sucursal: <strong>{{ $negocioNombre }}</strong>.</p>
        <p>A continuación, te proporcionamos tus credenciales para iniciar sesión en la plataforma:</p>
        
        <div style="background-color: #f8fafc; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 0;"><strong>URL de Acceso:</strong> <a href="{{ config('app.frontend_url', 'https://tu-dominio.com') }}/inicio-sesion">Iniciar Sesión</a></p>
            <p style="margin: 10px 0 0 0;"><strong>Correo Electrónico:</strong> {{ $encargado->email }}</p>
            <p style="margin: 10px 0 0 0;"><strong>Contraseña:</strong> {{ $password }}</p>
        </div>
        <p>Te recomendamos guardar este correo en un lugar seguro. Una vez que inicies sesión, podrás ver únicamente la información y reportes de tu sucursal asignada.</p>
        <p>Saludos,<br>El equipo de Mantenere</p>
    </div>
</body>
</html>
