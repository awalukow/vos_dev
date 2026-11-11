<?php
return [
    'bounce_address'=>env('TICKET_BOUNCE_ADDRESS'),
    // Example: {mail.example.com:993/imap/ssl}INBOX. Certificate validation stays enabled.
    'bounce_mailbox'=>env('TICKET_BOUNCE_MAILBOX'),
    'bounce_username'=>env('TICKET_BOUNCE_USERNAME'),
    'bounce_password'=>env('TICKET_BOUNCE_PASSWORD'),
];
