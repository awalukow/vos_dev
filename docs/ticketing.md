# VOS ticketing

The existing homepage is preserved with a **Tickets** navigation link.
The storefront is /tickets; staff management is /portal/ticketing/events.
Customer authentication uses a separate customer guard and customers table.
Existing portal accounts remain staff accounts.

## Setup on an existing installation

1. Back up the database and document storage. PHP needs GD, PDO, Fileinfo, Zip and
   OpenSSL alongside the existing Laravel dependencies.
2. Apply the additive ticketing schema and portal menu migrations (skip any already applied):

       php artisan migrate --path=database/migrations/2026_10_07_000001_create_ticketing_tables.php
       php artisan migrate --path=database/migrations/2026_10_07_000002_add_ticketing_portal_menus.php
       php artisan migrate --path=database/migrations/2026_10_07_000003_add_ticket_order_list_menu.php
       php artisan migrate --path=database/migrations/2026_10_07_000004_add_singers_and_booking_codes.php
       php artisan migrate --path=database/migrations/2026_10_08_000001_add_ticket_event_layout_dividers.php
       php artisan migrate --path=database/migrations/2026_10_08_000003_add_ticket_row_status.php

3. Set APP_URL to the real HTTPS origin. Configure MAIL_MAILER=smtp, MAIL_HOST,
   MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS
   and MAIL_FROM_NAME in the deployment environment. Do not commit credentials.
   Set SESSION_SECURE_COOKIE=true for HTTPS.
4. Run php artisan optimize:clear. Ensure storage and bootstrap/cache are writable.
5. Sign into the portal as Administrator or Adm2. Open **Ticketing** in the portal sidebar.
   Configure real bank details and upload your merchant QRIS image under
   **Payment methods**. Both methods start disabled.
6. Create a venue for numbered seating, then create the concert, classes, prices
   and thumbnail. Published concerts with a future start time appear under Upcoming
   concerts. All non-removed past concerts appear under Previous Concerts,
   including unpublished events, without prices or purchase buttons.
   The admin events list shows storefront visibility alongside publication status.
7. Test a complete booking in staging, including real SMTP delivery and payment
   confirmation against your bank/merchant records before issuing live tickets.

Historical migrations have an existing ordering problem: the January attendance
migration alters schedule, which is created by a March migration. The command
above installs ticketing on an existing portal without replaying that history.
Fresh installations need the historical migration ordering corrected separately.

Use MySQL/InnoDB or another transactional database with row locking for live
sales. The isolated SQLite tests do not simulate production concurrent
connections. The legacy /system/migrate endpoint now requires an authenticated Administrator
POST with CSRF protection; the old query-key GET shortcut no longer works.

No new Composer package or Vite build is required. Fixed asset routes support
both this repository's root front controller and a normal public document root.
Uploads are private, so no storage symlink is required. Concert dates are
entered/displayed in Jakarta time (WIB) and stored in UTC, without changing
the legacy app timezone.

## Staff and customer accounts

Events, bookings (`ticket_orders`) and issued tickets (`ticket_order_items`) use
`RowStatus`: `0` is active and `-1` is removed. Existing rows default to active.
Administrator and Adm2 can delete events and bookings from their lists. Event
removal stops new sales and hides the event, while existing bookings remain
accessible with their event details. Booking removal hides the booking and its
tickets, disables their receipt/QR links and releases inventory without deleting
payment evidence, ticket snapshots or audit logs. It does not issue a refund.
Customer cancellation remains limited to unpaid reservations and also marks the
booking and its tickets removed. Removed bookings still lock the original event
seating and prices. For internal history queries, use
`withoutGlobalScope('active')`; ordinary model queries exclude removed rows.

- Administrator and Adm2: events, venues, payment methods, customers, manual
  OTP generation and payment review.
- Ticket Operator (ticket_operator): payment proofs, approval/rejection and
  ticket email resend. An Administrator can assign this role in Portal → Users.
- Customers and other portal roles cannot access ticket administration.

