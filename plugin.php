<?php
/*
Plugin Name: White Label
Description: Retire les éléments de marque des pages HTML sans modifier le cœur.
Version: 1.0.0
Author: White Label
*/

namespace BeWhiteLabel;

if (!defined('YOURLS_ABSPATH')) {
    exit;
}

/** Le nom est toujours échappé au moment de son insertion dans le HTML. */
function site_name() {
    return defined('BE_WHITE_LABEL_NAME') ? (string) BE_WHITE_LABEL_NAME : 'Liens courts';
}

function title($original = '') {
    return site_name();
}

function empty_footer($original = '') {
    return '';
}

yourls_add_filter('html_title', __NAMESPACE__ . '\\title', 1000);
yourls_add_filter('html_footer_text', __NAMESPACE__ . '\\empty_footer', 1000);

/** Préserve les réponses API, téléchargements et redirections. */
function is_html_response() {
    foreach (headers_list() as $header) {
        if (stripos($header, 'Location:') === 0 || stripos($header, 'Content-Disposition:') === 0) {
            return false;
        }
        if (stripos($header, 'Content-Type:') === 0 &&
            !preg_match('~^Content-Type:\s*text/html\b~i', $header)) {
            return false;
        }
        if (preg_match('~^Content-Encoding:\s*(?!identity\b)\S+~i', $header)) {
            return false;
        }
    }
    return true;
}

function remove_node($node) {
    if ($node->parentNode) {
        $node->parentNode->removeChild($node);
    }
}

/** Modifie seulement des documents HTML complets, jamais les réponses JSON/AJAX. */
function clean_html($html) {
    if (!class_exists('DOMDocument') ||
        !preg_match('~^\s*(?:<!doctype\s+html[^>]*>\s*)?<html\b~i', $html)) {
        return $html;
    }

    // Les blocs exécutables et les contenus saisis sont restaurés octet pour octet.
    $protected = array();
    $prefix = 'wl-' . bin2hex(random_bytes(12)) . '-';
    $work = preg_replace_callback(
        '~<(script|style|textarea|pre|code)\b[^>]*>.*?</\1\s*>~is',
        function ($match) use (&$protected, $prefix) {
            $marker = '<!--' . $prefix . count($protected) . '-->';
            $protected[$marker] = $match[0];
            return $marker;
        },
        $html
    );
    if ($work === null) {
        return $html;
    }

    $previous = libxml_use_internal_errors(true);
    $doc = new \DOMDocument('1.0', 'UTF-8');
    try {
        $loaded = $doc->loadHTML('<?xml encoding="UTF-8">' . $work, LIBXML_NONET);
        if (!$loaded) {
            return $html;
        }
        foreach (iterator_to_array($doc->childNodes) as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                remove_node($node);
            }
        }
        $xp = new \DOMXPath($doc);
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';

        // Ne retire pas tous les headers : seulement celui contenant le logo natif.
        $queries = array(
            '//*[@id="yourls-logo"]/ancestor::header[1]',
            '//*[@id="yourls-logo"]/ancestor::h1[1]',
            '//*[@id="yourls-logo"]',
            '//footer[@id="footer"] | //div[@id="footer"]',
            '//meta[translate(@name,"' . $upper . '","' . $lower . '")="generator"]',
            '//meta[translate(@name,"' . $upper . '","' . $lower . '")="description" and contains(translate(@content,"' . $upper . '","' . $lower . '"),"yourls")]',
            '//link[contains(translate(@rel,"' . $upper . '","' . $lower . '"),"icon")]',
        );
        foreach ($queries as $query) {
            foreach (iterator_to_array($xp->query($query)) as $node) {
                remove_node($node);
            }
        }
        // Empêche le navigateur de demander /favicon.ico par défaut.
        $head = $doc->getElementsByTagName('head')->item(0);
        if ($head) {
            $icon = $doc->createElement('link');
            $icon->setAttribute('rel', 'icon');
            $icon->setAttribute('href', 'data:,');
            $head->appendChild($icon);
        }
        foreach ($doc->getElementsByTagName('title') as $node) {
            if (stripos($node->textContent, 'yourls') !== false) {
                $node->textContent = site_name();
            }
        }
        foreach (iterator_to_array($xp->query('//comment()')) as $node) {
            if (stripos($node->nodeValue, 'yourls') !== false) {
                remove_node($node);
            }
        }
        // Mentions dans l'interface seulement : ne réécrit pas les liens enregistrés,
        // formulaires, tableaux, scripts ou identifiants utilisés par le cœur.
        $chrome = '//body//text()[not(ancestor::table or ancestor::form or ancestor::main or ancestor::a or ancestor::button or ancestor::select or ancestor::svg)]';
        foreach ($xp->query($chrome) as $node) {
            $node->nodeValue = preg_replace('~\bYOURLS\b|Your Own URL Shortener~iu', site_name_replacement(), $node->nodeValue);
        }
        // Liens officiels hors des données et des zones de code.
        $links = '//body//a[@href][not(ancestor::table or ancestor::form or ancestor::main)]';
        foreach (iterator_to_array($xp->query($links)) as $node) {
            $host = parse_url($node->getAttribute('href'), PHP_URL_HOST);
            if ($host && preg_match('~(^|\.)yourls\.org$~i', $host)) {
                $label = preg_replace_callback('~\bYOURLS\b|Your Own URL Shortener~iu', function () { return site_name(); }, $node->textContent);
                $node->parentNode->replaceChild($doc->createTextNode($label), $node);
            }
        }
        $result = $doc->saveHTML();
        return $result === false ? $html : strtr($result, $protected);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

function site_name_replacement() {
    // preg_replace traite $ et les antislashs comme des références de capture.
    return str_replace(array('\\', '$'), array('\\\\', '\\$'), site_name());
}

function output($chunk, $phase) {
    static $pending = '';
    if ($phase & PHP_OUTPUT_HANDLER_CLEAN) {
        $pending = '';
        return '';
    }
    $pending .= $chunk;
    if (!($phase & PHP_OUTPUT_HANDLER_FINAL)) {
        return '';
    }
    $html = $pending;
    $pending = '';
    if (!is_html_response()) {
        return $html;
    }
    $clean = clean_html($html);
    if ($clean !== $html && !headers_sent()) {
        header_remove('Content-Length');
        header_remove('ETag');
        header_remove('Content-MD5');
    }
    return $clean;
}

// Démarre dès le chargement du plugin : couvre aussi la connexion et les pages
// personnalisées chargées ensuite. Aucun fichier du cœur n'est modifié.
ob_start(__NAMESPACE__ . '\\output');
