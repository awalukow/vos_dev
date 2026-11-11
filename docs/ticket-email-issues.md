# Ticket email delivery issues

Ticket verification codes, replacement-email codes, and ticket emails now receive a tracked Message-ID. Immediate send failures flag the customer. A scheduled command imports delivery-status notifications from a trusted inbox every five minutes, matching both the original Message-ID and failed recipient. Orders display their customer's email issue independently of payment/ticket status. The payment screen prioritizes an active email issue over the submission timestamp. After an address is corrected, an order whose latest email failed still shows "Delivery failed — resend needed" until it is resent. Successful submission is labeled "Submitted to mail server — delivery unconfirmed"; it is not proof of inbox delivery.

Administrators and adm2 see escaped delivery diagnostics, recipient, detection time, related order, and retained resolution history. Ticket operators see `Email Issue, ask user to confirm a new email and notify administrator`. Signed-in customers see `We're unable to confirm your email. Please change email by clicking here`, linking to `/tickets/email`.

The new address stays pending until a six-digit, ten-minute code is verified. Codes allow at most five attempts, and requests are rate limited. A verified change updates the existing customer (and therefore their orders), clears the warning, and retains error history. Manually generated registration codes and successful SMTP submission do not clear a delivery warning. No ticket/payment status is changed and tickets are not automatically resent.

## Server activation

1. Deploy the changed source files and run `php artisan migrate --force` before resuming traffic. The migration adds customer recovery fields and the delivery-history table. Run `php artisan optimize:clear` after deployment.
2. Enable PHP IMAP for the CLI PHP version used by the scheduler.
3. Configure these environment variables securely on the hosting server (do not commit credentials):

```dotenv
TICKET_BOUNCE_ADDRESS=noreply@voiceofsoulchoir.id
TICKET_BOUNCE_MAILBOX="{voiceofsoulchoir.id:993/imap/ssl}INBOX"
TICKET_BOUNCE_USERNAME=noreply@voiceofsoulchoir.id
TICKET_BOUNCE_PASSWORD="ENTER_MAILBOX_PASSWORD_ON_SERVER"
```

The supplied mail client settings specify voiceofsoulchoir.id on IMAP port 993 with SSL and authentication. TLS certificate validation must remain enabled. Confirm the SMTP provider permits this envelope return address and actually routes bounces to this inbox.

4. Ensure the existing Laravel scheduler runs every minute using the correct PHP binary: `php /absolute/project/path/artisan schedule:run`. The importer runs every five minutes with overlap protection. It examines read and unread messages from the last 30 days, leaves the mailbox unchanged, skips messages larger than 2 MB, and deduplicates matched failures. Use a dedicated bounce inbox or archive older mail regularly to keep scans small.
5. Run `php artisan tickets:import-bounces` to check connectivity. Missing IMAP support or connection failure returns a nonzero exit status; monitor the scheduler output. After configuring or changing environment values, refresh the application's configuration cache using your normal deployment process.
6. Validate using a controlled delivery failure from a newly sent tracked message. Check the customer and order warnings under administrator, adm2, and ticket-operator accounts, then verify a replacement email and confirm the warning clears.

## Manual import and limits

`php artisan tickets:import-bounces --file=/secure/path/bounce.eml` imports a saved full MIME delivery report; `--file=-` reads one from standard input. This is an administrator/hosting command, not a public webhook. Only use trusted mailbox input. The importer supports multipart MIME, base64/quoted-printable parts, per-recipient failed/delayed DSN reports, and returned original headers. Delivery acknowledgements are ignored.

Messages sent before tracking was deployed, pasted bounce summaries without original headers, or nonstandard reports without the tracked Message-ID cannot be automatically attributed. The October 14 bounce in the chat predates this tracking. Forwarded reports must preserve their original MIME structure. Mail providers that rewrite Message-IDs or omit original headers need a provider-specific adapter. The command reports how many recipient reports matched; repeat scans may report those matches again without duplicating or reopening records.

Only authenticated active customers can change their own email. Technical diagnostics are rendered only in the restricted staff views. An IP/server block such as S3150 still needs the sending provider's attention, even when the customer follows the requested change-email prompt.

No production migration, inbox connection, or live delivery test was performed during local implementation.
