<?php
declare(strict_types=1);

return [
    'instagram_url' => 'https://www.instagram.com/yorvis_think_beyond/',

    // Add the final Facebook and WhatsApp links here, or set the matching
    // environment variables on the server. Example WhatsApp URL:
    // https://wa.me/919876543210?text=Hello%20Yorvis
    'facebook_url' => getenv('YORVIS_FACEBOOK_URL') ?: '',
    'whatsapp_url' => getenv('YORVIS_WHATSAPP_URL') ?: '',

    // Replace with the YORVIS_ADMIN_PASSWORD_HASH environment variable in production.
    'admin_password_hash' => getenv('YORVIS_ADMIN_PASSWORD_HASH') ?: '$2y$10$mWDsWOzQm3djn4H9z1zeJ.MrTSXxGJ3z8YpUk4/ILl/E0pDgKc8H2',
];
