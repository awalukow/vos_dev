#!/usr/bin/env php
<?php
/**
 * Generate a self-signed X.509 certificate for VOS PDF digital signatures.
 *
 * Run once:
 *   php generate_cert.php
 *
 * This creates two files in storage/app/portal/certs/:
 *   - vos_cert.pem   (certificate)
 *   - vos_key.pem    (private key)
 *
 * These are used by DocSignController to digitally sign PDFs.
 */

$certDir = __DIR__ . '/storage/app/portal/certs';
if (!is_dir($certDir)) {
    mkdir($certDir, 0755, true);
}

$certFile = $certDir . '/vos_cert.pem';
$keyFile  = $certDir . '/vos_key.pem';

if (file_exists($certFile) && file_exists($keyFile)) {
    echo "Certificate already exists at:\n";
    echo "  Cert: {$certFile}\n";
    echo "  Key:  {$keyFile}\n";
    echo "Delete them and re-run this script to regenerate.\n";
    exit(0);
}

// Certificate details — customize as needed
$dn = [
    'countryName'            => 'ID',
    'stateOrProvinceName'    => 'DKI Jakarta',
    'localityName'           => 'Jakarta',
    'organizationName'       => 'Voice of Soul Choir Indonesia',
    'organizationalUnitName' => 'VOS Management Portal',
    'commonName'             => 'VOS DocSign',
    'emailAddress'           => 'admin@vos.org',
];

// Generate private key (2048-bit RSA)
$privateKey = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
]);

if (!$privateKey) {
    echo "ERROR: Failed to generate private key. Make sure OpenSSL extension is enabled.\n";
    exit(1);
}

// Generate certificate signing request
$csr = openssl_csr_new($dn, $privateKey, ['digest_alg' => 'sha256']);

if (!$csr) {
    echo "ERROR: Failed to create CSR.\n";
    exit(1);
}

// Self-sign the certificate (valid for 10 years)
$cert = openssl_csr_sign($csr, null, $privateKey, 3650, ['digest_alg' => 'sha256']);

if (!$cert) {
    echo "ERROR: Failed to sign certificate.\n";
    exit(1);
}

// Export certificate and key to PEM files
openssl_x509_export($cert, $certPem);
openssl_pkey_export($privateKey, $keyPem);

file_put_contents($certFile, $certPem);
file_put_contents($keyFile,  $keyPem);

// Restrict key file permissions
chmod($keyFile, 0600);

echo "✓ Certificate generated successfully!\n";
echo "  Cert: {$certFile}\n";
echo "  Key:  {$keyFile}\n";
echo "\n";
echo "Add these to your .env:\n";
echo "  VOS_CERT_PATH=" . $certFile . "\n";
echo "  VOS_KEY_PATH="  . $keyFile  . "\n";
echo "  VOS_CERT_PASS=\n";  // empty — no passphrase
echo "\n";
echo "Note: This is a self-signed certificate. PDF readers will show\n";
echo "  'Signature validity is unknown' until the certificate is\n";
echo "  trusted. For internal use this is fine.\n";
