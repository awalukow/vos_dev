# Customer sign-in with Google and Apple

This adds social authentication to ticket customers only. Existing email/password registration remains available. No provider tokens are stored on customer accounts. New accounts are created only after phone and a full date of birth are present. Provider identities are matched by provider and subject ID, not by email.

## Deployment

1. Enable PHP's `sodium` extension (required by the Apple JWT library), then run `composer install --no-dev --optimize-autoloader` from the committed lock file.
2. Run `php artisan migrate --force` to create `customer_social_accounts`.
3. Configure the credentials below in the server environment. Do not commit private keys or secrets. Buttons remain hidden until the corresponding provider is configured.
4. Use HTTPS with `SESSION_SECURE_COOKIE=true`. Keep the existing `SameSite=Lax` session setting. Use a persistent server-side session driver and cache (`file`, database, or Redis), not the `array` driver in production. Multiple app servers must share both stores.
5. Run `php artisan config:cache` after configuration changes.

## Google

Create a Web application OAuth client in Google Cloud, configure the consent screen and allowed users, and register this exact redirect URI:

`https://YOUR_DOMAIN/tickets/auth/google/callback`

Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI`.

### Production and development URLs

The app chooses Google's client credentials on each request by matching the current hostname exactly to the hostname in a configured callback URL:

- `voiceofsoulchoir.id`: use `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI=https://voiceofsoulchoir.id/tickets/auth/google/callback`.
- `dev.voiceofsoulchoir.id`: use `GOOGLE_DEV_CLIENT_ID`, `GOOGLE_DEV_CLIENT_SECRET`, and `GOOGLE_DEV_REDIRECT_URI=https://dev.voiceofsoulchoir.id/tickets/auth/google/callback`.

Populate the credentials in the server's `.env` (local `.env` files are not deployed through Git), then clear or rebuild Laravel's config cache. Both client configurations can coexist in the same deployment. Selection uses the request host, not `APP_ENV` or `APP_URL`, and does not change shared configuration. Both the authorization redirect and token exchange use the selected client; the flow also records the initiating host and client ID to prevent switching midway.

Local PHP development uses the development credentials and `GOOGLE_LOCAL_REDIRECT_URI`, defaulting to `http://localhost:8000/tickets/auth/google/callback`. Add that exact URL to the development OAuth client's authorized redirect URIs in Google Cloud, alongside the public development callback. The local scheme, hostname and port must match this setting; use it to configure another loopback address/port if needed. A local login returns to the local session, not the public dev server.

Google sign-in is unavailable on other unconfigured hosts. To support another development host, register its exact callback with Google and configure the corresponding development callback URI. An incomplete development configuration never falls back to the production client. The callback URI always comes from server configuration, never from an arbitrary request Host header.

If buttons are missing after publishing, check the `.env` on **each server**, not just this checkout: production needs the three `GOOGLE_*` credential/callback values, and the public development server needs the three `GOOGLE_DEV_*` values. Then run `php artisan config:clear`. Deploying tracked files does not deploy `.env`. Never commit the client secrets to fix a missing button.

Enable People API for the same project. With `GOOGLE_REQUEST_BIRTHDAY=true`, the registration button requests `https://www.googleapis.com/auth/user.birthday.read` alongside basic identity scopes. Configure the consent screen and any Google-required verification before public launch. Users may decline the birthday permission, omit their birthday, or share only month/day. API failure also falls back to manual entry. Only a valid complete date before today bypasses the DOB field. Standard login does not request the extra birthday scope; first-time users entering via login fill DOB manually.

Set `GOOGLE_REQUEST_BIRTHDAY=false` to always collect DOB manually without that extra scope.

References: [People API](https://developers.google.com/people/api/rest/v1/people/get), [OAuth consent](https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification).

## Apple

Apple is disabled by default while Google is the active provider. Leave `APPLE_ENABLED=false` for the Google-only rollout. When ready to use the paid Apple Developer Program, set `APPLE_ENABLED=true` along with the credentials below. Disabled Apple routes return 404 and its button is omitted on every device.

Create a Services ID associated with an eligible primary App ID in Apple Developer, enable Sign in with Apple, and configure the domain and return URL:

`https://YOUR_DOMAIN/tickets/auth/apple/callback`

Set `APPLE_CLIENT_ID` to the Services ID, `APPLE_REDIRECT_URI` to the URL above, and configure `APPLE_TEAM_ID`, `APPLE_KEY_ID`, and `APPLE_PRIVATE_KEY` (an absolute path to the private `.p8` key outside the public web directory). This generates short-lived client secrets automatically. Alternatively, supply `APPLE_CLIENT_SECRET` and rotate it before expiry.

Apple sends a cross-site POST. A sessionless relay stores only the code, state, and optional name for two minutes and redirects to a GET with an opaque receipt. It never replaces the initiating session cookie. The GET consumes the receipt, validates the state against the original browser session, exchanges the code, verifies the signed identity token through the provider library, and checks the original nonce. No global weakening of CSRF or cookie settings is needed.

The Apple button starts hidden and is revealed for iPhone/iPad/iPod or Mac browser platforms, including iPadOS desktop mode. Browser platform detection is best effort. Direct Apple endpoints remain available; visibility is a presentation rule, not an access restriction.

Apple supplies no DOB. Its name is generally available only on the first authorization; when missing, registration also asks for the name. Private relay email addresses are accepted; configure your sending domain with Apple's private email relay if you need to deliver ticket emails to these addresses.

Reference: [Apple web configuration](https://developer.apple.com/help/account/capabilities/configure-sign-in-with-apple-for-the-web).

## Existing accounts and verification

A linked provider signs the customer in directly, subject to the existing active-account and email-verification rules. A new provider with a matching email requires the existing account password once, or an already authenticated session for that exact customer, before linking. Customers who originally registered socially can sign in with their original provider, then visit the login/register page to connect the second provider. Accounts with different emails (including Apple private relay addresses) are not automatically merged.

Provider-verified email replaces email OTP for new social registrations. Phone is required and normalized using the existing Indonesian phone rules; it is not SMS-verified. Pending registration expires after 15 minutes.

## Validation

Run `php vendor/phpunit/phpunit/phpunit --filter 'TicketSocialAuthTest|TicketingTest'` and `node --test tests/social-device-detection.test.cjs`. The Apple signature test generates an ephemeral RSA key; on Windows, PHP/OpenSSL needs `OPENSSL_CONF` pointing to the installed `openssl.cnf`. Live Google/Apple consent and redirect tests still require real configured developer credentials and an HTTPS domain. Confirm Apple visibility on Apple devices and absence on Windows/Android, and test Apple private email delivery before launch.
