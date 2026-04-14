<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nueva Cotización de Mantenere</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f7f9; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { background: #ffc107; padding: 30px; text-align: center; }
        .header h1 { color: #000; margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 40px; }
        .content h2 { color: #2c3e50; margin-top: 0; }
        .details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #ffc107; }
        .details p { margin: 10px 0; font-size: 15px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #7f8c8d; border-top: 1px solid #eee; }
        .button { display: inline-block; padding: 12px 25px; background-color: #000; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MANTENERE</h1>
        </div>
        <div class="content">
            <h2>¡Hola, {{ $negocio->encargado ?? 'Cliente' }}!</h2>
            <p>Se ha generado una nueva cotización para tu negocio: <strong>{{ $negocio->nombre }}</strong>.</p>
            
            <div class="details">
                <p><strong>Descripción:</strong> {{ $cotizacion->descripcion }}</p>
                <p><strong>Monto Estimado:</strong> ${{ number_format($cotizacion->monto, 2) }} MXN</p>
                <p><strong>Referencia de Trabajo:</strong> {{ $cotizacion->trabajo->titulo }}</p>
            </div>

            <p>Para revisar los detalles, descargar el PDF y aprobar o rechazar esta cotización, por favor accede a la plataforma:</p>
            
            <center>
                <a href="{{ env('FRONTEND_URL', 'http://localhost:5173') }}/cliente/cotizaciones" class="button">Ver Cotización en la Web</a>
            </center>

            <p style="margin-top: 30px;">Quedamos a tus órdenes,<br><strong>El equipo de Mantenere</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Mantenere - Todos los derechos reservados.<br>
            Este es un correo automático, por favor no respondas directamente.
        </div>
    </div>
</body>
</html>
