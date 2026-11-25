<?php
// Exécuter : php tests/check.php (PHP avec extension DOM).
define('YOURLS_ABSPATH', __DIR__);
define('BE_WHITE_LABEL_NAME', 'Mes liens $1 & <Privé>');
function yourls_add_filter(...$args) {}
require dirname(__DIR__) . '/plugin.php';
ob_end_clean();
if (!class_exists('DOMDocument')) {
    fwrite(STDERR, "Extension PHP DOM requise pour ces tests.\n");
    exit(1);
}
function check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "ECHEC : $message\n");
        exit(1);
    }
}
$script = '<script>const yourls_token = "<tag>&YOURLS";</script>';
$input = '<!doctype html><html><head><meta charset="utf-8"><title>YOURLS</title>'
    . '<meta name="generator" content="YOURLS 1.10"><meta name="description" content="YOURLS">'
    . '<link rel="shortcut icon" href="/images/favicon.ico">' . $script . '</head><body>'
    . '<header><h1><a href="/admin"><img id="yourls-logo" src="/images/yourls-logo.svg"></a></h1></header>'
    . '<!-- Powered by YOURLS --><p>Gestion YOURLS</p>'
    . '<a href="https://yourls.org/download">YOURLS version 1.10</a>'
    . '<table><tr><td><a href="https://yourls.org/">YOURLS : lien enregistré</a></td></tr></table>'
    . '<form><textarea>YOURLS &amp; texte</textarea><input name="yourls_nonce" value="YOURLS"></form>'
    . '<footer id="footer"><p>Powered by YOURLS</p></footer></body></html>';
$out = \BeWhiteLabel\clean_html($input);
check(strpos($out, 'yourls-logo') === false, 'logo supprimé du HTML');
check(strpos($out, 'id="footer"') === false, 'footer supprimé');
check(strpos($out, 'name="generator"') === false, 'generator supprimé');
check(strpos($out, 'name="description"') === false, 'description native supprimée');
check(strpos($out, 'favicon.ico') === false && strpos($out, 'data:,') !== false, 'favicon neutralisé');
check(strpos($out, '<!-- Powered by') === false, 'commentaire supprimé');
check(strpos($out, $script) !== false, 'JavaScript préservé octet pour octet');
check(strpos($out, '<textarea>YOURLS &amp; texte</textarea>') !== false, 'saisie préservée');
check(strpos($out, 'name="yourls_nonce" value="YOURLS"') !== false, 'champ technique préservé');
check(strpos($out, 'YOURLS : lien enregistré') !== false, 'données des liens préservées');
check(strpos($out, 'https://yourls.org/download') === false, 'lien officiel de navigation retiré');
check(strpos($out, 'Mes liens $1 &amp; &lt;Privé&gt;') !== false, 'nom personnalisé échappé sans interpolation');
check(strpos($out, 'Gestion YOURLS') === false, 'mention de navigation remplacée');
$json = '{"title":"YOURLS","html":"<html>"}';
check(\BeWhiteLabel\clean_html($json) === $json, 'réponse JSON inchangée');
$fragment = '<tr><td>YOURLS</td></tr>';
check(\BeWhiteLabel\clean_html($fragment) === $fragment, 'fragment AJAX inchangé');
check(\BeWhiteLabel\output(substr($input, 0, 50), PHP_OUTPUT_HANDLER_START | PHP_OUTPUT_HANDLER_FLUSH) === '', 'flush différé');
check(\BeWhiteLabel\output(substr($input, 50), PHP_OUTPUT_HANDLER_FINAL) === $out, 'document traité après assemblage');
\BeWhiteLabel\output('à jeter', PHP_OUTPUT_HANDLER_START);
\BeWhiteLabel\output('', PHP_OUTPUT_HANDLER_CLEAN);
check(\BeWhiteLabel\output($json, PHP_OUTPUT_HANDLER_FINAL) === $json, 'nettoyage du tampon respecté');
echo "OK : vérifications réussies.\n";
