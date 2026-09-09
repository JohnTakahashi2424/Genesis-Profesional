<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación - Génesis Profesional</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f4f6f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <!-- CONTENEDOR PRINCIPAL -->
                <table role="presentation" width="100%" max-width="520" cellspacing="0" cellpadding="0" border="0" style="max-width: 520px; background-color: #ffffff; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.06); overflow: hidden; border: 1px solid #e2e8f0;">
                    
                    <!-- ENCABEZADO CON BRANDING UGB -->
                    <tr>
                        <td align="center" style="background-color: #000b58; padding: 32px 24px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 0.5px; font-family: 'Lora', Georgia, serif;">
                                Génesis Profesional
                            </h1>
                            <p style="margin: 6px 0 0 0; color: #cbd5e1; font-size: 13px;">
                                Universidad Gerardo Barrios
                            </p>
                        </td>
                    </tr>

                    <!-- CUERPO DEL MENSAJE -->
                    <tr>
                        <td style="padding: 36px 32px 24px 32px; text-align: left;">
                            <h2 style="margin: 0 0 14px 0; color: #0f172a; font-size: 20px; font-weight: 600;">
                                Restablecer contraseña
                            </h2>
                            <p style="margin: 0 0 16px 0; color: #475569; font-size: 14px; line-height: 1.6;">
                                Hola <strong>{{ $nombres }}</strong>, recibimos una solicitud para cambiar la contraseña de tu cuenta institucional en Génesis Profesional.
                            </p>
                            <p style="margin: 0 0 24px 0; color: #475569; font-size: 14px; line-height: 1.6;">
                                Introduce el siguiente código de verificación de 6 dígitos en la aplicación:
                            </p>

                            <!-- CAJA CON CÓDIGO DE 6 DÍGITOS -->
                            <div style="text-align: center; margin: 28px 0;">
                                <div style="display: inline-block; background-color: #f1f5f9; border: 2px dashed #94a3b8; border-radius: 14px; padding: 16px 32px;">
                                    <span style="font-family: 'Courier New', Courier, monospace; font-size: 36px; font-weight: 700; letter-spacing: 12px; color: #010c67; display: block; margin-left: 12px;">
                                        {{ $codigo }}
                                    </span>
                                </div>
                            </div>

                            <!-- INFORMACIÓN DE SEGURIDAD Y EXPIRACIÓN -->
                            <div style="background-color: #f8fafc; border-left: 4px solid #010c67; border-radius: 6px; padding: 12px 16px; margin: 24px 0;">
                                <p style="margin: 0; color: #334155; font-size: 13px; line-height: 1.5;">
                                    ⏱ <strong>Tiempo de validez:</strong> Este código expira en <strong>1 minuto y 30 segundos</strong>.
                                </p>
                            </div>

                            <p style="margin: 20px 0 0 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                                Si no solicitaste este cambio, puedes ignorar este correo de forma segura. Tu contraseña actual no será modificada.
                            </p>
                        </td>
                    </tr>

                    <!-- PIE DE CORREO -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 20px 24px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0; color: #94a3b8; font-size: 11px; line-height: 1.5;">
                                Este es un correo automático, por favor no respondas a este mensaje.<br>
                                &copy; {{ date('Y') }} Universidad Gerardo Barrios. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