All ticketing administration uses the existing portal layout, staff session,
dark-and-gold theme, header and sidebar. The Ticketing group contains Events,
Venue Designer, Customers & OTP, Payment Methods and Payment Approvals.
These are ordinary portal_menus entries controlled by the existing Menu
Permissions screen. Administrators and Adm2 receive all ticketing menus; ticket
operators receive only Payment Approvals. Backend role checks remain enforced.
The customer storefront layout is used only for customer-facing pages.

Customer name, DOB, phone and active state can be edited in the portal.
Email editing is deliberately unavailable to avoid bypassing verification.

## Customer journey

1. Browse published upcoming concerts. Purchase requires customer login.
2. Register with name, DOB, email, phone and confirmed password (10+ characters).
3. Verify the emailed six-digit OTP. Codes are hashed, expire after 10 minutes,
   allow 5 attempts, and have a 60-second resend cooldown. HTTP rate limits
   also apply to login, registration, verification and resend.
4. Choose up to 10 tickets per booking: quantities by class for free seating,
   or individual color-coded seats for numbered seating.
5. Review the confirmation dialog; confirm to reserve tickets for 30 minutes.
   Prices are calculated by the server.
6. Transfer or scan the configured QRIS code. Upload JPG, PNG or PDF proof
   (maximum 5 MB) before the reservation deadline.
7. Staff match the payment against bank/merchant records. Approval issues tickets;
   rejection releases inventory and displays the review note to the customer.
8. A booking QR and individual ticket QRs arrive by email, inline and as PNG
   attachments. Numbered tickets include their seat labels. Tickets are also
   accessible in **My tickets**, with print/save support.

## Inventory, approval and delivery

Every inventory mutation locks the event row first inside a database transaction.
This covers reservations, cancellations, proof submission, reviews, and event
inventory edits. Booking items snapshot prices and seat labels.

Expired unpaid reservations stop counting against availability immediately,
without a cron job. A subsequent booking marks expired reservations accordingly.
Payment-review reservations retain seats until approved or rejected; paid
bookings retain inventory. Approval is single-use, and email resends preserve
the same QR identities.

Cards are greyed out when no inventory remains. More than 80% reserved or sold
shows **Almost Sold**. **Limited Seating** is an independent admin checkbox.
Free-seating cards show a purple **FREE SEATING** badge.

After the first booking (including expired bookings), seating mode, venue,
classes and capacity are locked. Prices can always be changed for new bookings;
existing bookings retain their saved item prices and totals. Create a separate event for a new
inventory arrangement. Metadata remains editable; date changes do not
automatically email existing buyers.

Proofs require manual review. OCR is not used to approve payments: an uploaded
image cannot establish that money settled in your account.

Staff actions are recorded in ticket_audit_logs. The OTP Generator creates a
replacement code, displayed once in a session flash for manual assistance.
Plaintext OTPs are not stored in the database or audit trail.

Email is synchronous in this version. If ticket email fails, approval remains
committed; staff can use **Resend tickets**, and the customer can still open
issued tickets. Monitor Laravel mail errors and verify SMTP in staging.

QR verification now requires the active, verified booking owner or an active
administrator, ADM2, or ticket operator. Guests and other customers receive 403
without booking details. Authorized viewers see event/class/seat and payment
validity without customer contact details. It does not implement admission check-in or prevent
a QR from being presented twice. Gate scanning, refunds, automated settlement,
and customer password recovery are outside this implementation.

## Venue designer and file format

The designer supports row generation, dragging seats, label/class edits,
coordinate edits (also usable without dragging), removal, and JSON import/export.
The stage is at the top; empty grid positions represent aisles or section gaps.
Colors and prices belong to event classes, matched by exact venue class names.

Use **Multi-select seats** (or Ctrl/Cmd-click), **Select row**, or **Select all**,
then enter a class name and choose **Apply class to selected seats**. Existing
class names are suggested. Save the venue after editing.

Optional `dividers` in version 1 layouts contain `label` and integer `y` fields,
for example `"dividers": [{"label":"BALCONY","y":16}]`. Leave that grid row
empty of seats. The label sits between double lines across the map. Dividers
are copied to events together with the seating map and remain unchanged on
booked events. The stage shares the map's center and horizontal scroll area.

