<?php

/**
 * Get email header template with logo and styling
 * 
 * @return string HTML email header
 */
function get_email_header() {
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BahariHost</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #FF9800;
            --primary-light: #FFB74D;
            --primary-dark: #F57C00;
            --secondary: #00BCD4;
            --secondary-light: #4DD0E1;
            --secondary-dark: #0097A7;
            --success: #4CAF50;
            --dark: #1A237E;
            --light: #ffffff;
            --gray-50: #FAFAFA;
            --gray-100: #F5F5F5;
            --gray-200: #EEEEEE;
            --gray-900: #212121;
        }

        body {
            font-family: "Outfit", system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            background: var(--gray-50);
            color: var(--gray-900);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 700px;
            margin: 30px auto;
            background: var(--light);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08), 0 0 0 1px rgba(0,0,0,0.01);
        }

        .header {
            position: relative;
            padding: 45px 30px;
            text-align: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            overflow: hidden;
        }

        .header::before,
        .header::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(255,255,255,0.15), transparent);
        }

        .header::before {
            top: -150px;
            left: -150px;
        }

        .header::after {
            bottom: -150px;
            right: -150px;
        }

        .geometric-shape {
            position: absolute;
            background: rgba(255,255,255,0.1);
        }

        .shape-1 {
            width: 60px;
            height: 60px;
            top: 20%;
            left: 10%;
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
        }

        .shape-2 {
            width: 40px;
            height: 40px;
            top: 60%;
            right: 15%;
            clip-path: polygon(50% 0%, 100% 38%, 82% 100%, 18% 100%, 0% 38%);
        }

        .logo {
            max-width: 280px;
            height: auto;
            position: relative;
            z-index: 2;
        }

        .nav {
            background: var(--gray-50);
            padding: 20px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav a {
            color: var(--gray-900);
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--light);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05), 0 0 0 1px rgba(0,0,0,0.03);
        }

        .nav .login-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--light);
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);
        }

        .email-content {
            padding: 40px 30px;
            background: var(--light);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="geometric-shape shape-1"></div>
            <div class="geometric-shape shape-2"></div>
            <img src="https://www.baharihost.com/images/navbar.png" alt="BahariHost Logo" class="logo">
        </div>
        
        <div class="nav">
            <a href="https://www.baharihost.com">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="https://www.baharihost.com/hosting">
                <i class="fas fa-server"></i> Buy Hosting
            </a>
            <a href="https://www.baharihost.com/login" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        </div>
        
        <div class="email-content">';
}

/**
 * Get email footer template with social links and contact info
 * 
 * @return string HTML email footer
 */
function get_email_footer() {
    return '</div>
        <div class="footer" style="background: linear-gradient(135deg, #1E293B, #0F172A); color: var(--light); padding: 40px 30px; text-align: center; position: relative; overflow: hidden;">
            <h3 style="font-size: 1.8em; margin-bottom: 25px; background: linear-gradient(90deg, var(--primary), var(--secondary)); -webkit-background-clip: text; color: transparent;">BahariHost</h3>
            
            <div class="social-links" style="display: flex; justify-content: center; gap: 20px; margin: 30px 0;">
                <a href="https://www.facebook.com/baharihost" style="color: var(--light); width: 55px; height: 55px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; background: rgba(255,255,255,0.03);">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://www.instagram.com/baharihost" style="color: var(--light); width: 55px; height: 55px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; background: rgba(255,255,255,0.03);">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://www.youtube.com/@baharihost" style="color: var(--light); width: 55px; height: 55px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; background: rgba(255,255,255,0.03);">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="https://www.baharihost.com" style="color: var(--light); width: 55px; height: 55px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; background: rgba(255,255,255,0.03);">
                    <i class="fas fa-globe"></i>
                </a>
            </div>
            
            <a href="https://www.facebook.com/baharihost" style="color: var(--light); padding: 16px 40px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 1.1em; display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); box-shadow: 0 5px 20px rgba(255,152,0,0.3);">
                <i class="fas fa-comments"></i> LIVE CHAT
            </a>
            
            <div class="footer-links" style="display: flex; justify-content: center; gap: 25px; margin-top: 30px; padding-top: 30px; flex-wrap: wrap; border-top: 1px solid rgba(255,255,255,0.05);">
                <a href="https://www.baharihost.com" style="color: rgba(255,255,255,0.7); text-decoration: none;">Website</a>
                <a href="https://www.baharihost.com/tos" style="color: rgba(255,255,255,0.7); text-decoration: none;">Terms of Service</a>
                <a href="https://www.baharihost.com/privacy" style="color: rgba(255,255,255,0.7); text-decoration: none;">Privacy Policy</a>
            </div>
            
            <p style="color: rgba(255,255,255,0.5); margin-top: 20px;">© 2021-2025 BahariHost. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
}

/**
 * Build a complete HTML email message with header and footer
 * 
 * @param string $content The main content of the email
 * @return string Complete HTML email message
 */
function build_email_message($content) {
    return get_email_header() . $content . get_email_footer();
}

/**
 * Get status badge HTML for email
 * 
 * @param string $status The status (Pending, Verified, or Rejected)
 * @return string HTML for the status badge
 */
function get_status_badge_html($status) {
    $colors = [
        'Pending' => '#ffc107',
        'Verified' => '#4CAF50',
        'Rejected' => '#dc3545'
    ];
    
    $color = $colors[$status] ?? '#6c757d';
    
    return sprintf(
        '<span style="display: inline-block; padding: 6px 12px; background: %s; color: white; border-radius: 12px; font-weight: 600; font-size: 14px;">%s</span>',
        $color,
        $status
    );
} 