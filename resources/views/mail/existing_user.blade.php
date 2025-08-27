<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset - Centerpoint Group</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 40px 10px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; padding: 30px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="font-size: 18px; color: #333333;">
                            <p>Hi {{ $user->first_name }},</p>

                            <p>Your new password for the <strong>Centerpoint Group Database Portal</strong> has been successfully generated.</p>

                            <p>
                                You can access the portal using the link below:<br>
                                <a href="https://sql.centerpointgroup.com" target="_blank" style="color: #007bff; text-decoration: none;">
                                    https://sql.centerpointgroup.com
                                </a>
                            </p>

                            <p>Your updated login credentials are:</p>
                            <ul style="list-style: none; padding-left: 0;">
                                <li><strong>Username:</strong> {{ $user->email }}</li>
                                <li><strong>Password:</strong> {{ $password }}</li>
                            </ul>

                            <p>We recommend updating your password after logging in.</p>

                            <p>Best regards,<br>
                            <strong>Centerpoint Group Team</strong></p>
                        </td>
                    </tr>
                </table>
                <img src="https://sql.centerpointgroup.com/images/logo.jpg">
                <p style="font-size: 12px; color: #888888; margin-top: 20px;">
                    &copy; {{ date('Y') }} Centerpoint Group. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