The Gereja Toraja Jakarta file, `docs/gereja-toraja-jakarta.seating.json`, has
300 main-floor seats (A–O, 1–20 per row) in four five-seat blocks and a wider
central walkway. The outer block boundaries are 1.25 seat pitches apart. The centered balcony has 150 seats (P–T, 1–30 per row) in
three ten-seat blocks, following the BALCONY divider. All seats start as Regular.

**Remove venue** deletes the reusable template and unpublishes all linked
events. Existing orders, tickets, seat maps, and prices remain intact. A removed
venue is cleared from its events; numbered events cannot be republished without
an available venue. Events with bookings retain their locked arrangement and
must remain unpublished after removal; create a new event to resume sales.

Example version 1 layout:

    {
      "version": 1,
      "seats": [
        {"label": "A1", "class": "Premium", "x": 0, "y": 0},
        {"label": "A2", "class": "Premium", "x": 1, "y": 0},
        {"label": "A3", "class": "Premium", "x": 3, "y": 0},
        {"label": "B1", "class": "Regular", "x": 0, "y": 1}
      ]
    }

Labels and positions must be unique. X supports quarter-seat increments and Y is an integer, both from 0 to 100, with up to
3,000 seats. Labels allow 40 characters, class names 60, JSON uploads 1 MB.
Numbered-event capacity comes from mapped seats.

Venues are reusable templates. Each event copies the arrangement; editing a
venue never silently moves purchased seats. Edit and save an unbooked event to
apply a changed template. Booked events retain their original map.

A compatible starter file is docs/venue-layout.example.json.

## Validation

Run:

    php vendor/phpunit/phpunit/phpunit tests/Feature/TicketingTest.php

The suite overrides database, mail and storage with isolated test resources.
It covers account separation, OTP lifecycle, inventory/expiry, approval,
ownership, staff permissions, QR PNG generation, email rendering, all ticketing
pages, venue import/export, and server-calculated prices.

No production database migrations or real customer emails were run during
development. Browser visual verification was blocked by browser permissions.
## Customer details and order access

Birth dates use DD/MM/YYYY in registration and staff customer editing; storage remains ISO dates. Phone numbers normalize on leaving the field and on the server: 0812… and +62 812… become 62812…, while existing 62 prefixes are preserved.

The ticket storefront defaults to Bahasa Indonesia. Its language selector saves the Bahasa Indonesia / English choice in the session. Booking details group quantities and prices by class, with assigned seats on a third line only when present.

Portal → Ticketing → Order List is available to administrators, ADM2, and ticket operators. Search by booking reference, customer name/email, or status. Open an order to view its details and, after payment approval, booking and individual ticket QR barcodes.

## Singer referrals and booking codes

Administrator and Adm2 can add, edit and deactivate singers under **User Management → Singer List**.
Each singer has a unique referral code in `portal_singers`. Customers choose an active singer
(or No singer referral) alongside the payment method. Proof submission saves the singer ID,
name and referral code on the order; later singer edits do not rewrite historical referrals.
My tickets, booking details, confirmation emails and the portal order table display the referral.
The portal table retains status, event, customer, email, reference and total, adds booking code
and referral, and puts Open order in the last column. Search accepts either code or singer name.

Every reservation receives a unique random five-character uppercase alphanumeric booking code.
The new migration backfills existing orders; UUID references and existing QR URLs remain unchanged.
QR PNGs include the booking code above the barcode, including emailed attachments. Individual tickets
use `ABCDE/1`, `ABCDE/2` for free seating and `ABCDE/C1` for numbered seats. The short code is a label;
private booking and validation links continue using their existing UUIDs. A unique database index
prevents duplicate codes and reservation creation retries a concurrent code collision.

## Promo codes

