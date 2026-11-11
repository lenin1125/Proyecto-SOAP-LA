<?php

/** @return array{username: ?string, token: ?string} */
function obtenerCredencialesWsSecurity(string $cuerpo): array
{
    $vacio = ['username' => null, 'token' => null];
    if ($cuerpo === '') {
        return $vacio;
    }
    $previo = libxml_use_internal_errors(true);
    try {
        $dom = new DOMDocument();
        if (! $dom->loadXML($cuerpo, LIBXML_NONET) || $dom->doctype !== null) {
            return $vacio;
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
        $nodes = $xpath->query('/soap:Envelope/soap:Header/wsse:Security/wsse:UsernameToken');
        if ($nodes->length !== 1) {
            return $vacio;
        }
        $usuarios = $xpath->query('wsse:Username', $nodes->item(0));
        $tokens = $xpath->query('wsse:Password', $nodes->item(0));
        if ($usuarios->length !== 1 || $tokens->length !== 1) {
            return $vacio;
        }
        $tipo = $tokens->item(0)->getAttribute('Type');
        if ($tipo !== '' && $tipo !== 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText') {
            return $vacio;
        }

        return ['username' => trim($usuarios->item(0)->textContent), 'token' => trim($tokens->item(0)->textContent)];
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previo);
    }
}

function validarUsuarioTokenBD(?string $username, ?string $token, ?PDO $pdo = null): bool
{
    if (! $username || ! $token || ! preg_match('/^\d+\|[A-Za-z0-9]+$/D', $token)) {
        return false;
    }
    try {
        $pdo ??= getConexion();
        $stmt = $pdo->prepare('SELECT api_token FROM usuarios WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $guardado = $stmt->fetchColumn();

        return is_string($guardado) && strlen($guardado) === 64 && hash_equals($guardado, hash('sha256', $token));
    } catch (Throwable) {
        error_log('No se pudo validar el token contra la base de datos SOAP.');

        return false;
    }
}

function validarAutenticacionSOAP(): bool
{
    $credenciales = obtenerCredencialesWsSecurity($GLOBALS['soap_cuerpo_solicitud'] ?? '');

    return validarUsuarioTokenBD($credenciales['username'], $credenciales['token']);
}
