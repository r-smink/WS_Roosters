<div class="rp-login-wrapper">
    <div class="rp-login-card">
        <div class="rp-login-logo">
            <span class="rp-icon">📅</span>
            <h2>Rooster Planner</h2>
            <p>Medewerker Login</p>
        </div>
        
        <?php
        $login_url = wp_login_url(home_url('/medewerker-dashboard/'));
        wp_login_form([
            'redirect' => home_url('/medewerker-dashboard/'),
            'label_username' => 'Email / Gebruikersnaam',
            'label_password' => 'Wachtwoord',
            'label_remember' => 'Onthoud mij',
            'label_log_in' => 'Inloggen',
        ]);
        ?>
        
        <div class="rp-login-links">
            <a href="<?php echo wp_lostpassword_url(); ?>">Wachtwoord vergeten?</a>
        </div>
        
        <div class="rp-login-help">
            <p>Problemen met inloggen? Neem contact op met je manager.</p>
        </div>
    </div>
</div>

<style>
.rp-login-wrapper {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.rp-login-card {
    background: #fff;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 400px;
}
.rp-login-logo {
    text-align: center;
    margin-bottom: 30px;
}
.rp-icon {
    font-size: 48px;
}
.rp-login-logo h2 {
    margin: 15px 0 5px;
    color: #1f2937;
}
.rp-login-logo p {
    color: #6b7280;
    margin: 0;
}
.rp-login-card form {
    margin: 0;
}
.rp-login-card p {
    margin: 15px 0;
}
.rp-login-card label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #374151;
}
.rp-login-card input[type="text"],
.rp-login-card input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s;
}
.rp-login-card input[type="text"]:focus,
.rp-login-card input[type="password"]:focus {
    outline: none;
    border-color: #4F46E5;
}
.rp-login-card input[type="submit"] {
    width: 100%;
    padding: 14px 24px;
    background: #4F46E5;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.rp-login-card input[type="submit"]:hover {
    background: #4338CA;
}
.rp-login-card .forgetmenot {
    display: flex;
    align-items: center;
    gap: 8px;
}
.rp-login-card .forgetmenot input {
    margin: 0;
}
.rp-login-links {
    text-align: center;
    margin-top: 20px;
}
.rp-login-links a {
    color: #4F46E5;
    text-decoration: none;
}
.rp-login-links a:hover {
    text-decoration: underline;
}
.rp-login-help {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}
.rp-login-help p {
    color: #9ca3af;
    font-size: 14px;
    margin: 0;
}
@media (max-width: 480px) {
    .rp-login-wrapper {
        padding: 10px;
    }
    .rp-login-card {
        padding: 30px 20px;
    }
}
</style>