Run migration `2026_10_08_000004_create_ticket_promos.php` when deploying this update.
Administrators, ADM2 and ticket operators manage codes under **Ticketing → Promo Codes**.
Offers support percentage or fixed rupiah discounts, repeating Buy 1 Get 1,
X free tickets with at least Y paid tickets (once per booking), or one free ticket.
Customers select all tickets/seats, including the free ones, before applying the
code in Booking Details after the singer referral. Free offers discount the cheapest
selected tickets; no unselected seats are issued. The existing ten-ticket booking
limit applies to paid and free tickets together. One promo applies per booking.

Optional settings include expiry in Jakarta time, single use per customer, a shared
daily booking limit, and restriction to one or many registered customer email addresses.
Blank expiry/daily limit means unlimited. Disable a code through its edit form.
Applying a code reserves usage on that Jakarta calendar day; expired/cancelled holds
and rejected payments release usage. Submitted and paid orders retain their usage,
even if staff subsequently delete the booking. Reapplying the same code is idempotent.
Global limits serialize under a promo row lock across events, inside booking transactions.

Successful application shows a green dialog and updates the payable total. Payment
submission checks eligibility again, and changes to the amount require reapplying
the code before submitting proof. Zero-total bookings use **Confirm free tickets**
without payment proof and issue the selected tickets. Orders and confirmation emails
retain the applied code and discount; editing a promo does not rewrite completed orders.

The promo migration was applied to the local `vos_dev` database. The automated suite
uses isolated SQLite, storage and mail. MySQL concurrency under live load and browser
visual layout are not covered by those tests.


## Midtrans QRIS

Run `php artisan migrate --path=database/migrations/2026_10_09_000001_add_midtrans_payments.php`.
Midtrans is seeded **deactivated**, in sandbox mode, with both fees set to zero.
In Ticketing → Payment Methods, administrators and ADM2 configure the environment,
merchant ID and API keys. Blank key inputs retain saved keys; changing environments
requires a new server key. Keys are encrypted using APP_KEY, hidden from model JSON,
and excluded from flashed validation inputs. Keep APP_KEY backed up.
Ticket operators can activate/deactivate Midtrans and edit its two fees; account and
key changes are rejected server-side. Existing manual methods remain admin-managed.

Processing Fee and Platform Fee each support a fixed rupiah amount or a percentage
(0–100%). Zero disables a fee. Both apply independently to the ticket total after
promotions, once per booking, and round to whole rupiah. Checkout shows each charge,
its information tooltip and the final total. Fees and credentials are snapshotted
when starting payment; subsequent settings changes do not alter pending payments.
Free bookings continue without gateway fees.

Customers choose **QRIS (Automated Check)** and continue to hosted Midtrans Snap,
restricted to `other_qris`. Enable GoPay or ShopeePay QRIS on the merchant account.
Copy the notification URL displayed in the portal into the Midtrans dashboard:
`https://YOUR-DOMAIN/tickets/midtrans/notification`. Use a publicly reachable HTTPS
site and set APP_URL correctly. No client-side redirect or query parameter confirms
payment: notifications require a valid SHA-512 signature and the server fetches the
current provider status, checks reference, amount and IDR currency, then confirms
settlement once and sends tickets. Deactivation stops new checkouts but existing
payments continue to reconcile with their original credentials.

Run Laravel's scheduler every minute (`php artisan schedule:run`) for missed webhook
recovery. `php artisan tickets:sync-midtrans` also reconciles pending payments manually.
Customers can refresh status from their booking. Midtrans reservations retain seats
and promo usage until confirmed paid, denied, cancelled or expired. They cannot be
manually approved, cancelled, changed or removed while a payment is pending.
Snap and QRIS receive a fixed 30-minute blanket expiry. A status API 404 before expiry
means no payment method has been selected yet; inventory remains held. After expiry
plus a five-minute reconciliation grace period, 404 releases an abandoned checkout.
Network/authentication failures never release inventory automatically. The command
reports unresolved bookings for follow-up. A checkout creation timeout may require
an organizer to investigate in Midtrans; retries reuse the same order reference to
avoid creating a second charge. Failed ticket emails can use the existing staff resend.

Reference: [Midtrans Other QRIS](https://docs.midtrans.com/reference/other-qris),
[notifications](https://docs.midtrans.com/docs/https-notification-webhooks), and
[Snap expiry](https://docs.midtrans.com/reference/expire-a-snap-session).
Local tests fake Midtrans HTTP responses; a merchant sandbox payment and notification
round trip are still required before production activation.


Administrators and ADM2 can use **Test connection** on the Midtrans settings card.
Save settings first: the test uses only the saved environment and server key, works
while deactivated, and does not modify settings or create a payment. It performs an
authenticated lookup of a random nonexistent order; Midtrans's application-level
404 confirms API access. Authentication failures, timeouts and unexpected responses
are shown inline without exposing keys or raw provider responses. This verifies API
access only, not QRIS enablement, the client key, merchant ID, or webhook delivery.

## Concert memories, performance dashboard and Excel reports

Deploy this update with:

    php artisan migrate --path=database/migrations/2026_10_09_000002_add_concert_memories_and_dashboard.php

This migration adds memory media fields and makes **Dashboard** the first Ticketing
submenu for Administrator, ADM2 and Ticket Operator. It has been applied to the
local `vos_dev` database. PHP ZipArchive is required for the native XLSX exports;
no new Composer dependency is needed.

All non-removed events move to **Previous Concerts** once their start time is
reached, regardless of publication status. Cards show only the banner, date/time,
concert name, location and **See Memories**. Removed events and their media URLs
return 404. The popup supports previous/next photos and keyboard arrow controls,
a YouTube video tab, and **Coming Soon** when no media is available. Closing the
popup removes the video frame to stop playback. YouTube must allow embedding.

In **Events → Edit**, past concerts show a **Manage memories** banner above
The essentials. Administrators and ADM2 can add up to 30 JPG, PNG or WebP photos
(4 MB each), remove individual photos, and save or clear a YouTube link. Uploads
append to the existing slideshow and use private local storage served by checked
routes. Future events cannot have memories edited. Server PHP upload limits still
apply to the total request size. Edit and Delete are now in separate event-table
columns, and deletion retains its confirmation prompt.

The read-only dashboard defaults to all non-removed concerts, with a single-concert
filter. Numbers are calculated from active orders over the full sales period:

- **Lunas**: ticket count on paid orders, including free tickets.
- **Belum Lunas**: tickets held by unexpired awaiting-payment orders, manual payment
  review, or pending Midtrans payments. Expired, rejected and cancelled orders do
  not occupy seats.
- **Sisa Kursi**: capacity minus paid seats and active unpaid holds, floored at zero
  separately for each concert.
- **Nominal Terjual**: paid order totals after discounts, excluding processing and
  platform fees. Fees and total receipts are shown separately and reconcile.
- **Top 5 Referral**: paid seats grouped by saved referral code, ordered by seat
  count, then ticket revenue, then code. Unreferred orders are excluded.

**Export laporan** downloads an XLSX performance report with scope, timestamp,
metric definitions, reconciliation notes, per-concert results, referral ranking
and order statuses. It uses the same concert filter and calculations as the screen.
**Order List → Export Excel** downloads all matching transactions across all pages,
including removed records clearly marked with RowStatus -1, plus a separate ticket
line-item sheet. Search and status filters apply. Amounts are numeric Excel cells;
booking codes, telephone numbers and user-entered text remain literal strings, so
text beginning with `=` cannot become a spreadsheet formula. Credentials, QR tokens
and private evidence links are never exported. Reports require the ticket staff
role and are delivered with private/no-store headers.

Confirmation email subjects and content are always Bahasa Indonesia, including
month names and promo/fee descriptions, independent of storefront language. The
original `vos-logo.jpg` is embedded inline in place of the VOS TICKETS wordmark.

Validation includes isolated SQLite/mail/storage feature tests for these flows,
XLSX XML/row/value checks and browser checks with synthetic fixtures for the
slideshow, video frame cleanup and Coming Soon state. No real customer email was
sent. Provider-hosted YouTube playback and final delivery in email clients require
normal live-service connectivity.
